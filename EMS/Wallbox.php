<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 4, 17.11.2026): Wallbox-Steuerung: Freigaben schalten, Ladepläne (Ladeart, Ziel, Frist), Bedienvariablen und Statuszeilen, Tibber-Fahrzeugeinstellungen.
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSWallbox;" in der Klasse EMS.
 */
trait EMSWallbox
{
    /**
     * Kleinste sinnvolle Ladeleistung der Wallbox $n (W). Eine ausdruecklich eingestellte Mindestleistung gilt immer.
     * Steht die Einstellung noch auf dem vorsichtigen Standardwert (4140 W = 3-phasig 6 A) und meldet die Wallbox ihre
     * aktuelle Phasenzahl (`phases`, ChargerHub-Vertrag ab 1.6), rechnet das EMS Phasen x 230 V x Mindeststrom
     * (`stationMinCurrentA` aus OCPP, sonst 6 A nach IEC 61851). Fehlt die Phasenzahl, bleibt es beim Standardwert.
     */
    private function wallboxMinPowerW(int $n): float
    {
        $prop = (float)$this->ReadPropertyInteger('WB' . $n . '_Min_Power_W');
        if ((int)$prop !== 4140) { return max(500.0, $prop); }
        $e  = $this->getChargerEntry($n);
        $ph = (int)($e['phases'] ?? 0);
        if ($ph !== 1 && $ph !== 3) { return max(500.0, $prop); }
        $minA = max(6.0, (float)($e['stationMinCurrentA'] ?? 6));
        return max(500.0, $ph * 230.0 * $minA);
    }

    private function wallboxPriceAllowed(int $n, array $s, float $price, float $thWB): bool
    {
        if (empty($s['tib_active']) || $price < $thWB) { return true; }
        if (!$this->ReadPropertyBoolean('WB_PV_Ueberschuss')) { return false; }
        $minW    = $this->wallboxMinPowerW($n);
        $surplus = (float)($s['pv_total_w'] ?? 0.0) - (float)($s['house_pow_w'] ?? 0.0);
        $charging = ((float)($s['wb' . $n . '_pow_kw'] ?? 0.0) * 1000.0) > 100.0;
        return $surplus >= ($charging ? 0.5 * $minW : $minW);
    }

    private function wallboxWritable(int $n): bool
    {
        $configured = $this->ReadPropertyInteger('WB' . $n . '_Instance');
        if ($configured > 0) {
            foreach ($this->getWritableChargers() as $c) {
                if (($c['instanceID'] ?? 0) === $configured) { return true; }
            }
            return function_exists('GOeCharger_SetMode');
        }
        return $this->getControlEntry($n) !== null;
    }

    /**
     * Tibbers Fahrzeugliste (TIBBERGR_GetFlexDevices, Vertrag 1.0, seit Tibber 2.9.2): array('available' => bool,
     * 'vehicles' => deviceId => Eintrag). 'available' false, wenn Tibber fehlt oder den Aufruf noch nicht kennt
     * oder eine fremde Vertrags-Major liefert (dann bleibt die Kopplung aus, der Rest laeuft normal weiter).
     */
    private function getTibberVehicles(): array
    {
        $out = array('available' => false, 'vehicles' => array());
        if (!function_exists('TIBBERGR_GetFlexDevices')) { return $out; }
        $tibberId = $this->getTibberGridRewardInstance();
        if ($tibberId <= 0) { return $out; }
        $list = @TIBBERGR_GetFlexDevices($tibberId);
        if (!is_array($list)) { return $out; }
        $out['available'] = true;
        foreach ($list as $e) {
            if (!is_array($e) || ($e['type'] ?? '') !== 'vehicle') { continue; }
            if ((int)explode('.', (string)($e['contractVersion'] ?? '1.0'))[0] !== 1) { $out['available'] = false; continue; }
            $id = (string)($e['deviceId'] ?? '');
            if ($id !== '') { $out['vehicles'][$id] = $e; }
        }
        return $out;
    }

    /**
     * Stand des einer Wallbox zugeordneten Tibber-Fahrzeugs: array('name', 'smart' => bool|null, 'plugged' => bool|null,
     * 'stale' => bool, 'found' => bool). Wird das Fahrzeug gerade nicht gemeldet, gilt der zuletzt gesehene Stand
     * ('stale' => true), damit ein kurzer Tibber-Ausfall den Modus nicht umschlagen laesst. $persist: Stand merken.
     */
    private function tibberVehicleState(int $n, string $deviceId, bool $persist, array $tib): array
    {
        if ($tib['available'] && isset($tib['vehicles'][$deviceId])) {
            $v     = $tib['vehicles'][$deviceId];
            $smart = array_key_exists('isSmartChargingEnabled', $v) && $v['isSmartChargingEnabled'] !== null ? (bool)$v['isSmartChargingEnabled'] : null;
            if ($persist && $smart !== null) {
                $seen = $smart ? 1 : 0;
                if ($this->ReadAttributeInteger('WB' . $n . 'SmartSeen') !== $seen) { $this->WriteAttributeInteger('WB' . $n . 'SmartSeen', $seen); }
            }
            return array('found' => true, 'stale' => false, 'smart' => $smart, 'name' => (string)($v['name'] ?? '?'),
                'plugged' => array_key_exists('isPluggedIn', $v) && $v['isPluggedIn'] !== null ? (bool)$v['isPluggedIn'] : null);
        }
        $seen = $this->ReadAttributeInteger('WB' . $n . 'SmartSeen');
        return array('found' => false, 'stale' => true, 'smart' => $seen >= 0 ? ($seen === 1) : null, 'name' => '', 'plugged' => null,
            'available' => $tib['available']);
    }

    /**
     * Aufgeloester Modus einer Wallbox: array('mode' => 'smart'|'ems'|'observe', 'manual' => bool, 'writable' => bool
     * [, 'tibber' => Stand des zugeordneten Tibber-Fahrzeugs]).
     * Einstellung 0 = automatisch: meldet Tibber fuer das zugeordnete Fahrzeug Smart Charging -> 'smart'; sonst
     * schaltbar -> 'ems', nicht schaltbar -> 'observe'. ($override*: Auswahl aus dem offenen Formular.)
     */
    private function wallboxMode(int $n, ?int $override = null, ?string $vehicleOverride = null): array
    {
        $setting  = ($override !== null) ? $override : $this->ReadPropertyInteger('WB' . $n . '_Mode');
        $writable = $this->wallboxWritable($n);
        $vehicle  = ($vehicleOverride !== null) ? trim($vehicleOverride) : trim($this->ReadPropertyString('WB' . $n . '_TibberVehicle'));
        $tibber   = ($vehicle !== '') ? $this->tibberVehicleState($n, $vehicle, $vehicleOverride === null, $this->getTibberVehicles()) : null;
        switch ($setting) {
            case 1:  $r = array('mode' => 'smart',   'manual' => true,  'writable' => $writable); break;
            case 2:  $r = array('mode' => 'ems',     'manual' => true,  'writable' => $writable); break;
            case 3:  $r = array('mode' => 'observe', 'manual' => true,  'writable' => $writable); break;
            default:
                $mode = ($tibber !== null && $tibber['smart'] === true) ? 'smart' : ($writable ? 'ems' : 'observe');
                $r = array('mode' => $mode, 'manual' => false, 'writable' => $writable);
        }
        if ($tibber !== null) { $r['tibber'] = $tibber; }
        return $r;
    }

    private function tibberVehicleLabel(array $v): string
    {
        $name = (string)($v['name'] ?? '?');
        $make = (string)($v['make'] ?? '');
        return $make !== '' ? $name . ' (' . $make . ')' : $name;
    }

    private function tibberVehicleOptions(string $selected): array
    {
        $opts = array(array('caption' => self::fest('(no assignment)'), 'value' => ''));
        $tib  = $this->getTibberVehicles();
        foreach ($tib['vehicles'] as $id => $v) {
            $opts[] = array('caption' => (($v['isPluggedIn'] ?? false) ? sprintf($this->t('%s — plugged in'), $this->tibberVehicleLabel($v)) : $this->tibberVehicleLabel($v)), 'value' => (string)$id);
        }
        if ($selected !== '' && !isset($tib['vehicles'][$selected])) {
            $opts[] = array('caption' => sprintf($this->t('(currently not reported by Tibber) %s'), $selected), 'value' => $selected);
        }
        return $opts;
    }

    private function wallboxModeStatusLine(int $n, ?int $override = null, ?string $vehicleOverride = null)
    {
        $m   = $this->wallboxMode($n, $override, $vehicleOverride);
        $has = (count($this->getChargerList()) >= $n) || $this->ReadPropertyInteger('WB' . $n . '_Instance') > 0;
        $t   = $m['tibber'] ?? null;
        $who = ($t !== null && $t['name'] !== '') ? sprintf($this->t('"%s"'), $t['name']) : $this->t('the assigned vehicle');
        $old = ($t !== null && $t['stale']) ? $this->t(' (last known state, Tibber is currently not reporting the vehicle)') : '';

        if (!$has && !$m['manual'] && $t === null) {
            return sprintf($this->t('ℹ️ Wallbox %d: no wallbox found. As soon as ChargerHub or OCPPHub reports one, EMS detects by itself whether it may switch it.'), $n);
        }
        if ($m['mode'] === 'smart') {
            if (!$m['manual']) {
                return array('caption' => sprintf($this->t('🔗 Wallbox %d: Smart Charging (detected automatically: Tibber reports it as on for %s%s). Tibber is in charge, EMS neither blocks nor forces anything.'), $n, $who, $old), 'color' => EMS_COLOR_AUTO);
            }
            $text = sprintf($this->t('✏️ Wallbox %d: Smart Charging (own choice). Tibber is in charge, EMS neither blocks nor forces anything. If EMS blocked the wallbox itself earlier, it releases it once.'), $n);
            if ($t !== null && $t['smart'] === false) {
                return sprintf($this->t('⚠️ Wallbox %d: Smart Charging selected, but Smart Charging is off at Tibber for %s%s. Grid Rewards cannot take effect here, and nobody charges the vehicle deliberately.'), $n, $who, $old);
            }
            return array('caption' => $text, 'color' => -1);
        }
        if ($m['mode'] === 'observe') {
            $who = $this->chargerControlInfo($n);
            if (!$m['manual']) {
                return array('caption' => sprintf($this->t('🔗 Wallbox %d: controlled externally (detected automatically, EMS may not switch it). EMS only measures; the power counts towards house consumption.%s'), $n, $who), 'color' => EMS_COLOR_AUTO);
            }
            return array('caption' => sprintf($this->t('✏️ Wallbox %d: monitor only (own choice). EMS measures the power and switches nothing.%s'), $n, $who), 'color' => -1);
        }
        if ($t !== null && $t['smart'] === true) {
            return sprintf($this->t('⚠️ Wallbox %d: "EMS controls" selected, but Smart Charging is on at Tibber for %s%s. Then two parties control the same wallbox. Switch off Smart Charging in the vehicle or select Smart Charging here.'), $n, $who, $old);
        }
        if (!$m['writable']) {
            return sprintf($this->t('⚠️ Wallbox %d: "EMS controls" selected, but EMS may not switch this wallbox (controlled externally). Nothing happens.'), $n);
        }
        $tail = ($t !== null) ? sprintf($this->t(' Tibber reports Smart Charging as off for %s%s.'), $who, $old) : $this->t(' Smart Charging in the vehicle should be off, otherwise two parties control it.');
        if ($override === null && ($this->liveInt('WB' . $n . '_ChargeMode') > 0 || $this->wallboxTargetConfigured($n))) {
            $ct = trim($this->ReadAttributeString('WB' . $n . 'ChargeText'));
            if ($ct !== '') { $tail = sprintf($this->t(' Charging mode: %s.'), $ct) . $tail; }
        }
        if (!$m['manual']) {
            return array('caption' => sprintf($this->t('🔗 Wallbox %d: EMS controls enabling (detected automatically, wallbox is switchable).%s'), $n, $tail), 'color' => EMS_COLOR_AUTO);
        }
        return array('caption' => sprintf($this->t('✏️ Wallbox %d: EMS controls enabling (own choice).%s'), $n, $tail), 'color' => -1);
    }

    /**
     * Wer regelt diese Wallbox gerade und warum, laut ChargerHub (Vertrag 1.9: controlReason = deutscher Klartext nur zur Anzeige,
     * controlSince = Unix-Zeit des letzten Besitzerwechsels, 0 = unbekannt). Nur Anzeige fuer Wallboxen, die EMS beobachtet; fehlt das
     * Feld (aelterer ChargerHub) oder ist es leer, kommt nichts dazu.
     */
    private function chargerControlInfo(int $n): string
    {
        $entry = $this->getChargerEntry($n);
        $reason = trim((string)($entry['controlReason'] ?? ''));
        if ($reason === '') { return ''; }
        if (mb_strlen($reason) > 160) { $reason = mb_substr($reason, 0, 157) . '...'; }
        $since = (int)($entry['controlSince'] ?? 0);
        $when = '';
        if ($since > 0) { $when = sprintf($this->t(' (since %s)'), (date('Y-m-d', $since) === date('Y-m-d') ? date('H:i', $since) : date($this->t('M j H:i'), $since))); }
        return sprintf($this->t(' According to ChargerHub it is controlled like this: %s%s.'), $reason, $when);
    }

    private function tibberVehicleHint(int $n): string
    {
        if ($this->ReadPropertyInteger('WB_Count') !== 1 || trim($this->ReadPropertyString('WB' . $n . '_TibberVehicle')) !== '') { return ''; }
        $tib = $this->getTibberVehicles();
        if (count($tib['vehicles']) !== 1) { return ''; }
        $v = reset($tib['vehicles']);
        return sprintf($this->t(' ℹ️ Tibber reports exactly one vehicle (%s). Select it under "Tibber vehicle", then EMS detects Smart Charging automatically.'), $this->tibberVehicleLabel($v));
    }

    /**
     * Gibt eine Wallbox wieder frei, die EMS selbst gesperrt hatte (Modus Smart Charging: Tibber braucht die Freigabe).
     * Eine vom Nutzer gesperrte Wallbox bleibt unberuehrt. Das Merkmal faellt beim Senden der Freigabe weg; war die
     * Wallbox real schon frei (nichts zu senden) und kein Cooldown aktiv, wird es hier aufgeraeumt.
     */
    private function releaseOwnWallboxLock(int $n): void
    {
        if (!$this->ReadAttributeBoolean('WB' . $n . 'EmsLocked')) { return; }
        $this->controlWallbox($n, true);
        $cooldownOver = (time() - $this->ReadAttributeInteger('LastWB' . $n . 'Switch')) >= $this->ReadPropertyInteger('WB_Cooldown_Sec');
        if ($this->ReadAttributeBoolean('WB' . $n . 'EmsLocked') && $cooldownOver) {
            $this->WriteAttributeBoolean('WB' . $n . 'EmsLocked', false);
        }
    }

    /**
     * Wallbox-Freigaben der Entscheidung an die Wallboxen schreiben -- je nach Modus:
     * 'smart' loest nur eine eigene Sperre (auch waehrend Grid Rewards, Tibber braucht sie), 'ems' schreibt die
     * Entscheidung (ausser bei wb_hands_off), 'observe' schreibt nichts.
     */
    private function applyWallboxControl(array &$d, array $s): void
    {
        if (empty($s['wb_active'])) { return; }
        $count = (($s['wb_count'] ?? 1) >= 2) ? 2 : 1;
        $modes = array();
        for ($n = 1; $n <= $count; $n++) {
            $modes[$n] = $this->wallboxMode($n)['mode'];
            if ($modes[$n] === 'smart') { $this->releaseOwnWallboxLock($n); }
        }
        if (!empty($d['wb_hands_off'])) {
            for ($n = 1; $n <= $count; $n++) {
                $this->publishWallboxPlan($n, ($modes[$n] === 'smart') ? $this->t('Smart Charging: Tibber controls the charging, EMS does not intervene.') : $this->t('Grid Rewards: Tibber controls the charging, EMS does not intervene.'));
            }
            return;
        }
        // Ladearten (manuell, nur PV, PV-Anteil, Hybrid): Netzbetreiber-Sperre und Batterie-Boost behalten Vorrang, dann gilt die
        // Ladeart statt der Preisfreigabe. Die geplante Freigabe geht vor dem Netzanschluss-Budget in die Entscheidung ein.
        $plans = array();
        for ($n = 1; $n <= $count; $n++) {
            $u = $this->ReadAttributeInteger('WB' . $n . 'NowUntil');
            if ($u > 0 && $u <= time()) {
                $this->WriteAttributeInteger('WB' . $n . 'NowUntil', 0);
                $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Jetzt laden abgelaufen, es gilt wieder die Ladeart', $n));
            }
        }
        if (!in_array((string)($d['source'] ?? ''), array('netzbetreiber', 'nutzer'), true)) {
            for ($n = 1; $n <= $count; $n++) {
                if ($modes[$n] !== 'ems' || ($this->liveInt('WB' . $n . '_ChargeMode') <= 0 && !$this->wallboxTargetConfigured($n) && $this->ReadAttributeInteger('WB' . $n . 'NowUntil') <= time())) { continue; }
                $plan = $this->wallboxChargePlanFor($n, $s);
                if ($plan === null) { continue; }
                $plans[$n] = $plan;
                $d['wb' . $n . '_enable'] = $plan['enable'];
            }
        }
        $this->enforceGridImportBudget($d, $s);
        for ($n = 1; $n <= $count; $n++) {
            if ($modes[$n] !== 'ems') { continue; }
            if (isset($plans[$n]) && !empty($d['wb' . $n . '_enable'])) { $this->applyWallboxChargePlan($n, $plans[$n]); }
            else { $this->controlWallbox($n, $d['wb' . $n . '_enable']); }
        }
        for ($n = 1; $n <= $count; $n++) {
            $this->publishWallboxPlan($n, $this->wallboxPlanText($n, $modes[$n], $d, $s, $plans[$n] ?? null));
        }
    }

    /**
     * Klartext "was die Wallbox als Naechstes tut" (Stufe A, Dietmar 23.10.2026: "Ich sehe nicht, was die Wallbox tun wird").
     * Vorrang wie in der Steuerung: Netzbetreiber, Batterie-Boost, Smart Charging, Beobachten, Jetzt laden, Ladeziel, Ladeart, Preis.
     */
    private function wallboxPlanText(int $n, string $mode, array $d, array $s, ?array $plan): string
    {
        $src = (string)($d['source'] ?? '');
        $plugged = (int)($s['wb' . $n . '_cable'] ?? 0) > 0;
        $head = $plugged ? '' : $this->t('No vehicle plugged in. ');
        if ($mode === 'smart') { return $head . $this->t('Smart Charging: Tibber controls the charging, EMS does not intervene.'); }
        if ($mode === 'observe') {
            $who = trim($this->chargerControlInfo($n));
            return $head . $this->t('Monitor only: EMS does not switch this wallbox.') . ($who !== '' ? ' ' . $who : '');
        }
        if ($src === 'netzbetreiber') { return $head . $this->t('§14a: the grid operator is currently limiting, charging is blocked.'); }
        if ($src === 'nutzer') { return $head . $this->t('Battery boost: wallbox enabled with maximum current until the boost ends.'); }
        if ((int)($s['wb' . $n . '_error'] ?? 0) !== 0) { return $head . sprintf($this->t('The wallbox reports error %d, charging is blocked.'), (int)$s['wb' . $n . '_error']); }
        $enabled = !empty($d['wb' . $n . '_enable']);
        if ($plan !== null) {
            $t = (string)$plan['text'];
            if ($plan['enable'] && !$enabled) { $t .= $this->t(' (currently blocked by the grid connection budget)'); }
            return $head . $t . '.';
        }
        $note = $this->ReadAttributeString('WB' . $n . 'TargetNote');
        $th = $this->priceThresholds()['wb'];
        $price = $this->currentPriceEur()['eur'];
        $pv = $this->ReadPropertyBoolean('WB_PV_Ueberschuss') ? $this->t(' or with PV surplus') : '';
        $pTxt = ($price !== null) ? sprintf($this->t(' (price %s ct)'), $this->num($price * 100, 1)) : '';
        if (!$plugged) {
            $state = ($price !== null && $price < $th) ? $this->t('price currently below, charges immediately after plugging in') . $pTxt : $this->t('waiting') . $pTxt;
        } else {
            $state = ($enabled ? $this->t('enabled now') : $this->t('blocked now')) . $pTxt;
        }
        $txt = sprintf($this->t('Price-controlled: charges at a price below %s ct%s; %s.'), $this->num($th * 100, 1), $pv, $state);
        return $head . ($note !== '' ? $note . ' ' : '') . $txt;
    }

    private function publishWallboxPlan(int $n, string $text): void
    {
        $vid = @IPS_GetObjectIDByIdent('EMS_WB' . $n . '_Plan', $this->InstanceID);
        if ($vid !== false && $vid > 0 && GetValue($vid) !== $text) { SetValue($vid, $text); }
        if (!function_exists('CHUB_SetPlanInfo')) { return; }
        $entry = $this->getChargerEntry($n);
        if (($entry['source'] ?? '') !== 'chargerhub' || (int)($entry['instanceID'] ?? 0) <= 0) { return; }
        $interval = max(30, (int)$this->ReadPropertyInteger('EMS_Interval'));
        $now = time();
        if ($text === $this->ReadAttributeString('WB' . $n . 'PlanSent') && $now - $this->ReadAttributeInteger('WB' . $n . 'PlanSentAt') < $interval) { return; }
        try { @CHUB_SetPlanInfo((int)$entry['instanceID'], $text, $now + 3 * $interval); } catch (Throwable $e) { return; }
        $this->WriteAttributeString('WB' . $n . 'PlanSent', $text);
        $this->WriteAttributeInteger('WB' . $n . 'PlanSentAt', $now);
    }

    /**
     * Bedien-Variablen je Wallbox fuers WebFront (Stufe B, eine Quelle: ein Klick aendert die Einstellung selbst): Plan (Anzeige),
     * Ladeart, Ladeziel, Frist, Befehl (Jetzt 1 Stunde laden, Uebersteuerung beenden, Ladeziel loeschen). Nur fuer aktive Wallboxen.
     */
    private function maintainWallboxControlVariables(): void
    {
        $opt = function (array $map) { $o = array(); foreach ($map as $v => $c) { $o[] = array('Value' => $v, 'Caption' => $c, 'IconActive' => false, 'IconValue' => '', 'Color' => -1); } return json_encode($o); };
        $modes = $opt(array(0 => $this->t('Price-controlled'), 1 => $this->t('Manual'), 2 => $this->t('PV only'), 3 => $this->t('PV share'), 4 => $this->t('Hybrid'), 5 => $this->t('Off')));
        $cmds = $opt(array(0 => $this->t('Ready'), 1 => $this->t('Charge now for 1 hour'), 2 => $this->t('End override'), 3 => $this->t('Delete charging target')));
        for ($n = 1; $n <= 2; $n++) {
            $keep = $this->ReadPropertyBoolean('WB_Active') && $n <= max(1, $this->ReadPropertyInteger('WB_Count'));
            $pos = 300 + $n * 10;
            $this->MaintainVariable('EMS_WB' . $n . '_Plan', sprintf($this->t('Wallbox %d: plan (what happens next)'), $n), VARIABLETYPE_STRING, array('PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION), $pos, $keep);
            $this->MaintainVariable('EMS_WB' . $n . '_ChargeMode', sprintf($this->t('Wallbox %d: charging mode'), $n), VARIABLETYPE_INTEGER, array('PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => $modes), $pos + 1, $keep);
            $this->MaintainVariable('EMS_WB' . $n . '_TargetKwh', sprintf($this->t('Wallbox %d: charging target (kWh, 0 = off)'), $n), VARIABLETYPE_FLOAT, array('PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0.0, 'MAX' => 200.0, 'STEP_SIZE' => 0.5, 'SUFFIX' => ' kWh', 'DIGITS' => 1), $pos + 2, $keep);
            $this->MaintainVariable('EMS_WB' . $n . '_TargetTime', sprintf($this->t('Wallbox %d: deadline (HH:MM)'), $n), VARIABLETYPE_STRING, array('PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT), $pos + 3, $keep);
            $this->MaintainVariable('EMS_WB' . $n . '_Command', sprintf($this->t('Wallbox %d: command'), $n), VARIABLETYPE_INTEGER, array('PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => $cmds), $pos + 4, $keep);
            if (!$keep) { continue; }
            foreach (array('ChargeMode', 'TargetKwh', 'TargetTime', 'Command') as $k) { $this->EnableAction('EMS_WB' . $n . '_' . $k); }
            $this->syncWallboxControlVariables($n);
        }
    }

    private function syncWallboxControlVariables(int $n): void
    {
        $set = function (string $ident, $v) { $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID); if ($id !== false && $id > 0 && GetValue($id) !== $v) { SetValue($id, $v); } };
        $set('EMS_WB' . $n . '_ChargeMode', (int)$this->liveInt('WB' . $n . '_ChargeMode'));
        $set('EMS_WB' . $n . '_TargetKwh', (float)$this->liveFloat('WB' . $n . '_TargetKwh'));
        $set('EMS_WB' . $n . '_TargetTime', (string)$this->liveStr('WB' . $n . '_TargetTime'));
        $set('EMS_WB' . $n . '_Command', 0);
    }

    /**
     * Formular-Panel "Wallboxen bedienen": je aktiver Wallbox Ladeart (wirkt beim Auswaehlen), Ladeziel und Frist (wirken mit
     * "Ziel übernehmen"), Befehle als Schaltflaechen und die Planzeile. Alles laeuft ueber dieselben Aktionen wie im WebFront
     * (IPS_RequestAction auf EMS_WBn_*), also eine Quelle; Ladeart/Ziel/Frist laden die Instanz dabei einmal neu.
     */
    private function wallboxControlPanel(): ?array
    {
        if (!$this->ReadPropertyBoolean('WB_Active')) { return null; }
        $count = min(2, max(1, $this->ReadPropertyInteger('WB_Count')));
        $modes = array();
        foreach (array(0 => self::fest('Price-controlled'), 1 => self::fest('Manual (fixed current)'), 2 => self::fest('PV only'), 3 => self::fest('PV share'), 4 => self::fest('Hybrid (minimum current + PV)'), 5 => self::fest('Off (charging blocked)')) as $v => $c) {
            $modes[] = array('caption' => $c, 'value' => $v);
        }
        $items = array();
        for ($n = 1; $n <= $count; $n++) {
            $plan = '';
            $pid = @IPS_GetObjectIDByIdent('EMS_WB' . $n . '_Plan', $this->InstanceID);
            if ($pid !== false && $pid > 0) { $plan = (string)GetValue($pid); }
            if ($plan === '') { $plan = $this->t('No plan calculated yet (appears with the next control cycle).'); }
            $nowUntil = $this->ReadAttributeInteger('WB' . $n . 'NowUntil');
            $items[] = array('type' => 'Label', 'caption' => '── Wallbox ' . $n . ' ─────────────────────────');
            $items[] = array('type' => 'Label', 'name' => 'FormWB' . $n . 'Plan', 'caption' => '📋 ' . $plan);
            if ($nowUntil > time()) {
                $items[] = array('type' => 'Label', 'caption' => sprintf($this->t('⚡ Charge now is running until %s.'), date('H:i', $nowUntil)), 'color' => EMS_COLOR_AUTO);
            }
            $items[] = array('type' => 'Select', 'name' => 'FormWB' . $n . 'ChargeMode', 'caption' => 'Charging mode (takes effect immediately)', 'options' => $modes,
                'value' => $this->liveInt('WB' . $n . '_ChargeMode'),
                'onChange' => 'IPS_RequestAction($id, "EMS_WB' . $n . '_ChargeMode", $FormWB' . $n . 'ChargeMode);');
            $items[] = array('type' => 'RowLayout', 'items' => array(
                array('type' => 'NumberSpinner', 'name' => 'FormWB' . $n . 'TargetKwh', 'caption' => 'Charging target (kWh, 0 = off)', 'minimum' => 0, 'maximum' => 200, 'digits' => 1, 'suffix' => ' kWh',
                    'value' => (float)$this->liveFloat('WB' . $n . '_TargetKwh')),
                array('type' => 'ValidationTextBox', 'name' => 'FormWB' . $n . 'TargetTime', 'caption' => 'Deadline (HH:MM)', 'validate' => '^([01]?\\d|2[0-3]):[0-5]\\d$',
                    'value' => (string)$this->liveStr('WB' . $n . '_TargetTime')),
                array('type' => 'Button', 'caption' => 'Apply target',
                    'onClick' => 'IPS_RequestAction($id, "EMS_WB' . $n . '_TargetTime", $FormWB' . $n . 'TargetTime); IPS_RequestAction($id, "EMS_WB' . $n . '_TargetKwh", $FormWB' . $n . 'TargetKwh); echo "' . addslashes(sprintf($this->t('Charging target for wallbox %d applied.'), $n)) . '";'),
            ));
            $items[] = array('type' => 'RowLayout', 'items' => array(
                array('type' => 'Button', 'caption' => '⚡ Charge now for 1 hour', 'onClick' => 'IPS_RequestAction($id, "EMS_WB' . $n . '_Command", 1); echo "' . addslashes(sprintf($this->t('Wallbox %d: charge now started for 1 hour.'), $n)) . '";'),
                array('type' => 'Button', 'caption' => '⏹ End override', 'onClick' => 'IPS_RequestAction($id, "EMS_WB' . $n . '_Command", 2); echo "' . addslashes(sprintf($this->t('Wallbox %d: override ended, the charging mode applies again.'), $n)) . '";'),
                array('type' => 'Button', 'caption' => '🗑 Delete charging target', 'onClick' => 'IPS_RequestAction($id, "EMS_WB' . $n . '_Command", 3); echo "' . addslashes(sprintf($this->t('Wallbox %d: charging target deleted.'), $n)) . '";'),
            ));
        }
        $items[] = array('type' => 'Label', 'caption' => 'The same controls are available in the WebFront as the variables "Wallbox n: charging mode / charging target / deadline / command / plan" below this instance (create links there for a category of your own). The settings in the section "🚗 Wallboxes" further down show the same values.');
        return array('type' => 'ExpansionPanel', 'name' => 'PanelWallboxControl', 'caption' => '🚗 Operate wallboxes', 'expanded' => true, 'items' => $items);
    }

    private function wallboxControlAction(int $n, string $what, $value): void
    {
        $prop = 'WB' . $n . '_' . $what;
        switch ($what) {
            case 'ChargeMode':
                $v = (int)$value;
                if ($v < 0 || $v > 5) { return; }
                $this->liveSet($prop, (string)$v);
                break;
            case 'TargetKwh':
                $this->liveSet($prop, (string)max(0.0, min(200.0, round((float)$value, 1))));
                break;
            case 'TargetTime':
                $t = trim((string)$value);
                if ($this->nextClockTs($t, time()) === null) {
                    $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Frist „%s“ ist keine gültige Uhrzeit (HH:MM), nicht übernommen', $n, $t));
                    $this->syncWallboxControlVariables($n);
                    return;
                }
                $this->liveSet($prop, $t);
                break;
            case 'Command':
                $c = (int)$value;
                if ($c === 1) {
                    $this->WriteAttributeInteger('WB' . $n . 'NowUntil', time() + 3600);
                    $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: manuell Jetzt laden für 1 Stunde (bis %s)', $n, date('H:i', time() + 3600)));
                } elseif ($c === 2) {
                    $this->WriteAttributeInteger('WB' . $n . 'NowUntil', 0);
                    $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Übersteuerung beendet', $n));
                } elseif ($c === 3) {
                    $this->WriteAttributeBoolean('WB' . $n . 'TargetCleared', true); // wirkt auch auf eine Wochen-Frist dieser Sitzung
                    $this->liveSet('WB' . $n . '_TargetKwh', '0');
                    $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Ladeziel gelöscht', $n));
                }
                $this->syncWallboxControlVariables($n);
                return;
            default:
                return;
        }
        $this->syncWallboxControlVariables($n);
    }

    /**
     * Wallbox $num (1 oder 2) freigeben/sperren. Bevorzugt den NRG-Stack-
     * ChargerHub-Kontrollkanal (nur Instanzen mit managedBy none/ems,
     * siehe getWritableChargers() — Situation A). Die alte Property
     * WB{n}_Instance dient nur noch als optionale Zuordnung, WELCHER
     * gefundene Lader "WB1" bzw. "WB2" ist; ist sie leer, wird einfach
     * der n-te automatisch gefundene, EMS-steuerbare Lader genommen.
     * Fallback ohne ChargerHub: alte direkte GO-eCharger-Steuerung.
     */
    private function controlWallbox($num, $enable)
    {
        $configuredInstance = $this->ReadPropertyInteger('WB' . $num . '_Instance');
        $chargers = $this->getWritableChargers();
        $entry = null;
        foreach ($chargers as $c) {
            if ($configuredInstance > 0 && ($c['instanceID'] ?? 0) === $configuredInstance) {
                $entry = $c;
                break;
            }
        }
        if ($entry === null && $configuredInstance <= 0) {
            $entry = $this->getControlEntry($num);
        }

        if ($entry !== null) {
            $this->controlWallboxViaChargerHub($num, $entry, $enable);
            return;
        }

        // Fallback: alte direkte GO-eCharger-Steuerung ueber manuelle Instanz-Property
        if ($configuredInstance <= 0 || !function_exists('GOeCharger_SetMode')) { return; }

        $lastSwitch = $this->ReadAttributeInteger('LastWB' . $num . 'Switch');
        $cooldown   = $this->ReadPropertyInteger('WB_Cooldown_Sec');
        if ((time() - $lastSwitch) < $cooldown) {
            $this->emsLog(EMS_LOG_VERBOSE, 'WB' . $num . ': Cooldown aktiv');
            return;
        }

        $isActive = ((int)$this->readVar('VAR_WB' . $num . '_Active', 0) == 1);

        if ($enable && !$isActive) {
            $maxPower = $this->ReadPropertyInteger('WB' . $num . '_Max_Power_W');
            GOeCharger_SetMode($configuredInstance, 2);
            GOeCharger_SetCurrentChargingWatt($configuredInstance, $maxPower);
            $this->WriteAttributeInteger('LastWB' . $num . 'Switch', time());
            $this->emsLog(EMS_LOG_BASIC, 'WB' . $num . ' freigegeben (' . $maxPower . ' W)');
        } elseif (!$enable && $isActive) {
            GOeCharger_SetMode($configuredInstance, 1);
            $this->WriteAttributeInteger('LastWB' . $num . 'Switch', time());
            $this->emsLog(EMS_LOG_BASIC, 'WB' . $num . ' gesperrt');
        }
    }

    private function controlWallboxViaChargerHub($num, $entry, $enable)
    {
        $instance = $entry['instanceID'] ?? 0;
        if ($instance <= 0) { return; }

        $lastSwitch = $this->ReadAttributeInteger('LastWB' . $num . 'Switch');
        $cooldown   = $this->ReadPropertyInteger('WB_Cooldown_Sec');
        if ((time() - $lastSwitch) < $cooldown) {
            $this->emsLog(EMS_LOG_VERBOSE, 'WB' . $num . ': Cooldown aktiv');
            return;
        }

        $chargeEnableID = $entry['chargeEnableID'] ?? 0;
        // Ohne lesbaren Freigabe-Status (kein chargeEnableID) gilt der zuletzt selbst gesendete Zustand, sonst liesse sich
        // die Wallbox nie sperren (isActive waere immer false).
        $isActive = ($chargeEnableID > 0 && IPS_VariableExists($chargeEnableID))
            ? (bool)GetValue($chargeEnableID)
            : $this->ReadAttributeBoolean('WB' . (int)$num . 'LastSentEnable');

        if ($enable && !$isActive) {
            $maxCurrentA = (int)($entry['maxCurrent'] ?? 16);
            if (isset($entry['stationMaxCurrentA']) && (int)$entry['stationMaxCurrentA'] >= 6) {
                $maxCurrentA = min($maxCurrentA, (int)$entry['stationMaxCurrentA']); // Hardware-Grenze laut OCPP (OCPPHub 1.7)
            }
            $configuredMaxW = $this->ReadPropertyInteger('WB' . $num . '_Max_Power_W');
            if ($configuredMaxW > 0) {
                $maxCurrentA = min($maxCurrentA, max(6, (int)round($configuredMaxW / 230)));
            }
            // RequestAction() ist Kernel-reserviert, nie als Prefix_RequestAction()
            // exponiert -- Einstiegspunkt ist IPS_RequestAction() (siehe ChargerHub-Befund
            // 25.07.2026).
            $this->ipsWrite($instance, 'ctl_curr_limit', $maxCurrentA);
            $this->ipsWrite($instance, 'ctl_enable', true);
            $this->WriteAttributeBoolean('WB' . (int)$num . 'LastSentEnable', true);
            $this->WriteAttributeBoolean('WB' . (int)$num . 'EmsLocked', false);
            $this->WriteAttributeInteger('LastWB' . $num . 'Switch', time());
            $this->emsLog(EMS_LOG_BASIC, 'WB' . $num . ' (' . $this->chargerSourceLabel($entry) . ' #' . $instance . ') freigegeben (' . $maxCurrentA . ' A)');
        } elseif (!$enable && $isActive) {
            $this->ipsWrite($instance, 'ctl_enable', false);
            $this->WriteAttributeBoolean('WB' . (int)$num . 'LastSentEnable', false);
            $this->WriteAttributeBoolean('WB' . (int)$num . 'EmsLocked', true);
            $this->WriteAttributeInteger('LastWB' . $num . 'Switch', time());
            $this->emsLog(EMS_LOG_BASIC, 'WB' . $num . ' (' . $this->chargerSourceLabel($entry) . ' #' . $instance . ') gesperrt');
        }
    }

    /**
     * Reine Rechnung der Ladeart. Eingaben: mode (1 bis 4), surplusW, shareP, manualA, minA, maxA, stepA, phases, volt, on (laedt/ist
     * freigegeben), sinceSwitchS, cooldownS, minChargeS. Ergebnis: enable, currentA, text. Die Wartezeiten gelten nur fuer das Umschalten
     * zwischen Ein und Aus; die Stromstaerke darf sich in jedem Zyklus aendern (die Rate begrenzt der Aufrufer).
     */
    private function wallboxChargePlan(array $in): array
    {
        $mode = (int)$in['mode'];
        $minA = max(1.0, (float)$in['minA']);
        $maxA = max($minA, (float)$in['maxA']);
        $step = ((float)$in['stepA'] > 0) ? (float)$in['stepA'] : 1.0;
        $ph   = ((int)$in['phases'] === 1) ? 1 : 3;
        $v    = ((float)$in['volt'] > 0) ? (float)$in['volt'] : 230.0;
        $on   = !empty($in['on']);
        $floor = function (float $a) use ($step) { return floor($a / $step + 1e-9) * $step; };
        $clamp = function (float $a) use ($minA, $maxA, $floor) { return max($minA, min($maxA, $floor($a))); };
        if ($mode === 5) {
            return array('enable' => false, 'currentA' => 0.0, 'text' => $this->t('Off: charging blocked'));
        }
        if ($mode === 1) {
            $a = ((int)$in['manualA'] > 0) ? $clamp((float)$in['manualA']) : $maxA;
            return array('enable' => true, 'currentA' => $a, 'text' => sprintf($this->t('Manual: fixed %s A'), $this->fmtA($a)));
        }
        $surplus = max(0.0, (float)$in['surplusW']);
        if ($mode === 3) { $surplus *= max(0, min(100, (int)$in['shareP'])) / 100.0; }
        $raw = $surplus / ($v * $ph);
        if ($mode === 4) {
            $a = $clamp($raw);
            return array('enable' => true, 'currentA' => $a, 'text' => sprintf($this->t('Hybrid: %s A (minimum current %s A plus PV surplus %.0f W)'), $this->fmtA($a), $this->fmtA($minA), (float)$in['surplusW']));
        }
        $label = ($mode === 3) ? sprintf($this->t('PV share %d %%'), (int)$in['shareP']) : $this->t('PV only');
        $enough = ($raw + 1e-9 >= $minA);
        $since = (int)$in['sinceSwitchS'];
        if ($enough) {
            if (!$on && $since < (int)$in['cooldownS']) {
                return array('enable' => false, 'currentA' => 0.0, 'text' => sprintf($this->t('%s: surplus %.0f W is sufficient, start in %d s (waiting time)'), $label, (float)$in['surplusW'], max(0, (int)$in['cooldownS'] - $since)));
            }
            $a = $clamp($raw);
            return array('enable' => true, 'currentA' => $a, 'text' => sprintf($this->t('%s: %s A at %.0f W surplus'), $label, $this->fmtA($a), (float)$in['surplusW']));
        }
        if ($on) {
            $hold = max((int)$in['cooldownS'], (int)$in['minChargeS']);
            if ($since < $hold) {
                return array('enable' => true, 'currentA' => $minA, 'text' => sprintf($this->t('%s: surplus %.0f W below the minimum current, stop in %d s (waiting time), %s A until then'), $label, (float)$in['surplusW'], $hold - $since, $this->fmtA($minA)));
            }
        }
        return array('enable' => false, 'currentA' => 0.0, 'text' => sprintf($this->t('%s: waiting for surplus (%.0f W, required from %.0f W)'), $label, (float)$in['surplusW'], $minA * $v * $ph));
    }

    /**
     * Reine Rechnung des Ladeziels. Eingaben: targetKwh, chargedKwh, now, deadline (Unix-Zeit), prices (Slotbeginn => EUR/kWh, fehlende
     * Viertelstunden gelten als teuer), powerKw (erwartete Ladeleistung), bufferPct (Zeitpuffer). Ergebnis: state (done, expired, late,
     * charge, wait), enable, text, slots (gewaehlte Slotbeginne). Bei zu wenig Zeit wird sofort geladen und gewarnt.
     */
    private function wallboxTargetPlan(array $in): array
    {
        $target = (float)$in['targetKwh'];
        $charged = max(0.0, (float)$in['chargedKwh']);
        $rest = $target - $charged;
        $now = (int)$in['now'];
        $dl = (int)$in['deadline'];
        $hhmm = date('H:i', $dl);
        $base = sprintf($this->t('Target %s kWh by %s: %s kWh charged'), $this->fmtKwh($target), $hhmm, $this->fmtKwh($charged));
        if ($rest <= 0.05) {
            return array('state' => 'done', 'enable' => false, 'text' => sprintf($this->t('%s, target reached'), $base), 'slots' => array());
        }
        if ($now >= $dl) {
            return array('state' => 'expired', 'enable' => false, 'text' => sprintf($this->t('%s, deadline passed, %s kWh missing'), $base, $this->fmtKwh($rest)), 'slots' => array());
        }
        $cur = $now - ($now % 900);
        $all = array();
        for ($t = $cur; $t < $dl; $t += 900) { $all[] = $t; }
        $kw = max(0.0, (float)$in['powerKw']);
        $need = ($kw > 0.0) ? (int)ceil($rest / $kw * (1.0 + max(0.0, (float)$in['bufferPct']) / 100.0) * 4.0 - 1e-9) : count($all);
        $need = max(1, $need);
        $prices = (array)$in['prices'];
        if ($need >= count($all)) {
            $possible = $kw * (count($all) - (($now - $cur) / 900.0)) / 4.0;
            $late = ($kw <= 0.0) || ($possible + $charged < $target);
            $txt = $late
                ? sprintf($this->t('%1$s, deadline cannot be met: only about %3$s kWh are possible by %2$s, charging immediately'), $base, $hhmm, $this->fmtKwh(min($target, $charged + $possible)))
                : sprintf($this->t('%s, %s kWh to go, tight: charging straight through now'), $base, $this->fmtKwh($rest));
            return array('state' => $late ? 'late' : 'charge', 'enable' => true, 'text' => $txt, 'slots' => $all);
        }
        $ranked = $all;
        usort($ranked, function ($a, $b) use ($prices) {
            $pa = isset($prices[$a]) && $prices[$a] !== null ? (float)$prices[$a] : INF;
            $pb = isset($prices[$b]) && $prices[$b] !== null ? (float)$prices[$b] : INF;
            return ($pa === $pb) ? ($a <=> $b) : ($pa <=> $pb);
        });
        $pick = array_slice($ranked, 0, $need);
        sort($pick);
        $known = array_filter($pick, function ($t) use ($prices) { return isset($prices[$t]) && $prices[$t] !== null; });
        $avg = count($known) ? array_sum(array_map(function ($t) use ($prices) { return (float)$prices[$t]; }, $known)) / count($known) : null;
        $on = in_array($cur, $pick, true);
        $txt = sprintf($this->t('%s, %s kWh to go, %d quarter-hours planned from %s%s: %s'), $base, $this->fmtKwh($rest), $need, date('H:i', $pick[0]),
            $avg !== null ? sprintf(', Ø %s ct', $this->num($avg * 100, 1)) : $this->t(' (without prices)'), $on ? $this->t('charging now') : $this->t('waiting for the next window'));
        return array('state' => $on ? 'charge' : 'wait', 'enable' => $on, 'text' => $txt, 'slots' => $pick);
    }

    private function wallboxTargetConfigured(int $n): bool
    {
        return $this->liveFloat('WB' . $n . '_TargetKwh') > 0 || $this->weekTargetsActive($n);
    }

    /**
     * Frist und Ziel beim Anstecken: Wochen-Frist (naechster Tag ab jetzt mit eingetragener Uhrzeit, heute nur wenn noch nicht vorbei;
     * Ziel des Tages oder, bei 0, das einheitliche Ladeziel), sonst wie bisher TargetTime/TargetKwh. Wanduhr (mktime), Sommerzeit-fest.
     * array('deadline' => int|null, 'kwh' => float, 'source' => 'week'|'single', 'note' => string)
     */
    private function wallboxEffectiveTarget(int $n, int $now): array
    {
        $single = (float)$this->liveFloat('WB' . $n . '_TargetKwh');
        if ($this->weekTargetsActive($n)) {
            $rows = $this->weekTargets($n);
            for ($off = 0; $off <= 7; $off++) {
                $ts  = mktime(0, 0, 0, (int)date('n', $now), (int)date('j', $now) + $off, (int)date('Y', $now));
                $row = $rows[(int)date('N', $ts) - 1];
                if ($row['Time'] === '' || !preg_match('/^(\d{1,2}):(\d{2})$/', $row['Time'], $m) || (int)$m[1] > 23 || (int)$m[2] > 59) { continue; }
                $dl = mktime((int)$m[1], (int)$m[2], 0, (int)date('n', $ts), (int)date('j', $ts), (int)date('Y', $ts));
                if ($dl <= $now) { continue; }
                $kwh = ($row['Kwh'] > 0) ? (float)$row['Kwh'] : $single;
                if ($kwh <= 0.0) {
                    return array('deadline' => null, 'kwh' => 0.0, 'source' => 'week', 'note' => sprintf($this->t('Charging target off: deadline %s %s without a charging target (neither for the day nor common).'), $row['Label'], $row['Time']));
                }
                return array('deadline' => $dl, 'kwh' => $kwh, 'source' => 'week', 'note' => '');
            }
            return array('deadline' => null, 'kwh' => 0.0, 'source' => 'week', 'note' => $this->t('Charging target off: no valid weekly deadline (HH:MM).'));
        }
        if ($single <= 0.0) { return array('deadline' => null, 'kwh' => 0.0, 'source' => 'single', 'note' => ''); }
        $dl = $this->nextClockTs($this->liveStr('WB' . $n . '_TargetTime'), $now);
        return array('deadline' => $dl, 'kwh' => $single, 'source' => 'single', 'note' => $dl === null ? $this->t('Charging target off: the deadline is not a valid time (HH:MM).') : '');
    }

    /**
     * In dieser Sitzung geladene kWh, unabhaengig davon, wann die Wallbox ihren Sitzungszaehler zuruecksetzt (bei keinem Geraet gemessen,
     * ChargerHub 23.10.2026): geladen = Vorrat + (Zaehler - Bezugspunkt beim Anstecken). Springt der Zaehler zurueck (Geraet setzt erst beim
     * ersten Stromfluss oder nach einer Pause zurueck), wird das bis dahin Geladene in den Vorrat uebernommen und ab 0 weitergezaehlt;
     * nie eine negative Menge.
     */
    private function wallboxSessionChargedKwh(int $n, float $value): float
    {
        $base = $this->ReadAttributeFloat('WB' . $n . 'TargetBase');
        $carry = $this->ReadAttributeFloat('WB' . $n . 'TargetCarry');
        $last = $this->ReadAttributeFloat('WB' . $n . 'TargetLast');
        if ($value < $last - 0.05) {
            $carry += max(0.0, $last - $base);
            $base = 0.0;
            $this->WriteAttributeFloat('WB' . $n . 'TargetCarry', $carry);
            $this->WriteAttributeFloat('WB' . $n . 'TargetBase', 0.0);
            $this->emsLog(EMS_LOG_VERBOSE, sprintf('WB%d: Sitzungszaehler sprang von %s auf %s kWh zurueck, zaehle ab 0 weiter (bisher %s kWh)', $n, $this->fmtKwh($last), $this->fmtKwh($value), $this->fmtKwh($carry)));
        }
        $this->WriteAttributeFloat('WB' . $n . 'TargetLast', $value);
        return max(0.0, $carry + $value - $base);
    }

    /**
     * Ladeziel-Plan fuer Wallbox $n: null, wenn kein Ziel gilt (aus, kein Fahrzeug, erreicht, Frist vorbei, Wallbox meldet keine
     * Sitzungsenergie). Sitzung = Fahrzeug angesteckt; die Frist wird beim Anstecken auf das naechste Auftreten der Uhrzeit gelegt.
     */
    private function wallboxTargetPlanFor(int $n, array $entry, float $maxA, int $phases): ?array
    {
        $this->WriteAttributeString('WB' . $n . 'TargetNote', '');
        $plugId = (int)($entry['plugStateID'] ?? 0);
        $plugged = ($plugId > 0 && IPS_VariableExists($plugId)) ? (bool)GetValue($plugId) : false;
        if (!$plugged) {
            if ($this->ReadAttributeInteger('WB' . $n . 'TargetSession') !== 0) {
                $this->WriteAttributeInteger('WB' . $n . 'TargetSession', 0);
                $this->WriteAttributeInteger('WB' . $n . 'TargetDeadline', 0);
                $this->WriteAttributeBoolean('WB' . $n . 'TargetDone', false);
                $this->WriteAttributeFloat('WB' . $n . 'TargetKwhSession', 0.0);
            }
            if ($this->ReadAttributeBoolean('WB' . $n . 'TargetCleared')) { $this->WriteAttributeBoolean('WB' . $n . 'TargetCleared', false); }
            return null;
        }
        if ($this->ReadAttributeBoolean('WB' . $n . 'TargetCleared')) {
            $this->WriteAttributeString('WB' . $n . 'TargetNote', $this->t('Charging target deleted for this charging session.'));
            return null;
        }
        if (!$this->wallboxTargetConfigured($n)) { return null; }
        $eId = (int)($entry['sessionEnergyID'] ?? 0);
        if ($eId <= 0 || !IPS_VariableExists($eId)) {
            $this->WriteAttributeString('WB' . $n . 'TargetNote', $this->t('Charging target not supported: the wallbox does not report the energy of the charging session.'));
            return null;
        }
        $now = time();
        if ($this->ReadAttributeInteger('WB' . $n . 'TargetSession') === 0) {
            $eff = $this->wallboxEffectiveTarget($n, $now);
            if ($eff['deadline'] === null) {
                $this->WriteAttributeString('WB' . $n . 'TargetNote', $eff['note']);
                return null;
            }
            $dl = $eff['deadline'];
            $this->WriteAttributeFloat('WB' . $n . 'TargetKwhSession', $eff['source'] === 'week' ? $eff['kwh'] : 0.0);
            $this->WriteAttributeInteger('WB' . $n . 'TargetSession', $now);
            $this->WriteAttributeInteger('WB' . $n . 'TargetDeadline', $dl);
            $this->WriteAttributeBoolean('WB' . $n . 'TargetDone', false);
            $v0 = (float)GetValue($eId);
            $this->WriteAttributeFloat('WB' . $n . 'TargetBase', $v0);
            $this->WriteAttributeFloat('WB' . $n . 'TargetCarry', 0.0);
            $this->WriteAttributeFloat('WB' . $n . 'TargetLast', $v0);
            $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Ladeziel %s kWh bis %s%s', $n, $this->fmtKwh($eff['kwh']), date('d.m. H:i', $dl), $eff['source'] === 'week' ? ' (Wochen-Frist)' : ''));
        }
        // Wochen-Frist: Ziel beim Anstecken festgelegt; sonst wie bisher das aktuelle Ladeziel (0 = aus)
        $wk = $this->ReadAttributeFloat('WB' . $n . 'TargetKwhSession');
        $target = ($wk > 0.0) ? $wk : (float)$this->liveFloat('WB' . $n . '_TargetKwh');
        if ($target <= 0.0) { return null; }
        if ($this->ReadAttributeBoolean('WB' . $n . 'TargetDone')) {
            $this->WriteAttributeString('WB' . $n . 'TargetNote', sprintf($this->t('Charging target %s kWh reached.'), $this->fmtKwh($target)));
            return null;
        }
        $kw = $maxA * 230.0 * $phases / 1000.0;
        $cfgW = $this->ReadPropertyInteger('WB' . $n . '_Max_Power_W');
        if ($cfgW > 0) { $kw = min($kw, $cfgW / 1000.0); }
        $plan = $this->wallboxTargetPlan(array('targetKwh' => $target, 'chargedKwh' => $this->wallboxSessionChargedKwh($n, (float)GetValue($eId)), 'now' => $now,
            'deadline' => $this->ReadAttributeInteger('WB' . $n . 'TargetDeadline'), 'prices' => $this->targetPriceMap(), 'powerKw' => $kw, 'bufferPct' => 20));
        if ($plan['state'] === 'done') {
            $this->WriteAttributeBoolean('WB' . $n . 'TargetDone', true);
            $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: %s', $n, $plan['text']));
            $this->WriteAttributeString('WB' . $n . 'TargetNote', $plan['text'] . '.');
            return null;
        }
        if ($plan['state'] === 'expired') {
            $this->WriteAttributeString('WB' . $n . 'TargetNote', '⚠️ ' . $plan['text'] . '.');
            return null;
        }
        if ($plan['state'] === 'late') { $plan['text'] = '⚠️ ' . $plan['text']; }
        $plan['currentA'] = $maxA;
        return $plan;
    }

    private function wallboxWriteEntry(int $num): ?array
    {
        $configured = $this->ReadPropertyInteger('WB' . $num . '_Instance');
        foreach ($this->getWritableChargers() as $c) {
            if ($configured > 0 && (int)($c['instanceID'] ?? 0) === $configured) { return $c; }
        }
        return ($configured <= 0) ? $this->getControlEntry($num) : null;
    }

    private function wallboxChargePlanFor(int $n, array $s): ?array
    {
        $entry = $this->wallboxWriteEntry($n);
        if ($entry === null || (int)($entry['instanceID'] ?? 0) <= 0 || (int)($entry['currentLimitID'] ?? 0) <= 0) {
            $this->WriteAttributeString('WB' . $n . 'ChargeText', $this->t('Charging mode not possible: the wallbox reports no writable current setpoint, price control applies'));
            return null;
        }
        $phSet = $this->ReadPropertyInteger('WB' . $n . '_PhaseSetting');
        $phases = ($phSet === 1 || $phSet === 3) ? $phSet : ((int)($entry['phases'] ?? 0) === 1 ? 1 : 3);
        $minA = (float)($entry['minCurrent'] ?? 6);
        $maxA = (float)($entry['maxCurrent'] ?? 16);
        if (isset($entry['stationMaxCurrentA']) && (int)$entry['stationMaxCurrentA'] >= 6) { $maxA = min($maxA, (float)$entry['stationMaxCurrentA']); }
        $cfgW = $this->ReadPropertyInteger('WB' . $n . '_Max_Power_W');
        if ($cfgW > 0) { $maxA = min($maxA, max($minA, floor($cfgW / (230.0 * $phases)))); }
        $nowUntil = $this->ReadAttributeInteger('WB' . $n . 'NowUntil');
        if ($nowUntil > time()) {
            $na = $this->ReadPropertyInteger('WB' . $n . '_NowCurrentA');
            $a = ($na > 0) ? max($minA, min($maxA, (float)$na)) : $maxA;
            $idN = (int)($entry['chargeEnableID'] ?? 0);
            $txt = sprintf($this->t('Manual: charge now with %s A, %d min left (until %s)'), $this->fmtA($a), (int)ceil(($nowUntil - time()) / 60), date('H:i', $nowUntil));
            $this->WriteAttributeString('WB' . $n . 'ChargeText', $txt);
            return array('enable' => true, 'currentA' => $a, 'text' => $txt, 'entry' => $entry,
                'on' => ($idN > 0 && IPS_VariableExists($idN)) ? (bool)GetValue($idN) : $this->ReadAttributeBoolean('WB' . $n . 'LastSentEnable'));
        }
        if ($nowUntil > 0) { $this->WriteAttributeInteger('WB' . $n . 'NowUntil', 0); }
        $target = $this->wallboxTargetPlanFor($n, $entry, $maxA, $phases);
        if ($target !== null) {
            $target['entry'] = $entry;
            $idT = (int)($entry['chargeEnableID'] ?? 0);
            $target['on'] = ($idT > 0 && IPS_VariableExists($idT)) ? (bool)GetValue($idT) : $this->ReadAttributeBoolean('WB' . $n . 'LastSentEnable');
            $this->WriteAttributeString('WB' . $n . 'ChargeText', $target['text']);
            return $target;
        }
        if ($this->liveInt('WB' . $n . '_ChargeMode') <= 0) {
            $this->WriteAttributeString('WB' . $n . 'ChargeText', $this->ReadAttributeString('WB' . $n . 'TargetNote'));
            return null;
        }
        $other = ($n === 1) ? (float)$s['wb2_pow_kw'] : (float)$s['wb1_pow_kw'];
        $surplus = (float)$s['pv_total_w'] - (float)$s['house_pow_w'] - $other * 1000.0;
        $idEn = (int)($entry['chargeEnableID'] ?? 0);
        $on = ($idEn > 0 && IPS_VariableExists($idEn)) ? (bool)GetValue($idEn) : $this->ReadAttributeBoolean('WB' . $n . 'LastSentEnable');
        $plan = $this->wallboxChargePlan(array(
            'mode' => $this->liveInt('WB' . $n . '_ChargeMode'), 'surplusW' => $surplus,
            'shareP' => $this->ReadPropertyInteger('WB' . $n . '_PvSharePct'), 'manualA' => $this->ReadPropertyInteger('WB' . $n . '_ManualCurrentA'),
            'minA' => $minA, 'maxA' => $maxA, 'stepA' => (float)($entry['currentStepA'] ?? 1.0), 'phases' => $phases, 'volt' => 230.0, 'on' => $on,
            'sinceSwitchS' => time() - $this->ReadAttributeInteger('LastWB' . $n . 'Switch'),
            'cooldownS' => $this->ReadPropertyInteger('WB_Cooldown_Sec'), 'minChargeS' => $this->ReadPropertyInteger('WB_Min_Charge_Min') * 60));
        $plan['entry'] = $entry;
        $plan['on'] = $on;
        $note = $this->ReadAttributeString('WB' . $n . 'TargetNote');
        $this->WriteAttributeString('WB' . $n . 'ChargeText', ($note !== '' ? $note . ' ' : '') . $plan['text']);
        return $plan;
    }

    /**
     * Ladeart-Plan an die Wallbox schreiben: Phasenwahl (nur Wechsel, mindestens Pause laut Vertrag), Stromstaerke (bei Aenderung, hoechstens alle
     * 30 s), Freigabe. Start immer erst Strom, dann Freigabe (ChargerHub-Vertrag 1.8); Pausieren nur ueber die Freigabe, nie ueber 0 A.
     */
    private function applyWallboxChargePlan(int $n, array $plan): void
    {
        $entry = $plan['entry'];
        $iid = (int)$entry['instanceID'];
        $now = time();
        $phSet = $this->ReadPropertyInteger('WB' . $n . '_PhaseSetting');
        $phId = (int)($entry['phaseModeID'] ?? 0);
        if (($phSet === 1 || $phSet === 3) && $phId > 0 && IPS_VariableExists($phId)) {
            $vals = (array)($entry['phaseModeValues'] ?? array());
            $want = $vals[$phSet === 1 ? 'one' : 'three'] ?? null;
            $pause = max(120, (int)($entry['phaseSwitchPauseSec'] ?? 0));
            if ($want !== null && (int)GetValue($phId) !== (int)$want && ($now - $this->ReadAttributeInteger('WB' . $n . 'PhaseSentAt')) >= $pause) {
                $this->ipsWrite($iid, 'ctl_phase_mode', (int)$want);
                $this->WriteAttributeInteger('WB' . $n . 'PhaseSentAt', $now);
                $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d: Phasenwahl auf %s gesetzt', $n, $phSet === 1 ? 'einphasig' : 'dreiphasig'));
            }
        }
        $a = (float)$plan['currentA'];
        $sentA = $this->ReadAttributeInteger('WB' . $n . 'CurrentSentA');
        $aKey = (int)round($a * 10);
        $on = !empty($plan['on']);
        if ($plan['enable']) {
            $currentChanged = ($aKey !== $sentA) && (($now - $this->ReadAttributeInteger('WB' . $n . 'CurrentSentAt')) >= 30 || $sentA < 0 || !$on);
            if ($currentChanged) {
                $this->ipsWrite($iid, 'ctl_curr_limit', (abs($a - round($a)) < 0.05) ? (int)round($a) : $a);
                $this->WriteAttributeInteger('WB' . $n . 'CurrentSentA', $aKey);
                $this->WriteAttributeInteger('WB' . $n . 'CurrentSentAt', $now);
            }
            if (!$on) {
                $this->ipsWrite($iid, 'ctl_enable', true);
                $this->WriteAttributeBoolean('WB' . $n . 'LastSentEnable', true);
                $this->WriteAttributeBoolean('WB' . $n . 'EmsLocked', false);
                $this->WriteAttributeInteger('LastWB' . $n . 'Switch', $now);
                $this->emsLog(EMS_LOG_BASIC, sprintf('WB%d (%s #%d) freigegeben (%s A) | %s', $n, $this->chargerSourceLabel($entry), $iid, $this->fmtA($a), $plan['text']));
            }
            return;
        }
    }

    private function setAllWallboxes($enable)
    {
        if (!$this->ReadPropertyBoolean('WB_Active')) { return; }
        $count = $this->ReadPropertyInteger('WB_Count');
        for ($i = 1; $i <= min($count, 2); $i++) {
            $instance = $this->ReadPropertyInteger('WB' . $i . '_Instance');
            if ($instance > 0) {
                GOeCharger_SetMode($instance, $enable ? 2 : 1);
            }
        }
    }
}
