<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 3, 17.11.2026): Schreibweg Solis (InverterHub ctl_force_mode/-w, Speicherbetrieb 43110: Halten, Netzlade-Erlaubnis, Zwangsladen/-entladen).
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSInverterSolis;" in der Klasse EMS.
 */
trait EMSInverterSolis
{
    private function isSolisDriver(int $iid): bool
    {
        return $this->findChildVariableIdByIdent($iid, 'ctl_force_mode') > 0
            && $this->findChildVariableIdByIdent($iid, 'ctl_force_w') > 0
            && $this->findChildVariableIdByIdent($iid, 'ctl_ems_mode') <= 0;
    }

    /**
     * Wiederholabstand fuer den Solis-Zwangsmodus (s). Das Geraet nimmt ihn nach dem Fernsteuer-Zeitlimit (Register 43282,
     * InverterHub `cfg_rc_timeout_min`, gemessen 3 min) zurueck. EMS schreibt hoechstens alle halbe Zeitlimit-Dauer neu,
     * nie seltener als alle 60 s, nie oefter als alle 15 s; ohne lesbares Zeitlimit 60 s.
     */
    private function solisRewriteIntervalSec(int $iid): int
    {
        $id = $this->findChildVariableIdByIdent($iid, 'cfg_rc_timeout_min');
        $min = ($id > 0) ? (int)GetValue($id) : 0;
        if ($min <= 0) { return 60; }
        return (int)max(15, min(60, intdiv($min * 60, 2)));
    }

    private function solisPvSurplusW(): float
    {
        try { return (float)$this->GetValue('EMS_PVPower') - (float)$this->GetValue('EMS_HousePower'); }
        catch (Throwable $e) { return 0.0; }
    }

    /**
     * Darf der Akku jetzt gehalten werden? Nicht bei PV-Ueberschuss (Beginn ab 200 W, Fortsetzen bis 500 W), solange nicht
     * gemessen ist, ob PV bei gesperrtem Entladen weiter in die Batterie laedt. Gesperrt nach wiederholtem Fehlschlag (10 min).
     */
    private function solisHoldAllowed(int $iid = 0): bool
    {
        if (time() < $this->ReadAttributeInteger('SolisHoldBlockedUntil')) { return false; }
        $limit = $this->ReadAttributeBoolean('SolisHoldActive') ? 500.0 : 200.0;
        if ($this->solisPvSurplusW() > $limit) { return false; }
        // Speicherbetrieb 17 (Reserve) haelt den Akku NUR unter dem Reserve-SOC des Geraets (Register 43024, InverterHub
        // `cfg_soc_backup`): darueber entlaedt der Solis ganz normal. Gemessen 22.10.2026: unter dem Reserve-SOC (80 %) mit PV
        // laedt 17 die Batterie mit der gesamten PV und bezieht das Haus aus dem Netz; ohne Reserve-SOC-Wert nie raten.
        if ($iid > 0) {
            $rid = $this->findChildVariableIdByIdent($iid, 'cfg_soc_backup');
            if ($rid <= 0) { return false; }
            try { $soc = (float)$this->GetValue('EMS_BatSOC'); } catch (Throwable $e) { return false; }
            if ($soc >= (float)GetValue($rid) - 1.0) { return false; }
        }
        return true;
    }

    /**
     * Speicherbetrieb, den der Nutzer eingestellt hat (fuer das Wiederherstellen nach Netzladen). Steht am Geraet der eigene
     * Haltewert 17 ("Akku halten", Entladesperre), gilt der vor dem Halten gemerkte Wert: SolisUserStorage, sonst der vom
     * InverterHub gemerkte svc_prev_storage. Ohne beides bleibt $cur.
     */
    private function solisUserStorageFor(int $iid, int $cur): int
    {
        if ($cur !== 17) { return $cur; }
        $user = $this->ReadAttributeInteger('SolisUserStorage');
        if ($user > 0 && $user !== 17) { return $user; }
        $prevId = $this->findChildVariableIdByIdent($iid, 'svc_prev_storage');
        $prev = ($prevId > 0) ? (int)GetValue($prevId) : 0;
        return ($prev > 0 && $prev !== 17) ? $prev : $cur;
    }

    /**
     * Nach dem Freigeben der Entladesperre: erwarteten Speicherbetrieb zuruecklesen und bei Abweichung hoechstens dreimal
     * (im Abstand von 60 s) ausdruecklich neu schreiben. Das Schreiben von Register 43110 kam am Solis nur bei 2 von 4
     * Versuchen an (gemessen 20.10.2026), deshalb nie blind vertrauen.
     */
    private function solisHoldHousekeeping(int $iid): void
    {
        $target = $this->ReadAttributeInteger('SolisRestoreTarget');
        if ($target <= 0) { return; }
        $stId = $this->findChildVariableIdByIdent($iid, 'cfg_storage_mode');
        $cur = ($stId > 0) ? (int)GetValue($stId) : -1;
        if ($cur !== $target && $stId > 0 && IPS_GetVariable($stId)['VariableUpdated'] <= $this->ReadAttributeInteger('SolisHoldTryAt')) { return; } // Rueckmeldung noch aelter als das Schreiben (Lesezyklus 5 min)
        if ($cur === $target || $cur <= 0) { if ($cur === $target) { $this->WriteAttributeInteger('SolisRestoreTarget', -1); $this->WriteAttributeInteger('SolisHoldTries', 0); } return; }
        $now = time();
        if ($now - $this->ReadAttributeInteger('SolisHoldTryAt') < 60) { return; }
        if ($this->ReadAttributeInteger('SolisHoldTries') >= 3) {
            $this->emsLog(EMS_LOG_BASIC, sprintf('Solis: Speicherbetrieb steht auf %d statt auf dem gemerkten Wert %d, auch nach drei Versuchen -- bitte am Gerät prüfen', $cur, $target));
            $this->WriteAttributeInteger('SolisRestoreTarget', -1);
            $this->WriteAttributeInteger('SolisHoldTries', 0);
            return;
        }
        $this->WriteAttributeInteger('SolisHoldTries', $this->ReadAttributeInteger('SolisHoldTries') + 1);
        $this->WriteAttributeInteger('SolisHoldTryAt', $now);
        $this->ipsWrite($iid, 'ctl_storage_mode', $target);
    }

    private function solisGridChargeTarget(int $cur): int
    {
        return (($cur & 16) !== 0) ? (($cur & ~16) | 32) : ($cur | 32);
    }

    private function solisGridChargeEnsure(int $iid, bool $wantCharge, int $now): void
    {
        $active = $this->ReadAttributeBoolean('SolisGcActive');
        $stId = $this->findChildVariableIdByIdent($iid, 'cfg_storage_mode');
        if ($stId <= 0 || $this->findChildVariableIdByIdent($iid, 'ctl_storage_mode') <= 0) { return; }
        $cur = (int)GetValue($stId);
        $fresh = (IPS_GetVariable($stId)['VariableUpdated'] > $this->ReadAttributeInteger('SolisGcTryAt'));
        if (!$wantCharge) {
            if (!$active) { return; }
            $saved = $this->ReadAttributeInteger('SolisGcSaved');
            if ($saved === 17) { $saved = $this->solisUserStorageFor($iid, 17); } // Altbestand aus der Zeit vor 0.106.9: nie den Haltewert zurueckschreiben
            $this->WriteAttributeBoolean('SolisGcActive', false);
            $this->WriteAttributeInteger('SolisGcTries', 0);
            if ($saved > 0) {
                $this->ipsWrite($iid, 'ctl_storage_mode', $saved);
                // Ruecklesen/Wiederholen uebernimmt solisHoldHousekeeping(); fuer ein anschliessendes Halten ist das der Nutzerwert
                $this->WriteAttributeInteger('SolisUserStorage', $saved);
                $this->WriteAttributeInteger('SolisRestoreTarget', $saved);
                $this->WriteAttributeInteger('SolisHoldTries', 0);
                $this->WriteAttributeInteger('SolisHoldTryAt', $now);
            }
            return;
        }
        if ($this->ReadAttributeInteger('SolisRestoreTarget') > 0) { return; } // erst die laufende Wiederherstellung abwarten
        if ($cur <= 0) { return; }
        if (!$active) {
            if (($cur & 32) !== 0) { return; } // Netzladen ist schon erlaubt
            // Befund Paul 18.11.2026: Folgt das Netzladen direkt auf "Akku halten", steht am Geraet noch der Haltewert 17. Wurde
            // der gemerkt, stellte das Laden-Ende um 05:00 wieder 17 her: Akku blieb gesperrt, Haus lief stundenlang aus dem Netz.
            // Gemerkt wird deshalb der Nutzerwert, nie der eigene Haltewert.
            $this->WriteAttributeInteger('SolisGcSaved', $this->solisUserStorageFor($iid, $cur));
            $this->WriteAttributeBoolean('SolisGcActive', true);
            $this->WriteAttributeInteger('SolisGcTries', 1);
            $this->WriteAttributeInteger('SolisGcTryAt', $now);
            $this->ipsWrite($iid, 'ctl_storage_mode', $this->solisGridChargeTarget($cur));
            $this->emsLog(EMS_LOG_BASIC, sprintf('Solis: Speicherbetrieb %d erlaubt kein Netzladen -- für das Zwangsladen vorübergehend %d', $cur, $this->solisGridChargeTarget($cur)));
            return;
        }
        if (($cur & 32) !== 0) { if ($this->ReadAttributeInteger('SolisGcTries') !== 0) { $this->WriteAttributeInteger('SolisGcTries', 0); } return; }
        if (!$fresh || ($now - $this->ReadAttributeInteger('SolisGcTryAt')) < 60) { return; }
        $tries = $this->ReadAttributeInteger('SolisGcTries');
        if ($tries >= 3) {
            if ($tries === 3) {
                $this->emsLog(EMS_LOG_BASIC, sprintf('Solis: Netzlade-Erlaubnis kam nach drei Versuchen nicht an (Speicherbetrieb %d) -- Zwangsladen wirkt nur aus PV, bitte am Gerät prüfen', $cur));
                $this->WriteAttributeInteger('SolisGcTries', 4);
            }
            return;
        }
        $this->WriteAttributeInteger('SolisGcTries', $tries + 1);
        $this->WriteAttributeInteger('SolisGcTryAt', $now);
        $this->ipsWrite($iid, 'ctl_storage_mode', $this->solisGridChargeTarget($this->ReadAttributeInteger('SolisGcSaved')));
    }

    /**
     * Entladesperre setzen oder freigeben (InverterHub `svc_discharge_inhibit`, Vertrag 1.5; Solis: Speicherbetrieb 17 = Reserve
     * ohne Netzladen, nie 49/51). EMS merkt den Nutzerwert VOR dem Setzen (zusaetzlich zum InverterHub) und stellt ihn beim
     * Freigeben wieder her. Schreiben nur bei Wechsel, mindestens 60 s Abstand (Register fest gespeichert, nicht zyklisch).
     */
    private function solisHoldApply(int $iid, bool $want, int $now): void
    {
        $active = $this->ReadAttributeBoolean('SolisHoldActive');
        $stId = $this->findChildVariableIdByIdent($iid, 'cfg_storage_mode');
        $cur = ($stId > 0) ? (int)GetValue($stId) : -1;
        if ($want) {
            if ($active) {
                // Rücklesen: kam der Befehl an? Sonst hoechstens dreimal im Abstand von 60 s neu, danach 10 min Pause
                // Nur eine Rueckmeldung zaehlt, die NACH dem letzten Schreiben gelesen wurde: der InverterHub liest den
                // Speicherbetrieb nur alle paar Minuten (Solis 5 min). Paul 19.11.2026 04:32-04:37: drei "Fehlversuche" gegen
                // einen veralteten Wert, der Befehl war aber angekommen.
                $fresh = ($stId > 0 && (int)(IPS_GetVariable($stId)['VariableUpdated'] ?? 0) > $this->ReadAttributeInteger('SolisHoldTryAt'));
                if ($cur > 0 && $cur !== 17 && $fresh && $now - $this->ReadAttributeInteger('SolisHoldTryAt') >= 60) {
                    if ($this->ReadAttributeInteger('SolisHoldTries') >= 3) {
                        $this->emsLog(EMS_LOG_BASIC, 'Solis: Entladesperre kam nach drei Versuchen nicht an (Speicherbetrieb ' . $cur . ') -- Sperre wird zurueckgenommen, Pause 10 min, Wechselrichter in Automatik');
                        // Aufgeben heisst immer auch: aktiv freigeben. Sonst bleibt eine spaet angekommene Sperre am Geraet stehen,
                        // ohne dass EMS sie noch kennt (Paul 19.11.2026: Akku ab 04:37 gesperrt, Haus aus dem Netz bei 49 ct).
                        $this->ipsWrite($iid, 'svc_discharge_inhibit', false);
                        $this->WriteAttributeBoolean('SolisHoldActive', false);
                        $this->WriteAttributeInteger('SolisHoldBlockedUntil', $now + 600);
                        $this->WriteAttributeInteger('SolisHoldChangedAt', $now);
                        $this->WriteAttributeInteger('SolisHoldTries', 0);
                        return;
                    }
                    $this->WriteAttributeInteger('SolisHoldTries', $this->ReadAttributeInteger('SolisHoldTries') + 1);
                    $this->WriteAttributeInteger('SolisHoldTryAt', $now);
                    $this->ipsWrite($iid, 'svc_discharge_inhibit', true);
                } elseif ($cur === 17) {
                    $this->WriteAttributeInteger('SolisHoldTries', 0);
                }
                return;
            }
            if ($now - $this->ReadAttributeInteger('SolisHoldChangedAt') < 60) { return; }
            if ($cur > 0 && $cur !== 17 && $this->ReadAttributeInteger('SolisRestoreTarget') <= 0) { $this->WriteAttributeInteger('SolisUserStorage', $cur); }
            $this->WriteAttributeInteger('SolisRestoreTarget', -1);
            $this->ipsWrite($iid, 'svc_discharge_inhibit', true);
            $this->WriteAttributeBoolean('SolisHoldActive', true);
            $this->WriteAttributeInteger('SolisHoldChangedAt', $now);
            $this->WriteAttributeInteger('SolisHoldTries', 0);
            $this->WriteAttributeInteger('SolisHoldTryAt', $now);
            return;
        }
        if (!$active) {
            // Selbstheilung: Steht die Entladesperre noch, und hat EMS sie selbst gesetzt (Aenderungszeit = eigener Schreibzeitpunkt),
            // kennt EMS sie aber nicht mehr (Altfall vor 0.107.2), wird sie freigegeben. Eine von anderen gesetzte Sperre bleibt.
            $inhId = $this->findChildVariableIdByIdent($iid, 'svc_discharge_inhibit');
            $own = $this->ReadAttributeInteger('SolisHoldChangedAt');
            if ($inhId > 0 && $own > 0 && GetValue($inhId) === true && $now - $own >= 60) {
                $vi = IPS_GetVariable($inhId);
                $chg = (int)($vi['VariableChanged'] ?? ($vi['VariableUpdated'] ?? 0));
                if (abs($chg - $own) <= 5) {
                    $this->emsLog(EMS_LOG_BASIC, 'Solis: eigene Entladesperre stand noch am Geraet, EMS kannte sie nicht mehr -- wird freigegeben');
                    $this->ipsWrite($iid, 'svc_discharge_inhibit', false);
                    $this->WriteAttributeInteger('SolisHoldChangedAt', $now);
                }
            }
            return;
        }
        $saved = $this->ReadAttributeInteger('SolisUserStorage');
        $prevId = $this->findChildVariableIdByIdent($iid, 'svc_prev_storage');
        $prev = ($prevId > 0) ? (int)GetValue($prevId) : 0;
        if ($prev > 0) {
            $this->ipsWrite($iid, 'svc_discharge_inhibit', false); // InverterHub schreibt den gemerkten Wert zurueck
            $this->WriteAttributeInteger('SolisRestoreTarget', $saved > 0 ? $saved : $prev);
        } elseif ($saved > 0) {
            $this->ipsWrite($iid, 'ctl_storage_mode', $saved);    // eigener gemerkter Wert (InverterHub hat nichts gemerkt)
            $this->WriteAttributeInteger('SolisRestoreTarget', $saved);
        } else {
            $this->emsLog(EMS_LOG_BASIC, 'Solis: Entladesperre soll freigegeben werden, aber kein vorheriger Speicherbetrieb ist gemerkt -- bitte den Speicherbetrieb am Gerät prüfen');
        }
        $this->WriteAttributeBoolean('SolisHoldActive', false);
        $this->WriteAttributeInteger('SolisHoldChangedAt', $now);
        $this->WriteAttributeInteger('SolisHoldTries', 0);
        $this->WriteAttributeInteger('SolisHoldTryAt', $now);
    }

    /**
     * Solis, Entladen auf Vorgabe (Dietmar 22.10.2026, Pauls Anlage): Sollleistung (W) fuer das Zwangsentladen, 0 = nicht entladen.
     * Nur fuer Entlademodi (3 "Entladen", 12) und nur ins Haus: Hauslast plus Wallboxen minus PV minus 150 W Reserve, hoechstens die
     * Vorgabe des Plans (bei Modus 3 eine Obergrenze), die vom InverterHub gemeldete Entladegrenze und 30 kW. Der Solis haelt die
     * Vorgabe als Sollwert (gemessen ~85 bis 98 %) und speist ins Netz ein, wenn sie die Hauslast uebersteigt; die Ausgangsbegrenzung
     * des Wechselrichters stoppt das nicht. Deshalb senkt eine Einspeisung ueber 200 W die Vorgabe sofort um den eingespeisten Betrag.
     */
    private function solisDischargeW(int $iid, int $mode, int $powerW): int
    {
        if ($mode !== GW_MODE_DISCHARGE && $mode !== GW_MODE_BAT_DISCH) { return 0; }
        try {
            // Wallbox nur, wenn EMS sie selbst steuert (Modus 'ems') und kein Grid Rewards laeuft (0.101.2, Pauls Anlage 12.11.2026:
            // bei Grid Rewards/Smart Charging entlud der Akku 3-4 kW ins von Tibber gesteuerte Auto -- die Praemie gibt es nur fuer Netzstrom).
            $demand = (float)$this->GetValue('EMS_HousePower');
            $gr = false;
            try { $gr = (bool)$this->GetValue('EMS_GridRewards'); } catch (Throwable $e) { }
            if (!$gr) {
                foreach (array(1, 2) as $n) {
                    if (($this->wallboxMode($n)['mode'] ?? '') === 'ems') { $demand += (float)$this->GetValue('EMS_WB' . $n . 'Power'); }
                }
            }
            $pv = (float)$this->GetValue('EMS_PVPower');
            $grid = (float)$this->GetValue('EMS_GridPower'); // + Einspeisung
        } catch (Throwable $e) { return 0; }
        $w = $demand - $pv - 150.0;
        if ($powerW > 0) { $w = min($w, (float)$powerW); }
        $capId = $this->findChildVariableIdByIdent($iid, 'bat_discharge_max_w');
        $cap = ($capId > 0) ? (float)GetValue($capId) : 0.0;
        if ($cap > 0.0) { $w = min($w, $cap); }
        $w = min($w, 30000.0);
        if ($grid > 200.0 && $this->ReadAttributeInteger('SolisLastMode') === 2) {
            $w = min($w, (float)$this->ReadAttributeInteger('SolisLastPowerW') - $grid);
        }
        return ($w < 100.0) ? 0 : (int)(round($w / 10.0) * 10);
    }

    /** Remote Dispatch nutzbar: InverterHub meldet dispatch_available (34502 = 0xAA55) und bietet ctl_dispatch_mode an. */
    private function solisDispatchUsable(int $iid): bool
    {
        $av = $this->findChildVariableIdByIdent($iid, 'dispatch_available');
        if ($av <= 0 || $this->findChildVariableIdByIdent($iid, 'ctl_dispatch_mode') <= 0 || $this->findChildVariableIdByIdent($iid, 'ctl_dispatch_w') <= 0) { return false; }
        return (bool)GetValue($av);
    }

    /**
     * EMS-Entscheidung auf Remote Dispatch abbilden (Solis Smart Control V1.0, SUITE.md "Vertragsfeld dispatch"):
     * Netzladen -> Modus 2 mit +W und Netzladen erlaubt; Entladen/Export -> Modus 2 mit -W; Auto aus dem Netz -> Modus 3 mit
     * Netzbezug = Vorgabe (0.108.1); Einspeisen mit Vorgabe -> Modus 3 mit +W, Netzbezug mit Vorgabe -> Modus 3 mit -W (0.108.2); Halten (auch Laden aus PV) -> Modus 1 Standby, ausser bei PV-Ueberschuss (Standby wuerde PV nicht in den Akku lassen); sonst aus.
     * Unabhaengig vom Reserved SOC des Geraets. Der Totmann liegt beim Geraet (SOLIS_DISPATCH_FAILSAFE_MIN).
     */
    private function solisDispatchApply(int $iid, int $mode, int $powerW, int $w, bool $wantCharge, int $now): void
    {
        $isHold = ((($mode === GW_MODE_AC_EXPORT || $mode === GW_MODE_STANDBY) && $powerW <= 0)
            || $mode === GW_MODE_CHARGE_PV || $mode === GW_MODE_BUY);
        $dmode = 0; $dw = 0; $why = '';
        if ($wantCharge) {
            $dmode = 2; $dw = $w;
        } elseif ($mode === GW_MODE_BUY && $powerW > 0) {
            // 0.108.1 "Auto aus dem Netz" (Grid Rewards/Smart Charging): Modus 3 regelt den Bezug am Netzanschluss auf die
            // Vorgabe (Ladeleistung Auto - 200 W, carFromGridSetting()); der Akku versorgt das Haus, nie das Auto. Live Paul
            // 19.11.2026: Vorgabe -2100 W -> Zaehler -2080/-2096 W. Vorzeichen Doku: - Bezug.
            $dmode = 3; $dw = -(int)(round(min((float)$powerW, 40000.0) / 10.0) * 10);
        } elseif (($mode === GW_MODE_AC_EXPORT || $mode === GW_MODE_SELL) && $powerW > 0) {
            // 0.108.2 Einspeisen mit Vorgabe (Vorentladen vor billigen Stunden, Export): Modus 3 regelt die Einspeisung am
            // Netzanschluss auf +W, wie GoodWe-Modus 5 (Xset = Einspeisung am Zaehler). Der Akku liefert, was Haus und PV nicht decken.
            $dmode = 3; $dw = (int)(round(min((float)$powerW, 40000.0) / 10.0) * 10);
        } elseif ($mode === GW_MODE_AC_IMPORT && $powerW > 0) {
            // GoodWe-Modus 4 (Netzbezug am Zaehler = Vorgabe, Rest laedt den Akku): Modus 3 mit -W und Netzladen erlaubt
            $dmode = 3; $dw = -(int)(round(min((float)$powerW, 40000.0) / 10.0) * 10);
        } elseif ($isHold) {
            if ($this->solisHoldAllowed(0)) { $dmode = 1; }
            else { $why = $this->t('Solis: hold battery paused (PV surplus or waiting time), the inverter stays in automatic mode'); }
        } else {
            $disch = $this->solisDischargeW($iid, $mode, $powerW);
            if ($disch > 0) { $dmode = 2; $dw = -$disch; }
            elseif ($mode !== GW_MODE_AUTO && $mode !== GW_MODE_STOP) {
                $why = ($mode === GW_MODE_DISCHARGE || $mode === GW_MODE_BAT_DISCH)
                    ? $this->t('Solis: discharging on demand paused (no demand beyond PV and reserve), the inverter stays in automatic mode')
                    : sprintf($this->t('Solis: mode %d is not supported, the inverter stays in automatic mode'), $mode);
            }
        }
        if ($this->ReadAttributeString('NoControlReason') !== $why) { $this->WriteAttributeString('NoControlReason', $why); }
        $this->solisDispatchSend($iid, $dmode, $dw, $now, $mode === GW_MODE_AC_IMPORT);
    }

    /**
     * Senden: erst Vorgaben, zuletzt ctl_dispatch_mode (nur das Schreiben des Modus geht ans Geraet). Aktive Modi bei jeder
     * Aenderung und spaetestens alle SOLIS_DISPATCH_REWRITE_SEC neu (Arbeitsspeicher, kein Verschleiss), damit der Totmann nie
     * ablaeuft, solange EMS laeuft. "Aus" nur einmal beim Wechsel.
     */
    private function solisDispatchSend(int $iid, int $dmode, int $dw, int $now, bool $mode3Charge = false): void
    {
        $last = $this->ReadAttributeInteger('SolisDispLast');
        $lastW = $this->ReadAttributeInteger('SolisDispLastW');
        $at = $this->ReadAttributeInteger('SolisDispAt');
        if ($dmode === 0) {
            if ($last === 0) { return; }
            $this->ipsWrite($iid, 'ctl_dispatch_mode', 0);
        } else {
            $need = ($dmode !== $last) || (abs($dw - $lastW) >= 100) || ($now - $at >= SOLIS_DISPATCH_REWRITE_SEC);
            if (!$need) { return; }
            $this->ipsWrite($iid, 'ctl_dispatch_failsafe_min', SOLIS_DISPATCH_FAILSAFE_MIN);
            $this->ipsWrite($iid, 'ctl_dispatch_w', ($dmode === 2 || $dmode === 3) ? $dw : 0); // Vorzeichen Solis-Doku: Modus 2 + Laden/- Entladen, Modus 3 - Bezug
            $this->ipsWrite($iid, 'ctl_dispatch_grid_charge', (($dmode === 2 && $dw > 0) || ($dmode === 3 && $mode3Charge)) ? 1 : 0);
            $this->ipsWrite($iid, 'ctl_dispatch_mode', $dmode);
        }
        $this->WriteAttributeInteger('SolisDispLast', $dmode);
        $this->WriteAttributeInteger('SolisDispLastW', $dw);
        $this->WriteAttributeInteger('SolisDispAt', $now);
    }

    /**
     * Solis (minimale Steuerung, Dietmar 20.10.2026): EMS-Entscheidung auf Zwangsladen/Automatik abbilden. Reine Weitergabe im
     * InverterHub, Dauerhalten und Rueckfall liegen hier: Netzladen (Modus 11 mit Leistung) setzt ctl_force_w und
     * ctl_force_mode=1 und schreibt sie im Wiederholabstand neu; alles andere gibt den Zwangsmodus frei (ctl_force_mode=0,
     * der Wechselrichter regelt selbst). Nicht abbildbare Modi (Laden aus PV, Netzbezugsregelung, Halten, Entladen) laufen als
     * Automatik und stehen als Grund in der Statuszeile. Die Leistung wird auf die vom InverterHub gemeldete Ladegrenze begrenzt.
     */
    private function setSolisMode(int $iid, int $mode, int $powerW): void
    {
        $now = time();
        $capId = $this->findChildVariableIdByIdent($iid, 'bat_charge_max_w');
        $cap = ($capId > 0) ? (float)GetValue($capId) : 0.0;
        if ($cap <= 0.0) { $cap = 30000.0; }
        $w = (int)(round(max(0.0, min((float)$powerW, $cap, 30000.0)) / 10.0) * 10);
        $wantCharge = ($mode === GW_MODE_BAT_CHARGE && $w > 0);
        // 0.108.0: Kann der Solis Remote Dispatch (InverterHub 0.91.0, Vertrag 1.8), laeuft die ganze Steuerung darueber. Die
        // alten Stellglieder (Zwangsmodus, Speicherbetrieb 17, Netzlade-Bit) werden dann nur noch freigegeben, falls EMS sie gesetzt hat.
        $viaDispatch = $this->solisDispatchUsable($iid);
        if ($viaDispatch) {
            $this->solisDispatchApply($iid, $mode, $powerW, $w, $wantCharge, $now);
            $mode = GW_MODE_AUTO; $powerW = 0; $w = 0; $wantCharge = false;
        } elseif ($this->ReadAttributeInteger('SolisDispLast') > 0) {
            $this->solisDispatchSend($iid, 0, 0, $now); // Dispatch verschwunden (z. B. InverterHub-Rueckstand): aus
        }
        // Akku halten (Plan/manuell: Modus 5 mit 0 W): Entladesperre ueber den Speicherbetrieb, nur wenn der InverterHub sie anbietet
        // und kein PV-Ueberschuss da ist (ob PV bei gesperrtem Entladen weiter laedt, ist am Solis noch nicht gemessen)
        $holdCapable = ($this->findChildVariableIdByIdent($iid, 'svc_discharge_inhibit') > 0 && $this->findChildVariableIdByIdent($iid, 'cfg_storage_mode') > 0);
        // Modus 2 (Laden aus PV, Akku entlaedt nicht) und Modus 9 (Auto aus dem Netz, Smart Charging/Grid Rewards) gibt es am Solis
        // nicht; beide sollen vor allem verhindern, dass der Akku entlaedt -- am Solis also Halten (0.101.2, Pauls Anlage 12.11.2026:
        // als Automatik gefahren speiste der Akku bei Grid Rewards 3-4 kW ins Auto).
        $isHold = ((($mode === GW_MODE_AC_EXPORT || $mode === GW_MODE_STANDBY) && $powerW <= 0)
            || $mode === GW_MODE_CHARGE_PV || $mode === GW_MODE_BUY);
        $wantHold = (!$wantCharge && $holdCapable && $isHold && $this->solisHoldAllowed($iid));
        $this->solisGridChargeEnsure($iid, $wantCharge, $now);
        $this->solisHoldHousekeeping($iid);
        $this->solisHoldApply($iid, $wantHold, $now);
        $dischW = $wantCharge ? 0 : $this->solisDischargeW($iid, $mode, $powerW);
        $wantDisch = ($dischW > 0);
        $unsupported = (!$wantCharge && !$wantHold && !$wantDisch && $mode !== GW_MODE_AUTO && $mode !== GW_MODE_STOP);
        $why = $unsupported ? sprintf($this->t('Solis: mode %d is not supported, the inverter stays in automatic mode'), $mode) : '';
        if ($unsupported && ($mode === GW_MODE_DISCHARGE || $mode === GW_MODE_BAT_DISCH)) {
            $why = $this->t('Solis: discharging on demand paused (no demand beyond PV and reserve), the inverter stays in automatic mode');
        }
        if ($unsupported && $isHold && $holdCapable) {
            $why = $this->t('Solis: hold battery paused (PV surplus, state of charge above the device\'s reserve SOC, or waiting time), the inverter stays in automatic mode');
        }
        if (!$viaDispatch && $this->ReadAttributeString('NoControlReason') !== $why) { $this->WriteAttributeString('NoControlReason', $why); } // bei Dispatch setzt solisDispatchApply() den Grund

        $devId = $this->findChildVariableIdByIdent($iid, 'cfg_force_mode');
        $dev = ($devId > 0) ? (int)GetValue($devId) : -1; // vom Geraet zurueckgelesen, -1 = unbekannt
        $interval = $this->solisRewriteIntervalSec($iid);
        $lastMode = $this->ReadAttributeInteger('SolisLastMode');
        $lastPower = $this->ReadAttributeInteger('SolisLastPowerW');
        $lastAt = $this->ReadAttributeInteger('SolisLastWriteAt');

        if ($wantCharge) {
            $need = ($lastMode !== 1) || ($w !== $lastPower) || (($now - $lastAt) >= $interval) || ($dev === 0 && ($now - $lastAt) >= 10);
            if ($dev === 0 && $lastMode === 1 && ($now - $lastAt) > (2 * $interval + 30)
                && $now - $this->ReadAttributeInteger('NoControlLoggedAt') > 3600) {
                $this->WriteAttributeInteger('NoControlLoggedAt', $now);
                $this->emsLog(EMS_LOG_BASIC, sprintf('Solis: der Zwangsmodus wurde vom Gerät zurückgenommen, obwohl alle %d s neu geschrieben wird -- Fernsteuer-Zeitlimit prüfen', $interval));
            }
            if ($need) {
                $this->ipsWrite($iid, 'ctl_force_w', $w);       // erst die Leistung, dann der Modus
                $this->ipsWrite($iid, 'ctl_force_mode', 1);
                $this->WriteAttributeInteger('SolisLastMode', 1);
                $this->WriteAttributeInteger('SolisLastPowerW', $w);
                $this->WriteAttributeInteger('SolisLastWriteAt', $now);
                $this->WriteAttributeInteger('SolisReleaseTries', 0);
            }
            return;
        }
        if ($wantDisch) {
            // Zwangsentladen (Register 43135 = 2): fallt ohne Neuschreiben nach dem Zeitlimit zurueck und reagiert frueher, daher alle
            // 30 s neu; sonst wie das Laden (erst die Leistung, dann der Modus, sofort neu bei zurueckgelesenem Modus 0)
            $intD = min($interval, 30);
            $need = ($lastMode !== 2) || (abs($dischW - $lastPower) >= 100) || (($now - $lastAt) >= $intD) || ($dev === 0 && ($now - $lastAt) >= 10);
            if ($need) {
                $this->ipsWrite($iid, 'ctl_force_w', $dischW);
                $this->ipsWrite($iid, 'ctl_force_mode', 2);
                $this->WriteAttributeInteger('SolisLastMode', 2);
                $this->WriteAttributeInteger('SolisLastPowerW', $dischW);
                $this->WriteAttributeInteger('SolisLastWriteAt', $now);
                $this->WriteAttributeInteger('SolisReleaseTries', 0);
            }
            return;
        }
        // Freigeben: NUR einen Zwangsmodus, den EMS selbst gesetzt hat (SolisLastMode = 1 Laden, 2 Entladen). Ein Zwangsmodus von aussen
        // (Messskript, Solis-App/-Portal, Fremdregler) wird nie angefasst, auch nicht im Trockenlauf und nicht bei
        // EMS aus (Befund 21.10.2026: mit EMS im Trockenlauf fiel ein extern gesetztes Zwangsentladen nach 31 bis 122 s
        // zurueck, mit EMS aus erst nach dem Geraete-Zeitlimit). Das Freigeben wird bis zur Bestaetigung hoechstens
        // dreimal im Abstand von 10 s wiederholt (Schreibbefehle gehen am Solis gelegentlich verloren), danach gibt das
        // Geraete-Zeitlimit den Modus ohnehin frei.
        if ($lastMode !== 1 && $lastMode !== 2) { return; }
        if ($dev === 0) {
            $this->WriteAttributeInteger('SolisLastMode', 0);
            $this->WriteAttributeInteger('SolisLastPowerW', 0);
            $this->WriteAttributeInteger('SolisReleaseTries', 0);
            return;
        }
        $tries = $this->ReadAttributeInteger('SolisReleaseTries');
        if ($tries >= 3 || ($tries > 0 && ($now - $lastAt) < 10)) {
            if ($tries >= 3) {
                $this->WriteAttributeInteger('SolisLastMode', 0);
                $this->WriteAttributeInteger('SolisLastPowerW', 0);
                $this->WriteAttributeInteger('SolisReleaseTries', 0);
            }
            return;
        }
        $this->ipsWrite($iid, 'ctl_force_mode', 0);
        $this->WriteAttributeInteger('SolisLastWriteAt', $now);
        if ($dev < 0) { // ohne Rueckmeldung nicht wiederholen
            $this->WriteAttributeInteger('SolisLastMode', 0);
            $this->WriteAttributeInteger('SolisLastPowerW', 0);
            return;
        }
        $this->WriteAttributeInteger('SolisReleaseTries', $tries + 1);
    }
}
