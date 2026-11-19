<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 3, 17.11.2026): Schreibweg GoodWe (InverterHub ctl_ems_mode/-power/-enable) und Totmann-Erkennung.
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSInverterGoodwe;" in der Klasse EMS.
 */
trait EMSInverterGoodwe
{
    /**
     * WR-Totmann-Erkennung + -Reaktion (Uebergabe von InverterHub,
     * 29.08.2026, siehe SUITE.md GoodWe-Steuerregister-Warnung).
     * InverterHub liest ctl_ems_mode jeden FastTimer-Zyklus zurueck -- die
     * IPS-Variable zeigt also den echten WR-Ist-Zustand, inkl. 255/STOPPED.
     * Flanken-erkannt (nur beim UEBERGANG nach 255 reagieren), nicht bei
     * jedem Zyklus erneut, waehrend es schon 255 ist -- sonst wuerde die
     * Pendel-Bremse bei anhaltendem 255-Zustand faelschlich staendig
     * mitzaehlen statt nur echte, einzelne Ausloesungen.
     */
    private function handleGoodweDeadman()
    {
        $inv = $this->getInverterEntry();
        if ($inv === null || ($inv['instanceID'] ?? 0) <= 0) { return; }
        $modeId = $this->findChildVariableIdByIdent($inv['instanceID'], 'ctl_ems_mode');
        if ($modeId <= 0) { return; }
        $raw = (int)@GetValueInteger($modeId);

        $lastSeen = $this->ReadAttributeInteger('LastSeenGoodweRawMode');
        $this->WriteAttributeInteger('LastSeenGoodweRawMode', $raw);
        $isNewDeadman = ($raw === 255 && $lastSeen !== 255);
        if (!$isNewDeadman) { return; }

        // Pendel-Bremse: Ausloese-Zeitstempel protokollieren, auf die
        // letzte Stunde begrenzen. >3 Ausloesungen/Stunde = vermutlich
        // schlechte Verbindung statt echter Einzelvorfaelle -- Fallback
        // (Szenario B) wuerde den WR sonst dauerhaft ein/aus schalten.
        $now = time();
        $log = json_decode($this->ReadAttributeString('DeadmanTriggerLog'), true);
        if (!is_array($log)) { $log = array(); }
        $log[] = $now;
        $log = array_values(array_filter($log, function ($ts) use ($now) { return $ts > $now - 3600; }));
        $this->WriteAttributeString('DeadmanTriggerLog', json_encode($log));

        $wasTripped = $this->ReadAttributeBoolean('DeadmanBrakeTripped');
        if (!$wasTripped && count($log) > 3) {
            $this->WriteAttributeBoolean('DeadmanBrakeTripped', true);
            $this->SetValue('WATCHDOG_Brake_Tripped', true);
            $this->emsLog(EMS_LOG_BASIC, '🛑 Totmann-Pendel-Bremse ausgelöst: >3 Totmann-Ereignisse in der letzten Stunde -- Fallback in WR-Eigenregelung gestoppt, bis manuell zurückgesetzt (WATCHDOG_Reset_Brake).');
            $wasTripped = true;
        }

        $this->emsLog(EMS_LOG_BASIC, '⚠️ WR-Totmann ausgelöst: ctl_ems_mode ist auf 255/STOPPED gefallen (Heartbeat ausgeblieben).');

        if ($wasTripped) {
            // Bremse aktiv -- immer Szenario A (nichts tun), unabhaengig
            // von der konfigurierten Reaktion, bis der Nutzer manuell
            // zurücksetzt.
            return;
        }

        $reaction = $this->ReadPropertyInteger('WATCHDOG_Deadman_Reaction');
        if ($reaction === 1) {
            // Szenario B: Fallback in WR-Eigenregelung.
            //
            // Live-Fund 29.08.2026 (Dietmar, nach dem A/B-Test): bei
            // enable=false haelt der WR den ZULETZT KOMMANDIERTEN Modus
            // dauerhaft, OHNE Heartbeat -- er faellt NICHT von selbst in
            // Automatik zurueck, nur weil enable auf false gesetzt wird.
            // enable=false ALLEIN wuerde also einfrieren, was gerade lief
            // (z.B. eine Entladung), nicht in Automatik zurueckfallen.
            // Fuer echte Automatik-Uebergabe muss deshalb explizit
            // enable=false + mode=1 (Automatik) + power=0 gesendet werden --
            // korrigiert die urspruengliche (falsche) Annahme, enable=false
            // allein reiche fuer "WR faehrt eigene Selbstverbrauchslogik".
            $this->ipsWrite($inv['instanceID'], 'ctl_ems_enable', false);
            $this->ipsWrite($inv['instanceID'], 'ctl_ems_mode', GW_MODE_AUTO);
            $this->ipsWrite($inv['instanceID'], 'ctl_ems_power', 0);
            $this->emsLog(EMS_LOG_BASIC, '↩️ Totmann-Reaktion: Fallback in WR-Eigenregelung (enable=false, mode=Automatik, power=0).');
        } else {
            $this->emsLog(EMS_LOG_BASIC, 'ℹ️ Totmann-Reaktion: Sicherheits-Stopp respektiert, keine weitere Aktion (Szenario A).');
        }
    }

    /**
     * Schreibt den Goodwe-EMS-Modus/-Leistungswert. Bevorzugt den
     * NRG-Stack-Kontrollkanal (IPS_RequestAction auf den automatisch
     * gefundenen WR, nur wenn dessen controlAuthority == 'ems' ist —
     * Situation A). GW_MODE_*-Konstanten entsprechen 1:1 dem Goodwe-
     * Register 47511, das sowohl das alte VAR_WR_EMS_Mode als auch
     * InverterHubs ctl_ems_mode adressiert, deshalb keine Modus-Uebersetzung
     * noetig. Fallback: alte manuelle Variablenverknuepfung.
     */
    private function setGoodweMode($mode, $powerW, $enable = true)
    {
        $inv = $this->getInverterEntry();

        if ($inv !== null && $inv['source'] === 'inverterhub') {
            $authority = $inv['controlAuthority'] ?? 'none';
            if ($authority === 'ems' && ($inv['controllable'] ?? false)) {
                // RequestAction() ist ein IPSModule-Kernel-Methodenname und wird NIE als
                // Prefix_RequestAction() exponiert -- der Kernel-Einstiegspunkt ist
                // IPS_RequestAction($InstanceID, $Ident, $Value) (siehe ChargerHub-Befund
                // 25.07.2026, gleiche Ursache bei InverterHub).
                //
                // ctl_ems_enable (Register 47505) ist der Hauptschalter. KORRIGIERT
                // 29.08.2026 (Dietmar, nach sauberem A/B-Test mit InverterHub -- die
                // fruehere Fund-25.07.2026-Annahme unten war FALSCH/unvollstaendig):
                // - =false: der zuletzt kommandierte $mode/$powerW wird vom WR
                //   ANGENOMMEN UND DAUERHAFT AUSGEFUEHRT, OHNE Heartbeat -- NICHT
                //   "WR ignoriert den Befehl und faehrt Selbstverbrauchslogik", wie
                //   frueher angenommen. Um echte Automatik zu erzwingen, muss man
                //   explizit mode=GW_MODE_AUTO+power=0 SENDEN, nicht einfach nur
                //   enable=false setzen (siehe handleGoodweDeadman()-Kommentar).
                // - =true: SEMS+-Portal zeigt "3rd party EMS", aber der Befehl muss
                //   dann periodisch (< 60-70s) neu geschrieben werden, sonst faellt
                //   der WR nach ~70-120s ohne Heartbeat selbst auf 255/STOPPED
                //   zurueck (Fund 30.07.2026 + 29.08.2026, SUITE.md GoodWe-
                //   Steuerregister-Warnung). $enable=true lohnt sich also nur bei
                //   laufendem Reassert-Zyklus (unser applyDecision(), alle
                //   EMS_Interval Sekunden) -- fuer einen einmaligen/seltenen Befehl
                //   ist $enable=false der zuverlaessigere, unkompliziertere Weg.
                // Der Aufrufer (optimize()) schreibt weiterhin explizit, welchen
                // $enable-Wert er will -- die Fallback-Branch (siehe applyFallback())
                // nutzt bereits enable=false MIT mode=GW_MODE_AUTO, war also schon vor
                // dieser Korrektur richtig verdrahtet.
                // Schreibreihenfolge (12.09.2026, Code-Review svc_* mit InverterHub):
                // Jede Zwei-Schritt-Reihenfolge laesst fuer irgendeinen Uebergang
                // kurz einen gefaehrlichen Zwischenzustand zu -- z. B. Modus 3
                // ("Entladen+Solar", Xmax ist live bestaetigt ein SOLLWERT) mit
                // einem alten hohen Leistungswert = Entladestoss ins Netz. Sicher
                // ist der Uebergang ueber Null: Leistung 0 -> Modus -> Zielleistung
                // -> enable zuletzt. Der Null-Schritt nur bei ECHTEM Moduswechsel
                // (erkannt am vom Geraet zurueckgelesenen ctl_ems_mode), sonst
                // wuerde der Reassert-Zyklus z. B. bei Grid Rewards alle 30 s kurz
                // auf 0 W fallen. Unbekannter Ist-Modus (keine Rueckmeldung, 255)
                // gilt als Wechsel -- sichere Richtung.
                // Leistung wird IMMER geschrieben, auch 0: ctl_ems_power (47512) ist
                // in GW_MODE_CHARGE_PV eine Netzbezugs-OBERGRENZE; ein stehen-
                // gebliebener Altwert fuehrte am 27.07.2026 zu 3,4 kW ungewolltem
                // Netzbezug.
                $iid       = $inv['instanceID'];
                $modeVarId = $this->findChildVariableIdByIdent($iid, 'ctl_ems_mode');
                // Wechselrichter ohne EMS-Stellglieder (andere Hersteller): nichts schreiben, einmal pro Stunde melden
                // Solis (InverterHub-Steuerpunkte ctl_force_mode/ctl_force_w, ohne ctl_ems_*): eigener, kleiner Zweig
                if ($this->inverterKind((int)$iid) === 'solis') {
                    $this->setSolisMode((int)$iid, (int)$mode, (int)$powerW);
                    return;
                }
                // Andere Hersteller mit gemeinsamer Fernsteuerung (FoxESS, Deye, ...): eigener, einheitlicher Zweig (0.109.0)
                if ($this->inverterKind((int)$iid) === 'remote') {
                    $this->setRemoteMode((int)$iid, (int)$mode, (int)$powerW);
                    return;
                }
                $hasKids = count(IPS_GetChildrenIDs($iid)) > 0;
                if ($hasKids && ($modeVarId <= 0 || $this->findChildVariableIdByIdent($iid, 'ctl_ems_power') <= 0 || $this->findChildVariableIdByIdent($iid, 'ctl_ems_enable') <= 0)) {
                    $this->WriteAttributeString('NoControlReason', $this->t('inverter without EMS actuators'));
                    if (time() - $this->ReadAttributeInteger('NoControlLoggedAt') > 3600) {
                        $this->WriteAttributeInteger('NoControlLoggedAt', time());
                        $this->emsLog(EMS_LOG_BASIC, sprintf('Wechselrichtersteuerung: WR #%d hat keine EMS-Stellglieder (ctl_ems_mode/-power/-enable) -- EMS beobachtet nur', $iid));
                    }
                    return;
                }
                $istModus  = ($modeVarId > 0) ? (int)GetValue($modeVarId) : -1;
                if ($istModus !== (int)$mode) {
                    $this->ipsWrite($iid, 'ctl_ems_power', 0);
                    $this->ipsWrite($iid, 'ctl_ems_mode', $mode);
                    if ((int)$powerW > 0) {
                        $this->ipsWrite($iid, 'ctl_ems_power', (int)$powerW);
                    }
                } else {
                    $this->ipsWrite($iid, 'ctl_ems_mode', $mode);
                    $this->ipsWrite($iid, 'ctl_ems_power', (int)$powerW);
                }
                $this->ipsWrite($iid, 'ctl_ems_enable', (bool)$enable);
                if ($this->ReadAttributeString('NoControlReason') !== '') { $this->WriteAttributeString('NoControlReason', ''); }
                return;
            }
            $this->WriteAttributeString('NoControlReason', $this->t('control authority is not with the EMS'));
            if (time() - $this->ReadAttributeInteger('NoControlLoggedAt') > 3600) {
                $this->WriteAttributeInteger('NoControlLoggedAt', time());
                $this->emsLog(EMS_LOG_BASIC, sprintf(
                    'Wechselrichtersteuerung: WR #%d hat Steuerhoheit "%s" (nicht "ems") oder ist nicht steuerbar — EMS greift nicht ein (Situation B)',
                    $inv['instanceID'], $authority
                ));
            }
            return;
        }

        // Fallback: alte manuelle Variablenverknuepfung (kein Partnermodul gefunden)
        $varMode  = $this->ReadPropertyInteger('VAR_WR_EMS_Mode');
        $varPower = $this->ReadPropertyInteger('VAR_WR_EMS_Power');
        $want = ($varMode > 0) ? '' : $this->t('no inverter with actuators found or linked');
        if ($this->ReadAttributeString('NoControlReason') !== $want) { $this->WriteAttributeString('NoControlReason', $want); }
        if ($varMode  > 0) { $this->writeVar($varMode, $mode); }
        if ($varPower > 0 && $powerW > 0) { $this->writeVar($varPower, $powerW); }
    }
}
