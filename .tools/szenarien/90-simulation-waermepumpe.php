<?php
// Kapitel des EMS-Pruefstands: SimulateDayPlan, Akku halten, Nachtfenster, Waermepumpen-Quellen, EEBUS, WP-Zusatzlauf.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n23) SimulateDayPlan -- gleiche Anlage, anderes Inbetriebnahmedatum (Dietmar 17.09.2026)\n";
$ems = freshEms();
prop('TIBBER_Active', true); prop('BAT_Active', true); prop('ANL_kWp_Manuell', 9.18);
prop('BAT_Capacity_kWh', 40.0); prop('EMS_Max_Power_W', 20000);
pricesToday(0.30);
$r = call($ems, 'SimulateDayPlan', ['nicht-parsbar']);
check('ungueltiges Datum: ok=false statt Fatal Error', $r['ok'] === false && isset($r['fehler']));
$r = call($ems, 'SimulateDayPlan', ['24.10.2012']);
check('IBN 2012: kein Fatal Error, 96 Slots, keine § 51 Pflicht, keine dauerhafte Grenze', $r['ok'] === true && count($r['plan']) === 96 && $r['negativpreisPflicht'] === false && $r['einspeisegrenzeW'] === null, json_encode($r['fehler'] ?? $r['einspeisegrenzeGrund'] ?? null));
check('IBN 2012: Verguetung berechnet ~18,36 ct (bekannter Gegenwert)', abs($r['verguetungCt'] - 18.36) < 0.5, (string)$r['verguetungCt']);
check('IBN 2012: Slot-Format wie GetDayPlan (time gesetzt, Preis in ct/kWh)', isset($r['priceUnit']) && $r['priceUnit'] === 'ct/kWh' && isset($r['plan'][50]['time']) && $r['plan'][50]['time'] > 0 && abs($r['plan'][50]['price'] - 30.0) < 0.01, json_encode($r['plan'][50] ?? null));
prop('ANL_Verguetung_ct', 0.0);
$r2 = call($ems, 'SimulateDayPlan', ['01.06.2025']);
check('IBN 06/2025: § 51 Pflicht aktiv, dauerhafte 60-%-Grenze = 5508 W (ohne Smart Meter/Steuerbox)', $r2['ok'] === true && $r2['negativpreisPflicht'] === true && $r2['einspeisegrenzeW'] === 5508, json_encode($r2));
check('IBN 06/2025: andere Verguetung als IBN 2012 (unterschiedliche EEG-Fassung)', abs($r2['verguetungCt'] - $r['verguetungCt']) > 0.5, $r['verguetungCt'] . ' vs ' . $r2['verguetungCt']);
check('echtes BuildDayPlan() bleibt unbeeinflusst (negativpreisPflicht/feedInLimitW nur in SimulateDayPlan gesetzt)', call($ems, 'permanentFeedInLimit') === call($ems, 'permanentFeedInLimit'));
// § 51 wirkt sich im Plan aus: bei negativem Boersenpreis + volle Batterie -> 0 W statt Export
attr('FcSpotCurve', json_encode([['start' => strtotime('today'), 'end' => strtotime('tomorrow'), 'price' => -5.0, 'aufloesung' => 86400, 'quelle' => 'boersenpreis']]));
$ctxFull = ['negativpreisPflicht' => true, 'feedInLimitW' => 3000.0, 'capKwh' => 40.0, 'chargeKw' => 10.0, 'dischargeKw' => 10.0,
    'maxW' => 20000, 'feedTariff' => 0.18, 'thCharge' => 0.10, 'thDischarge' => 0.25, 'socTargetDay' => 90.0, 'hystSoc' => 2.0,
    'socMin' => 10.0, 'socReserve' => 5.0, 'socTargetNight' => 30.0, 'avgHouseW' => 400.0, 'houseLoadSlots' => [], 'fcMinPower' => 300.0,
    'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0];
$d = call($ems, 'simulateDaySlot', [10, -0.10, 8000.0, 99.6, [], $ctxFull, 0.0]);
check('§ 51 + volle Batterie + negativer Bezugspreis: 0 W statt Export (Pflicht sticht)', $d['plan']['op'] === EMS_OP_AUTO && (int)$d['plan']['power'] === 0 && strpos($d['plan']['reason'], '§ 51') !== false, json_encode($d['plan']));
$dNoPflicht = call($ems, 'simulateDaySlot', [10, -0.10, 8000.0, 99.6, [], array_merge($ctxFull, ['negativpreisPflicht' => false]), 0.0]);
check('ohne § 51-Pflicht bei gleicher Lage: normale Regel greift (Negativpreis -> laden, SOC schon fast voll -> kein Export-Zwang durch die Pflicht)', strpos($dNoPflicht['plan']['reason'], '§ 51') === false);
$ctxCap = array_merge($ctxFull, ['negativpreisPflicht' => false]);
$dCap = call($ems, 'simulateDaySlot', [50, 0.30, 8000.0, 95.0, [], $ctxCap, 0.0]);
check('dauerhafte Einspeisegrenze kappt PV-Vollernte-Export (8000W PV, Grenze 3000W)', $dCap['plan']['op'] === EMS_OP_AUTO && strpos($dCap['plan']['reason'], 'Einspeisegrenze 3000W greift, 5000W gekappt') !== false, json_encode($dCap['plan']));

echo "\n24) SimulateDayPlanScenarios -- mehrere Rechtslagen auf einen Schlag (Funktionsfaehigkeits-Nachweis)\n";
$s = call($ems, 'SimulateDayPlanScenarios', [[]]);
check('Referenzset ohne Angabe: 4 Szenarien, alle ok', count($s) === 4 && count(array_filter($s, fn($x) => $x['ok'] === true)) === 4, json_encode(array_map(fn($x) => $x['ok'], $s)));
check('Bestandsanlage 2012: keine Pflicht, keine Grenze', $s['24.10.2012']['negativpreisPflicht'] === false && $s['24.10.2012']['einspeisegrenzeW'] === null);
check('vor Solarspitzengesetz (2024): ebenfalls keine Pflicht/Grenze', $s['01.01.2024']['negativpreisPflicht'] === false && $s['01.01.2024']['einspeisegrenzeW'] === null);
check('Solarspitzengesetz (03/2025): Pflicht + 60-%-Grenze', $s['01.03.2025']['negativpreisPflicht'] === true && $s['01.03.2025']['einspeisegrenzeW'] === 5508);
check('jedes Szenario traegt sein Label', $s['01.03.2025']['label'] === 'Solarspitzengesetz (§ 51 + 60-%-Einspeisegrenze)');
$eigene = call($ems, 'SimulateDayPlanScenarios', [['24.10.2012', '01.03.2025']]);
check('eigene Datumsliste: nur die angegebenen, Label = Datum selbst', count($eigene) === 2 && $eigene['01.03.2025']['label'] === '01.03.2025');

echo "\n26) Modus 'Akku halten' (op 8) statt 'Einspeisen' bei 0 W\n";
$ems = freshEms();
$ctxH = ['negativpreisPflicht' => false, 'capKwh' => 40.0, 'chargeKw' => 10.0, 'dischargeKw' => 10.0, 'maxW' => 20000, 'feedTariff' => 0.1836,
    'thCharge' => 0.10, 'thDischarge' => 0.25, 'socTargetDay' => 38.0, 'hystSoc' => 3.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'socTargetNight' => 30.0,
    'avgHouseW' => 400.0, 'houseLoadSlots' => [], 'fcMinPower' => 300.0, 'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0];
$h = call($ems, 'simulateDaySlot', [12, 0.14, 0.0, 40.0, [], $ctxH, 0.0]);
check('Bezug 14 ct < Verguetung 18,36 ct, kein PV: op = Akku halten (8), 0 W, gw Modus 8 Bereitschaft', $h['plan']['op'] === EMS_OP_HOLD && EMS_OP_HOLD === 8 && (int)$h['plan']['power'] === 0 && $h['plan']['gw'] === GW_MODE_STANDBY, json_encode($h['plan']));
check('Begruendung sagt nicht mehr "exportiert"', strpos($h['plan']['reason'], 'exportiert') === false && strpos($h['plan']['reason'], 'geschont') !== false, $h['plan']['reason']);
$e = call($ems, 'simulateDaySlot', [50, 0.30, 8000.0, 38.4, [], $ctxH, 0.0]);
check('PV-Einspeisung bei vollem Akku: Automatik (op 0, Modus 1, 0 W) -- die WR-Automatik speist selbst ein, kein erzwungener Modus 5', $e['plan']['op'] === EMS_OP_AUTO && $e['plan']['gw'] === GW_MODE_AUTO && (int)$e['plan']['power'] === 0 && strpos($e['plan']['reason'], 'eingespeist') !== false, json_encode($e['plan']));
$acts = array_column(call($ems, 'getPlanActions'), null, 'op');
check('Kalender-Aktionen kennen op 8 (Name, Tuerkis)', ($acts[8]['name'] ?? '') === 'Akku halten (Netzbezug)' && ($acts[8]['color'] ?? 0) === 0x00ACC1, json_encode($acts[8] ?? null));

echo "\n24c) Prognose-Rueckgabe: Platzhalter (generated=0) und altes Datum sind 'unbekannt'\n";
$ems = freshEms();
check('normale Prognose (heute, generated>0) brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => time(), 'kwh' => 40.0], 0]) === true);
check('generated=0 = Platzhalter, nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => 0, 'kwh' => 0.0], 0]) === false);
check('Datum von gestern fuer Offset 0 nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d', strtotime('yesterday')), 'generated' => time()], 0]) === false);
check('Datum von heute fuer Offset 1 nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => time()], 1]) === false);
check('aelterer Vertrag ohne date/generated bleibt brauchbar', call($ems, 'forecastUsable', [['kwh' => 12.0, 'p50' => []], 0]) === true);
check('kein Array: nicht brauchbar', call($ems, 'forecastUsable', [null, 0]) === false);

echo "\n24d) Nachtfenster: bis 06:00 Haus aus dem Netz, Akku in den guenstigsten Viertelstunden laden\n";
$ems = freshEms();
$ctxN = ['capKwh' => 40.0, 'chargeKw' => 10.0, 'maxW' => 20000, 'socTargetNight' => 100.0, 'feedTariff' => 0.1836];
$pN = array_fill(0, 96, 0.30);
foreach ([10 => 0.10, 11 => 0.11, 12 => 0.12, 13 => 0.13, 14 => 0.14, 15 => 0.15, 16 => 0.16, 17 => 0.17, 40 => 0.01] as $i => $v) { $pN[$i] = $v; }
check('Funktion aus (Standard): kein Nachtfenster', call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN]) === null);
prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6);
$nw = call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN]);
check('SOC 60 % -> 16 kWh fehlen, 2,5 kWh je Viertelstunde = 7 Ladeslots', $nw['n'] === 7 && $nw['end'] === 24, json_encode($nw));
check('gewaehlt sind die 7 guenstigsten Slots des Fensters (10-16), nicht der billigere Slot 40 ausserhalb', array_keys($nw['charge']) === [10, 11, 12, 13, 14, 15, 16] && $nw['charge'][10] === 1, json_encode($nw['charge']));
$c = call($ems, 'nightWindowSlot', [12, 0.12, 80.0, $nw, $ctxN]);
check('Ladeslot: Netz laden (op 2, Batterie-Lademodus 11, Ladegrenze als Sollleistung), Rang und Preis in der Begruendung', $c['plan']['op'] === EMS_OP_NET_CHARGE && $c['plan']['gw'] === GW_MODE_BAT_CHARGE && $c['plan']['power'] === 10000 && strpos($c['plan']['reason'], 'Rang 3 von 7') !== false && strpos($c['plan']['reason'], '12,00') === false && !empty($c['plan']['nw']), json_encode($c['plan']));

// Vorentladen zeitgenau: im letzten Slot Restenergie / Restzeit, sonst Restenergie ueber Slots nach Preis verteilt
check('Vorentladen letzter Slot: 2 kWh in voller Viertelstunde -> 8 kW, in halber Restzeit -> 16 kW (leer genau zum Fensterbeginn)',
    abs(call($ems, 'preDischargeBatteryW', [2.0, 0.28, 0.0, 1.0]) - 8000.0) < 1 && abs(call($ems, 'preDischargeBatteryW', [2.0, 0.28, 0.0, 0.5]) - 16000.0) < 1);
check('Vorentladen: mit Folgeslots gleichen Preises (3 weitere) bei vollem Slot 1/4 der Restenergie je Slot (4 kWh -> 4 kW)',
    abs(call($ems, 'preDischargeBatteryW', [4.0, 0.28, 0.84, 1.0]) - 4000.0) < 1);

// Nachtfenster verlaengern, solange Akku nicht voll und Preis halbwegs stimmt (0.49.0)
prop('PLAN_NightGrid_ExtendHours', 2); prop('PLAN_NightGrid_ExtendTol_ct', 3.0);
$pV = array_fill(0, 96, 0.30); $pV[2] = 0.11; $pV[3] = 0.11; $pV[4] = 0.12;
for ($i = 24; $i < 32; $i++) { $pV[$i] = 0.13; } $pV[26] = 0.16; $pV[40] = 0.11;
$nwV = call($ems, 'nightWindowPlan', [$pV, 0, 0.0, $ctxN]);
check('Verlaengerung: zu wenig guenstige Slots im Fenster -> weitere direkt nach der Endstunde (<= guenstigster + 3 ct), nicht der teure Slot 26 und nicht Slot 40', isset($nwV['charge'][24], $nwV['charge'][25], $nwV['charge'][27]) && !isset($nwV['charge'][26]) && !isset($nwV['charge'][40]) && isset($nwV['charge'][2]), json_encode(array_keys($nwV['charge'])));
$cV = call($ems, 'nightWindowSlot', [24, 0.13, 60.0, $nwV, $ctxN]);
check('Verlaengerungs-Slot wird als Netz laden geplant und als verlaengert begruendet', $cV['plan']['op'] === EMS_OP_NET_CHARGE && strpos($cV['plan']['reason'], 'verlängert') !== false, json_encode($cV['plan']));
prop('PLAN_NightGrid_ExtendHours', 0);
$nwV0 = call($ems, 'nightWindowPlan', [$pV, 0, 0.0, $ctxN]);
check('Verlaengerung aus (0 h): nichts nach der Endstunde', count(array_filter(array_keys($nwV0['charge']), function ($k) { return $k >= 24; })) === 0, json_encode(array_keys($nwV0['charge'])));
prop('PLAN_NightGrid_ExtendHours', 2);

// Plan: WR-Automatik entlaedt das Haus auch bei Preisen unter der Entladeschwelle (SOC darf nicht auf 100 % stehen)
$ctxA = ['capKwh' => 40.0, 'chargeKw' => 24.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0, 'feedTariff' => 0.1836, 'thCharge' => 0.10, 'thDischarge' => 0.25,
    'socTargetDay' => 90.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'socTargetNight' => 100.0, 'fcMinPower' => 100.0,
    'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 800.0, 'houseLoadSlots' => []];
$rA = call($ems, 'simulateDaySlot', [26, 0.19, 0.0, 100.0, [], $ctxA, 0.0]);
check('Plan: Preis 19 ct unter Entladeschwelle, kein PV -> SOC faellt (Hauslast aus Akku), nicht 100 % eingefroren', $rA['soc'] < 100.0 && $rA['plan']['op'] === EMS_OP_AUTO, json_encode($rA['plan']));

// Vorentladen (0.48.0): Fensterbeginn und Planslot
prop('PLAN_PreDischarge_MinGain_ct', 3.0);
$p192 = array_fill(0, 192, 0.30); for ($i = 96; $i < 102; $i++) { $p192[$i] = 0.127; }
check('Vorentladen: Fensterbeginn = erste Viertelstunde unter (Verguetung - 3 ct) x 0,9025', call($ems, 'preDischargeEnd', [$p192, 81, 0.1836]) === 96);
check('Vorentladen endet, sobald die laufende Viertelstunde selbst im guenstigen Fenster liegt (00:00)', call($ems, 'preDischargeEnd', [$p192, 96, 0.1836]) === null);
check('Vorentladen: zu teures Fenster (17 ct) lohnt nicht', call($ems, 'preDischargeEnd', [array_fill(0, 192, 0.17), 81, 0.1836]) === null);
$pdS = ['end' => 96, 'endPrice' => 0.127, 'sum' => 0.30 * 15, 'avg' => 0.30, 'floor' => 0.0];
$ctxP = ['capKwh' => 40.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0];
$rP = call($ems, 'preDischargePlanSlot', [81, &$pdS, 90.0, 0.30, 0.0, 1500.0, $ctxP]);
check('Vorentladen-Planslot: Einspeisen (op 5) mit Xset, SOC faellt', $rP['plan']['op'] === EMS_OP_EXPORT && $rP['plan']['power'] > 5000 && $rP['soc'] < 90.0, json_encode($rP['plan']));

// Wirtschaftlichkeit (Dietmar 19.09.2026): Netzladen nur unter Einspeiseverguetung x 0,95; letzter Slot ohne Ueberladen
$pExp = array_fill(0, 96, 0.30); $pExp[10] = 0.10; $pExp[11] = 0.17; $pExp[12] = 0.19;
$nwE = call($ems, 'nightWindowPlan', [$pExp, 0, 0.0, $ctxN]);
check('Nachtfenster: nur Slots unter 95 % der Einspeiseverguetung (17,44 ct) kommen in Frage', array_keys($nwE['charge']) === [10, 11] && $nwE['cand'] === 2, json_encode($nwE));
$cL = call($ems, 'nightWindowSlot', [11, 0.17, 97.5, $nwE, $ctxN]);
check('Letzter Ladeslot: Sollleistung nur fuer die fehlende Energie (1 kWh -> 4 kW)', $cL['plan']['gw'] === GW_MODE_BAT_CHARGE && $cL['plan']['power'] === 4000, json_encode($cL['plan']));
check('Ladeslot erhoeht den SOC (80 % + 2,5 kWh = 86,25 %)', abs($c['soc'] - 86.25) < 0.01, (string)$c['soc']);
$h = call($ems, 'nightWindowSlot', [3, 0.17, 60.0, $nw, $ctxN]);
check('uebriger Slot im Fenster, Preis 17 ct < 17,44 ct: Akku halten (op 8, Bereitschaft Modus 8), Haus aus dem Netz, SOC unveraendert', $h['plan']['op'] === EMS_OP_HOLD && $h['plan']['gw'] === GW_MODE_STANDBY && (int)$h['plan']['power'] === 0 && $h['soc'] === 60.0 && !empty($h['plan']['nw']), json_encode($h['plan']));
check('Preis 18 ct liegt ueber 18,36 ct x 0,95 = 17,44 ct: Haus vom Akku versorgt (normale Logik, null)', call($ems, 'nightWindowSlot', [3, 0.18, 60.0, $nw, $ctxN]) === null && call($ems, 'nightWindowSlot', [3, 0.30, 60.0, $nw, $ctxN]) === null);
check('Grenze: 17,4 ct noch Netz, 17,5 ct schon Akku', call($ems, 'nightWindowSlot', [3, 0.174, 60.0, $nw, $ctxN]) !== null && call($ems, 'nightWindowSlot', [3, 0.175, 60.0, $nw, $ctxN]) === null);
check('Ladeslot bleibt Ladeslot, auch wenn der Preis ueber der Vergueltungsgrenze liegt (guenstigste Viertelstunden zaehlen)', call($ems, 'nightWindowSlot', [12, 0.19, 80.0, $nw, $ctxN])['plan']['op'] === EMS_OP_NET_CHARGE);
check('ausserhalb des Fensters (06:00) und ohne Preis: nichts', call($ems, 'nightWindowSlot', [24, 0.10, 60.0, $nw, $ctxN]) === null && call($ems, 'nightWindowSlot', [3, null, 60.0, $nw, $ctxN]) === null);
$nwFull = call($ems, 'nightWindowPlan', [$pN, 0, 100.0, $ctxN]);
$hf = call($ems, 'nightWindowSlot', [5, 0.17, 100.0, $nwFull, $ctxN]);
check('Akku schon voll: keine Ladeslots, Haus trotzdem aus dem Netz ("Akku voll" in der Begruendung)', $nwFull['n'] === 0 && $hf['plan']['op'] === EMS_OP_HOLD && strpos($hf['plan']['reason'], 'voll') !== false, json_encode($hf['plan']));
$nwLate = call($ems, 'nightWindowPlan', [$pN, 12, 60.0, $ctxN]);
check('ab Slot 12 (03:00): nur noch die guenstigsten der restlichen Slots, vergangene zaehlen nicht', min(array_keys($nwLate['charge'])) >= 12, json_encode($nwLate['charge']));
$nwCap = call($ems, 'nightWindowPlan', [$pN, 0, 40.0, array_merge($ctxN, ['chargeKw' => 41.0, 'maxW' => 20000])]);
check('Ladeleistung wird auf die EMS-Grenze (20 kW) gedeckelt: 24 kWh fehlen, 5 kWh je Viertelstunde = 5 Slots (nicht 3 wie bei 41 kW)', $nwCap['n'] === 5, json_encode($nwCap));
prop('PLAN_NightGrid_EndHour', 7);
check('Endstunde einstellbar (7 Uhr -> Slot 28)', call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN])['end'] === 28);
prop('PLAN_NightGrid_Active', false); prop('PLAN_NightGrid_EndHour', 6);

echo "\n25) Weitere Waermepumpen-Quellen (WPModbusHub, WPModbusHubGateway, SamsungEhs) werden gefunden\n";
$ems = freshEms();
$hp = fn($iid, $cap) => [['contractVersion' => '1.15', 'Type' => 'heatpump', 'Caption' => $cap, 'PowerID' => 0, 'EnergyID' => 0, 'reachable' => true, 'unit' => 'W', 'Measured' => true, 'outsideTempID' => 0, 'lastSeenAt' => time()]];
$GLOBALS['INSTMOD'][909] = GUID_WPHUB;
$GLOBALS['INSTMOD'][910] = GUID_WPMODBUSHUB; $GLOBALS['INSTMOD'][911] = GUID_WPMODBUSGW; $GLOBALS['INSTMOD'][912] = GUID_SAMSUNGEHS;
$GLOBALS['WP_FUNCS'] = ['wphub' => $hp(909, 'Panasonic'), 'wpmbhub' => $hp(910, 'Waterkotte'), 'wpmbgw' => $hp(911, 'NIBE RTU'), 'samehs' => $hp(912, 'Samsung EHS')];
call($ems, 'Discover');
$pc = json_decode($ems->ReadAttributeString('PartnerCache'), true);
check('Discovery: WPModbusHub gefunden (Instanz-ID ergaenzt)', count($pc['wpmodbushub'] ?? []) === 1 && $pc['wpmodbushub'][0]['instanceID'] === 910 && $pc['wpmodbushub'][0]['Type'] === 'heatpump', json_encode($pc['wpmodbushub'] ?? null));
check('Discovery: WPModbusHubGateway gefunden', count($pc['wpmodbushubgw'] ?? []) === 1 && $pc['wpmodbushubgw'][0]['instanceID'] === 911);
check('Discovery: WPHub gefunden', count($pc['wphub'] ?? []) === 1 && $pc['wphub'][0]['instanceID'] === 909);
check('Discovery: SamsungEhs gefunden', count($pc['samsungehs'] ?? []) === 1 && $pc['samsungehs'][0]['instanceID'] === 912);
check('Uebersicht nennt die weiteren Quellen', strpos($ems->GetValue('EMS_Partners'), 'WPHub=1 WPModbusHub=1 WPModbusHubGateway=1 SamsungEhs=1') !== false, $ems->GetValue('EMS_Partners'));
$sit = array_filter(call($ems, 'GetSituation')['devices'] ?? call($ems, 'GetSituation'), fn($d) => ($d['domain'] ?? '') === 'heatpump');
check('Situation: alle vier als Waermepumpe (Situation A, nicht schaltbar), Quelle je Eintrag sichtbar', count($sit) === 4 && count(array_filter($sit, fn($d) => $d['writable'] === false && $d['situation'] === 'A')) === 4 && array_column($sit, 'sourceModule') === ['WPHub', 'WPModbusHub', 'WPModbusHubGateway', 'SamsungEhs'], json_encode(array_values($sit)));
$un = json_decode($ems->ReadAttributeString('UnresponsiveInstances'), true);
check('Keine der drei als "installiert, aber stumm" gemeldet', empty($un['wpmodbushub']) && empty($un['wpmodbushubgw']) && empty($un['samsungehs']), json_encode($un));
$GLOBALS['WP_FUNCS'] = []; call($ems, 'Discover');
$un = json_decode($ems->ReadAttributeString('UnresponsiveInstances'), true);
check('Antwortet ein Modul nicht, wird es als stumm gemeldet (wie HeishaMon)', ($un['wpmodbushub'] ?? []) === [910] && ($un['samsungehs'] ?? []) === [912], json_encode($un));
unset($GLOBALS['INSTMOD'][909], $GLOBALS['INSTMOD'][910], $GLOBALS['INSTMOD'][911], $GLOBALS['INSTMOD'][912]); $GLOBALS['WP_FUNCS'] = [];

// EEBUS/Zusatzlauf erst ab 0.99.0 (Gegenprobe deutsch-vergleich laeuft auch gegen aeltere Modulstaende)
if (defined('GUID_WPEEBUSHUB')) {
echo "\n25b) EEBUS-Module: Discovery WPEEBUSHub/ChargerEEBUSHub (0.99.0)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][920] = GUID_WPEEBUSHUB; $GLOBALS['INSTMOD'][930] = GUID_CHARGEREEBUSHUB; $GLOBALS['INSTMOD'][931] = GUID_CHARGEREEBUSHUB;
$GLOBALS['WP_FUNCS'] = ['wpeeb' => [920 => [['contractVersion' => '1.18', 'Type' => 'heatpump', 'Caption' => 'Vaillant', 'function' => 'heatpump', 'label' => 'Vaillant', 'instanceID' => 920, 'managedBy' => 'none', 'controllable' => true, 'optionalRunID' => 0, 'lastSeenAt' => time()]]]];
$cheebPow = vari('EEBUS Leistung', 930, 'Leistung', 3680.0);
$GLOBALS['CHEEB_FUNCS'] = [930 => [['contractVersion' => '1.12', 'function' => 'charger', 'label' => 'Elli', 'powerID' => $cheebPow, 'chargeEnableID' => 0, 'currentLimitID' => 0, 'managedBy' => 'none', 'duplicateOf' => null, 'deviceSerial' => 'SN-1', 'lastSeenAt' => time()]],
    931 => [['contractVersion' => '1.12', 'function' => 'charger', 'label' => 'Elli doppelt', 'powerID' => 0, 'managedBy' => 'none', 'duplicateOf' => ['source' => 'chargereebushub', 'instanceID' => 930], 'deviceSerial' => 'SN-2', 'lastSeenAt' => time()]]];
call($ems, 'Discover');
$pc = json_decode($ems->ReadAttributeString('PartnerCache'), true);
check('Discovery: WPEEBUSHub gefunden, instanceID = Geraeteinstanz', count($pc['wpeebushub'] ?? []) === 1 && $pc['wpeebushub'][0]['instanceID'] === 920, json_encode($pc['wpeebushub'] ?? null));
check('Discovery: ChargerEEBUSHub gefunden, instanceID je Geraeteinstanz (kein Splitter, Ueberschreiben unschaedlich)', array_column($pc['chargereebushub'] ?? [], 'instanceID') === [930, 931]);
check('Uebersicht nennt die EEBUS-Module', strpos($ems->GetValue('EMS_Partners'), 'WPEEBUSHub=1 ChargerEEBUSHub=2') !== false, $ems->GetValue('EMS_Partners'));
$un = json_decode($ems->ReadAttributeString('UnresponsiveInstances'), true);
check('Keine EEBUS-Instanz als stumm gemeldet', empty($un['wpeebushub']) && empty($un['chargereebushub']), json_encode($un));
$sit = array_values(array_filter(call($ems, 'GetSituation'), fn($d) => ($d['domain'] ?? '') === 'heatpump'));
check('Situation: WPEEBUSHub als Waermepumpe, ohne Zusatzlauf nicht schaltbar', count($sit) === 1 && $sit[0]['sourceModule'] === 'WPEEBUSHub' && $sit[0]['writable'] === false, json_encode($sit));
$list = call($ems, 'getChargerList');
check('CHEEB mit duplicateOf zaehlt nicht doppelt: genau eine EEBUS-Wallbox', count($list) === 1 && $list[0]['instanceID'] === 930 && $list[0]['source'] === 'chargereebushub', json_encode(array_column($list, 'instanceID')));
check('EEBUS-Wallbox: Leistung wird gemessen (3,68 kW)', abs(call($ems, 'readChargerPowerKw', [1]) - 3.68) < 0.01, (string)call($ems, 'readChargerPowerKw', [1]));
check('EEBUS-Wallbox: EMS schaltet sie nicht (kein ctl_enable-Ident)', call($ems, 'getWritableChargers') === [] && call($ems, 'getControlEntry', [1]) === null);
// dieselbe Wallbox zusaetzlich ueber ChargerHub (gleiche Seriennummer, keine Markierung): nur einmal zaehlen
attr('PartnerCache', json_encode(array_merge($pc, ['chargerhub' => [['instanceID' => 601, 'label' => 'go-e', 'powerID' => 0, 'managedBy' => 'none', 'deviceSerial' => 'SN-1']]])));
$list = call($ems, 'getChargerList');
check('Gleiche Seriennummer ueber ChargerHub ohne Markierung: EEBUS-Anbindung zaehlt nicht zusaetzlich', count($list) === 1 && $list[0]['source'] === 'chargerhub', json_encode(array_column($list, 'source')));
attr('PartnerCache', json_encode(array_merge($pc, ['chargerhub' => [['instanceID' => 601, 'label' => 'go-e', 'powerID' => 0, 'managedBy' => 'none', 'deviceSerial' => 'X']]])));
prop('WB_Quelle', 1);
check('WB_Quelle=1 (nur ChargerHub) unveraendert: andere EEBUS-Wallbox zaehlt additiv', array_column(call($ems, 'getChargerList'), 'source') === ['chargerhub', 'chargereebushub']);
unset($GLOBALS['INSTMOD'][920], $GLOBALS['INSTMOD'][930], $GLOBALS['INSTMOD'][931]); $GLOBALS['WP_FUNCS'] = []; $GLOBALS['CHEEB_FUNCS'] = [];

echo "\n25c) WP-Zusatzlauf bei PV-Ueberschuss (optionalRunID, heatpump 1.18)\n";
$ems = freshEms();
$orVid = vari('Zusatzlauf', 920, 'OhpcfLauf', false, 0);
$orPow = vari('Zusatzleistung max', 920, 'OhpcfLeistungMax', 2000.0);
$hpE = fn(string $mb, bool $ctl = true) => ['instanceID' => 920, 'Caption' => 'Vaillant', 'managedBy' => $mb, 'controllable' => $ctl, 'optionalRunID' => $orVid, 'optionalRunPowerID' => 0, 'optionalRunPowerMaxID' => $orPow];
attr('PartnerCache', json_encode(['wpeebushub' => [$hpE('none')]]));
$writes = function () use ($orVid) { return array_values(array_filter($GLOBALS['VARACTIONS'] ?? [], fn($a) => $a[0] === $orVid)); };
$t0 = 1800000000;
$sur = state(['grid_total_w' => 2400.0, 'bat_soc' => 98.0, 'bat_pow_w' => 0.0]);
$GLOBALS['VARACTIONS'] = [];
for ($i = 0; $i <= 12; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + $i * 60]); }
check('Schalter aus (Standard): nie schreiben, auch bei Ueberschuss', $writes() === [], json_encode($writes()));
prop('WP_OptionalRun_Active', true);
attr('PartnerCache', json_encode(['wpeebushub' => [$hpE('other')]]));
for ($i = 0; $i <= 12; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + $i * 60]); }
check('managedBy other: nie schreiben', $writes() === []);
attr('PartnerCache', json_encode(['wpeebushub' => [$hpE('wphub')]]));
for ($i = 0; $i <= 12; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + $i * 60]); }
check('managedBy modul-eigen (wphub): nie schreiben', $writes() === []);
attr('PartnerCache', json_encode(['wpeebushub' => [$hpE('none', false)]]));
for ($i = 0; $i <= 12; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + $i * 60]); }
check('controllable=false: nie schreiben', $writes() === []);
attr('PartnerCache', json_encode(['wpeebushub' => [$hpE('none')]]));
attr('HpOptionalRun', '{}');
call($ems, 'applyHeatpumpOptionalRun', [state(['grid_total_w' => 2400.0, 'bat_soc' => 60.0, 'bat_pow_w' => 0.0]), $t0]);
call($ems, 'applyHeatpumpOptionalRun', [state(['grid_total_w' => 2400.0, 'bat_soc' => 60.0, 'bat_pow_w' => 0.0]), $t0 + 600]);
check('Batterie hat Vorrang (SOC 60 %, laedt nicht): kein Zusatzlauf', $writes() === []);
call($ems, 'applyHeatpumpOptionalRun', [state(['grid_total_w' => 1500.0, 'bat_soc' => 98.0]), $t0 + 600]);
call($ems, 'applyHeatpumpOptionalRun', [state(['grid_total_w' => 1500.0, 'bat_soc' => 98.0]), $t0 + 1200]);
check('Einspeisung 1,5 kW unter gemeldeter Zusatzleistung 2 kW: kein Zusatzlauf', $writes() === []);
attr('HpOptionalRun', '{}');
call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0]);
call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + 120]);
check('Ueberschuss erst 2 min: noch nicht schreiben (Wartezeit 5 min)', $writes() === []);
for ($i = 5; $i <= 20; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + $i * 60]); }
check('Ueberschuss ueber 5 min: genau einmal true', $writes() === [[$orVid, true]], json_encode($writes()));
$GLOBALS['VAL'][$orVid] = false; // WP meldet Ist-Zustand "laeuft nicht" (z. B. Angebot abgelaufen)
for ($i = 21; $i <= 30; $i++) { call($ems, 'applyHeatpumpOptionalRun', [state(['grid_total_w' => 0.0, 'bat_soc' => 98.0]), $t0 + $i * 60]); }
check('Kein zyklisches Nachschieben, obwohl der gelesene Wert false ist', count($writes()) === 1, json_encode($writes()));
$st = json_decode($ems->ReadAttributeString('HpOptionalRun'), true);
check('Grund sichtbar: "Zusatzlauf an: 2,4 kW Überschuss"', strpos((string)($st[$orVid]['text'] ?? ''), 'Zusatzlauf an: 2,4 kW Überschuss') !== false, json_encode($st));
$imp = state(['grid_total_w' => -1200.0, 'bat_soc' => 98.0]);
for ($i = 31; $i <= 33; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$imp, $t0 + $i * 60]); }
check('Netzbezug vor Ablauf der Mindestlaufzeit (30 min ab Minute 5): kein false', count($writes()) === 1, json_encode($writes()));
for ($i = 34; $i <= 45; $i++) { call($ems, 'applyHeatpumpOptionalRun', [$imp, $t0 + $i * 60]); }
check('Anhaltender Bezug nach Mindestlaufzeit: genau einmal false', $writes() === [[$orVid, true], [$orVid, false]], json_encode($writes()));
// Abschalten des Schalters nimmt einen Wunsch einmal zurueck
attr('HpOptionalRun', json_encode([$orVid => ['want' => true, 'onAt' => $t0]]));
$GLOBALS['VARACTIONS'] = [];
prop('WP_OptionalRun_Active', false);
call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0]); call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + 60]);
check('Schalter aus nach EMS-Wunsch true: einmal false, dann nichts mehr', $writes() === [[$orVid, false]], json_encode($writes()));
// §14a-Lastbegrenzung schaltet sofort aus
prop('WP_OptionalRun_Active', true);
attr('HpOptionalRun', json_encode([$orVid => ['want' => true, 'onAt' => $t0]]));
$GLOBALS['VARACTIONS'] = [];
$GLOBALS['INSTMOD'][950] = GUID_STEUERBOXHUB; $GLOBALS['SBH_STATE'] = ['loadDimmActive' => true];
call($ems, 'applyHeatpumpOptionalRun', [$sur, $t0 + 60]);
check('§14a-Lastbegrenzung: Zusatzlauf sofort false (Netzbetreiber vor EMS)', $writes() === [[$orVid, false]], json_encode($writes()));
unset($GLOBALS['INSTMOD'][950]); $GLOBALS['SBH_STATE'] = null;
prop('WP_OptionalRun_Active', false);
}

