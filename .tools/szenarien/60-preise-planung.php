<?php
// Kapitel des EMS-Pruefstands: Netzentgelt-Stufen, Preisschaetzung, Vorentladen, Restwert, Wirkungsgrade, Ladekurve.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8k15) Netzentgelt-Stufen fuer Preisquellen ohne Netzentgelt, Preis-Schaetzung als Sicherheitsnetz\n";
$ems = freshEms();
$flat = json_encode(array_fill(0, 96, 0.20));
check('ohne Stufen: Preise unveraendert', call($ems, 'parsePT15M', [$flat, 0])[0] === 0.20);
prop('TARIF_NetzentgeltStufen', json_encode([
    ['From' => '00:00', 'To' => '06:00', 'Ct' => -7.5, 'Days' => 0],
    ['From' => '17:00', 'To' => '21:00', 'Ct' => 5.4, 'Days' => 0],
    ['From' => 'Unsinn', 'To' => '07:00', 'Ct' => 99, 'Days' => 0],
]));
$pp = call($ems, 'parsePT15M', [$flat, 0]);
check('Niedertarif 00:00-06:00: 20 - 7,5 = 12,5 ct (Slot 0 und 23)', abs($pp[0] - 0.125) < 1e-9 && abs($pp[23] - 0.125) < 1e-9, json_encode([$pp[0], $pp[23]]));
check('06:00 wieder 20 ct (Bis exklusiv), ungueltige Zeile ignoriert', abs($pp[24] - 0.20) < 1e-9);
check('Hochtarif 17:00-21:00: +5,4 ct (Slot 68), 21:00 wieder normal (Slot 84)', abs($pp[68] - 0.254) < 1e-9 && abs($pp[84] - 0.20) < 1e-9);
prop('TARIF_NetzentgeltStufen', json_encode([['From' => '22:00', 'To' => '02:00', 'Ct' => -3, 'Days' => 0], ['From' => '20:00', 'To' => '24:00', 'Ct' => 1, 'Days' => 0]]));
$pp = call($ems, 'parsePT15M', [$flat, 0]);
check('ueber Mitternacht (22:00-02:00) trifft 23:45 und 00:00, nicht 02:00', abs($pp[95] - 0.17) < 1e-9 && abs($pp[0] - 0.17) < 1e-9 && abs($pp[8] - 0.20) < 1e-9);
check('erste passende Zeile gilt, 24:00 als Ende erlaubt (20:00-22:00 = +1 ct)', abs($pp[80] - 0.21) < 1e-9);
$dowT = (int)date('N'); $weekend = $dowT >= 6;
prop('TARIF_NetzentgeltStufen', json_encode([['From' => '00:00', 'To' => '06:00', 'Ct' => -7.5, 'Days' => 1]]));
$pp = call($ems, 'parsePT15M', [$flat, 0]);
check('Zeile nur Mo-Fr: gilt heute ' . ($weekend ? 'nicht (Wochenende)' : '(Werktag)'), $weekend ? abs($pp[0] - 0.20) < 1e-9 : abs($pp[0] - 0.125) < 1e-9);
prop('TARIF_NetzentgeltStufen', json_encode([['From' => '00:00', 'To' => '06:00', 'Ct' => -7.5, 'Days' => 0]]));
$d0x = strtotime('today');
$tibJ = json_encode([['start' => $d0x, 'end' => $d0x + 900, 'price' => 11.0, 'netzentgelt' => 'enthalten'], ['start' => $d0x + 900, 'end' => $d0x + 1800, 'price' => 11.0]]);
$pp = call($ems, 'parsePT15M', [$tibJ, 0]);
check('Tibber-Eintrag mit netzentgelt "enthalten": keine doppelte Absenkung; Eintrag ohne Angabe: Stufe wirkt', abs($pp[0] - 0.11) < 1e-9 && abs($pp[1] - 0.035) < 1e-9, json_encode([$pp[0], $pp[1]]));
prop('TARIF_NetzentgeltStufen', '[]');
check('Uhrzeit-Pruefung: 24:00 ok, 24:15 und 7 Uhr ungueltig', call($ems, 'clockMinutes', ['24:00']) === 1440 && call($ems, 'clockMinutes', ['24:15']) === null && call($ems, 'clockMinutes', ['7 Uhr']) === null);
// Schaetzung
attr('PriceEstimate', 'Preise geschätzt: morgen (wie heute). Vorentladen ins Netz ist ausgesetzt, bis echte Preise vorliegen.');
prop('PLAN_PreDischarge_Active', true); prop('PLAN_NightGrid_Active', true);
prop('ANL_Verguetung_ct', 18.36); prop('BAT_SOC_Min', 10); prop('BAT_Capacity_kWh', 40.0); prop('EMS_Max_Power_W', 10000);
$nsPd = (int)(((int)date('G') * 60 + (int)date('i')) / 15);
$dpT = []; $dpM = [];
for ($i = 0; $i < 96; $i++) { $dpT[$i] = ['price' => ($i >= $nsPd + 8) ? 0.05 : 0.30]; $dpM[$i] = ['price' => 0.05]; } // guenstiges Fenster 2 h ab jetzt, unabhaengig von der Uhrzeit
attr('DayPlan', json_encode($dpT)); attr('DayPlanTomorrow', json_encode($dpM));
$sPd = state(['bat_active' => true, 'bat_soc' => 80.0, 'house_pow_w' => 300.0]);
attr('PriceEstimate', '');
$pdReal = call($ems, 'applyPreDischarge', [$sPd]);
attr('PriceEstimate', 'Preise geschätzt: morgen (wie heute). Vorentladen ins Netz ist ausgesetzt, bis echte Preise vorliegen.');
check('Gegenprobe: mit echten Preisen wuerde vorentladen; mit geschaetzten Preisen nie (Laufzeit)', is_array($pdReal) && call($ems, 'applyPreDischarge', [$sPd]) === null, json_encode($pdReal));
$cc = call($ems, 'configCheckLines');
check('Konfigurationspruefung nennt die Schaetzung als Warnung', count(array_filter($cc, fn($l) => $l['level'] === 'warn' && strpos($l['text'], 'Preise geschätzt') === 0)) === 1);
attr('PriceEstimate', ''); prop('PLAN_PreDischarge_Active', false);
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$TIBE = 520; $GLOBALS['INSTMOD'][$TIBE] = GUID_TIBBERGRIDREWARD;
$cpE = vari('Aktueller Preis', $TIBE, 'CurrentPrice', 0.30, 2);
$yd = strtotime('yesterday');
$GLOBALS['ARCHIVE'][$cpE] = [[$yd - 3600, 0.25], [$yd + 12 * 3600, 0.30]];
$ests = call($ems, 'estimatePricesFromHistory', [$yd]);
check('Schaetzung aus dem Vortag: 00:00 = 25 ct, 12:00 = 30 ct, alle 96 Slots belegt', abs($ests[0] - 0.25) < 1e-9 && abs($ests[48] - 0.30) < 1e-9 && count(array_filter($ests, fn($v) => $v !== null)) === 96, json_encode([$ests[0], $ests[48]]));
prop('TIBBER_Active', true);
$full = array_fill(0, 96, 0.2); $none = array_fill(0, 96, null);
$e = call($ems, 'priceEstimates', [$full, $none, 16]);
check('morgen fehlt um 16 Uhr: morgen = heute, als geschaetzt gekennzeichnet', $e['tomorrow'] === $full && strpos($e['text'], 'morgen (wie heute)') !== false);
$e = call($ems, 'priceEstimates', [$full, $none, 12]);
check('morgen fehlt um 12 Uhr (Tibber meldet erst gegen 13 Uhr): keine Schaetzung', $e['tomorrow'] === $none && $e['text'] === '');
$e = call($ems, 'priceEstimates', [$none, $none, 2]);
check('heute fehlt (Quelle ausgefallen): Vortag aus dem Archiv, gekennzeichnet', $e['today'] === true && abs($e['prices'][0] - 0.25) < 1e-9 && strpos($e['text'], 'heute (Vortag)') !== false, $e['text']);
$e = call($ems, 'priceEstimates', [$full, $full, 16]);
check('alle Preise da: keine Schaetzung', $e['text'] === '' && $e['today'] === false);
unset($GLOBALS['INSTMOD'][500], $GLOBALS['INSTMOD'][$TIBE]);

echo "\n8k16) Netzbezug unter der Einspeiseverguetung: Akku schonen (Dietmar 24.10.2026, 18,01 ct gegen 18,36 ct)\n";
$ems = freshEms();
$fCtx = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 600.0, 'houseLoadSlots' => [],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 24.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03];
$r = call($ems, 'simulateDaySlot', [24, 0.1801, 0.0, 99.7, [], $fCtx, 0.0]);
check('Sonntag 06:00, Bezug 18,01 ct < Verguetung 18,36 ct, Akku 99,7 %: Akku halten, Haus aus dem Netz, SOC bleibt', $r['plan']['op'] === EMS_OP_HOLD && abs($r['soc'] - 99.7) < 1e-9 && strpos($r['plan']['reason'], 'Einspeisevergütung 18.36ct') !== false, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [24, 0.1840, 0.0, 99.7, [], $fCtx, 0.0]);
check('Bezug 18,40 ct > Verguetung: Akku deckt das Haus (kein Halten)', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [24, 0.1801, 0.0, 11.0, [], $fCtx, 0.0]);
check('Akku nahe Reserve (11 %): keine Halteregel noetig, normale Logik', $r['plan']['op'] !== EMS_OP_HOLD || abs($r['soc'] - 11.0) < 1e-9, json_encode($r['plan']));

echo "\n8k17) Vorentladen endet am Beginn der billigen Strecke (00:00 statt 01:30), Halten bis 0,01 ct Abstand\n";
$ems = freshEms();
prop('ANL_Verguetung_ct', 18.36); prop('PLAN_PreDischarge_MinGain_ct', 3.0); prop('BAT_Price_EUR', 9998); prop('BAT_Cycles', 8000); prop('BAT_Capacity_kWh', 40.0);
// Abend 19-23 ct bis 23:45, ab 00:00 11,7 ... 11,1 ct, ab 01:30 11,0, ab 02:00 10,5 (Dietmars Kurve 24./25.10.2026)
$p192 = array_fill(0, 192, null);
for ($i = 84; $i < 96; $i++) { $p192[$i] = 0.21; } $p192[95] = 0.191;
foreach ([0.117, 0.116, 0.112, 0.111, 0.114, 0.111] as $k => $v) { $p192[96 + $k] = $v; }
$p192[102] = 0.110; $p192[103] = 0.109; for ($i = 104; $i < 120; $i++) { $p192[$i] = 0.105; }
$end = call($ems, 'preDischargeEnd', [$p192, 87, 0.1836]);
check('Vorentladen um 21:45: Ende 00:00 (Slot 96), nicht 01:30 (Slot 102)', $end === 96, json_encode($end));
check('um 00:00 selbst: kein Vorentladen mehr (schon in der billigen Strecke)', call($ems, 'preDischargeEnd', [$p192, 96, 0.1836]) === null);
check('um 23:45: Ende 00:00, also nur noch dieser Slot', call($ems, 'preDischargeEnd', [$p192, 95, 0.1836]) === 96);
$p2 = $p192; $p2[98] = 0.20;
check('liegt dazwischen ein Slot ueber der Verguetung (00:30 = 20 ct), endet es erst nach ihm (00:45)', call($ems, 'preDischargeEnd', [$p2, 87, 0.1836]) === 99, json_encode(call($ems, 'preDischargeEnd', [$p2, 87, 0.1836])));
check('Wiederkauf-Text: guenstigster Preis der naechsten 6 h ab dem Ende (10,5 ct um 02:00), nicht der Preis um 00:00 (11,7 ct)', abs(call($ems, 'preDischargeRebuyPrice', [$p192, 96]) - 0.105) < 1e-9);
$fCtx2 = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 600.0, 'houseLoadSlots' => [],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 24.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03];
$r = call($ems, 'simulateDaySlot', [64, 0.1833, 0.0, 100.0, [], $fCtx2, 0.0]);
check('16:00 mit 18,33 ct (0,03 ct unter der Verguetung): Akku halten', $r['plan']['op'] === EMS_OP_HOLD, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [60, 0.1801, 2500.0, 100.0, [], $fCtx2, 0.0]);
check('15:00 Akku voll, PV-Ueberschuss, 18,01 ct < Verguetung: Bereitschaft (Modus 8) statt Automatik', $r['plan']['op'] === EMS_OP_HOLD && $r['plan']['gw'] === GW_MODE_STANDBY && strpos($r['plan']['reason'], 'Bereitschaft') !== false, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [60, 0.2500, 2500.0, 100.0, [], $fCtx2, 0.0]);
check('Akku voll, PV-Ueberschuss, 25 ct > Verguetung: Automatik wie bisher', $r['plan']['op'] === EMS_OP_AUTO, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [24, 0.1801, 0.0, 99.7, [], $fCtx2, 0.0]);
check('Halten setzt Modus 8 (Batterie-Bereitschaft), nicht AC-Export', $r['plan']['gw'] === GW_MODE_STANDBY && (int)$r['plan']['power'] === 0, json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [64, 0.1836, 0.0, 100.0, [], $fCtx2, 0.0]);
check('Preis gleich der Verguetung: kein Halten', $r['plan']['op'] !== EMS_OP_HOLD);

echo "\n8k18) Formular-Panel 'Wallboxen bedienen' ganz oben\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2); prop('WB1_ChargeMode', 4); prop('WB1_TargetKwh', 12.5); prop('WB1_TargetTime', '06:30'); prop('EMS_Risk_Acknowledged', true);
call($ems, 'maintainWallboxControlVariables');
SetValue(IPS_GetObjectIDByIdent('EMS_WB1_Plan', EMS_IID), 'Hybrid: 6 A (Mindeststrom 6 A plus PV-Überschuss 0 W).');
$fj = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$idx = null; foreach ($fj['elements'] as $k => $e) { if (($e['caption'] ?? '') === '🚗 Wallboxen bedienen') { $idx = $k; break; } }
check('Panel "Wallboxen bedienen" ganz oben (Position 0 oder direkt nach "Neu")', $idx !== null && $idx <= 1, var_export($idx, true));
$pj = json_encode($fj['elements'][$idx] ?? [], JSON_UNESCAPED_UNICODE);
check('Panel zeigt Planzeile, aktuelle Ladeart, Ziel, Frist fuer Wallbox 1 und Wallbox 2', strpos($pj, '"name":"FormWB1Plan"') !== false && strpos($pj, 'Hybrid: 6 A') !== false && strpos($pj, '"name":"FormWB1ChargeMode"') !== false && strpos($pj, '"value":4') !== false && strpos($pj, '"value":12.5') !== false && strpos($pj, '"value":"06:30"') !== false && strpos($pj, '"name":"FormWB2ChargeMode"') !== false, $pj);
check('Ladeart wirkt sofort ueber dieselbe Aktion wie im WebFront (EMS_WB1_ChargeMode)', strpos($pj, 'IPS_RequestAction($id, \"EMS_WB1_ChargeMode\", $FormWB1ChargeMode);') !== false);
check('Schaltflaechen Jetzt laden / Uebersteuerung beenden / Ladeziel loeschen mit Rueckmeldung', strpos($pj, 'EMS_WB1_Command\", 1); echo') !== false && strpos($pj, 'EMS_WB1_Command\", 2); echo') !== false && strpos($pj, 'EMS_WB1_Command\", 3); echo') !== false);
prop('WB_Count', 1);
$pj1 = ''; foreach (json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements'] as $e) { if (($e['caption'] ?? '') === '🚗 Wallboxen bedienen') { $pj1 = json_encode($e, JSON_UNESCAPED_UNICODE); } }
check('nur eine Wallbox: kein Wallbox-2-Block', strpos($pj1, 'FormWB2') === false && strpos($pj1, 'FormWB1') !== false);
prop('WB_Active', false);
$has = false; foreach (json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements'] as $e) { if (($e['caption'] ?? '') === '🚗 Wallboxen bedienen') { $has = true; } }
check('Wallboxen aus: kein Bedien-Panel', $has === false);
prop('WB1_ChargeMode', 0); prop('WB1_TargetKwh', 0.0); prop('WB1_TargetTime', '07:00');

echo "\n8l) Wallbox bei PV-Ueberschuss auch ueber der Preisschwelle\n";
$ems = freshEms();
$sW = state(['tib_active' => true, 'pv_total_w' => 3000.0, 'house_pow_w' => 800.0, 'wb1_pow_kw' => 0.0]);
check('Standard-Mindestleistung 4140 W (3-phasig, vorsichtig): 2,2 kW Ueberschuss genuegen nicht', call($ems, 'wallboxPriceAllowed', [1, $sW, 0.25, 0.20]) === false);
prop('WB1_Min_Power_W', 1380); // einphasige Wallbox
check('Preis 25 ct ueber Schwelle 20 ct, aber 2,2 kW PV-Ueberschuss (>= 1380 W): Wallbox darf laden', call($ems, 'wallboxPriceAllowed', [1, $sW, 0.25, 0.20]) === true);
check('kein PV-Ueberschuss (nur 500 W): Preis entscheidet, Wallbox gesperrt', call($ems, 'wallboxPriceAllowed', [1, array_merge($sW, ['pv_total_w' => 1300.0]), 0.25, 0.20]) === false);
check('Preis unter Schwelle: erlaubt, auch ohne PV', call($ems, 'wallboxPriceAllowed', [1, array_merge($sW, ['pv_total_w' => 0.0]), 0.15, 0.20]) === true);
check('Hysterese: ladende Wallbox darf bis zur halben Mindestleistung weiterlaufen (800 W Ueberschuss)', call($ems, 'wallboxPriceAllowed', [1, array_merge($sW, ['pv_total_w' => 1600.0, 'wb1_pow_kw' => 1.4]), 0.25, 0.20]) === true && call($ems, 'wallboxPriceAllowed', [1, array_merge($sW, ['pv_total_w' => 1600.0, 'wb1_pow_kw' => 0.0]), 0.25, 0.20]) === false);
prop('WB_PV_Ueberschuss', false);
check('Option aus: nur der Preis entscheidet (wie bisher)', call($ems, 'wallboxPriceAllowed', [1, $sW, 0.25, 0.20]) === false);
prop('WB_PV_Ueberschuss', true);
check('ohne dynamischen Tarif immer erlaubt', call($ems, 'wallboxPriceAllowed', [1, array_merge($sW, ['tib_active' => false, 'pv_total_w' => 0.0]), 0.99, 0.20]) === true);

echo "\n8m) Wirkungsgrad einstellbar, Status 'nur beobachtend'\n";
$ems = freshEms();
check('Standard-Wirkungsgrad 95 %', abs(call($ems, 'convEff') - 0.95) < 1e-9);
prop('BAT_Conv_Eff_Pct', 90.0);
check('Wirkungsgrad 90 %: Netzlade-Grenze gegen Verguetung 18,36 ct sinkt auf 16,52 ct', abs(call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.1836, 'refMode' => 0]]) - 0.16524) < 1e-6);
prop('BAT_Conv_Eff_Pct', 60.0);
check('unsinnige Eingabe wird auf 70 % begrenzt', abs(call($ems, 'convEff') - 0.70) < 1e-9);
prop('BAT_Conv_Eff_Pct', 95.0);
check('Statuszeile normal: OK', call($ems, 'statusText', ['Test']) === 'OK: Test');
attr('NoControlReason', 'Wechselrichter ohne EMS-Stellglieder');
check('Statuszeile ohne Stellglied: nur beobachtend, mit Grund', call($ems, 'statusText', ['Test']) === 'Nur beobachtend (Wechselrichter ohne EMS-Stellglieder): Test');

echo "\n8n) Ladeleistung nach SOC (vom BMS gemeldet und gelernt, bei jedem Nutzer)\n";
$ems = freshEms(); prop('EMS_Max_Power_W', 34500);
call($ems, 'learnChargeLimit', [50.0, 24.0]); call($ems, 'learnChargeLimit', [92.0, 6.0]); call($ems, 'learnChargeLimit', [100.0, 2.1]);
$cv = call($ems, 'chargeCurveKw');
check('drei SOC-Stufen gelernt (50 %, 92 %, 100 %)', isset($cv[10], $cv[18], $cv[19]) && abs($cv[10] - 24.0) < 1e-6 && abs($cv[19] - 2.1) < 1e-6, json_encode($cv));
$ctxT = ['capKwh' => 40.0, 'chargeKw' => 2.1, 'maxW' => 34500, 'chargeCurve' => $cv, 'socTargetNight' => 100.0];
check('bei SOC 50 % ist die Ladeleistung 24 kW, bei 100 % nur 2,1 kW', abs(call($ems, 'ctxChargeKw', [$ctxT, 50.0]) - 24.0) < 0.5 && abs(call($ems, 'ctxChargeKw', [$ctxT, 100.0]) - 2.1) < 0.3);
$nFlat = call($ems, 'chargeSlotsNeeded', [array_merge($ctxT, ['chargeCurve' => []]), 20.0, 100.0]);
$nCurve = call($ems, 'chargeSlotsNeeded', [$ctxT, 20.0, 100.0]);
check('20 -> 100 %: mit fester 2,1 kW (alter Wert) sehr viele Slots, mit gelernter Kurve deutlich weniger', $nFlat > $nCurve && $nCurve >= 6 && $nCurve <= 20, "flach=$nFlat kurve=$nCurve");
check('ohne gelernte Kurve rechnet der Plan wie bisher (fester Wert)', abs(call($ems, 'ctxChargeKw', [['chargeKw' => 7.5, 'chargeCurve' => []], 30.0]) - 7.5) < 1e-9);
prop('BAT_Charge_Max_kW', 10.0);
$cv2 = call($ems, 'chargeCurveKw');
check('reale Obergrenze BAT_Charge_Max_kW deckelt die gelernte Kurve', $cv2[10] <= 10.0 + 1e-9, json_encode($cv2));

echo "\n8o) Vertragsfelder fuer das Dashboard: dryRun/observeOnly und Ladefenster mit Ersparnis\n";
$ems = freshEms();
$cd = call($ems, 'GetCurrentDecision');
check('Entscheidung 1.1: dryRun false, observeOnly false, observeReason leer', $cd['contractVersion'] === '1.1' && $cd['dryRun'] === false && $cd['observeOnly'] === false && $cd['observeReason'] === '', json_encode($cd));
prop('EMS_DryRun', true); attr('NoControlReason', 'Wechselrichter ohne EMS-Stellglieder');
$cd = call($ems, 'GetCurrentDecision');
check('Trockenlauf und nur beobachtend werden als eigene Felder geliefert', $cd['dryRun'] === true && $cd['observeOnly'] === true && $cd['observeReason'] === 'Wechselrichter ohne EMS-Stellglieder', json_encode($cd));
prop('EMS_DryRun', false); attr('NoControlReason', '');
$planW = [];
for ($i = 0; $i < 96; $i++) { $planW[$i] = ['op' => EMS_OP_AUTO, 'gw' => GW_MODE_AUTO, 'power' => 0, 'reason' => '', 'price' => 0.30, 'soc' => 50.0]; }
for ($i = 8; $i < 12; $i++) { $planW[$i] = ['op' => EMS_OP_NET_CHARGE, 'gw' => GW_MODE_BAT_CHARGE, 'power' => 8000, 'reason' => '', 'price' => 0.10, 'soc' => 60.0]; }
attr('DayPlan', json_encode($planW)); attr('DayPlanTomorrow', '[]');
$gp = call($ems, 'GetDayPlan');
check('Tagesplan 1.3: ein Ladefenster, 8 kWh (4 x 8 kW x 0,25 h)', $gp['contractVersion'] === '1.3' && count($gp['windows']) === 1 && $gp['planAvailable'] === true && $gp['reason'] === '' && abs($gp['windows'][0]['kWh'] - 8.0) < 0.05, json_encode($gp['windows']));
$refExp = (92 * 30.0 + 4 * 10.0) / 96; $saveExp = round(($refExp - 10.0) * 8.0 / 100.0, 2);
check('Ersparnis = (Oe-Planpreis - Fensterpreis) x kWh, Tagessumme = Fenstersumme', abs($gp['windows'][0]['savingsEur'] - $saveExp) < 0.011 && abs($gp['savingsEur'] - $saveExp) < 0.011, "erwartet $saveExp: " . json_encode($gp));
check('Fenster hat Start < Ende und Preis in ct', $gp['windows'][0]['end'] - $gp['windows'][0]['start'] === 3600 && abs($gp['windows'][0]['avgPriceCt'] - 10.0) < 0.01, json_encode($gp['windows'][0]));
attr('DayPlan', json_encode(array_fill(0, 96, ['op' => EMS_OP_AUTO, 'gw' => GW_MODE_AUTO, 'power' => 0, 'reason' => '', 'price' => 0.2, 'soc' => 50.0])));
$gp = call($ems, 'GetDayPlan');
check('Ohne Ladefenster: leere Liste, Ersparnis 0', $gp['windows'] === [] && $gp['savingsEur'] == 0, json_encode($gp['windows']));

echo "\n8p) Ladeslots zusammenhaengend waehlen (kleiner Aufschlag je Ladeblock)\n";
$ems = freshEms();
$pk = call($ems, 'pickChargeSlots', [[0 => 0.10, 1 => 0.10, 2 => 0.102, 3 => 0.10, 4 => 0.10], 4]);
check('Zwischenslot nur 0,2 ct teurer: Luecke wird geschlossen (Slot 2 gewaehlt, ein Block)', isset($pk[2]) && count($pk) === 4, json_encode($pk));
$pk = call($ems, 'pickChargeSlots', [[0 => 0.10, 1 => 0.10, 2 => 0.12, 3 => 0.10, 4 => 0.10], 4]);
check('Zwischenslot 2 ct teurer: Luecke bleibt, guenstigste Slots gewaehlt', !isset($pk[2]) && count($pk) === 4, json_encode($pk));
$pk = call($ems, 'pickChargeSlots', [[0 => 0.10, 1 => 0.11, 5 => 0.09], 5]);
check('Mehr Bedarf als Kandidaten: alle Kandidaten, Rang nach Preis', count($pk) === 3 && $pk[5] === 1 && $pk[0] === 2 && $pk[1] === 3, json_encode($pk));
check('Kein Bedarf: keine Slots', call($ems, 'pickChargeSlots', [[0 => 0.10], 0]) === []);
$pk = call($ems, 'pickChargeSlots', [[0 => 0.10, 1 => 0.10, 2 => 0.10, 3 => 0.10, 4 => 0.10, 5 => 0.10], 3]);
check('Gleiche Preise: ein zusammenhaengender Block', count($pk) === 3 && (max(array_keys($pk)) - min(array_keys($pk))) === 2, json_encode($pk));

echo "\n8q) Symcon-Strompreis als automatische Preisquelle (Stundenpreise, ohne Tibber)\n";
$ems = freshEms();
$d0 = strtotime('today'); $md = [];
for ($h = 0; $h < 24; $h++) { $md[] = ['start' => mktime($h, 0, 0, (int)date('n', $d0), (int)date('j', $d0), (int)date('Y', $d0)), 'end' => mktime($h + 1, 0, 0, (int)date('n', $d0), (int)date('j', $d0), (int)date('Y', $d0)), 'price' => 20.0 + $h]; } // Wanduhr-Stunden (23-/25-Stunden-Tag)
$md2 = []; $d1 = strtotime('tomorrow');
for ($h = 0; $h < 24; $h++) { $a1 = mktime($h, 0, 0, (int)date('n', $d1), (int)date('j', $d1), (int)date('Y', $d1)); $md2[] = ['start' => $a1, 'end' => mktime($h + 1, 0, 0, (int)date('n', $d1), (int)date('j', $d1), (int)date('Y', $d1)), 'price' => 50.0 + $h]; } // Wanduhr-Stunden: auch am 23-/25-Stunden-Tag
$GLOBALS['INSTMOD'][7001] = GUID_POWERPRICE; obj(7001, 1, 'aWATTar', 0);
$mdVar = vari('Marktdaten', 7001, 'MarketData', json_encode(array_merge($md, $md2)), 3);
$GLOBALS['TIBBER_CURVE'] = [];
$j = call($ems, 'getPT15MTodayJson');
check('ohne Tibber und ohne eigenes Feld: Kurve kommt aus dem Symcon-Strompreis', $j !== '' && strpos($j, '"price"') !== false);
$pt = call($ems, 'parsePT15M', [$j, 0]); $pm = call($ems, 'parsePT15M', [call($ems, 'getPT15MTomorrowJson'), 1]);
check('Stundenpreis gilt fuer alle vier Viertelstunden (08:00-08:59 = Slot 32..35 = 28 ct)', abs($pt[32] - 0.28) < 1e-9 && abs($pt[35] - 0.28) < 1e-9 && abs($pt[36] - 0.29) < 1e-9, json_encode(array_slice($pt, 32, 5)));
check('alle 96 Slots heute belegt', count(array_filter($pt, fn($v) => $v !== null)) === 96);
check('Morgen: Slot 0 = 50 ct (Tageswechsel richtig einsortiert)', abs($pm[0] - 0.50) < 1e-9 && abs($pm[95] - 0.73) < 1e-9, json_encode([$pm[0], $pm[95]]));
$GLOBALS['INSTMOD'][7002] = GUID_POWERPRICE; obj(7002, 1, 'zweite', 0);
check('Zwei Strompreis-Instanzen ohne Auswahl: nicht raten, keine Quelle', call($ems, 'getPowerPriceInstance') === 0 && call($ems, 'getPT15MTodayJson') === '');
prop('PRICE_Source_Instance', 7001);
check('Mit ausdruecklicher Auswahl wieder die gewaehlte Instanz', call($ems, 'getPowerPriceInstance') === 7001 && call($ems, 'getPT15MTodayJson') !== '');
prop('PRICE_Source_Instance', 0); unset($GLOBALS['INSTMOD'][7002]);
$GLOBALS['VAL'][$mdVar] = '[]';
check('Leere Marktdaten: keine Quelle', call($ems, 'getPT15MTodayJson') === '');
$GLOBALS['VAL'][$mdVar] = json_encode($md);
$GLOBALS['INSTMOD'][7003] = GUID_TIBBERGRIDREWARD; $GLOBALS['TIBBER_CURVE'] = [['start' => $d0, 'end' => $d0 + 900, 'price' => 12.0]];
check('Tibber hat weiter Vorrang', strpos(call($ems, 'getPT15MTodayJson'), '"price":12') !== false);
$GLOBALS['TIBBER_CURVE'] = []; unset($GLOBALS['INSTMOD'][7001], $GLOBALS['INSTMOD'][7003]);
check('Ohne Instanz keine Quelle', call($ems, 'getPT15MTodayJson') === '');
$pOld = call($ems, 'parsePT15M', [json_encode([['start' => $d0, 'price' => 10.0], ['start' => $d0 + 900, 'price' => 20.0]]), 0]);
check('Alte Kurven ohne end-Feld: unveraendert eine Viertelstunde je Eintrag', abs($pOld[0] - 0.10) < 1e-9 && abs($pOld[1] - 0.20) < 1e-9 && $pOld[2] === null, json_encode(array_slice($pOld, 0, 3)));

echo "\n8r) Wallbox-Mindestleistung aus den gemeldeten Phasen (ChargerHub 1.6, OCPPHub 1.7), Ausfallzeit\n";
$ems = freshEms();
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'managedBy' => 'none']]]));
check('Ohne Phasenangabe: Standardwert 4140 W', call($ems, 'wallboxMinPowerW', [1]) === 4140.0);
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'managedBy' => 'none', 'phases' => 1]]]));
check('1-phasig gemeldet: 1 x 230 V x 6 A = 1380 W', call($ems, 'wallboxMinPowerW', [1]) === 1380.0);
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'managedBy' => 'none', 'phases' => 3, 'stationMinCurrentA' => 8]]]));
check('3-phasig, Hardware-Mindeststrom 8 A: 5520 W', call($ems, 'wallboxMinPowerW', [1]) === 5520.0);
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'managedBy' => 'none', 'phases' => 7]]]));
check('Unsinnige Phasenzahl wird ignoriert (Standardwert)', call($ems, 'wallboxMinPowerW', [1]) === 4140.0);
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'managedBy' => 'none', 'phases' => 1]]]));
prop('WB1_Min_Power_W', 2000);
check('Ausdruecklich eingestellter Wert schlaegt die Meldung der Wallbox', call($ems, 'wallboxMinPowerW', [1]) === 2000.0);
prop('WB1_Min_Power_W', 4140);

echo "\n8s) Restwert der Batterieenergie (Beta): Akku schonen, wenn spaetere Viertelstunden mehr bringen\n";
$ems = freshEms();
$rwCtx = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 300.0, 'houseLoadSlots' => [],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 48.0, 'dischargeKw' => 48.156, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03,
    'restwert' => true, 'rwOffset' => 0, 'rwCharge' => []];
$mkP = function (array $late) { $p = array_fill(0, 192, null); for ($i = 0; $i < 192; $i++) { $p[$i] = 0.30; } foreach ($late as $i => $v) { $p[$i] = $v; } return $p; };
$mkN = function (array $dem) { $n = array_fill(0, 192, 0.0); foreach ($dem as $i => $v) { $n[$i] = $v; } return $n; };
// 6 kWh nutzbar (SOC 25 %: 25 - 10 Reserve = 15 % von 40 kWh); spaeter 5 kWh zu 50 ct und 20 kWh zu 35 ct
$late = []; $dem = [];
for ($i = 60; $i < 65; $i++) { $late[$i] = 0.50; $dem[$i] = 1.0; }
for ($i = 70; $i < 90; $i++) { $late[$i] = 0.35; $dem[$i] = 1.0; }
$c1 = array_merge($rwCtx, ['rwP' => $mkP($late), 'rwNet' => $mkN($dem)]);
$rv = call($ems, 'restwertValue', [$c1, 40, 25.0]);
check('Restwert: 6 kWh decken die 5 kWh zu 50 ct und 1 kWh zu 35 ct -> Grenzwert 35 ct', abs($rv['value'] - 0.35) < 1e-9 && abs($rv['usable'] - 6.0) < 1e-6, json_encode($rv));
$r = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 25.0, [], $c1, 0.0]);
check('Bezug jetzt 30 ct < Restwert 35 ct - 3 ct Spanne: Akku halten, Haus aus dem Netz, SOC bleibt', $r['plan']['op'] === EMS_OP_HOLD && abs($r['soc'] - 25.0) < 1e-9 && isset($r['plan']['rw']), json_encode($r['plan']));
$r = call($ems, 'simulateDaySlot', [40, 0.34, 0.0, 25.0, [], $c1, 0.0]);
check('Bezug 34 ct: Abstand zum Restwert kleiner als die Mindestspanne, kein Halten', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
$c1off = $c1; unset($c1off['restwert']);
$r = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 25.0, [], $c1off, 0.0]);
check('Schalter aus: Verhalten wie bisher (kein Restwert-Halten)', $r['plan']['op'] !== EMS_OP_HOLD || strpos($r['plan']['reason'], 'Restwert') === false, json_encode($r['plan']));
// Energie reicht fuer alles: kein Halten
$c2 = array_merge($rwCtx, ['rwP' => $mkP($late), 'rwNet' => $mkN($dem)]);
$rv2 = call($ems, 'restwertValue', [$c2, 40, 90.0]);
check('Genug Energie (SOC 90 %, 32 kWh nutzbar) fuer alle 25 kWh Bedarf: Restwert = Wiederbeschaffung (30 ct / 0,95), kein Engpass', abs($rv2['value'] - 0.30 / 0.95) < 1e-6 && $rv2['covered'] < $rv2['usable'], json_encode($rv2));
$r = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 90.0, [], array_merge($c2, ['thDischarge' => 0.99]), 0.0]);
check('Ohne Engpass wird nicht geschont', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
// PV fuellt vorher wieder auf: nichts zu bewahren
$netPv = $dem; for ($i = 50; $i < 58; $i++) { $netPv[$i] = -4.0; }
$rv3 = call($ems, 'restwertValue', [array_merge($rwCtx, ['rwP' => $mkP($late), 'rwNet' => $mkN($netPv)]), 40, 25.0]);
check('PV-Ueberschuss fuellt die Batterie vor dem teuren Bedarf wieder auf: Bedarf danach zaehlt nicht (Wiederbeschaffungswert)', abs($rv3['value'] - 0.30 / 0.95) < 1e-6, json_encode($rv3));
// geplantes Netzladen davor
$rv4 = call($ems, 'restwertValue', [array_merge($rwCtx, ['rwP' => $mkP($late), 'rwNet' => $mkN($dem), 'rwCharge' => [55]]), 40, 25.0]);
check('Geplantes Netzladen vor dem teuren Bedarf: Energie ist ersetzbar, Bedarf danach zaehlt nicht', abs($rv4['value'] - 0.30 / 0.95) < 1e-6, json_encode($rv4));
// morgen-Plan: Offset 96
$rv5 = call($ems, 'restwertValue', [array_merge($rwCtx, ['rwOffset' => 96, 'rwP' => $mkP([160 => 0.60]), 'rwNet' => $mkN([160 => 10.0])]), 40, 25.0]);
check('Morgen-Plan (Offset 96): Slot 40 morgen schaut ab Index 137, Bedarf bei 160 wird gefunden, Grenzwert 60 ct', abs($rv5['value'] - 0.60) < 1e-9, json_encode($rv5));

echo "\n8t) Restwert rechnet mit vorsichtiger PV (p10): Schnee-Tag\n";
$GLOBALS['PVF_FC'] = [];
if (!function_exists('PVF_GetForecast')) { function PVF_GetForecast($iid, $offset = 0) { return $GLOBALS['PVF_FC'][$offset] ?? []; } }
$ems = freshEms();
$GLOBALS['INSTMOD'][7100] = GUID_PVFORECAST; obj(7100, 1, 'PV', 0);
$GLOBALS['PVF_FC'] = [0 => ['p10' => array_fill(0, 96, 0.0), 'p50' => array_fill(0, 96, 3000.0)], 1 => ['p10' => array_fill(0, 96, 100.0), 'p50' => array_fill(0, 96, 4000.0)]];
$lo = call($ems, 'getPvfSlotsWatt', ['p10']); $mid = call($ems, 'getPvfSlotsWatt');
check('p10 wird geliefert (Schnee: 0 W heute, 100 W morgen), Standard bleibt p50', count($lo) === 192 && $lo[10] == 0.0 && $lo[100] == 100.0 && $mid[10] == 3000.0 && $mid[100] == 4000.0, json_encode([$lo[10], $lo[100], $mid[10]]));
$GLOBALS['PVF_FC'] = [0 => ['p50' => array_fill(0, 96, 3000.0)], 1 => ['p50' => array_fill(0, 96, 4000.0)]];
$lo2 = call($ems, 'getPvfSlotsWatt', ['p10']);
check('Fehlt p10 (aeltere Prognose), gilt p50', $lo2[10] == 3000.0 && $lo2[100] == 4000.0);
unset($GLOBALS['INSTMOD'][7100]); $GLOBALS['PVF_FC'] = [];

echo "\n8u) Allen-Nutzer-Pruefung: Restwert und Symcon-Strompreis in ungewoehnlichen Konstellationen\n";
$ems = freshEms();
$base = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 300.0, 'houseLoadSlots' => [],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 48.0, 'dischargeKw' => 48.156, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03, 'restwert' => true, 'rwOffset' => 0, 'rwCharge' => []];
// Restwert ohne jede Prognosedaten (kein rwP/rwNet): darf nichts halten und nicht abstuerzen
$r = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 50.0, [], $base, 0.0]);
check('Restwert ohne Preis-/Bedarfsdaten: kein Halten, kein Fehler', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
// SOC unter der Reserve: nichts zu schonen
$mkP2 = function (array $set) { $p = array_fill(0, 192, 0.30); foreach ($set as $i => $v) { $p[$i] = $v; } return $p; };
$mkN2 = function (array $set) { $n = array_fill(0, 192, 0.0); foreach ($set as $i => $v) { $n[$i] = $v; } return $n; };
$cLow = array_merge($base, ['rwP' => $mkP2([60 => 0.90]), 'rwNet' => $mkN2([60 => 5.0])]);
$rv = call($ems, 'restwertValue', [$cLow, 40, 8.0]);
check('SOC unter Mindest-/Reservegrenze: nutzbare Energie 0, kein Halten', $rv['usable'] == 0.0, json_encode($rv));
$r = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 8.0, [], $cLow, 0.0]);
check('... auch in der Planung', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
// Speicher mit unbekannter Kapazitaet (0)
$rv0 = call($ems, 'restwertValue', [array_merge($cLow, ['capKwh' => 0.0]), 40, 50.0]);
$r0 = call($ems, 'simulateDaySlot', [40, 0.30, 0.0, 50.0, [], array_merge($cLow, ['capKwh' => 0.0]), 0.0]);
check('Kapazitaet 0: keine Division durch Null, kein Halten', $r0['plan']['op'] !== EMS_OP_HOLD && is_finite($rv0['value']), json_encode($rv0));
// Preise mit Luecken (null) im Horizont
$pGap = $mkP2([60 => 0.90, 61 => null, 62 => null]);
$rvg = call($ems, 'restwertValue', [array_merge($base, ['rwP' => $pGap, 'rwNet' => $mkN2([60 => 5.0, 61 => 5.0, 62 => 5.0])]), 40, 40.0]);
check('Preisluecken (null) werden uebersprungen, kein Fehler', is_finite($rvg['value']), json_encode($rvg));
// Restwert liefert nie Halten, wenn gerade PV-Ueberschuss da ist (Vorrang PV-Laden)
$r = call($ems, 'simulateDaySlot', [40, 0.30, 5000.0, 30.0, [], array_merge($base, ['rwP' => $mkP2([60 => 0.90]), 'rwNet' => $mkN2([60 => 30.0])]), 0.0]);
check('Bei PV-Ueberschuss hat das PV-Laden Vorrang vor dem Restwert', $r['plan']['op'] !== EMS_OP_HOLD, json_encode($r['plan']));
// Symcon-Strompreis: nur heute vorhanden (morgen noch nicht veroeffentlicht) und Stundenraster
$GLOBALS['INSTMOD'][7201] = GUID_POWERPRICE; obj(7201, 1, 'Strompreis', 0);
$dd0 = strtotime('today'); $mdOne = [];
for ($h = 0; $h < 24; $h++) { $mdOne[] = ['start' => mktime($h, 0, 0, (int)date('n', $dd0), (int)date('j', $dd0), (int)date('Y', $dd0)), 'end' => mktime($h + 1, 0, 0, (int)date('n', $dd0), (int)date('j', $dd0), (int)date('Y', $dd0)), 'price' => 25.0]; } // Wanduhr-Stunden
vari('Marktdaten', 7201, 'MarketData', json_encode($mdOne), 3);
$pt = call($ems, 'parsePT15M', [call($ems, 'getPT15MTodayJson'), 0]); $pm = call($ems, 'parsePT15M', [call($ems, 'getPT15MTomorrowJson'), 1]);
check('Nur heute veroeffentlicht: heute 96 Slots belegt, morgen alle leer (null), kein Fehler', count(array_filter($pt, fn($v) => $v !== null)) === 96 && count(array_filter($pm, fn($v) => $v !== null)) === 0);
// kaputte Marktdaten
$GLOBALS['VAL'][$GLOBALS['NEXTID'] - 1] = 'kein json';
check('Kaputte Marktdaten (kein JSON): keine Quelle statt Fehler', call($ems, 'getPT15MTodayJson') === '');
unset($GLOBALS['INSTMOD'][7201]);

echo "\n8v) Verschleisskosten aus Preis und Zyklen (im Formular erfragt)\n";
$ems = freshEms(); prop('BAT_Capacity_kWh', 40.0);
check('Ohne Angaben: keine Verschleisskosten', call($ems, 'cycleCostCt') === 0.0);
prop('BAT_Price_EUR', 9998.0);
check('Nur Preis, keine Zyklen: keine Verschleisskosten (nichts raten)', call($ems, 'cycleCostCt') === 0.0);
prop('BAT_Cycles', 8000);
check('9998 EUR / (8000 Zyklen x 40 kWh) = 3,12 ct/kWh', abs(call($ems, 'cycleCostCt') - 3.124375) < 1e-6, (string)call($ems, 'cycleCostCt'));
prop('BAT_CycleCost_ct', 5.0);
check('Ausdruecklich eingetragener Wert schlaegt die Rechnung', call($ems, 'cycleCostCt') === 5.0);
prop('BAT_CycleCost_ct', 0.0); prop('BAT_Capacity_kWh', 0.0);
check('Unbekannte Kapazitaet: keine Division durch Null', is_finite(call($ems, 'cycleCostCt')));
prop('BAT_Capacity_kWh', 40.0);
$lim0 = call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.1836, 'refMode' => 0, 'cycleCost' => 0.0]]);
$ctxC = ['feedTariff' => 0.1836, 'refMode' => 0, 'cycleCost' => call($ems, 'cycleCostCt') / 100.0];
$lim1 = call($ems, 'gridChargeLimitEur', [$ctxC]);
check('Mit Verschleiss sinkt die Netzlade-Grenze um 3,1 ct: 17,44 -> ca. 14,3 ct', abs($lim0 - 0.17442) < 1e-4 && abs($lim1 - (0.17442 - 0.031244)) < 1e-4, "$lim0 / $lim1");
$formJson = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
check('Formular bleibt gueltig und enthaelt die neuen Felder', strpos(json_encode($formJson), 'BAT_Cycles') !== false && strpos(json_encode($formJson), 'BAT_Price_EUR') !== false);
check('Formular zeigt die geltenden Verschleisskosten als Status (Berechnet aus Preis und Zyklen)', strpos(json_encode($formJson, JSON_UNESCAPED_UNICODE), 'Berechnet aus Preis und Zyklen') !== false, 'Statuszeile nicht im Formular');

echo "\n8w) Ladekurve auch aus beobachteter Leistung; kein Neustart des Nachtladens bei kleinem Rueckgang\n";
$ems = freshEms(); prop('EMS_Max_Power_W', 34500);
call($ems, 'learnChargeLimit', [90.0, 8.0]);                      // Batteriemanagement meldet 8 kW bei Stufe 90-95 %
call($ems, 'learnChargeObserved', [90.0, 21.5]);                  // real gemessen 21,5 kW
call($ems, 'learnChargeObserved', [90.0, 19.0]);                  // kleinerer Wert aendert den Spitzenwert nicht
$cv = call($ems, 'chargeCurveKw');
check('Stufe 90-95 %: Maximum aus Meldung (8 kW) und Beobachtung (21,5 kW)', abs($cv[18] - 21.5) < 1e-6, json_encode($cv));
call($ems, 'learnChargeObserved', [50.0, 22.0]);
check('Stufe ohne Meldung des Batteriemanagements: die Beobachtung gilt allein', abs(call($ems, 'chargeCurveKw')[10] - 22.0) < 1e-6);
call($ems, 'learnChargeObserved', [60.0, 0.3]);
check('Kleine Leistungen (< 0,5 kW, z. B. Erhaltungsladung) werden nicht gelernt', !isset(call($ems, 'chargeCurveKw')[12]));
call($ems, 'learnChargeLimit', [50.0, 30.0]);
check('Meldung ueber Beobachtung: gilt die hoehere (30 kW), begrenzt durch EMS-Grenze', abs(call($ems, 'chargeCurveKw')[10] - 30.0) < 1e-6);
prop('BAT_Charge_Max_kW', 24.0);
check('Reale Obergrenze deckelt auch die beobachtete Kurve', abs(call($ems, 'chargeCurveKw')[10] - 24.0) < 1e-6 && abs(call($ems, 'chargeCurveKw')[18] - 21.5) < 1e-6);
// Nachtladen: Ziel erreicht, kleiner Rueckgang
$ems = freshEms(); prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6);
$ctxN = ['enwgActive' => false, 'avgHouseW' => 300.0, 'houseLoadSlots' => [], 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 8.0, 'chargeCurve' => [], 'dischargeKw' => 8.0, 'maxW' => 34500.0, 'feedTariff' => 0.1836, 'refMode' => 0, 'cycleCost' => 0.0, 'spread' => 0.03];
$pN = array_fill(0, 96, null); for ($i = 0; $i < 24; $i++) { $pN[$i] = 0.15; }
$nw1 = call($ems, 'nightWindowPlan', [$pN, 18, 99.0, $ctxN]);
check('Ohne "Ziel erreicht": bei 99 % wird fuer das letzte Prozent noch ein Ladeslot geplant', $nw1['n'] >= 1, json_encode($nw1));
$nw2 = call($ems, 'nightWindowPlan', [$pN, 18, 99.0, array_merge($ctxN, ['nightDone' => true])]);
check('Ziel schon erreicht, SOC nur 1 Punkt darunter: kein neues Netzladen', $nw2['n'] === 0, json_encode($nw2));
$nw3 = call($ems, 'nightWindowPlan', [$pN, 18, 90.0, array_merge($ctxN, ['nightDone' => true])]);
check('Ziel erreicht, aber SOC deutlich gefallen (90 %): es wird wieder geladen', $nw3['n'] >= 1, json_encode($nw3));

