<?php
// Kapitel des EMS-Pruefstands: Bedarfsabsicherung, manuelle Eintraege, Verbund-Gesundheit, Store-Review, GridImportOutlook, Rundwirkungsgrade.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n20z) Bedarfsabsicherung und manuelle Plan-Eintraege (0.73.0/0.74.0, Dietmar 05.10.2026)\n";
$ems = freshEms();
$ctxA = ['avgHouseW' => 300.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'capKwh' => 40.0, 'chargeKw' => 20.0, 'chargeCurve' => [],
    'dischargeKw' => 20.0, 'maxW' => 34500.0, 'cycleCost' => 0.031, 'spread' => 0.03, 'feedTariff' => 0.1836];
prop('BAT_SOC_Safety_Margin_Pct', 10); prop('PLAN_Abend_MinSoc', 0); prop('PLAN_Bedarf_Aktiv', true);
// Kurve ueber 192 Slots: Tag (Slot 28-72 = 07-18 Uhr) PV 4000 W, sonst 0; Last flach; heute + morgen
$mk = function (float $pvW, float $loadW) {
    $pv = array_fill(0, 192, 0.0); $ld = array_fill(0, 192, $loadW);
    foreach ([[28, 72], [124, 168]] as [$a, $b]) { for ($i = $a; $i < $b; $i++) { $pv[$i] = $pvW; } }
    return [$pv, $ld];
};
$p192 = array_fill(0, 192, 0.40); foreach (range(68, 76) as $i) { $p192[$i] = 0.55; } foreach ([4,5,6,7] as $i) { $p192[$i] = 0.20; } foreach (range(80, 87) as $i) { $p192[$i] = 0.20; }
// 1) Leichte Last (300 W): kein Engpass -> kein Einkauf
[$pv, $ld] = $mk(4000.0, 300.0);
check('Bedarf: leichte Last, PV fuellt -> kein Einkauf', call($ems, 'bedarfInsuranceSlots', [$p192, $pv, $ld, 40, 60.0, $ctxA]) === []);
// 2) Heizlast (3500 W, Waermepumpe) und Batterie 40 %: Engpass in der Nacht -> Einkauf VOR dem Engpass, nur Slots unter der Lohnschwelle
[$pv, $ld] = $mk(6000.0, 3500.0);
$ab = call($ems, 'bedarfInsuranceSlots', [$p192, $pv, $ld, 60, 40.0, $ctxA]);
check('Bedarf: hohe Heizlast, Akku 40 % -> Slots vorgemerkt', count($ab) >= 1, json_encode($ab) . ' rp=' . var_export(call($ems, 'replacementPriceEur', [$p192, 60]), true) . ' conv=' . call($ems, 'convEff') . ' cc=' . var_export($ctxA['cycleCost'], true));
check('Bedarf: nur Slots unterhalb der Lohnschwelle (20-ct-Slots)', empty(array_filter(array_keys($ab), fn($i) => $p192[$i] >= 0.30)), json_encode(array_keys($ab)));
$firstBuy = min(array_keys($ab));
check('Bedarf: Einkauf liegt vor dem Engpass (Slot >= jetzt)', $firstBuy >= 60);
// 3) Gegenprobe: gleiche Last, Akku schon 100 % und morgen frueh genug PV -> deutlich weniger/kein Einkauf
$abFull = call($ems, 'bedarfInsuranceSlots', [$p192, $pv, $ld, 60, 100.0, $ctxA]);
check('Bedarf: voller Akku braucht weniger Einkauf als ein halber', count($abFull) <= count($ab), count($abFull) . ' vs ' . count($ab));
// 3b) Live-Befund 24.10.2026: Akku um Mitternacht fast leer, Nacht 00:00-01:45 11-11,7 ct, ab 02:00 10,5 ct -> gekauft wird ab 02:00, nicht ab 00:00
$pN = array_fill(0, 192, 0.18); foreach (range(88, 95) as $i) { $pN[$i] = 0.20; }
foreach ([0.117, 0.116, 0.112, 0.111, 0.114, 0.111, 0.110, 0.109] as $k => $v) { $pN[96 + $k] = $v; }
for ($i = 104; $i < 120; $i++) { $pN[$i] = 0.105; } for ($i = 162; $i < 180; $i++) { $pN[$i] = 0.29; }
$pvN = array_fill(0, 192, 0.0); for ($i = 132; $i < 160; $i++) { $pvN[$i] = 2500.0; }
$ldN = array_fill(0, 192, 900.0);
$abN = call($ems, 'bedarfInsuranceSlots', [$pN, $pvN, $ldN, 92, 15.0, $ctxA]);
check('Bedarf, Akku fast leer vor einer guenstigen Nacht: kein Einkauf von 00:00-01:45 zu 11-11,7 ct (das Nachtfenster laedt ab 02:00 fuer 10,5 ct)', empty(array_filter(array_keys($abN), fn($i) => $pN[$i] > 0.106)), json_encode(array_map(fn($i) => [$i, $pN[$i]], array_keys($abN))));
$pvZ = array_fill(0, 192, 0.0);
$abH = call($ems, 'bedarfInsuranceSlots', [$pN, $pvZ, $ldN, 92, 50.0, $ctxA]);
check('Bedarf: Akku 50 %, Nacht und Tag billiger als die gespeicherte Energie (gehalten), Abend 4 kWh: kein Einkauf noetig', $abH === [], json_encode($abH));
// 4) Ohne Preisvorteil (Abendpreis = Einkauf) nichts
check('Bedarf: ohne Preisvorteil kein Einkauf', call($ems, 'bedarfInsuranceSlots', [array_fill(0, 192, 0.30), $pv, $ld, 60, 40.0, $ctxA]) === []);
// 4c) 0.102.0 Akku-Optimierer (Dietmar 13.11.2026: keine feste Preisgrenze entscheidet ueber den Einkauf)
$cnt = function (array $o, string $a) { return count(array_filter($o, fn($x) => $x['act'] === $a)); };
$pvZ0 = array_fill(0, 192, 0.0); $ld3 = array_fill(0, 192, 3000.0);
$pO = array_fill(0, 192, 0.42); foreach (range(44, 51) as $i) { $pO[$i] = 0.355; } foreach (range(68, 95) as $i) { $pO[$i] = 0.49; }
$oP = call($ems, 'optimizeBattery', [array_slice($pO, 0, 96), $pvZ0, $ld3, 40, 30.0, $ctxA]);
$chg = array_keys(array_filter($oP, fn($x) => $x['act'] === 'charge'));
check('Optimierer (Paul 14.11.): Bestpreis 35,5 ct ueber jeder alten Schwelle, Abend 49 ct -> laedt in den 35,5-ct-Slots, nie zu 42 ct',
    !empty($chg) && empty(array_filter($chg, fn($i) => $pO[$i] > 0.36)), json_encode($chg));
check('Optimierer: haelt (Haus aus dem Netz) zu 42 ct vor dem Abend statt den Akku zu leeren', $cnt($oP, 'hold') > 0, json_encode(array_count_values(array_column($oP, 'act'))));
$pD = array_fill(0, 96, 0.45); foreach (range(40, 47) as $i) { $pD[$i] = 0.41; } foreach (range(68, 95) as $i) { $pD[$i] = 0.93; }
$oD = call($ems, 'optimizeBattery', [$pD, $pvZ0, $ld3, 30, 20.0, $ctxA]);
check('Optimierer (Dietmars Beispiel): Tagesbestpreis 41 ct, abends 93 ct -> kauft zu 41 ct', !empty(array_filter($oD, fn($x, $i) => $x['act'] === 'charge' && $pD[$i] == 0.41, ARRAY_FILTER_USE_BOTH)), json_encode(array_count_values(array_column($oD, 'act'))));
$oF = call($ems, 'optimizeBattery', [array_fill(0, 96, 0.30), $pvZ0, $ld3, 0, 60.0, $ctxA]);
check('Optimierer: flacher Preis -> nur Automatik (Laden/Halten bringt nichts)', $cnt($oF, 'auto') === count($oF), json_encode(array_count_values(array_column($oF, 'act'))));
$pN2 = array_fill(0, 96, 0.45); foreach (range(8, 20) as $i) { $pN2[$i] = 0.20; }
$oN = call($ems, 'optimizeBattery', [$pN2, $pvZ0, $ld3, 0, 15.0, $ctxA]);
check('Optimierer: billige Nacht 20 ct, Tag 45 ct -> laedt nachts', $cnt($oN, 'charge') > 0 && empty(array_filter($oN, fn($x, $i) => $x['act'] === 'charge' && $pN2[$i] > 0.2, ARRAY_FILTER_USE_BOTH)), json_encode(array_count_values(array_column($oN, 'act'))));
check('Optimierer: Reserve (10 %) wird nie unterschritten', min(array_column($oN, 'soc')) >= 10.0 - 0.01 && min(array_column($oP, 'soc')) >= 10.0 - 0.01);
$fz = []; foreach (range(44, 51) as $i) { $fz[$i] = 'charge'; }
$oZ = call($ems, 'optimizeBattery', [array_slice($pO, 0, 96), $pvZ0, $ld3, 40, 30.0, $ctxA, $fz]);
check('Optimierer mit manuellem Netzladen 11-13 Uhr: Vorgabe wird uebernommen, SOC danach hoeher als ohne (Paul 13.11.: Plan sprang von 72 auf 41 %)',
    $oZ[44]['act'] === 'charge' && $oZ[51]['act'] === 'charge' && $oZ[52]['soc'] > 40.0, json_encode([$oZ[51] ?? null, $oZ[52] ?? null]));
// 0.103.0: geschaetzte Preise hinter dem bekannten Horizont
$pE = array_fill(0, 96, 0.30); for ($i = 96; $i < 192; $i++) { $pE[$i] = ($i % 96 >= 68 && $i % 96 < 88) ? 0.60 : 0.10; }
$oE = call($ems, 'optimizeBattery', [$pE, array_fill(0, 192, 0.0), array_fill(0, 192, 1500.0), 0, 60.0, $ctxA, [], 96]);
check('Geschaetzte Preise: auf geschaetzte 10-ct-Slots wird nie aktiv geladen (nur Automatik/Halten)',
    empty(array_filter($oE, fn($x, $i) => $i >= 96 && $x['act'] === 'charge', ARRAY_FILTER_USE_BOTH)), json_encode(array_count_values(array_column(array_slice($oE, 96, null, true), 'act'))));
$pK = array_fill(0, 96, 0.30); $pK2 = $pK; for ($i = 96; $i < 192; $i++) { $pK2[$i] = ($i % 96 >= 68 && $i % 96 < 88) ? 0.60 : 0.30; }
$oK = call($ems, 'optimizeBattery', [$pK, array_fill(0, 192, 0.0), array_fill(0, 192, 1500.0), 0, 60.0, $ctxA]);
$oK2 = call($ems, 'optimizeBattery', [$pK2, array_fill(0, 192, 0.0), array_fill(0, 192, 1500.0), 0, 60.0, $ctxA, [], 96]);
check('Geschaetzter teurer Abend morgen (60 ct): heute wird Akkuenergie aufgehoben (Halten), ohne Schaetzung nicht',
    $cnt($oK2, 'hold') > 0 && $cnt($oK, 'hold') === 0 && $oK2[95]['soc'] > $oK[95]['soc'], json_encode([$cnt($oK, 'hold'), $cnt($oK2, 'hold'), $oK[95]['soc'], $oK2[95]['soc']]));
// 0.105.3: grosser Akku (Befund Paul 17.11.2026, 114 kWh): Die Restenergie darf nicht so bewertet werden, als koenne der Akku
// am geschaetzten Tag nie nachgeladen werden -- sonst haelt der Plan den ganzen Tag bei 33 ct, obwohl der Abend den Akku kaum leert.
$ctxP = array_merge($ctxA, ['capKwh' => 114.0, 'chargeKw' => 17.0, 'dischargeKw' => 17.0, 'maxW' => 29900, 'socMin' => 10.0, 'socReserve' => 0.0, 'chargeCurve' => []]);
$pP = []; for ($i = 0; $i < 192; $i++) { $h = $i % 96; $pP[$i] = $h < 24 ? 0.28 : ($h < 61 ? 0.33 : 0.42); }
$oP1 = call($ems, 'optimizeBattery', [$pP, array_fill(0, 192, 0.0), array_fill(0, 192, 2500.0), 24, 100.0, $ctxP, [], 96]);
$dayHold = count(array_filter($oP1, fn($x, $i) => $i >= 24 && $i < 61 && $x['act'] === 'hold', ARRAY_FILTER_USE_BOTH));
check('Grosser Akku (114 kWh, voll): tagsueber bei 33 ct wird entladen statt gehalten, wenn die naechste Nacht zu 28 ct nachladen kann',
    $dayHold === 0, json_encode([$dayHold, $oP1[60]['soc'] ?? null, $oP1[95]['soc'] ?? null]));
check('Grosser Akku: auch mit der Aenderung kein Laden auf geschaetzte Preise im Plan',
    empty(array_filter($oP1, fn($x, $i) => $i >= 96 && $x['act'] === 'charge', ARRAY_FILTER_USE_BOTH)));
$an = EMS_Planner::anchorEstimate(...[[1000 => 0.40, 1900 => 0.45], array_combine(range(0, 59), array_fill(0, 60, 0.30)), array_combine(range(0, 59), array_fill(0, 60, 0.354))]);
check('Schaetzung wird am bekannten Tag ausgerichtet: echte Preise 5,4 ct unter der Methode -> Schaetzung 5,4 ct tiefer, Verlauf bleibt',
    abs($an[1000] - 0.346) < 1e-9 && abs($an[1900] - 0.396) < 1e-9, json_encode($an));
$an2 = EMS_Planner::anchorEstimate(...[[1000 => 0.40], array_fill(0, 20, 0.30), array_fill(0, 20, 0.354)]);
check('Zu wenig Vergleichs-Slots (< 48): Schaetzung unveraendert', $an2 === [1000 => 0.40]);
check('Optimierer: ohne Preise leer', call($ems, 'optimizeBattery', [[], $pvZ0, $ld3, 0, 50.0, $ctxA]) === []);
// 5) Schalter aus
prop('PLAN_Bedarf_Aktiv', false);
check('Bedarf: Schalter aus -> nichts', call($ems, 'bedarfInsuranceSlots', [$p192, $pv, $ld, 60, 40.0, $ctxA]) === []);
prop('PLAN_Bedarf_Aktiv', true);
// 6) Marge wirkt: groessere Marge -> mindestens so viel Einkauf
prop('BAT_SOC_Safety_Margin_Pct', 40);
$abM = call($ems, 'bedarfInsuranceSlots', [$p192, $pv, $ld, 60, 40.0, $ctxA]);
check('Bedarf: groessere Sicherheitsmarge kauft mindestens so viel', count($abM) >= count($ab), count($abM) . ' vs ' . count($ab));
prop('BAT_SOC_Safety_Margin_Pct', 10);
// 7) Untere Grenze zur Abendstunde
[$pv2, $ld2] = $mk(1000.0, 300.0);
$p192b = $p192; foreach (range(50, 53) as $i) { $p192b[$i] = 0.20; }
prop('PLAN_Abend_MinSoc', 80); prop('PLAN_Abend_Hour', 17);
$abMin = call($ems, 'bedarfInsuranceSlots', [$p192b, $pv2, $ld2, 40, 30.0, $ctxA]);
check('Bedarf: optionale untere Grenze zum Abend erzeugt Einkauf vor dem Abend', !empty($abMin) && max(array_keys($abMin)) < 68, json_encode(array_keys($abMin)));
prop('PLAN_Abend_MinSoc', 0);
$r = call($ems, 'bedarfInsurancePlanSlot', [5, 0.20, 40.0, 70.0, $ctxA]);
check('Absicherungs-Slot: Netzladen (Modus 11), Marker ab + Zielwert, SOC steigt', $r['plan']['op'] === EMS_OP_NET_CHARGE && $r['plan']['gw'] === GW_MODE_BAT_CHARGE && !empty($r['plan']['ab']) && $r['plan']['abT'] === 70.0 && $r['soc'] > 40.0, json_encode($r['plan']));

$today = date('Y-m-d'); $tomorrow = date('Y-m-d', strtotime('tomorrow'));
prop('PLAN_Manual', json_encode([
    ['Date' => $today, 'From' => '12:00', 'To' => '13:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 8000],
    ['Date' => '', 'From' => '22:00', 'To' => '', 'Action' => EMS_OP_HOLD, 'Power' => 0],
    ['Date' => '2020-01-01', 'From' => '10:00', 'To' => '11:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 0],
    ['Date' => $tomorrow, 'From' => '06:30', 'To' => '07:00', 'Action' => EMS_OP_AUTO, 'Power' => 0],
    ['Date' => $today, 'From' => 'kaputt', 'To' => '13:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 0],
]));
$m = call($ems, 'manualPlanEntries');
check('Manuell: heute 12:00-13:00 = Slots 48-51 Netz laden 8000 W', isset($m[0][48], $m[0][51]) && !isset($m[0][47], $m[0][52]) && $m[0][48]['op'] === EMS_OP_NET_CHARGE && $m[0][48]['power'] === 8000, json_encode(array_keys($m[0])));
check('Manuell: ohne Bis gilt der Eintrag bis Mitternacht, ohne Datum an beiden Tagen', isset($m[0][88], $m[0][95], $m[1][88], $m[1][95]) && $m[0][88]['op'] === EMS_OP_HOLD);
check('Manuell: vergangenes Datum und ungueltige Zeit werden ignoriert', !isset($m[0][40]) && !isset($m[1][40]) && count(array_filter(array_keys($m[0]), fn($i) => $i < 48)) === 0);
// Datumsformate (Dietmar 20.10.2026: "21.10.2026" wurde stumm ignoriert, weil nur JJJJ-MM-TT erkannt wurde)
check('Datum: JJJJ-MM-TT, TT.MM.JJJJ, T.M.JJJJ und TT.MM. werden gelesen, leer = jeden Tag', call($ems, 'manualEntryDate', ['2026-10-21']) === '2026-10-21' && call($ems, 'manualEntryDate', ['21.10.2026']) === '2026-10-21' && call($ems, 'manualEntryDate', ['1.2.2027']) === '2027-02-01' && call($ems, 'manualEntryDate', ['21.10.']) === date('Y') . '-10-21' && call($ems, 'manualEntryDate', ['  ']) === '');
check('Datum: Unsinn und ungueltige Kalendertage sind nicht lesbar (null)', call($ems, 'manualEntryDate', ['morgen']) === null && call($ems, 'manualEntryDate', ['31.02.2026']) === null && call($ems, 'manualEntryDate', ['2026-13-01']) === null);
$deTomorrow = date('d.m.Y', strtotime('tomorrow'));
prop('PLAN_Manual', json_encode([['Date' => $deTomorrow, 'From' => '00:00', 'To' => '06:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 20000]]));
$mDe = call($ems, 'manualPlanEntries');
check('Eintrag mit deutschem Datum (morgen) landet im Morgen-Plan, Slots 0-23, nicht heute', isset($mDe[1][0], $mDe[1][23]) && !isset($mDe[1][24]) && count($mDe[0]) === 0 && $mDe[1][0]['power'] === 20000, json_encode(array_keys($mDe[1])));
prop('PLAN_Manual', json_encode([['Date' => '21.10.2023', 'From' => '00:00', 'To' => '06:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 20000]]));
$mOld = call($ems, 'manualPlanEntries');
check('Eintrag mit Datum 21.10.2023: ignoriert (nichts im Plan)', count($mOld[0]) === 0 && count($mOld[1]) === 0);
// Statuszeilen im Formular
prop('PLAN_Manual', json_encode([
    ['Date' => '21.10.2023', 'From' => '00:00', 'To' => '06:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 20000],
    ['Date' => $deTomorrow, 'From' => '00:00', 'To' => '06:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 0],
    ['Date' => $today, 'From' => '12:00', 'To' => '13:00', 'Action' => EMS_OP_HOLD, 'Power' => 0],
    ['Date' => '', 'From' => '22:00', 'To' => '', 'Action' => EMS_OP_AUTO, 'Power' => 0],
    ['Date' => 'irgendwann', 'From' => '10:00', 'To' => '11:00', 'Action' => EMS_OP_AUTO, 'Power' => 0],
    ['Date' => $today, 'From' => 'kaputt', 'To' => '13:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 0],
    ['Date' => date('d.m.Y', strtotime('+5 days')), 'From' => '10:00', 'To' => '11:00', 'Action' => EMS_OP_HOLD, 'Power' => 0],
]));
$sl = call($ems, 'manualPlanStatusLines');
check('Status: sieben Zeilen, vergangenes Datum und kaputte Zeit/Datum als nicht ok, der Rest ok', count($sl) === 7 && $sl[0]['ok'] === false && $sl[1]['ok'] === true && $sl[2]['ok'] === true && $sl[3]['ok'] === true && $sl[4]['ok'] === false && $sl[5]['ok'] === false && $sl[6]['ok'] === true, json_encode(array_column($sl, 'ok')));
check('Status: Vergangenheit nennt das Datum und "Vergangenheit", morgen nennt "morgen", unlesbares Datum nennt den Text', strpos($sl[0]['text'], '21.10.2023') !== false && strpos($sl[0]['text'], 'Vergangenheit') !== false && strpos($sl[1]['text'], 'morgen') !== false && strpos($sl[4]['text'], 'irgendwann') !== false, json_encode(array_column($sl, 'text')));
prop('PLAN_Manual', '[]');
check('Status: ohne Eintraege keine Zeilen', call($ems, 'manualPlanStatusLines') === []);
check('Manuell: morgen 06:30-07:00 = Slots 26-27 Automatik', isset($m[1][26], $m[1][27]) && !isset($m[1][28]) && $m[1][26]['op'] === EMS_OP_AUTO);
$mc = call($ems, 'manualPlanSlot', [48, 0.2, 0.0, 50.0, ['op' => EMS_OP_NET_CHARGE, 'power' => 8000], $ctxA]);
check('Manueller Ladeslot: Leistung 8000 W, Marker man, SOC steigt', $mc['plan']['power'] === 8000 && !empty($mc['plan']['man']) && $mc['soc'] > 50.0, json_encode($mc['plan']));
$mh = call($ems, 'manualPlanSlot', [88, 0.2, 0.0, 50.0, ['op' => EMS_OP_HOLD, 'power' => 0], $ctxA]);
check('Manueller Haltslot: Akku halten, SOC bleibt', $mh['plan']['op'] === EMS_OP_HOLD && abs($mh['soc'] - 50.0) < 1e-9);

// Laufzeit: manueller Slot wird auch OHNE Arbitrage-Tag ausgefuehrt (Preis 0,30 > Verguetung)
pricesToday(0.30);
$GLOBALS['ATTR'][EMS_IID]['DayPlan'] = json_encode(array_fill(0, 96, ['op' => EMS_OP_NET_CHARGE, 'gw' => GW_MODE_BAT_CHARGE, 'power' => 5000, 'man' => 1, 'reason' => 'Manueller Eintrag: Netz laden', 'price' => 0.3, 'soc' => 60]));
$d = call($ems, 'optimize', [state(['bat_soc' => 50.0, 'pv_total_w' => 0.0, 'house_pow_w' => 300.0])]);
check('Laufzeit: manueller Ladeslot gilt auch ohne Arbitrage-Tag (Modus 11)', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_BAT_CHARGE && $d['gw_enable'] === true && $d['source'] === 'tagesplan', fmt($d));
check('Laufzeit: manueller Ladeslot traegt die Kennzeichnung manual bis zur Entscheidung (Waechter-Ausnahme 0.89.2)', !empty($d['manual']), fmt($d));
$GLOBALS['ATTR'][EMS_IID]['DayPlan'] = json_encode(array_fill(0, 96, ['op' => EMS_OP_NET_CHARGE, 'gw' => GW_MODE_BAT_CHARGE, 'power' => 5000, 'reason' => 'Plan', 'price' => 0.3, 'soc' => 60]));
$d = call($ems, 'optimize', [state(['bat_soc' => 50.0, 'pv_total_w' => 0.0, 'house_pow_w' => 300.0])]);
check('Gegenprobe: derselbe Slot OHNE man-Marker wird an einem Nicht-Arbitrage-Tag nicht ausgefuehrt', isNativeAuto($d), fmt($d));
// Absicherungsslot: Mindest-SOC real erreicht -> kein Kauf
$GLOBALS['ATTR'][EMS_IID]['DayPlan'] = json_encode(array_fill(0, 96, ['op' => EMS_OP_NET_CHARGE, 'gw' => GW_MODE_BAT_CHARGE, 'power' => 5000, 'ab' => 1, 'abT' => 60.0, 'reason' => 'Bedarfsabsicherung', 'price' => 0.19, 'soc' => 60]));
$d = call($ems, 'optimize', [state(['bat_soc' => 50.0, 'pv_total_w' => 0.0, 'house_pow_w' => 300.0])]);
check('Laufzeit: Absicherungsslot kauft, solange der reale SOC unter dem geplanten Zielwert liegt', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_enable'] === true, fmt($d));
$d = call($ems, 'optimize', [state(['bat_soc' => 65.0, 'pv_total_w' => 0.0, 'house_pow_w' => 300.0])]);
check('Laufzeit: Absicherungsslot kauft NICHT mehr, wenn der reale SOC ueber dem Zielwert liegt', isNativeAuto($d), fmt($d));

// ===========================================================================
echo "\n21z) Verbund-Gesundheit: bewusst deaktivierte Instanz (Status 104) ist kein Fehler (0.74.1)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][7101] = 'x'; $GLOBALS['INSTMOD'][7102] = 'x'; $GLOBALS['INSTMOD'][7103] = 'x';
$GLOBALS['OBJ'][7101] = ['ObjectName' => 'Tibber Hauptinstanz']; $GLOBALS['OBJ'][7102] = ['ObjectName' => 'Tibber Demo']; $GLOBALS['OBJ'][7103] = ['ObjectName' => 'Kaputt'];
$GLOBALS['INSTSTATUS'][7101] = 102; $GLOBALS['INSTSTATUS'][7102] = 104; $GLOBALS['INSTSTATUS'][7103] = 201;
attr('PartnerCache', json_encode(['tibber' => [['instanceID' => 7101], ['instanceID' => 7102]], 'charger' => [['instanceID' => 7103]]]));
$h = call($ems, 'GetFederationHealth');
check('Status 104 zaehlt nicht als auffaellig', count($h['unhealthy']) === 1 && $h['unhealthy'][0]['instanceID'] === 7103, json_encode($h['unhealthy']));
check('Gesamtzahl ohne die deaktivierte Instanz (1 von 2 gesund)', $h['total'] === 2 && $h['healthyCount'] === 1, $h['summary']);
check('Deaktivierte Instanz wird getrennt gemeldet', strpos($h['summary'], 'deaktiviert: ') !== false && $h['inactiveCount'] === 1, $h['summary']);
check('Echter Fehler (Status 201) bleibt auffaellig', strpos($h['summary'], '(Status 201)') !== false);
unset($GLOBALS['INSTSTATUS'][7102], $GLOBALS['INSTSTATUS'][7103]);

// ===========================================================================
echo "\n27z) Store-Review-Checkliste Punkt 15: kein IPS_LogMessage in module.php\n";
$src = file_get_contents(EMS_TOOLS_DIR . '/../EMS/module.php');
check('module.php enthaelt kein IPS_LogMessage( (nur $this->LogMessage)', strpos($src, 'IPS_LogMessage(') === false);
check('emsLog() nutzt $this->LogMessage', strpos($src, '$this->LogMessage($message, KL_MESSAGE)') !== false);
check('Log-/Formulartexte sind herstellerneutral (kein "Goodwe ->" und kein "GoodWe-Exportmodus" mehr)', strpos($src, "'Goodwe -> ") === false && strpos($src, "- Goodwe Modus") === false && strpos(file_get_contents(EMS_TOOLS_DIR . '/../EMS/form.json'), 'GoodWe-Exportmodus') === false);

// ===========================================================================
echo "\n28z) EMS_GetGridImportOutlook (Berater fuer die Preis-Sperre der Heizungs-Automatik, 18.10.2026)\n";
$ems = freshEms();
$ctxO = ['capKwh' => 40.0, 'chargeKw' => 20.0, 'dischargeKw' => 20.0, 'chargeCurve' => [], 'maxW' => 34500.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'margin' => 10.0];
$flat = fn(float $v) => array_fill(0, 192, $v);
$core = fn(float $soc, float $pvW, float $loadW, float $price, float $delta, array $ops = [], ?array $pricesArr = null) =>
    call($ems, 'gridImportOutlookCore', [$pricesArr ?? $flat($price), $flat($pvW), $flat($loadW), $ops, 38, 40, 48, $soc, $delta, $ctxO]);
$o = $core(80.0, 0.0, 1000.0, 0.30, 0.0);
check('Volle Batterie (80 %), 1 kW Last ueber 2 h: kein Netzbezug, Batterie deckt, Reserve gewahrt', $o['expectedGridImportKwh'] == 0.0 && $o['batteryCoversLoad'] === true && $o['reserveRespected'] === true && $o['avgPriceCt'] === null, json_encode($o));
$o = $core(12.0, 0.0, 1000.0, 0.45, 0.0);
check('Fast leere Batterie (12 %, Reserve 10 %; die zwei Slots VOR dem Fenster leeren sie schon): Netzbezug im Fenster ca. 1,7 kWh zu 45 ct, Batterie deckt nicht, Reserve nicht gewahrt', $o['expectedGridImportKwh'] > 1.5 && $o['expectedGridImportKwh'] < 2.01 && $o['batteryCoversLoad'] === false && $o['reserveRespected'] === false && $o['avgPriceCt'] === 45.0 && $o['maxPriceCt'] === 45.0, json_encode($o));
$o = $core(12.0, 0.0, 1000.0, 0.45, -1000.0);
check('Mit -1000 W (Last entfaellt, z. B. WP gesperrt): kein Netzbezug im Szenario, vermiedener Bezug = Baseline', $o['expectedGridImportKwh'] == 0.0 && $o['avoidedImportKwh'] > 1.0 && abs($o['avoidedImportKwh'] - $o['baselineGridImportKwh']) < 0.001 && $o['baseline']['batteryCoversLoad'] === false, json_encode($o));
$o = $core(80.0, 0.0, 1000.0, 0.45, -1000.0);
check('Volle Batterie: Sperre vermeidet nichts (avoidedImportKwh 0), baseline.batteryCoversLoad=true -> HeishaMon sperrt nicht', $o['avoidedImportKwh'] == 0.0 && $o['baseline']['batteryCoversLoad'] === true && $o['baseline']['reserveRespected'] === true, json_encode($o));
$o = $core(30.0, 0.0, 1000.0, 0.45, 3000.0);
check('Zusatzlast (+3 kW) gilt nur IM Fenster: vorher laeuft die Batterie nur mit der Normallast (Netzbezug ca. 0,9 kWh, nicht ca. 2,4)', $o['expectedGridImportKwh'] > 0.5 && $o['expectedGridImportKwh'] < 1.4 && $o['avoidedImportKwh'] == 0.0, json_encode([$o['expectedGridImportKwh'], $o['baselineGridImportKwh']]));
$o = $core(12.0, 2000.0, 1000.0, 0.45, 0.0);
check('PV (2 kW) deckt die Last (1 kW): kein Netzbezug, SOC steigt', $o['expectedGridImportKwh'] == 0.0 && $o['slots'][count($o['slots']) - 1]['socEndPct'] > 12.0, json_encode($o['slots'][0]));
$o = $core(12.0, 0.0, 1000.0, 0.45, 0.0, [38 => 10000.0, 39 => 10000.0]);
$o0 = $core(12.0, 0.0, 1000.0, 0.45, 0.0);
check('Geplantes Netzladen vor dem Fenster (Slots 38/39): weniger Netzbezug im Fenster als ohne Ladung', $o['expectedGridImportKwh'] < $o0['expectedGridImportKwh'] - 0.5, $o['expectedGridImportKwh'] . ' vs ' . $o0['expectedGridImportKwh']);
$o = $core(12.0, 0.0, 1000.0, 0.45, 0.0, [42 => 10000.0]);
check('Geplantes Netzladen im Fenster wird ausgewiesen (plannedGridChargeKwh > 0)', $o['plannedGridChargeKwh'] > 1.0, json_encode($o['plannedGridChargeKwh']));
$pr = $flat(0.45); for ($i = 44; $i < 48; $i++) { $pr[$i] = null; }
$o = $core(12.0, 0.0, 1000.0, 0.45, 0.0, [], $pr);
check('Preise fehlen in Teilen des Fensters: Durchschnitt nur ueber bekannte Slots, Netzbezug zaehlt trotzdem', $o['expectedGridImportKwh'] > 1.0 && $o['avgPriceCt'] === 45.0, json_encode($o));
$o = call($ems, 'gridImportOutlookCore', [$flat(0.30), $flat(0.0), $flat(1000.0), [], 40, 38, 48, 12.0, 0.0, $ctxO]);
check('Fensterbeginn vor jetzt wird auf jetzt gekuerzt (8 Slots statt 10)', count($o['slots']) === 8, (string)count($o['slots']));
prop('BAT_Active', true);
$now = time();
$r = call($ems, 'GetGridImportOutlook', [$now + 60, $now + 30, 0.0, false]);
check('Fenster leer: ok=false mit Grund (HeishaMon faellt auf die einfache Regel zurueck)', $r['ok'] === false && strpos($r['reason'], 'Fenster') !== false, json_encode($r));
$r = call($ems, 'GetGridImportOutlook', [$now, $now + 13 * 3600, 0.0, false]);
check('Fenster ueber 12 h: ok=false', $r['ok'] === false && strpos($r['reason'], '12') !== false, json_encode($r));
$r = call($ems, 'GetGridImportOutlook', [$now, $now + 3600, 0.0, true]);
check('Ohne PV-Prognose: ok=false (nicht raten)', $r['ok'] === false && strpos($r['reason'], 'PV-Prognose') !== false, json_encode($r));
prop('BAT_Active', false);
$r = call($ems, 'GetGridImportOutlook', [$now, $now + 3600, 0.0, false]);
check('Batteriespeicher im EMS aus: ok=false', $r['ok'] === false && strpos($r['reason'], 'Batterie') !== false, json_encode($r));

// 0.99.2: Symcon 9.1 lehnt Wochenplan-Aktions-ID 0 ab -- Bestand behaelt op = ID, neue Plaene op + 1
if (!function_exists('IPS_GetEvent')) {
    function IPS_GetEvent($id) { return $GLOBALS['EVT'][$id] ?? array(); }
    function IPS_SetEventScheduleAction($id, $aid, $name, $color, $script) {
        if ($aid <= 0) { $GLOBALS['EVTWARN'][] = $aid; return false; }
        $GLOBALS['EVT'][$id]['ScheduleActions'][$aid] = array('ID' => $aid, 'Name' => $name); return true;
    }
    if (!function_exists('IPS_SetEventScheduleGroup')) { function IPS_SetEventScheduleGroup($id, $g, $d) { return true; } }
    function IPS_SetEventScheduleGroupPoint($id, $g, $p, $h, $m, $s, $aid) { $GLOBALS['EVT'][$id]['Points'][$p] = $aid; return true; }
}
if (method_exists('EMS', 'dayPlanLegacyActions')) {
$GLOBALS['OBJ'][90001] = true; $GLOBALS['OBJ'][90002] = true;
$GLOBALS['EVT'][90001] = array('ScheduleActions' => array(0 => array('ID' => 0, 'Name' => 'Automatik'), 1 => array('ID' => 1, 'Name' => 'x')));
$GLOBALS['EVT'][90002] = array('ScheduleActions' => array());
$GLOBALS['EVTWARN'] = array();
check('Wochenplan Bestand (Aktion 0 vorhanden) erkannt, neuer Plan nicht', call($ems, 'dayPlanLegacyActions', [90001]) === true && call($ems, 'dayPlanLegacyActions', [90002]) === false);
foreach (array(90001, 90002) as $evId) {
    $legacy = call($ems, 'dayPlanLegacyActions', [$evId]);
    foreach (call($ems, 'getPlanActions') as $a) { $aid = $legacy ? $a['op'] : $a['op'] + 1; if ($aid > 0) { IPS_SetEventScheduleAction($evId, $aid, $a['name'], $a['color'], ''); } }
}
check('Keine Aktions-ID 0 mehr gesetzt (Symcon 9.1)', empty($GLOBALS['EVTWARN']), json_encode($GLOBALS['EVTWARN']));
check('Neuer Plan: Automatik = Aktion 1, Akku halten = Aktion 9', ($GLOBALS['EVT'][90002]['ScheduleActions'][1]['Name'] ?? '') === 'Automatik' && isset($GLOBALS['EVT'][90002]['ScheduleActions'][9]), json_encode(array_keys($GLOBALS['EVT'][90002]['ScheduleActions'])));
attr('DayPlanEventId', 90002);
$planAuto = array_fill(0, 96, array('op' => EMS_OP_AUTO));
call($ems, 'writeDayPlanEvent', [$planAuto]);
check('Neuer Plan: Punkte zeigen auf Aktion 1 statt 0', ($GLOBALS['EVT'][90002]['Points'][0] ?? null) === 1, json_encode($GLOBALS['EVT'][90002]['Points'][0] ?? null));
attr('DayPlanEventId', 90001);
call($ems, 'writeDayPlanEvent', [$planAuto]);
check('Bestands-Plan: Punkte bleiben auf Aktion 0', ($GLOBALS['EVT'][90001]['Points'][0] ?? null) === 0, json_encode($GLOBALS['EVT'][90001]['Points'][0] ?? null));
}

echo "\n8al) Rundwirkungsgrade PV 91 % / Netz 82 % als Standard (0.100.0, Nachweis an mehreren Speichersystemen)\n";
if (method_exists('EMS', 'rtGrid')) {
$GLOBALS['EMS_RAW_DEFAULTS'] = true;
$ems = freshEms();
check('Standard Netz-Rundwirkungsgrad 0,82, PV 0,91', abs(call($ems, 'rtGrid') - 0.82) < 1e-9 && abs(call($ems, 'rtPv') - 0.91) < 1e-9);
check('eta je AC-Wandlung = Wurzel 0,82 = 0,9055', abs(call($ems, 'effAc') - sqrt(0.82)) < 1e-9, (string)call($ems, 'effAc'));
check('eta PV-Laden = 0,91 / 0,9055 = 1,005 -> gedeckelt 1,0', abs(call($ems, 'effPv') - 1.0) < 1e-9, (string)call($ems, 'effPv'));
$ctxD = ['refMode' => 1, 'replacePrice' => 0.46, 'spread' => 0.03, 'cycleCost' => 0.0, 'feedTariff' => 0.1836];
check('Netzladegrenze = 46 ct x 0,82 x 0,9 - 3 ct = 30,95 ct', abs(call($ems, 'gridChargeLimitEur', [$ctxD]) - (0.46 * 0.82 * 0.9 - 0.03)) < 1e-6);
check('Referenz Verguetung: 18,36 x 0,9055 = 16,63 ct', abs(call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.1836, 'refMode' => 0]]) - 0.1836 * sqrt(0.82)) < 1e-6);
$nwD = ['end' => 24, 'charge' => [], 'n' => 0, 'cand' => 0];
$ctxH = array_merge($ctxD, ['socTargetNight' => 100.0, 'capKwh' => 40.0]);
check('Haltegrenze = 46 x 0,9055 x 0,9 - 3 = 34,49 ct: 34 ct haelt, 35 ct nicht', call($ems, 'nightWindowSlot', [4, 0.34, 50.0, $nwD, $ctxH]) !== null && call($ems, 'nightWindowSlot', [4, 0.35, 50.0, $nwD, $ctxH]) === null);
$ctxA = ['avgHouseW' => 4000.0, 'houseLoadSlots' => [], 'socMin' => 0.0, 'socReserve' => 10.0, 'capKwh' => 40.0, 'chargeKw' => 25.0, 'dischargeKw' => 25.0];
$rA = call($ems, 'simulateAutomatikSlot', [0, 0.0, 0.3, 50.0, $ctxA]);
check('Planung: 1 kWh Hauslast zieht 1/0,9055 = 1,104 kWh aus dem Akku (SOC 50 -> 47,24)', abs($rA['soc'] - (50.0 - 1.0 / sqrt(0.82) / 40.0 * 100.0)) < 0.01, json_encode($rA));
$rP = call($ems, 'simulateAutomatikSlot', [0, 8000.0, 0.3, 50.0, $ctxA]);
check('Planung: 1 kWh PV-Ueberschuss laedt 1 kWh (eta_pv 1,0)', abs($rP['soc'] - 52.5) < 0.01, json_encode($rP));
$parD = ['eta' => sqrt(0.82), 'etaPv' => 1.0, 'capKwh' => 40.0, 'socMinPct' => 10.0, 'feed' => 0.1836, 'chKwh' => 6.0, 'disKwh' => 6.0];
$rS = call($ems, 'selfBalanceCompute', [[8000, 0], [0, 0], [4000, -4000], [0.30, 0.50], 10.0, $parD]);
check('Selbstbilanz: PV-Pfad ohne Verlust, Entladen mit eta_ac (Rest 1 - 0,9055 = 0,0945 kWh x 0,50)', abs($rS['auto'] - (1.0 - sqrt(0.82)) * 0.50) < 0.006, json_encode($rS));
prop('BAT_RT_Grid_Pct', 0.0); prop('BAT_RT_PV_Pct', 0.0); prop('BAT_Conv_Eff_Pct', 93.0);
check('0 = ableiten: Netz 0,93^2 = 0,8649, PV 0,93, eta_pv 1,0', abs(call($ems, 'rtGrid') - 0.8649) < 1e-9 && abs(call($ems, 'rtPv') - 0.93) < 1e-9 && abs(call($ems, 'effPv') - 1.0) < 1e-9);
prop('BAT_RT_Grid_Pct', 30.0);
check('Eingabe unter 50 % wird auf 50 % begrenzt', abs(call($ems, 'rtGrid') - 0.5) < 1e-9);
prop('BAT_RT_Grid_Pct', 82.0); prop('BAT_RT_PV_Pct', 70.0);
check('PV-Rundwirkungsgrad unter Netz: eta_pv = 0,70 / 0,9055 = 0,773', abs(call($ems, 'effPv') - 0.70 / sqrt(0.82)) < 1e-9);
$GLOBALS['EMS_RAW_DEFAULTS'] = false;
}

