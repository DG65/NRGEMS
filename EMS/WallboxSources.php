<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 4, 17.11.2026): Wallbox-Quellen: ChargerHub, OCPPHub und EEBUS finden, Zuordnung, Messwerte lesen, Schreibrecht.
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSWallboxSources;" in der Klasse EMS.
 */
trait EMSWallboxSources
{
    /**
     * Ladeleistung von Wallbox $n (1/2) in kW. Vorrang hat eine manuell
     * verknuepfte Variable (VAR_WBn_Power, Einheit kW wie bisher), sonst
     * der n-te per Discovery gefundene ChargerHub-Ladepunkt (Vertrag
     * `powerID`, Einheit W). Befund 12.09.2026: bei Dietmar waren die
     * Properties leer, EMS rechnete deshalb dauerhaft mit 0 kW -- Grid
     * Rewards bestellte 0 W Stromeinkauf, Autoladung lief als Hauslast.
     */
    private function readChargerPowerKw(int $n): float
    {
        if ($this->ReadPropertyInteger('VAR_WB' . $n . '_Power') > 0) {
            return (float)$this->readVar('VAR_WB' . $n . '_Power', 0);
        }
        $chg = $this->getChargerEntry($n);
        $id = (int)($chg['powerID'] ?? 0);
        if ($id <= 0 || !$this->isFreshVar($id, 'Wallbox ' . $n . ' Ladeleistung')) { return 0.0; }
        // Vertrag 1.3: letzte erfolgreiche Geraeteantwort (0 = noch nie).
        // Fehlt das Feld (aeltere ChargerHub-Version), gilt nur isFreshVar().
        // Deckt den Fall ab, den der Variablen-Zeitstempel nicht zeigt: ein
        // Modul, das bei jedem Zyklus dieselbe 0 neu schreibt.
        if (array_key_exists('lastSeenAt', $chg) && (time() - (int)$chg['lastSeenAt']) > 600) {
            $this->emsLog(EMS_LOG_VERBOSE, sprintf('Wallbox %d: letzte Geräteantwort %s -- Leistung unbekannt', $n,
                (int)$chg['lastSeenAt'] > 0 ? date('d.m. H:i', (int)$chg['lastSeenAt']) : 'nie'));
            return 0.0;
        }
        return max(0.0, (float)GetValue($id) / 1000.0);
    }

    private function readChargerCable(int $n): int
    {
        if ($this->ReadPropertyInteger('VAR_WB' . $n . '_Cable') > 0) {
            return (int)$this->readVar('VAR_WB' . $n . '_Cable', 0);
        }
        $id = (int)($this->getChargerEntry($n)['plugStateID'] ?? 0);
        return ($id > 0 && IPS_VariableExists($id)) ? (int)(bool)GetValue($id) : 0;
    }

    private function readChargerStatus(int $n): int
    {
        if ($this->ReadPropertyInteger('VAR_WB' . $n . '_Status') > 0) {
            return (int)$this->readVar('VAR_WB' . $n . '_Status', 0);
        }
        $id = (int)($this->getChargerEntry($n)['stateID'] ?? 0);
        return ($id > 0 && IPS_VariableExists($id)) ? (int)GetValue($id) : 0;
    }

    /**
     * Fehlercode der Wallbox (0 = kein Fehler, alles andere sperrt die Freigabe) -- manuelle Variable vor ChargerHub-`errorCodeID`
     * (Vertrag 1.11, nur DaheimLader/Fox ESS/CHARX). Der Ladestatus wird herstelleruebergreifend NIE als Fehler gedeutet.
     */
    private function readChargerError(int $n): int
    {
        if ($this->ReadPropertyInteger('VAR_WB' . $n . '_Error') > 0) {
            return (int)$this->readVar('VAR_WB' . $n . '_Error', 0);
        }
        $id = (int)($this->getChargerEntry($n)['errorCodeID'] ?? 0);
        return ($id > 0 && IPS_VariableExists($id)) ? (int)GetValue($id) : 0;
    }

    /**
     * Statuszeilen „woher kommt der Wert“ fuer Wallbox $n (Verbund-Regel: automatische Verbindungen muessen sichtbar sein): Instanz,
     * Leistung, Angesteckt, Status, Fehler. Je Zeile 🔗 automatisch ueber ChargerHub/OCPPHub, ✏️ eigene Variable, ⚠️ nicht verbunden,
     * jeweils mit aktuellem Wert. $ov: Auswahl aus dem offenen Formular (Instance, Power, Cable, Status, Error), null = gespeichert.
     */
    private function wallboxLinkLines(int $n, ?array $ov = null): array
    {
        $get = function (string $k, string $prop) use ($ov) { return ($ov !== null && array_key_exists($k, $ov)) ? (int)$ov[$k] : $this->ReadPropertyInteger($prop); };
        $entry = $this->getChargerEntry($n);
        $iid = (int)($entry['instanceID'] ?? 0);
        $src = $this->chargerSourceLabel($entry);
        $hub = ($iid > 0) ? sprintf('%s «%s» (#%d)', $src, IPS_ObjectExists($iid) ? IPS_GetName($iid) : '?', $iid) : '';
        $out = array();
        $inst = $get('Instance', 'WB' . $n . '_Instance');
        if ($inst > 0) {
            $out['Instanz'] = array('caption' => sprintf($this->t('✏️ Wallbox %d: own assignment instance #%d (%s)'), $n, $inst, IPS_ObjectExists($inst) ? IPS_GetName($inst) : $this->t('does not exist')), 'color' => -1);
        } elseif ($hub !== '') {
            $out['Instanz'] = array('caption' => sprintf($this->t('🔗 Wallbox %d: connected automatically via %s'), $n, $hub), 'color' => EMS_COLOR_AUTO);
        } else {
            $out['Instanz'] = array('caption' => sprintf($this->t('⚠️ Wallbox %d: no wallbox found. As soon as ChargerHub or OCPPHub reports one, EMS connects it by itself.'), $n), 'color' => -1);
        }
        $fields = array(
            // [0] = interner Schluessel (Formularfeld WBnLink<Schluessel>, nie uebersetzen), [4] = angezeigte Bezeichnung
            'Power'  => array('Leistung',   'VAR_WB' . $n . '_Power',  'powerID',     $this->t(', EMS calculates without charging power'), $this->t('Power')),
            'Cable'  => array('Angesteckt', 'VAR_WB' . $n . '_Cable',  'plugStateID', $this->t(', EMS does not know whether a vehicle is plugged in'), $this->t('Plugged in')),
            'Status' => array('Status',     'VAR_WB' . $n . '_Status', 'stateID',     $this->t(' (display only)'), $this->t('Status')),
            'Error'  => array('Fehler',     'VAR_WB' . $n . '_Error',  'errorCodeID', $this->t(', enabling is not checked for errors'), $this->t('Error')),
        );
        foreach ($fields as $k => $f) {
            list($key, $prop, $field, $missing, $label) = $f;
            $man = $get($k, $prop);
            if ($man > 0) {
                $out[$key] = IPS_VariableExists($man)
                    ? array('caption' => sprintf($this->t('✏️ %s: own variable #%d (%s), currently %s'), $label, $man, IPS_GetName($man), $this->wallboxLinkValue($k, GetValue($man), true)), 'color' => -1)
                    : array('caption' => sprintf($this->t('⚠️ %s: the own variable #%d does not exist'), $label, $man), 'color' => -1);
                continue;
            }
            $vid = (int)($entry[$field] ?? 0);
            if ($vid > 0 && IPS_VariableExists($vid)) {
                $out[$key] = array('caption' => sprintf($this->t('🔗 %s: automatically from %s, currently %s'), $label, $hub, $this->wallboxLinkValue($k, GetValue($vid), false)), 'color' => EMS_COLOR_AUTO);
            } else {
                $out[$key] = array('caption' => sprintf($this->t('⚠️ %s: not connected%s'), $label, ($hub !== '' ? sprintf($this->t(' (%s does not report this value for the wallbox)'), $src) : '') . $missing), 'color' => -1);
            }
        }
        return $out;
    }

    private function wallboxLinkValue(string $k, $v, bool $manual): string
    {
        switch ($k) {
            case 'Power':  $w = $manual ? (float)$v * 1000.0 : (float)$v; return $this->num($w, 0, true) . ' W';
            case 'Cable':  return ((int)(bool)$v === 1) ? $this->t('plugged in') : $this->t('not plugged in');
            case 'Status': return sprintf($this->t('value %s (meaning depends on the manufacturer)'), (is_bool($v) ? (int)$v : (string)$v));
            case 'Error':  return ((int)$v === 0) ? $this->t('no error') : sprintf($this->t('error %d, enabling blocked'), (int)$v);
        }
        return (string)$v;
    }

    private function getChargerEntry(int $n): array
    {
        $list = $this->getChargerList();
        return (array)($list[$n - 1] ?? array());
    }

    /**
     * OCPPHub-Ladepunkte (OHUB_GetFunctions am Splitter, feldgleich CHUB 1.3).
     * Anders als discoverContract(): die instanceID des Eintrags ist der
     * Ladepunkt und bleibt erhalten -- Schaltbefehle (ctl_enable/
     * ctl_curr_limit) gehen an ihn, nicht an den Splitter.
     */
    private function discoverOcppHub(): array
    {
        $results = array();
        if (!function_exists('OHUB_GetFunctions')) { return $results; }
        foreach (IPS_GetInstanceListByModuleID(GUID_OCPPHUB_SPLITTER) as $sid) {
            // Vom Nutzer ausgeschalteter Splitter (IS_INACTIVE 104) liefert zwar
            // noch Eintraege, misst aber nicht mehr -- nicht als Quelle fuehren
            // (Dietmar 13.09.2026: Splitter aus, weil ein Ladepunkt nicht einzeln geht).
            if ((int)(@IPS_GetInstance($sid)['InstanceStatus'] ?? 0) === 104) { continue; }
            try {
                $data = OHUB_GetFunctions($sid);
            } catch (Throwable $e) {
                $this->emsLog(EMS_LOG_BASIC, 'OHUB_GetFunctions #' . $sid . ' fehlgeschlagen: ' . $e->getMessage());
                continue;
            }
            if (is_string($data)) { $data = json_decode($data, true); }
            if (!is_array($data)) { continue; }
            foreach ($data as $entry) {
                if (!is_array($entry)) { continue; }
                // einzeln deaktivierter Ladepunkt (Status 104) zaehlt nicht. NICHT
                // `active` auswerten: bei OCPPHub heisst active=false "extern geregelt"
                // (managedBy other) -- eine fremdgesteuerte Wallbox wird weiter gemessen.
                $lp = (int)($entry['instanceID'] ?? 0);
                if ($lp > 0 && (int)(@IPS_GetInstance($lp)['InstanceStatus'] ?? 0) === 104) { continue; }
                $entry['instanceID'] = (int)($entry['instanceID'] ?? $sid);
                $entry['splitterID'] = $sid;
                $entry['source']     = 'ocpphub';
                $results[] = $entry;
            }
        }
        return $results;
    }

    /**
     * Welche Wallbox-Quelle gilt. Automatisch: nur eine gefunden -> diese;
     * beide gefunden -> vermutlich dieselben Geraete doppelt (Dietmars Anlage:
     * go-e ueber ChargerHub und OCPP). Dann nicht doppelt zaehlen, bisheriges
     * Verhalten (ChargerHub) behalten und warnen (Regel 9f), bis der Nutzer waehlt.
     */
    private function chargerSourceStatus(): array
    {
        $p = $this->GetPartners();
        $c = (array)($p['chargerhub'] ?? array());
        $o = (array)($p['ocpphub'] ?? array());
        switch ((int)$this->ReadPropertyInteger('WB_Quelle')) {
            case 1: return array('chargerhub' => true,  'ocpphub' => false, 'warn' => false);
            case 2: return array('chargerhub' => false, 'ocpphub' => true,  'warn' => false);
            case 3: return array('chargerhub' => true,  'ocpphub' => true,  'warn' => false);
        }
        // duplicateOf (CHUB/OHUB 1.4, Nutzerangabe am Quellmodul): markierte
        // Eintraege zaehlen nie. Ist irgendwo markiert, hat der Nutzer die
        // Zuordnung geprueft -- die uebrigen sind verschiedene Geraete.
        $hasC = !empty($this->withoutDuplicates($c));
        $hasO = !empty($this->withoutDuplicates($o));
        $reviewed = count($this->withoutDuplicates(array_merge($c, $o))) < count($c) + count($o);
        if ($hasC && $hasO && !$reviewed) { return array('chargerhub' => true, 'ocpphub' => false, 'warn' => true); }
        return array('chargerhub' => $hasC, 'ocpphub' => $hasO, 'warn' => false);
    }

    private function getChargerList(): array
    {
        $p   = $this->GetPartners();
        $use = $this->chargerSourceStatus();
        $list = array();
        if ($use['chargerhub']) {
            foreach ($this->withoutDuplicates((array)($p['chargerhub'] ?? array())) as $c) { $c['source'] = 'chargerhub'; $list[] = $c; }
        }
        if ($use['ocpphub']) {
            foreach ($this->withoutDuplicates((array)($p['ocpphub'] ?? array())) as $c) { $c['source'] = 'ocpphub'; $list[] = $c; }
        }
        // EEBUS-Wallboxen (ChargerEEBUSHub) zaehlen additiv, unabhaengig von WB_Quelle (deren gespeicherte Werte 0-3 betreffen
        // nur ChargerHub/OCPPHub und bleiben unveraendert): fuer ein ueber EEBUS angebundenes Geraet gibt es meist keine andere
        // Quelle. Doppelt nur, wenn dasselbe Geraet auch ueber ChargerHub/OCPPHub kommt -- dann gilt duplicateOf (Nutzerangabe),
        // und ohne Markierung bei gleicher Seriennummer zaehlt der bestehende Eintrag, die EEBUS-Anbindung nicht (Warnung im Log).
        foreach ($this->withoutDuplicates((array)($p['chargereebushub'] ?? array())) as $c) {
            $c['source'] = 'chargereebushub';
            if ($this->eebusChargerSerialTwin($c, $list) !== null) { continue; }
            $list[] = $c;
        }
        // 0.110.0 Fremdanbindung: Wallboxen fremder Module zaehlen additiv; dasselbe Geraet (gleiche Seriennummer) ueber eine
        // eigene Anbindung hat Vorrang.
        foreach ((array)($p['extcharger'] ?? array()) as $c) {
            if (!empty($c['duplicateOf'])) { continue; }
            if ($this->eebusChargerSerialTwin($c, $list) !== null) { continue; }
            $list[] = $c;
        }
        return $list;
    }

    /**
     * Darf EMS ueber diesen Eintrag schalten? managedBy none/ems wie bisher. EEBUS-Wallboxen (ChargerEEBUSHub) werden in dieser
     * Version nur gemessen: EMS schaltet ueber die Idents ctl_enable/ctl_curr_limit (ChargerHub/OCPPHub), die dieses Modul nicht hat.
     */
    private function chargerEmsWritable(array $e): bool
    {
        if (($e['source'] ?? '') === 'chargereebushub') { return false; }
        if (!$this->extWritable($e)) { return false; } // Fremdmodul: Freigabe je Instanz + Rueckfall (failsafeSec) + frisch
        return in_array($e['managedBy'] ?? 'none', array('none', 'ems'), true);
    }

    /**
     * Schon gezaehlter ChargerHub-/OCPPHub-Eintrag mit derselben Seriennummer wie die EEBUS-Wallbox $c (ohne duplicateOf-Markierung
     * vermutlich dasselbe Geraet); null, wenn keiner. Nie geraten ohne Seriennummer.
     */
    private function eebusChargerSerialTwin(array $c, array $counted): ?array
    {
        $sn = trim((string)($c['deviceSerial'] ?? ''));
        if ($sn === '') { return null; }
        foreach ($counted as $o) {
            if (strcasecmp(trim((string)($o['deviceSerial'] ?? '')), $sn) === 0) { return $o; }
        }
        return null;
    }

    private function getWritableChargers()
    {
        $result = array();
        foreach ($this->getChargerList() as $chg) {
            if ($this->chargerEmsWritable($chg)) {
                $result[] = $chg;
            }
        }
        return $result;
    }

    private function chargerSourceLabel(array $entry): string
    {
        $src = (string)($entry['source'] ?? '');
        if (strpos($src, 'ext:') === 0) { return substr($src, 4) . ' (' . $this->t('external module') . ')'; }
        return ($src === 'ocpphub') ? 'OCPPHub' : (($src === 'chargereebushub') ? 'ChargerEEBUSHub' : 'ChargerHub');
    }
}
