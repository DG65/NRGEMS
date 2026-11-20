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
        if ($this->ReadAttributeString('ExtDiscoveryErrors') !== json_encode($errors)) {
            $this->WriteAttributeString('ExtDiscoveryErrors', json_encode($errors));
            foreach ($errors as $m) { $this->emsLog(EMS_LOG_BASIC, 'Fremdanbindung: ' . $m); }
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
}
