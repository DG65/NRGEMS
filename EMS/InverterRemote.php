<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (0.109.0, 19.11.2026): gemeinsame Fernsteuerung ueber den InverterHub-Vertrag 1.9 `remoteControl`
 * (ctl_rc_*), mit Rueckfall auf den Solis-Vertrag 1.8 `dispatch` (ctl_dispatch_*). Eine Abbildung fuer alle Hersteller
 * (Solis, FoxESS, Deye, spaeter weitere); herstellerspezifisch ist nur, welche Modi `rc_modes` anbietet.
 *
 * Modi (SUITE.md "Vertragsfeld remoteControl"): 0 aus, 1 halten, 2 Akku +Laden/-Entladen, 3 Netzanschlusspunkt
 * +Einspeisung/-Bezug. Nur das Schreiben des Modus sendet; aktive Modi werden spaetestens alle SOLIS_DISPATCH_REWRITE_SEC
 * neu gesendet, der Totmann liegt beim Geraet (SOLIS_DISPATCH_FAILSAFE_MIN). Fehlt ein Modus, weicht die Abbildung aus
 * (z. B. Auto aus dem Netz ohne Modus 3: Akku liefert nur die Hauslast ueber Modus 2). Adapter mit rc_tested = false
 * steuert das EMS nur, wenn der Nutzer INV_Remote_AllowUntested eingeschaltet hat.
 */
trait EMSInverterRemote
{
    /** Welcher Fernsteuer-Vertrag ist da? 'rc' (1.9), 'dispatch' (1.8, nur Solis) oder '' (keiner). */
    private function remoteChannel(int $iid): string
    {
        if ($iid <= 0) { return ''; }
        $av = $this->findChildVariableIdByIdent($iid, 'rc_available');
        if ($this->findChildVariableIdByIdent($iid, 'ctl_rc_mode') > 0 && $av > 0 && (bool)GetValue($av)) { return 'rc'; }
        $dav = $this->findChildVariableIdByIdent($iid, 'dispatch_available');
        if ($this->findChildVariableIdByIdent($iid, 'ctl_dispatch_mode') > 0 && $this->findChildVariableIdByIdent($iid, 'ctl_dispatch_w') > 0
            && $dav > 0 && (bool)GetValue($dav)) { return 'dispatch'; }
        return '';
    }

    /** Angebotene Modi (ohne 0). Vertrag 1.8 (Solis) kann 1, 2, 3. */
    private function remoteModes(int $iid, string $ch): array
    {
        if ($ch === 'dispatch') { return array(1, 2, 3); }
        $id = $this->findChildVariableIdByIdent($iid, 'rc_modes');
        if ($id <= 0) { return array(); }
        $out = array();
        foreach (explode(',', (string)GetValue($id)) as $m) { $m = (int)trim($m); if ($m >= 1 && $m <= 3) { $out[] = $m; } }
        return $out;
    }

    /** Ungetesteter Adapter (rc_tested = false) nur mit ausdruecklicher Freigabe des Nutzers. */
    private function remoteTested(int $iid, string $ch): bool
    {
        if ($ch === 'dispatch') { return true; }
        $id = $this->findChildVariableIdByIdent($iid, 'rc_tested');
        return $id > 0 && (bool)GetValue($id);
    }

    /** Netzladen-Erlaubnis und SOC-Fenster: Vertrag 1.8 immer, Vertrag 1.9 nur, wo der Adapter es anbietet (heute Solis). */
    private function remoteHasExtras(int $iid, string $ch): bool
    {
        if ($ch === 'dispatch') { return true; }
        $id = $this->findChildVariableIdByIdent($iid, 'rc_vendor');
        return $id > 0 && stripos((string)GetValue($id), 'solis') !== false;
    }

    private function remoteUsable(int $iid): bool
    {
        $ch = $this->remoteChannel($iid);
        if ($ch === '' || $this->remoteModes($iid, $ch) === array()) { return false; }
        // InverterHub 1.10: rc_failsafe_decl_sec = Totmann, den der Adapter ins Geraet schreibt; 0 = keiner bekannt (z. B. Kostal)
        // -> nie steuern (SUITE.md Fremdanbindung 5). Fehlt die Variable (InverterHub < 0.96.0), gilt das bisherige Verhalten.
        $fsId = $this->findChildVariableIdByIdent($iid, 'rc_failsafe_decl_sec');
        // Nie beschrieben (VariableUpdated 0, z. B. neu angelegt und noch kein langsamer Zyklus) = unbekannt, nicht "kein Totmann"
        // (0.112.1, Befund Paul 20.11.: InverterHub 0.96.1 legte die Variable an, beschrieb sie aber nicht -> Solis stand still).
        if ($ch === 'rc' && $fsId > 0 && (int)(IPS_GetVariable($fsId)['VariableUpdated'] ?? 0) > 0 && (int)GetValue($fsId) <= 0) { return false; }
        return $this->remoteTested($iid, $ch) || $this->ReadPropertyBoolean('INV_Remote_AllowUntested');
    }

    /** Wechselrichter ohne eigene Zweige (FoxESS, Deye, ...): nur ueber die Fernsteuerung. */
    private function setRemoteMode(int $iid, int $mode, int $powerW): void
    {
        $now = time();
        if (!$this->remoteUsable($iid)) {
            $ch = $this->remoteChannel($iid);
            $why = ($ch !== '' && !$this->remoteTested($iid, $ch) && !$this->ReadPropertyBoolean('INV_Remote_AllowUntested'))
                ? $this->t('remote control for this inverter is untested -- not used until you allow it under "My system"')
                : $this->t('inverter without EMS actuators');
            if ($this->ReadAttributeString('NoControlReason') !== $why) { $this->WriteAttributeString('NoControlReason', $why); }
            if ($this->ReadAttributeInteger('SolisDispLast') > 0) { $this->remoteSendOff($iid, $now); }
            return;
        }
        $capId = $this->findChildVariableIdByIdent($iid, 'bat_charge_max_w');
        $cap = ($capId > 0) ? (float)GetValue($capId) : 0.0;
        if ($cap <= 0.0) { $cap = 30000.0; }
        $w = (int)(round(max(0.0, min((float)$powerW, $cap, 30000.0)) / 10.0) * 10);
        $this->remoteApply($iid, $mode, $powerW, $w, $mode === GW_MODE_BAT_CHARGE && $w > 0, $now);
    }

    private function remoteHouseDeficitW(): float
    {
        try { return max(0.0, (float)$this->GetValue('EMS_HousePower') - (float)$this->GetValue('EMS_PVPower')); }
        catch (Throwable $e) { return 0.0; }
    }

    /**
     * EMS-Entscheidung auf die Fernsteuerung abbilden. Netzladen -> 2 (+W); Entladen auf Vorgabe -> 2 (-W); Auto aus dem Netz
     * -> 3 (Bezug = Vorgabe), ohne Modus 3: 2 mit -Hauslastdefizit (Akku versorgt nur das Haus); Einspeisen -> 3 (+W), ohne
     * Modus 3: 2 mit -(Einspeisung + Hauslastdefizit); Netzbezug mit Vorgabe -> 3 (-W, Netzladen erlaubt); Halten -> 1, ausser
     * bei PV-Ueberschuss; ohne Modus 1: aus mit Grund. Unabhaengig vom Reserved SOC des Geraets.
     */
    private function remoteApply(int $iid, int $mode, int $powerW, int $w, bool $wantCharge, int $now): void
    {
        $ch = $this->remoteChannel($iid);
        $modes = $this->remoteModes($iid, $ch);
        $has = function (int $m) use ($modes): bool { return in_array($m, $modes, true); };
        $r10 = function (float $x): int { return (int)(round(min($x, 40000.0) / 10.0) * 10); };
        $isHold = ((($mode === GW_MODE_AC_EXPORT || $mode === GW_MODE_STANDBY) && $powerW <= 0)
            || $mode === GW_MODE_CHARGE_PV || $mode === GW_MODE_BUY);
        $dmode = 0; $dw = 0; $why = ''; $gridCharge = false;
        $unsupported = function (int $m) { return sprintf($this->t('remote control of this inverter does not offer mode %d, the inverter stays in automatic mode'), $m); };
        if ($wantCharge) {
            if ($has(2)) { $dmode = 2; $dw = $w; $gridCharge = true; } else { $why = $unsupported(2); }
        } elseif ($mode === GW_MODE_BUY && $powerW > 0) {
            // Auto aus dem Netz: Bezug = Vorgabe (Ladeleistung - 200 W, carFromGridSetting()); Akku versorgt das Haus, nie das Auto
            if ($has(3)) { $dmode = 3; $dw = -$r10((float)$powerW); }
            elseif ($has(2) && $this->remoteHouseDeficitW() >= 100.0) { $dmode = 2; $dw = -$r10($this->remoteHouseDeficitW()); }
            elseif ($has(1)) { $dmode = 1; }
            else { $why = $unsupported(3); }
        } elseif (($mode === GW_MODE_AC_EXPORT || $mode === GW_MODE_SELL) && $powerW > 0) {
            if ($has(3)) { $dmode = 3; $dw = $r10((float)$powerW); }
            elseif ($has(2)) { $dmode = 2; $dw = -$r10((float)$powerW + $this->remoteHouseDeficitW()); }
            else { $why = $unsupported(3); }
        } elseif ($mode === GW_MODE_AC_IMPORT && $powerW > 0) {
            if ($has(3)) { $dmode = 3; $dw = -$r10((float)$powerW); $gridCharge = true; } else { $why = $unsupported(3); }
        } elseif ($isHold) {
            if (!$this->solisHoldAllowed(0)) { $why = $this->t('Solis: hold battery paused (PV surplus or waiting time), the inverter stays in automatic mode'); }
            elseif ($has(1)) { $dmode = 1; }
            else { $why = $unsupported(1); }
        } else {
            $disch = $this->solisDischargeW($iid, $mode, $powerW);
            if ($disch > 0 && $has(2)) { $dmode = 2; $dw = -$disch; }
            elseif ($disch > 0) { $why = $unsupported(2); }
            elseif ($mode !== GW_MODE_AUTO && $mode !== GW_MODE_STOP) {
                $why = ($mode === GW_MODE_DISCHARGE || $mode === GW_MODE_BAT_DISCH)
                    ? $this->t('Solis: discharging on demand paused (no demand beyond PV and reserve), the inverter stays in automatic mode')
                    : sprintf($this->t('Solis: mode %d is not supported, the inverter stays in automatic mode'), $mode);
            }
        }
        if ($this->ReadAttributeString('NoControlReason') !== $why) { $this->WriteAttributeString('NoControlReason', $why); }
        if ($dmode === 0) { $this->remoteSendOff($iid, $now); return; }
        $this->remoteSend($iid, $ch, $dmode, $dw, $now, $gridCharge);
    }

    /**
     * Senden: erst Vorgaben, zuletzt der Modus (nur das Schreiben des Modus geht ans Geraet). Aktive Modi bei jeder Aenderung
     * und spaetestens alle SOLIS_DISPATCH_REWRITE_SEC neu (Arbeitsspeicher), damit der Totmann nie ablaeuft, solange EMS laeuft.
     */
    private function remoteSend(int $iid, string $ch, int $dmode, int $dw, int $now, bool $gridCharge): void
    {
        $last = $this->ReadAttributeInteger('SolisDispLast');
        $need = ($dmode !== $last) || (abs($dw - $this->ReadAttributeInteger('SolisDispLastW')) >= 100)
            || ($now - $this->ReadAttributeInteger('SolisDispAt') >= SOLIS_DISPATCH_REWRITE_SEC);
        if (!$need) { return; }
        $p = ($ch === 'rc') ? 'ctl_rc_' : 'ctl_dispatch_';
        if ($ch === 'rc') { $this->ipsWrite($iid, 'ctl_rc_failsafe_sec', SOLIS_DISPATCH_FAILSAFE_MIN * 60); }
        else { $this->ipsWrite($iid, 'ctl_dispatch_failsafe_min', SOLIS_DISPATCH_FAILSAFE_MIN); }
        if ($this->remoteHasExtras($iid, $ch)) {
            // SOC-Fenster: wo der Akku Leistung abgibt (2 entladen, 3), gilt die EMS-Untergrenze auch im Geraet
            if ($this->findChildVariableIdByIdent($iid, $p . 'soc_min') > 0) {
                $floor = (int)min(95, max(0, $this->ReadPropertyInteger('BAT_SOC_Min') + $this->ReadPropertyInteger('BAT_SOC_Reserve_Backup')));
                $this->ipsWrite($iid, $p . 'soc_min', (($dmode === 2 && $dw < 0) || $dmode === 3) ? $floor : 0);
                if ($this->findChildVariableIdByIdent($iid, $p . 'soc_max') > 0) { $this->ipsWrite($iid, $p . 'soc_max', 100); }
            }
        }
        $this->ipsWrite($iid, $p . 'w', ($dmode === 2 || $dmode === 3) ? $dw : 0); // + Laden/Einspeisung, - Entladen/Bezug
        if ($this->remoteHasExtras($iid, $ch)) { $this->ipsWrite($iid, $p . 'grid_charge', $gridCharge ? 1 : 0); }
        $this->ipsWrite($iid, $p . 'mode', $dmode);
        $this->WriteAttributeInteger('SolisDispLast', $dmode);
        $this->WriteAttributeInteger('SolisDispLastW', $dw);
        $this->WriteAttributeInteger('SolisDispAt', $now);
    }

    /** "Aus" nur einmal beim Wechsel; ueber den Vertrag, den das Geraet hat (beide, falls im Uebergang beide da sind). */
    private function remoteSendOff(int $iid, int $now): void
    {
        if ($this->ReadAttributeInteger('SolisDispLast') === 0) { return; }
        if ($this->findChildVariableIdByIdent($iid, 'ctl_rc_mode') > 0) { $this->ipsWrite($iid, 'ctl_rc_mode', 0); }
        if ($this->findChildVariableIdByIdent($iid, 'ctl_dispatch_mode') > 0) { $this->ipsWrite($iid, 'ctl_dispatch_mode', 0); }
        $this->WriteAttributeInteger('SolisDispLast', 0);
        $this->WriteAttributeInteger('SolisDispLastW', 0);
        $this->WriteAttributeInteger('SolisDispAt', $now);
    }
}
