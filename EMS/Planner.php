<?php

declare(strict_types=1);

/**
 * Planungskern des EMS (Umbau Schritt 2c, 17.11.2026): reine Rechnung ohne Symcon-Aufrufe -- Akku-Physik einer Viertelstunde,
 * Optimierer ueber den Preis-Horizont, Ladekurve, Ausrichtung der Preisschaetzung. Die Wirkungsgrade kommen von aussen, damit
 * die Klasse ohne Einstellungen pruefbar ist. Das Modul ruft sie ueber gleichnamige Weiterleitungen (ctxChargeKw, batteryStep,
 * optimizeBattery, anchorEstimate).
 */
final class EMS_Planner
{
    /** @var float Wirkungsgrad je AC-Wandlung (Netz -> Akku, Akku -> Haus) */
    private $etaAc;
    /** @var float Wirkungsgrad PV -> Akku (DC/DC) */
    private $etaPv;

    public function __construct(float $etaAc, float $etaPv)
    {
        $this->etaAc = $etaAc;
        $this->etaPv = $etaPv;
    }

    /** Ladeleistung (kW) bei diesem SOC: linear zwischen den gelernten Stufenmitten, sonst der feste Wert des Kontexts. */
    public static function chargeKw(array $ctx, float $soc): float
    {
        $curve = $ctx['chargeCurve'] ?? array();
        if (empty($curve)) { return (float)$ctx['chargeKw']; }
        $x = max(0.0, min(100.0, $soc)) / 5.0 - 0.5;   // Position in Stufenmitten
        $lo = null; $hi = null;
        foreach ($curve as $b => $kw) {
            if ($b <= $x && ($lo === null || $b > $lo)) { $lo = $b; }
            if ($b >= $x && ($hi === null || $b < $hi)) { $hi = $b; }
        }
        if ($lo === null && $hi === null) { return (float)$ctx['chargeKw']; }
        if ($lo === null) { return (float)$curve[$hi]; }
        if ($hi === null || $hi === $lo) { return (float)$curve[$lo]; }
        return (float)$curve[$lo] + ((float)$curve[$hi] - (float)$curve[$lo]) * (($x - $lo) / ($hi - $lo));
    }

    /**
     * Akku-Physik einer Viertelstunde, eine Stelle fuer Optimierer, Planzeilen und Automatik-Simulation (Umbau 2a, 17.11.2026;
     * vorher an drei Stellen eigen gerechnet). PV-Ueberschuss laedt mit eta_pv (gedeckelt durch Ladeleistung und freien Platz),
     * der Rest geht ins Netz. Fehlbedarf: bei 'auto' entlaedt der Akku oberhalb der Reserve, gedeckelt auf die Entladeleistung
     * am Akku (dischargeKw), ans Haus kommt davon eta_ac an; sonst Netz. 'charge' laedt zusaetzlich aus dem Netz mit
     * eta_ac (Ladeleistung, maxW, freier Platz). 'hold' tut nichts ausser PV-Laden.
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float} [SOC, Netzbezug, Einspeisung, PV-Ladung, Akku ans Haus,
     *         Netzladung] -- Energien in kWh; Liste statt Schluessel, weil der Optimierer die Funktion sehr oft ruft
     */
    public function batteryStep(float $soc, float $pvW, float $loadW, string $act, array $ctx): array
    {
        $cap = max(0.001, (float)$ctx['capKwh']);
        $floor = (float)$ctx['socMin'] + (float)$ctx['socReserve'];
        // Wirkungsgrade einmal je Planung vorab in $ctx (etaAc/etaPv), sonst je Aufruf aus den Einstellungen: der Optimierer ruft
        // diese Funktion ueber 100.000-mal je Plan, das Lesen der Einstellungen verdreifachte die Rechenzeit (gemessen 17.11.2026)
        $etaAc = $ctx['etaAc'] ?? $this->etaAc; $etaPv = $ctx['etaPv'] ?? $this->etaPv;
        $chgKw = empty($ctx['chargeCurve']) ? (float)$ctx['chargeKw'] : self::chargeKw($ctx, $soc);
        $sur = $pvW - $loadW;
        $imp = 0.0; $exp = 0.0; $g = 0.0; $out = 0.0; $in = 0.0;
        $room = max(0.0, (100.0 - $soc) / 100.0 * $cap);
        if ($sur > 0.0) {
            $g = min($sur / 1000.0 * 0.25 * $etaPv, $chgKw * 0.25, $room);
            $soc += $g / $cap * 100.0; $room -= $g;
            $exp = max(0.0, $sur / 1000.0 * 0.25 - $g / $etaPv);
        } else {
            $d = -$sur / 1000.0 * 0.25;
            if ($act === 'auto' && $soc > $floor) {
                // dischargeKw ist die Grenze AM AKKU (InverterHub bat_discharge_max_w, Strom x Spannung): ans Haus kommt davon eta_ac an.
                // Bis 0.105.5 rechnete der Optimierer die Grenze am Haus (leicht zu optimistisch), die Automatik-Planzeile am Akku.
                $out = min($d, (float)$ctx['dischargeKw'] * 0.25 * $etaAc, ($soc - $floor) / 100.0 * $cap * $etaAc);
                $soc -= $out / $etaAc / $cap * 100.0; $d -= $out;
            }
            $imp += $d;
        }
        if ($act === 'charge' && $room > 0.0) {
            $in = min(min($chgKw * 1000.0, (float)$ctx['maxW']) / 1000.0 * 0.25, $room / $etaAc);
            $soc += $in * $etaAc / $cap * 100.0; $imp += $in;
        }
        return array(min(100.0, max(0.0, $soc)), $imp, $exp, $g, $out, $in);
    }

    /**
     * Akku-Optimierer (0.102.0, Dietmar 13.11.2026: "Eine feste Preisgrenze darf nicht darueber entscheiden, ob gekauft wird").
     * Dynamische Programmierung ueber den ganzen Preis-Horizont (bis 192 Viertelstunden): je Slot Automatik, Halten
     * oder Netzladen, so dass Netzbezug x Preis - Einspeisung x Verguetung minimal wird. Jede Entscheidung wird gegen
     * den Preis gerechnet, zu dem die Energie sonst tatsaechlich beschafft wuerde, nicht gegen eine Schwelle. Physik wie
     * simulateAutomatikSlot(): PV-Laden x eta_pv, Entladen Last / eta_ac, Netzladen x eta_ac, Reserve nie unterschritten.
     * Rein (Pruefstand). Rueckgabe je kuenftigem Slot: ['act' => 'auto'|'hold'|'charge', 'soc' => SOC danach].
     * Leer, wenn keine Preise. Restenergie am Horizontende zaehlt mit dem Wiederbeschaffungswert (guenstigster Preis
     * der letzten 24 h / eta_ac), damit der Plan den Akku nicht kuenstlich leerfaehrt.
     */
    public function optimizeBattery(array $prices, array $pv, array $load, int $fromSlot, float $soc, array $ctx, array $forced = array(), int $estFrom = PHP_INT_MAX): array
    {
        $n = 0;
        for ($i = $fromSlot; $i < count($prices); $i++) { if (!isset($prices[$i]) || $prices[$i] === null) { break; } $n = $i + 1; }
        if ($n <= $fromSlot || (float)$ctx['capKwh'] <= 0.0) { return array(); }
        $cap = (float)$ctx['capKwh']; $floor = (float)$ctx['socMin'] + (float)$ctx['socReserve'];
        $etaAc = $this->etaAc; $etaPv = $this->etaPv; $feed = (float)($ctx['feedTariff'] ?? 0.0);
        $cyc = (float)($ctx['cycleCost'] ?? 0.0); $disKw = (float)$ctx['dischargeKw'];
        $step = 0.5; $ns = (int)round(100.0 / $step);
        $last = array();
        for ($i = max($fromSlot, $n - 96); $i < $n; $i++) { $last[] = (float)$prices[$i]; }
        $endVal = min($last); // je kWh, die der Akku am Horizontende noch abgeben kann: erspart spaeter Netzbezug, angesetzt zum guenstigsten Preis
        $ctx['etaAc'] = $etaAc; $ctx['etaPv'] = $etaPv;
        // 0.113.0 Netzsignal: Aufschlag (EUR/kWh) NUR auf Netzbezug je Slot (Rot teurer, Supergruen billiger). PV und Einspeisung
        // bleiben unberuehrt -- Lehre aus der entfernten "Gruensten Ladezeit" (0.53.0), die PV drosselte.
        $sig = (array)($ctx['gridSig'] ?? array());
        $sim = function (int $i, float $s, string $act) use ($prices, $pv, $load, $ctx, $feed, $cyc, $sig): array {
            $b = $this->batteryStep($s, (float)($pv[$i] ?? 0.0), (float)($load[$i] ?? $ctx['avgHouseW']), $act, $ctx);
            return array($b[0], $b[1] * ((float)$prices[$i] + (float)($sig[$i] ?? 0.0)) - $b[2] * $feed + $b[5] * $cyc);
        };
        $interp = function (array $V, float $s) use ($step, $ns): float {
            $x = max(0.0, min(100.0, $s)) / $step; $k = (int)floor($x); if ($k >= $ns) { return $V[$ns]; }
            $f = $x - $k; return $V[$k] * (1.0 - $f) + $V[$k + 1] * $f;
        };
        $V = array(); for ($k = 0; $k <= $ns; $k++) { $V[$k] = -max(0.0, $k * $step - $floor) / 100.0 * $cap * $etaAc * $endVal; }
        $Vs = array($n => $V);
        $acts = array('auto', 'hold', 'charge');
        for ($i = $n - 1; $i >= $fromSlot; $i--) {
            $W = array();
            $ai = isset($forced[$i]) ? array($forced[$i]) : (($i >= $estFrom) ? array('auto', 'hold') : $acts); // feste Vorgaben; auf geschaetzte Preise nie aktiv laden (0.103.0)
            for ($k = 0; $k <= $ns; $k++) {
                $best = INF;
                foreach ($ai as $a) { list($s2, $c) = $sim($i, $k * $step, $a); $v = $c + $interp($Vs[$i + 1], $s2); if ($v < $best) { $best = $v; } }
                $W[$k] = $best;
            }
            $Vs[$i] = $W;
        }
        $out = array();
        for ($i = $fromSlot; $i < $n; $i++) {
            $bestA = 'auto'; $best = INF; $bestS = $soc;
            foreach ((isset($forced[$i]) ? array($forced[$i]) : (($i >= $estFrom) ? array('auto', 'hold') : $acts)) as $a) {
                list($s2, $c) = $sim($i, $soc, $a); $v = $c + $interp($Vs[$i + 1], $s2);
                if ($v < $best - 1e-6) { $best = $v; $bestA = $a; $bestS = $s2; }
            }
            $out[$i] = array('act' => $bestA, 'soc' => round($bestS, 1));
            $soc = $bestS;
        }
        return $out;
    }

    /**
     * Schaetzpreise am Niveau des letzten bekannten Tages ausrichten (0.105.3, Befund Paul 17.11.2026): Das Mittel der letzten
     * vier gleichartigen Tage lag 5,4 ct ueber den echten Preisen von morgen; der Optimierer hielt deshalb den 114-kWh-Akku
     * den ganzen Tag voll fuer einen vermeintlich teuren Folgetag. Verschiebung = mittlere Abweichung echter Preis minus
     * Schaetzung (gleiche Methode) am bekannten Tag; der Tagesverlauf bleibt. Weniger als 48 Vergleichs-Slots: unveraendert.
     */
    public static function anchorEstimate(array $est, array $knownActual, array $knownEst): array
    {
        $diff = array();
        foreach ($knownActual as $t => $v) { if (isset($knownEst[$t])) { $diff[] = (float)$v - (float)$knownEst[$t]; } }
        if (count($diff) < 48 || empty($est)) { return $est; }
        $shift = array_sum($diff) / count($diff);
        foreach ($est as $t => $v) { $est[$t] = (float)$v + $shift; }
        return $est;
    }

    /**
     * Preisschaetzung aus der Historie (0.103.0): Mittel derselben Uhrzeit an den letzten vier gleichartigen Tagen (Werktag bzw.
     * Wochenende, hoechstens 21 Tage zurueck). Nachgerechnet 14.10.-12.11.2026: mittlere Abweichung 7,9 ct, aber 80 % Treffer bei
     * den billigsten/teuersten vier Stunden -- fuer die Bewertung der Restenergie reicht das, gehandelt wird darauf nie.
     * @param array<int,float> $hist Startzeit => Preis (EUR/kWh); Rueckgabe Startzeit => Schaetzung, fehlend = nicht im Ergebnis
     */
    public static function estimateFromHistory(array $hist, array $slotStarts): array
    {
        $we = function (int $t): bool { return (int)date('N', $t) >= 6; };
        $out = array();
        foreach ($slotStarts as $t) {
            $d0 = strtotime(date('Y-m-d', $t)); $off = $t - $d0; $v = array();
            for ($k = 1; $k <= 21 && count($v) < 4; $k++) {
                $d = strtotime('-' . $k . ' days', $d0);
                if ($we($d) !== $we($d0)) { continue; }
                if (isset($hist[$d + $off])) { $v[] = $hist[$d + $off]; }
            }
            if (!empty($v)) { $out[$t] = array_sum($v) / count($v); }
        }
        return $out;
    }

    /**
     * Preisschaetzung mit Niveau (0.105.3): Tagesverlauf aus estimateFromHistory(), verschoben um die mittlere Abweichung, die
     * dieselbe Methode am letzten bekannten Tag gegenueber den echten Preisen hat (anchorEstimate()).
     */
    public static function estimateAnchored(array $hist, array $needStarts, array $knownActual): array
    {
        $est = self::estimateFromHistory($hist, $needStarts);
        return self::anchorEstimate($est, $knownActual, self::estimateFromHistory($hist, array_keys($knownActual)));
    }

    /** Anzahl der Viertelstunden mit bekanntem Preis vom Horizontbeginn an (bis zur ersten Luecke). */
    public static function knownPriceSlots(array $prices): int
    {
        $known = 0;
        for ($i = 0; $i < count($prices); $i++) { if (isset($prices[$i]) && $prices[$i] !== null) { $known = $i + 1; } else { break; } }
        return $known;
    }

    /**
     * Optimierer-Horizont hinter dem letzten bekannten Preis um bis zu 96 geschaetzte Viertelstunden verlaengern (0.103.0). Bricht
     * an der ersten Luecke der Schaetzung ab. PV fehlt dort vorsichtig mit 0, Last aus der Lastprognose fuer Tag 3 ($loadD3,
     * Index ab 0 = Slot 192), sonst $avgW. Auf geschaetzte Slots laedt der Optimierer nie aktiv (Rueckgabe estFrom).
     * @return array{0: array, 1: array, 2: array, 3: int} [Preise, PV, Last, erster geschaetzter Slot oder PHP_INT_MAX]
     */
    public static function extendHorizon(array $P, array $PV, array $L, int $known, array $estIdx, array $loadD3, float $avgW): array
    {
        $estFrom = PHP_INT_MAX;
        for ($i = $known; $i < $known + 96; $i++) {
            if (!isset($estIdx[$i])) { break; }
            if ($estFrom === PHP_INT_MAX) { $estFrom = $i; }
            $P[$i] = $estIdx[$i];
            if (!isset($PV[$i])) { $PV[$i] = 0.0; } // ohne PV-Prognose fuer Tag 3 vorsichtig: keine PV
            if (!isset($L[$i])) { $L[$i] = (float)($loadD3[$i - 192] ?? $avgW); }
        }
        return array($P, $PV, $L, $estFrom);
    }
}
