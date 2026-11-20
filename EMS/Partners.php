<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (0.110.0, 20.11.2026): Fremdanbindung (Code-Bestandsaufnahme/Fremdanbindung-Konzept.md, SUITE.md
 * "Fremdanbindung"). Findet Module anderer Autoren, die einen NRG-Stack-Vertrag einhalten, ohne ihr Praefix oder ihre GUID zu
 * kennen: IPS_GetModuleList -> Geraetemodul -> Praefix -> function_exists(<PRAEFIX>_<Funktion>). Eintraege werden am Vertragstyp
 * erkannt (contractType, sonst das Feld function) und nur mit Major 1 angenommen. Eigene Module (bekannte Praefixe) sind
 * ausgenommen und haben Vorrang. Lesen sofort; Schreiben nur nach Freigabe je Instanz (EXT_Write_Allowed) und nur mit Rueckfall.
 */
trait EMSPartnerDiscovery
{
    /**
     * Fremde Vertragseintraege eines Typs. $func z. B. 'GetFunctions', $type z. B. 'charger', $ownPrefixes ohne Unterstrich.
     * Laeuft nur im Partner-Zyklus (Minuten). Jeder Aufruf gekapselt; ein Modul, das wirft oder Muell liefert, wird uebersprungen
     * und in ExtDiscoveryErrors vermerkt. Gesamtbudget 3 s.
     */
    private function discoverForeign(string $func, string $type, array $ownPrefixes, array $args = array()): array
    {
        $out = array();
        $errors = array();
        $t0 = microtime(true);
        $own = array_map('strtoupper', $ownPrefixes);
        foreach (IPS_GetModuleList() as $guid) {
            if (microtime(true) - $t0 > 3.0) { $errors[] = 'Zeitbudget erschoepft'; break; }
            $mod = @IPS_GetModule($guid);
            if (!is_array($mod) || (int)($mod['ModuleType'] ?? 0) !== 3) { continue; }
            $prefix = (string)($mod['Prefix'] ?? '');
            if ($prefix === '' || in_array(strtoupper($prefix), $own, true) || !function_exists($prefix . '_' . $func)) { continue; }
            foreach (IPS_GetInstanceListByModuleID($guid) as $id) {
                $status = (int)(IPS_GetInstance($id)['InstanceStatus'] ?? 0);
                if ($status === 104) { continue; } // vom Nutzer deaktiviert
                try { $data = call_user_func_array($prefix . '_' . $func, array_merge(array($id), $args)); }
                // Gleichnamige Funktion mit anderer Signatur (z. B. SGW_GetForecast($id, $von, $bis) bei der Prognose-Suche):
                // kein Vertragspartner fuer diese Suche, kein Fehler (0.112.1, Befund Paul 20.11.: Logeintrag alle 30 s)
                catch (\ArgumentCountError $e) { continue 2; }
                catch (\Throwable $e) { $errors[] = sprintf('%s #%d: %s', $prefix, $id, $e->getMessage()); continue; }
                if (is_string($data)) { $data = json_decode($data, true); }
                if (!is_array($data)) { continue; }
                $entries = (array_keys($data) === range(0, count($data) - 1)) ? $data : array($data);
                foreach ($entries as $e) {
                    if (!is_array($e)) { continue; }
                    $ct = (string)($e['contractType'] ?? ($e['function'] ?? ($e['Type'] ?? ''))); // heatpump-Vertrag bis 1.18: Feld Type
                    if ($ct !== $type) { continue; }
                    if ((int)explode('.', (string)($e['contractVersion'] ?? '0'))[0] !== 1) { $errors[] = sprintf('%s #%d: Vertragsversion %s nicht unterstuetzt', $prefix, $id, (string)($e['contractVersion'] ?? '?')); continue; }
                    $e['instanceID'] = $id;
                    $e['source'] = 'ext:' . $prefix;
                    $e['extPrefix'] = $prefix;
                    $out[] = $e;
                }
            }
        }
        // Je Suche (Funktion/Typ) getrennt merken: sonst ueberschreiben sich die Suchen gegenseitig und jeder Fehler wird
        // in jedem Zyklus erneut geloggt (0.112.1)
        $all = json_decode($this->ReadAttributeString('ExtDiscoveryErrors'), true);
        if (!is_array($all) || array_keys($all) === range(0, count($all) - 1)) { $all = array(); }
        $key = $func . '/' . $type;
        if (($all[$key] ?? array()) !== $errors) {
            foreach (array_diff($errors, $all[$key] ?? array()) as $m) { $this->emsLog(EMS_LOG_BASIC, 'Fremdanbindung: ' . $m); }
            if ($errors === array()) { unset($all[$key]); } else { $all[$key] = $errors; }
            $this->WriteAttributeString('ExtDiscoveryErrors', json_encode($all));
        }
        return $out;
    }

    /** Hat der Nutzer das Schreiben an diese Fremdinstanz freigegeben? (Liste EXT_Write_Allowed, Standard leer) */
    private function extWriteAllowed(int $instanceID): bool
    {
        $rows = json_decode($this->ReadPropertyString('EXT_Write_Allowed'), true);
        if (!is_array($rows)) { return false; }
        foreach ($rows as $r) { if ((int)($r['InstanceID'] ?? 0) === $instanceID) { return true; } }
        return false;
    }

    private function isExtSource(array $e): bool
    {
        return strpos((string)($e['source'] ?? ''), 'ext:') === 0;
    }

    /**
     * Darf an einen Fremdeintrag geschrieben werden? Freigabe je Instanz, Rueckfall im Vertrag (failsafeSec > 0) und frische Daten
     * (lastSeenAt hoechstens 10 min alt, sofern geliefert). Ohne Rueckfall nie – Lehre aus der haengenden Solis-Sperre (19.11.2026).
     */
    private function extWritable(array $e): bool
    {
        if (!$this->isExtSource($e)) { return true; }
        if (!$this->extWriteAllowed((int)($e['instanceID'] ?? 0))) { return false; }
        if ((int)($e['failsafeSec'] ?? 0) <= 0) { return false; }
        if (array_key_exists('failsafeVerified', $e) && $e['failsafeVerified'] === false) { return false; } // SUITE.md 5d: Rueckfall nicht nachgewiesen
        $seen = $e['lastSeenAt'] ?? null;
        if ($seen !== null && time() - (int)$seen > 600) { return false; }
        return true;
    }

    /** Nachschreiben faellig? Jeder Eintrag mit Totmann (failsafeSec > 0, failsafeMode 'deadman' = Standard) spaetestens nach failsafeSec/2; 'maxDuration' nie. */
    private function extKeepAliveDue(array $e, int $lastAt): bool
    {
        $fs = (int)($e['failsafeSec'] ?? 0);
        // gilt fuer JEDE Quelle mit Totmann (auch eigene, z. B. OCPPHub 0.9.0 mit eingeschaltetem Rueckfall), nicht nur Fremdmodule
        if ($fs <= 0) { return false; }
        // SUITE.md Regel 5a: 'maxDuration' = das Modul beendet einen EIN-Wunsch selbst, Nachschreiben waere falsch
        if ((string)($e['failsafeMode'] ?? 'deadman') === 'maxDuration') { return false; }
        return time() - $lastAt >= max(10, intdiv($fs, 2));
    }

    /**
     * 0.112.0: rein lesende Fremdquellen (forecast, price, gridsignal). Aus den Eintraegen von discoverForeign() wird je Instanz
     * EIN Datensatz (bei price liefert jede Viertelstunde einen Eintrag). Behalten werden nur die Felder, die der Konsument zur
     * Auswahl braucht; die Daten selbst werden bei Bedarf frisch abgerufen.
     */
    private function discoverForeignReadSources(string $func, string $type, array $ownPrefixes, array $args, array $keep): array
    {
        $out = array();
        foreach ($this->discoverForeign($func, $type, $ownPrefixes, $args) as $e) {
            $id = (int)$e['instanceID'];
            if (isset($out[$id])) { continue; }
            $r = array('instanceID' => $id, 'source' => $e['source'], 'extPrefix' => $e['extPrefix'], 'contractVersion' => (string)($e['contractVersion'] ?? ''));
            foreach ($keep as $k) { if (array_key_exists($k, $e)) { $r[$k] = $e[$k]; } }
            $out[$id] = $r;
        }
        return array_values($out);
    }

    /** Erster Fremd-Datensatz eines PartnerCache-Schluessels, der $match erfuellt und dessen Instanz noch existiert. */
    private function extReadSource(string $cacheKey, callable $match): ?array
    {
        $cache = json_decode($this->ReadAttributeString('PartnerCache'), true);
        foreach ((array)($cache[$cacheKey] ?? array()) as $r) {
            if (!is_array($r) || !$match($r)) { continue; }
            if (!IPS_InstanceExists((int)($r['instanceID'] ?? 0))) { continue; }
            return $r;
        }
        return null;
    }

    /**
     * Prognose eines Fremdmoduls (forecast 1.x, Anleitung Prognose EIGENES_PROGNOSE_MODUL.md) fuer $kind 'pv'|'load'.
     * Wird nur genutzt, wenn das eigene Modul (PVF/LFC) fehlt. Geprueft: Typ, Art, Major 1, Kalendertag, generated nicht 0 und
     * hoechstens 6 h alt, mean vorhanden; sonst null (= keine Prognose, der Aufrufer faellt auf seinen Standard zurueck).
     */
    private function extForecastDay(string $kind, int $offset): ?array
    {
        $r = $this->extReadSource('extforecast', function (array $r) use ($kind) { return (string)($r['kind'] ?? '') === $kind; });
        if ($r === null) { return null; }
        $fn = $r['extPrefix'] . '_GetForecast';
        if (!function_exists($fn)) { return null; }
        try { $fc = call_user_func($fn, (int)$r['instanceID'], $offset); } catch (\Throwable $e) { return null; }
        if (is_string($fc)) { $fc = json_decode($fc, true); }
        if (!is_array($fc) || (string)($fc['contractType'] ?? '') !== 'forecast' || (string)($fc['kind'] ?? '') !== $kind) { return null; }
        if ((int)explode('.', (string)($fc['contractVersion'] ?? '0'))[0] !== 1) { return null; }
        if ((string)($fc['date'] ?? '') !== date('Y-m-d', strtotime(sprintf('today %+d days', $offset)))) { return null; }
        $gen = (int)($fc['generated'] ?? 0);
        if ($gen <= 0 || time() - $gen > 6 * 3600) { return null; }
        if (!is_array($fc['mean'] ?? null) || count($fc['mean']) < 2) { return null; }
        $fc['source'] = $r['source'];
        return $fc;
    }

    /**
     * Preiskurve eines Fremdmoduls (price 1.x, Anleitung Tibber EIGENES_PREISMODUL.md). $basis 'endkunde' nur mit Netzentgelt
     * (sonst waeren Ladeentscheidungen zu billig gerechnet), 'spot' fuer die Negativpreis-Pflicht. Je Eintrag Typ und Major
     * geprueft; Eintraege ohne start/end/price fallen weg. Leer = keine Quelle.
     */
    private function extPriceCurve(string $basis): array
    {
        $r = $this->extReadSource('extprice', function (array $r) use ($basis) {
            if ((string)($r['basis'] ?? '') !== $basis) { return false; }
            return $basis !== 'endkunde' || (string)($r['netzentgelt'] ?? '') === 'enthalten';
        });
        if ($r === null) { return array(); }
        $fn = $r['extPrefix'] . '_GetPriceCurve';
        if (!function_exists($fn)) { return array(); }
        try { $c = call_user_func($fn, (int)$r['instanceID']); } catch (\Throwable $e) { return array(); }
        if (is_string($c)) { $c = json_decode($c, true); }
        $out = array();
        foreach ((array)$c as $e) {
            if (!is_array($e) || (string)($e['contractType'] ?? '') !== 'price') { continue; }
            if ((int)explode('.', (string)($e['contractVersion'] ?? '0'))[0] !== 1) { continue; }
            if (!isset($e['start'], $e['end'], $e['price']) || (int)$e['end'] <= (int)$e['start'] || !is_numeric($e['price'])) { continue; }
            $e['source'] = $r['source'];
            $out[] = $e;
        }
        return $out;
    }

    /**
     * 0.113.0: Netzsignal (gridsignal 1.x, StromGedacht SGW oder Fremdmodul) als Aufschlag je Slot fuer den Tagesplan.
     * $weightPct 0..100 (Schieberegler). Index wie die Planreihen: 0 = heute 0:00 nach Wanduhr, 96 je Tag. Bei 100 %:
     * Rot +10 ct, Orange +5 ct, Gelb +2 ct, Gruen 0, Supergruen -5 ct je kWh Netzbezug. Unbekannte Slots 0.
     */
    private function gridSignalSurcharges(int $weightPct, int $nSlots): array
    {
        $out = array();
        if ($weightPct <= 0 || $nSlots <= 0) { return $out; }
        $map = array(-1 => -0.5, 1 => 0.0, 2 => 0.2, 3 => 0.5, 4 => 1.0);
        $scale = EMS_GRIDSIG_MAX_EUR * min(100, $weightPct) / 100.0;
        $src = $this->gridSignalSource();
        if ($src === null) { return $out; }
        $from = strtotime('today'); $to = strtotime(sprintf('today +%d days', (int)ceil($nSlots / 96)));
        $segs = array();
        if (function_exists($src['prefix'] . '_GetForecast')) {
            try { $segs = call_user_func($src['prefix'] . '_GetForecast', $src['id'], $from, $to); } catch (\Throwable $e) { $segs = array(); }
            if (is_string($segs)) { $segs = json_decode($segs, true); }
        }
        $now = time();
        try { $st = call_user_func($src['prefix'] . '_GetState', $src['id']); } catch (\Throwable $e) { $st = null; }
        if (is_string($st)) { $st = json_decode($st, true); }
        for ($i = 0; $i < $nSlots; $i++) {
            $day = intdiv($i, 96);
            $ts = mktime(intdiv($i % 96, 4), ($i % 4) * 15, 0, (int)date('n', $from), (int)date('j', $from) + $day, (int)date('Y', $from));
            $v = null;
            foreach ((array)$segs as $g) {
                if (!is_array($g) || (string)($g['contractType'] ?? 'gridsignal') !== 'gridsignal') { continue; }
                // Nur Ampel-Segmente: StromGedacht liefert in derselben Liste auch GSI (0-100) und Energy-Charts (0-2) mit
                // eigener Skala (Befund Paul 20.11.2026); bei SGW zaehlt nur source 'stromgedacht'.
                $gs = (string)($g['source'] ?? '');
                if (in_array($gs, array('gsi', 'energycharts'), true) || ($src['prefix'] === 'SGW' && $gs !== 'stromgedacht')) { continue; }
                if ((int)($g['from'] ?? 0) <= $ts && $ts < (int)($g['to'] ?? 0)) { $v = (int)round((float)($g['value'] ?? 1)); break; }
            }
            // aktueller Slot ohne Vorschau: Zustand von jetzt, sofern frisch (hoechstens 2 h)
            if ($v === null && is_array($st) && $ts <= $now && $now < $ts + 900 && isset($st['state']) && $now - (int)($st['updated'] ?? 0) <= 7200) { $v = (int)$st['state']; }
            if ($v !== null && isset($map[$v])) { $out[$i] = $scale * $map[$v]; }
        }
        return $out;
    }

    /** Netzsignal-Quelle: eigenes StromGedacht, sonst Fremdmodul (gridsignal). null = keine. */
    private function gridSignalSource(): ?array
    {
        $id = (int)$this->getStromGedachtInstance();
        if ($id > 0) { return array('prefix' => 'SGW', 'id' => $id); }
        $r = $this->extReadSource('extgridsignal', function (array $r) { return true; });
        return $r === null ? null : array('prefix' => (string)$r['extPrefix'], 'id' => (int)$r['instanceID']);
    }
}
