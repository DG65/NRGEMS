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

echo "\n" . ($fails === 0 ? "PLANUNGSKERN BESTANDEN" : "$fails PRUEFUNG(EN) VERLETZT") . "\n";
exit($fails === 0 ? 0 : 1);
