<?php

declare(strict_types=1);

// Prüfstand für den Planungskern ohne Symcon (Umbau 2c, 17.11.2026). Lädt NUR EMS/Planner.php: läuft er durch, hängt der Kern
// nachweislich an keiner Symcon-Funktion. Aufruf: php .tools/test-planner.php  (0 = bestanden)

require __DIR__ . '/../EMS/Planner.php';

$fails = 0;
function ok(string $label, bool $cond, string $detail = ''): void
{
    global $fails;
    echo ($cond ? '  ok    ' : '  FEHLT ') . $label . ($cond || $detail === '' ? '' : "  ($detail)") . "\n";
    if (!$cond) { $fails++; }
}

$eta = sqrt(0.9);
$p = new EMS_Planner($eta, min(1.0, 0.91 / $eta));
$ctx = ['capKwh' => 10.0, 'chargeKw' => 4.0, 'dischargeKw' => 4.0, 'maxW' => 20000.0, 'socMin' => 10.0, 'socReserve' => 0.0,
        'chargeCurve' => [], 'avgHouseW' => 500.0, 'feedTariff' => 0.08, 'cycleCost' => 0.0];

echo "Akku-Physik einer Viertelstunde\n";
$b = $p->batteryStep(50.0, 0.0, 2000.0, 'auto', $ctx);
ok('Automatik, 2 kW Last, kein PV: 0,5 kWh ans Haus, kein Netz', abs($b[4] - 0.5) < 1e-9 && abs($b[1]) < 1e-9, json_encode($b));
ok('Akku gibt dafuer 0,5 / eta ab: SOC sinkt um 5,27 Punkte', abs(50.0 - $b[0] - 0.5 / $eta / 10.0 * 100.0) < 1e-9, json_encode($b));
$b = $p->batteryStep(50.0, 0.0, 2000.0, 'hold', $ctx);
ok('Halten: Akku unveraendert, Haus 0,5 kWh aus dem Netz', $b[0] === 50.0 && abs($b[1] - 0.5) < 1e-9, json_encode($b));
$b = $p->batteryStep(50.0, 0.0, 8000.0, 'auto', $ctx);
ok('Last ueber der Entladegrenze: ans Haus nur 4 kW x eta (Grenze am Akku), Rest aus dem Netz', abs($b[4] - 1.0 * $eta) < 1e-9 && abs($b[1] - (2.0 - $eta)) < 1e-9, json_encode($b));
$b = $p->batteryStep(10.5, 0.0, 2000.0, 'auto', $ctx);
ok('Nahe der Reserve: Akku entlaedt nur bis 10 %', abs($b[0] - 10.0) < 1e-9, json_encode($b));
$b = $p->batteryStep(50.0, 6000.0, 1000.0, 'auto', $ctx);
ok('PV-Ueberschuss 5 kW: Laden gedeckelt auf 4 kW (1 kWh), Rest eingespeist', abs($b[3] - 1.0) < 1e-9 && $b[2] > 0.0, json_encode($b));
$b = $p->batteryStep(99.0, 0.0, 500.0, 'charge', $ctx);
ok('Netzladen bei 99 %: nur der freie Platz (0,1 kWh) wird gefuellt', abs($b[0] - 100.0) < 1e-9 && abs($b[5] - 0.1 / $eta) < 1e-9, json_encode($b));

echo "Ladekurve\n";
ok('Ohne Kurve gilt chargeKw', EMS_Planner::chargeKw($ctx, 80.0) === 4.0);
$cc = array_merge($ctx, ['chargeCurve' => [0 => 4.0, 19 => 1.0]]);
ok('Mit Kurve: 97,5 % (Stufe 19) = 1 kW', abs(EMS_Planner::chargeKw($cc, 97.5) - 1.0) < 1e-9);

echo "Optimierer\n";
$prices = []; for ($i = 0; $i < 96; $i++) { $prices[$i] = ($i < 24) ? 0.20 : (($i >= 68 && $i < 88) ? 0.45 : 0.30); }
$o = $p->optimizeBattery($prices, array_fill(0, 96, 0.0), array_fill(0, 96, 1500.0), 0, 20.0, $ctx);
$charge = count(array_filter($o, fn($x, $i) => $i < 24 && $x['act'] === 'charge', ARRAY_FILTER_USE_BOTH));
ok('Billige Nacht (20 ct), teurer Abend (45 ct): nachts wird geladen', $charge > 0, (string)$charge);
ok('Der Akku wird abends genutzt (SOC sinkt von 17 bis 22 Uhr)', $o[68]['soc'] > $o[87]['soc'], json_encode([$o[68]['soc'], $o[87]['soc']]));
$flat = $p->optimizeBattery(array_fill(0, 96, 0.30), array_fill(0, 96, 0.0), array_fill(0, 96, 1500.0), 0, 50.0, $ctx);
ok('Gleicher Preis den ganzen Tag: nie Netzladen (Verluste lohnen nicht)', count(array_filter($flat, fn($x) => $x['act'] === 'charge')) === 0);
ok('Ohne Preise: leeres Ergebnis', $p->optimizeBattery([], [], [], 0, 50.0, $ctx) === []);

echo "Preisschaetzung\n";
$a = EMS_Planner::anchorEstimate([1 => 0.40], array_fill(0, 60, 0.30), array_fill(0, 60, 0.35));
ok('Verschiebung um die mittlere Abweichung am bekannten Tag (-5 ct)', abs($a[1] - 0.35) < 1e-9, json_encode($a));

// Historie: Mo 03.11. bis So 16.11.2026, Werktage 30 ct (Mi 12.11. 40 ct), Wochenende 20 ct, je Viertelstunde gleich
$hist = [];
for ($d = strtotime('2026-11-03'); $d < strtotime('2026-11-17'); $d += 86400) {
    $wd = (int)date('N', $d); $v = $wd >= 6 ? 0.20 : (date('Y-m-d', $d) === '2026-11-12' ? 0.40 : 0.30);
    for ($q = 0; $q < 96; $q++) { $hist[$d + $q * 900] = $v; }
}
$wed = strtotime('2026-11-18 08:00'); $sat = strtotime('2026-11-21 08:00');
$e = EMS_Planner::estimateFromHistory($hist, [$wed, $sat]);
ok('Werktag: Mittel der letzten 4 Werktage (Mo 17. fehlt, Fr 14., Do 13., Mi 12. 40 ct, Di 11.) = 32,5 ct', abs($e[$wed] - 0.325) < 1e-9, json_encode($e));
ok('Samstag: nur Wochenendtage (15., 16., 8., 9.) = 20 ct', abs($e[$sat] - 0.20) < 1e-9, json_encode($e));
ok('Ohne Historie: keine Schaetzung', EMS_Planner::estimateFromHistory([], [$wed]) === []);
$known = []; for ($q = 0; $q < 96; $q++) { $known[strtotime('2026-11-17') + $q * 900] = 0.25; }
$a2 = EMS_Planner::estimateAnchored($hist, [$wed], $known);
$k0 = EMS_Planner::estimateFromHistory($hist, [strtotime('2026-11-17')]);
ok('Mit Ausrichtung: Schaetzung um (echter Preis 17.11. minus Methode am 17.11.) verschoben', abs($a2[$wed] - ($e[$wed] + 0.25 - $k0[strtotime('2026-11-17')])) < 1e-9, json_encode([$a2, $k0]));

echo "Horizont\n";
$P = array_merge(array_fill(0, 120, 0.30), array_fill(0, 72, null));
ok('Bekannte Preise bis zur ersten Luecke: 120', EMS_Planner::knownPriceSlots($P) === 120);
$estIdx = []; for ($i = 120; $i < 216; $i++) { $estIdx[$i] = 0.25; }
list($P2, $PV2, $L2, $ef) = EMS_Planner::extendHorizon($P, array_fill(0, 192, 100.0), array_fill(0, 192, 500.0), 120, $estIdx, array_fill(0, 96, 700.0), 400.0);
ok('Verlaengert: Schaetzpreise ab Slot 120 bis 215, erster geschaetzter Slot 120', $ef === 120 && $P2[120] === 0.25 && $P2[215] === 0.25 && !isset($P2[216]));
ok('Vorhandene PV/Last bleiben, Tag 3 PV = 0 und Last aus Prognose Tag 3', $PV2[150] === 100.0 && $PV2[200] === 0.0 && $L2[150] === 500.0 && $L2[200] === 700.0);
unset($estIdx[130]);
list(, , , $ef2) = EMS_Planner::extendHorizon($P, [], [], 120, $estIdx, [], 400.0);
list($P3) = EMS_Planner::extendHorizon($P, [], [], 120, $estIdx, [], 400.0);
ok('Luecke in der Schaetzung: Horizont endet davor', $ef2 === 120 && isset($P3[129]) && $P3[130] === null);
ok('Ohne Schaetzung: Horizont unveraendert, estFrom offen', EMS_Planner::extendHorizon($P, [], [], 120, [], [], 400.0)[3] === PHP_INT_MAX);

echo "\n" . ($fails === 0 ? "PLANUNGSKERN BESTANDEN" : "$fails PRUEFUNG(EN) VERLETZT") . "\n";
exit($fails === 0 ? 0 : 1);
