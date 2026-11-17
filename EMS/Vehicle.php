<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 4, 17.11.2026): Fahrzeuge: Vertrag vehicle 1.0 finden, Frische der Daten, Akkureserve für ein heimfahrendes Auto.
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSVehicle;" in der Klasse EMS.
 */
trait EMSVehicle
{
    /**
     * vehicle 1.0: Konsumenten entscheiden nicht mit Altdaten. lastSeenAt null oder aelter als $maxAgeSec
     * = nicht frisch. Alte Tessie-Staende (legacy, ohne GetVehicleData) liefern kein lastSeenAt und
     * gelten wie bisher als frisch.
     */
    private function vehicleFresh(array $veh, int $maxAgeSec): bool
    {
        if (!empty($veh['legacy'])) { return true; }
        $seen = $veh['lastSeenAt'] ?? null;
        return $seen !== null && (time() - (int)$seen) <= $maxAgeSec;
    }

    /**
     * Fahrzeug-Discovery nach Vertrag `vehicle` 1.0 (SUITE.md, 15.11.2026): jedes Geraetemodul (ModuleType 3),
     * dessen Praefix eine Funktion <PREFIX>_GetVehicleData anbietet, ist Anbieter. Neue Hersteller brauchen
     * keine Aenderung am EMS. Nur Instanzen mit Status 102 werden abgefragt, die uebrigen als "inaktiv"
     * gefuehrt. Angenommen wird nur contractType 'vehicle' mit Major 1 (jede Minor).
     * Uebergang: Tessie-Instanzen ohne GetVehicleData (Tessie < 2.40.0) liefern weiter ueber
     * TESSIE_GetVehicleState; deren Felder sind eine Obermenge der Pflichtfelder bis auf lastSeenAt (null).
     */
    private function discoverVehicles(): array
    {
        $active = array();
        $inactive = array();
        $seen = array();
        foreach (IPS_GetModuleList() as $guid) {
            $mod = IPS_GetModule($guid);
            if ((int)($mod['ModuleType'] ?? 0) !== 3) { continue; }
            $prefix = (string)($mod['Prefix'] ?? '');
            if ($prefix === '' || !function_exists($prefix . '_GetVehicleData')) { continue; }
            foreach (IPS_GetInstanceListByModuleID($guid) as $id) {
                $status = (int)(IPS_GetInstance($id)['InstanceStatus'] ?? 0);
                // Abgefragt wird jede Instanz ausser 104 (vom Nutzer deaktiviert) und 1xx-Systemzustaenden.
                // Modul-eigene Status >= 200 sind oft nur Warnungen: Tessie meldet 203, wenn ein schlafendes
                // Auto keine Telemetrie schickt (Daten gueltig). Ob Daten frisch genug sind, sagt lastSeenAt,
                // nicht der Instanzstatus (Live-Befund Privat 15.11.2026: echte Autos 203, Altreste 102).
                if ($status !== 102 && $status < 200) {
                    $inactive[] = array('instanceID' => $id, 'provider' => $prefix, 'status' => $status, 'name' => IPS_GetName($id));
                    continue;
                }
                try {
                    $data = call_user_func($prefix . '_GetVehicleData', $id);
                } catch (\Throwable $e) {
                    $inactive[] = array('instanceID' => $id, 'provider' => $prefix, 'status' => $status, 'name' => IPS_GetName($id), 'error' => $e->getMessage());
                    continue;
                }
                if (is_string($data)) { $data = json_decode($data, true); }
                if (!is_array($data) || ($data['contractType'] ?? '') !== 'vehicle'
                    || (int)explode('.', (string)($data['contractVersion'] ?? '0'))[0] !== 1) {
                    $inactive[] = array('instanceID' => $id, 'provider' => $prefix, 'status' => $status, 'name' => IPS_GetName($id), 'error' => 'contract');
                    continue;
                }
                $data['instanceID'] = $id;
                $data['provider'] = $data['provider'] ?? $prefix;
                if (!isset($data['name'])) { $data['name'] = IPS_GetName($id); }
                $active[] = $data;
                $seen[$id] = true;
            }
        }
        if (function_exists('TESSIE_GetVehicleState')) {
            foreach ($this->discoverContract(GUID_TESSIEVEHICLE, 'TESSIE_GetVehicleState') as $veh) {
                $id = (int)($veh['instanceID'] ?? 0);
                if (isset($seen[$id])) { continue; }
                $veh['provider'] = 'TESSIE';
                $veh['contractType'] = 'vehicle';
                $veh['legacy'] = true;
                if (!array_key_exists('lastSeenAt', $veh)) { $veh['lastSeenAt'] = null; }
                if (!isset($veh['name'])) { $veh['name'] = IPS_GetName($id); }
                $active[] = $veh;
            }
        }
        return array('active' => $active, 'inactive' => $inactive);
    }

    /**
     * Zusaetzlicher Reserve-Bedarf durch Fahrzeuge auf dem Heimweg (Dietmars
     * Anforderung 25.08.2026, ausgeloest durch den Schneeflocke/Philippsburg-
     * Fund: das aktuelle Navigationsziel faelschlich als Heimfahrt gedeutet).
     * Nutzt TESSIE_GetVehicleState contractVersion 1.4
     * (distanceToHomeKm/headingHome/expectedHomeArrivalSocPercent) --
     * NUR uebernommen, wenn das Fahrzeug tatsaechlich Richtung Zuhause
     * navigiert (headingHome===true), nicht bei irgendeinem anderen Ziel.
     * Der Umkreis (VEH_Home_Radius_Km) ist bewusst eine EMS-eigene
     * Geschaeftsregel ("gilt eine Rueckfahrt heute noch als plausibel?"),
     * nicht Teil des Tessie-Vertrags.
     */
    private function computeVehicleReserveKwh()
    {
        $radiusKm = (float)$this->ReadPropertyInteger('VEH_Home_Radius_Km');
        $partners = $this->GetPartners();
        $total = 0.0;
        foreach ((array)($partners['vehicles'] ?? array()) as $veh) {
            if (($veh['headingHome'] ?? false) !== true) { continue; }
            // Schon zuhause: kein Heimkehr-Bedarf, auch wenn im Auto noch ein altes Navigationsziel
            // "Zuhause" steht (Tessie-Befund 15.11.2026: atHome true, distanceToHomeKm 0, headingHome true).
            if (($veh['atHome'] ?? false) === true) { continue; }
            // vehicle 1.0: ohne frische Daten nicht entscheiden. Ein heimfahrendes Auto sendet laufend;
            // lastSeenAt null oder aelter als 1 h = Altdaten. Alte Tessie-Staende (legacy) liefern kein lastSeenAt.
            if (!$this->vehicleFresh($veh, 3600)) { continue; }
            $dist = $veh['distanceToHomeKm'] ?? null;
            $arrivalSoc = $veh['expectedHomeArrivalSocPercent'] ?? null;
            $limit = $veh['chargeLimit'] ?? null;
            $capKwh = $veh['batteryCapacityKwh'] ?? null;
            if ($dist === null || $arrivalSoc === null || $limit === null || $capKwh === null) { continue; }
            if ($dist > $radiusKm) { continue; }
            $neededPct = max(0.0, (float)$limit - (float)$arrivalSoc);
            $total += $neededPct / 100.0 * (float)$capKwh;
        }
        return $total;
    }
}
