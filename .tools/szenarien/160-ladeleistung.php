<?php
// Kapitel des EMS-Pruefstands: Ladeleistung lernen (gemessen / Archiv), Messung anstossen, Ladefenster (EMS 0.114.0, ChargerHub-Vertrag 1.15).
// Wird von .tools/test-scenarios.php geladen; einzeln nicht lauffaehig.

echo "\n16a) Ladeleistung aus dem Archiv (Dietmar 23.11.2026: Peugeot an WB2 laedt ~5 kW, Plan rechnete mit 11 kW)\n";
$rows = [];
for ($i = 0; $i < 40; $i++) { $rows[] = ['Value' => 4800 + ($i % 5) * 50]; }   // 4800..5000 W
for ($i = 0; $i < 30; $i++) { $rows[] = ['Value' => 300]; }                     // Standby/Ende, unter 1 kW
$p = EMS::chargePowerPercentile($rows);
check('90. Perzentil der Ladeleistung ueber 1 kW = 5000 W, Standby-Werte zaehlen nicht', $p === 5000, (string)$p);
check('Zu wenig Werte (unter 8) = 0, dann gilt "Max. Leistung"', EMS::chargePowerPercentile(array_slice($rows, 0, 5)) === 0);
check('Nur Werte unter 1 kW = 0', EMS::chargePowerPercentile(array_fill(0, 20, ['Value' => 500])) === 0);

$now = time();
$pid = 916001;
$archBefore = $GLOBALS['INSTMOD'][916099] ?? null;
$GLOBALS['INSTMOD'][916099] = GUID_ARCHIVECONTROL;
vari('WB2 Leistung Archiv', 0, '', 0.0);
$GLOBALS['VAR'][$pid] = ['VariableType' => 2, 'VariableUpdated' => $now];
$GLOBALS['LOGGED'][$pid] = true;
$GLOBALS['ARCHIVE'][$pid] = [];
for ($i = 0; $i < 20; $i++) { $GLOBALS['ARCHIVE'][$pid][] = [$now - 86400 - $i * 60, 5100.0]; }
attr('WB2ArchAt', 0);
$l = call($ems, 'wallboxLearnedPowerW', [2, ['powerID' => $pid]]);
check('Ohne Messwert: aus dem Archiv gelernt (5100 W)', $l['w'] === 5100 && $l['src'] === 'archive', json_encode($l));
$GLOBALS['ARCHIVE'][$pid] = [];
$l = call($ems, 'wallboxLearnedPowerW', [2, ['powerID' => $pid]]);
check('Archiv wird hoechstens alle 6 h neu ausgewertet (Wert bleibt 5100 W)', $l['w'] === 5100, json_encode($l));
$l = call($ems, 'wallboxLearnedPowerW', [2, ['powerID' => $pid, 'measuredMaxPowerW' => 7200]]);
check('Gemessene Leistung (Vertrag 1.15) geht vor dem Archiv', $l['w'] === 7200 && $l['src'] === 'measured', json_encode($l));
attr('WB2ArchAt', 0);
$GLOBALS['LOGGED'][$pid] = false;
$l = call($ems, 'wallboxLearnedPowerW', [2, ['powerID' => $pid]]);
check('Leistungsvariable nicht archiviert: 0 (dann "Max. Leistung")', $l['w'] === 0 && $l['src'] === '', json_encode($l));
unset($GLOBALS['INSTMOD'][916099]);

echo "\n16b) Ladeziel 10 kWh bis 08:00 mit gelernten 5 kW statt 11 kW\n";
$dl = $now + 8 * 3600;
$in = ['targetKwh' => 10.0, 'chargedKwh' => 0.0, 'now' => $now, 'deadline' => $dl, 'prices' => [], 'bufferPct' => 20];
$r11 = call($ems, 'wallboxTargetPlan', [$in + ['powerKw' => 11.0]]);
$r5 = call($ems, 'wallboxTargetPlan', [$in + ['powerKw' => 5.0]]);
check('Mit 11 kW geplant: 5 Viertelstunden (reichte im Archiv nur fuer ~6,3 kWh)', count($r11['slots']) === 5, (string)count($r11['slots']));
check('Mit gelernten 5 kW: 10 Viertelstunden (10 kWh / 5 kW * 1,2 Puffer = 2,4 h)', count($r5['slots']) === 10, (string)count($r5['slots']));

echo "\n16c) Ladefenster aus den geplanten Viertelstunden (CHUB_SetPlanWindows)\n";
$t0 = strtotime('tomorrow 02:00');
$w = EMS::slotsToWindows([$t0 + 900, $t0, $t0 + 3600, $t0 + 2 * 900], 5000);
check('Zusammenhaengende Viertelstunden werden ein Fenster, Luecken trennen', count($w) === 2 && $w[0]['from'] === $t0 && $w[0]['to'] === $t0 + 2700 && $w[1]['from'] === $t0 + 3600 && $w[1]['to'] === $t0 + 4500, json_encode($w));
check('Fenster tragen die geplante Leistung', $w[0]['powerW'] === 5000);
check('Ohne Leistung kein powerW-Feld', !isset(EMS::slotsToWindows([$t0], 0)[0]['powerW']));

$wbIid = 916100;
$entryOk = ['source' => 'chargerhub', 'instanceID' => $wbIid];
$plan = ['slots' => [$t0, $t0 + 900], 'powerW' => 5000, 'deadline' => $t0 + 6 * 3600];
$GLOBALS['PLANWIN'] = [];
attr('WB1WinSent', '');
call($ems, 'publishWallboxWindows', [1, $plan, $entryOk]);
$pw = $GLOBALS['PLANWIN'][0] ?? null;
check('Fenster gehen an den ChargerHub, gueltig bis zur Frist', $pw !== null && $pw[0] === $wbIid && count(json_decode($pw[1], true)) === 1 && $pw[2] === $t0 + 6 * 3600, json_encode($pw));
call($ems, 'publishWallboxWindows', [1, $plan, $entryOk]);
check('Unveraenderte Fenster werden nicht erneut gesendet', count($GLOBALS['PLANWIN']) === 1);
call($ems, 'publishWallboxWindows', [1, null, $entryOk]);
check('Plan weg: einmal leere Liste senden (Fenster loeschen)', count($GLOBALS['PLANWIN']) === 2 && $GLOBALS['PLANWIN'][1][1] === '[]' && $GLOBALS['PLANWIN'][1][2] === 0, json_encode($GLOBALS['PLANWIN'][1] ?? null));
call($ems, 'publishWallboxWindows', [1, null, $entryOk]);
check('Danach keine weiteren leeren Meldungen', count($GLOBALS['PLANWIN']) === 2);
call($ems, 'publishWallboxWindows', [1, $plan, ['source' => 'ocpphub', 'instanceID' => 5]]);
check('Nur an ChargerHub (OCPPHub/Fremdmodule haben die Funktion nicht)', count($GLOBALS['PLANWIN']) === 2);

echo "\n16d) Messung der Ladegeschwindigkeit anstossen (ChargerHub 1.15)\n";
$plugId = 916200;
$GLOBALS['VAR'][$plugId] = ['VariableType' => 0, 'VariableUpdated' => $now];
$GLOBALS['VAL'][$plugId] = true;
$e = ['source' => 'chargerhub', 'instanceID' => $wbIid, 'plugStateID' => $plugId];
$GLOBALS['CURVE'] = [];
attr('WB1CurveTried', 0);
attr('Live_WB1_ChargeMode', 0);
call($ems, 'maybeStartChargeCurve', [1, $e]);
check('Angesteckt + preisgesteuert + kein Messwert: Messung angestossen', $GLOBALS['CURVE'] === [$wbIid], json_encode($GLOBALS['CURVE']));
call($ems, 'maybeStartChargeCurve', [1, $e]);
check('Nur einmal je Sitzung', count($GLOBALS['CURVE']) === 1);
$GLOBALS['VAL'][$plugId] = false;
call($ems, 'maybeStartChargeCurve', [1, $e]);
$GLOBALS['VAL'][$plugId] = true;
call($ems, 'maybeStartChargeCurve', [1, $e + ['measuredMaxPowerW' => 0]]);
check('Abstecken und neu anstecken: neue Messung', count($GLOBALS['CURVE']) === 2);
attr('WB1CurveTried', 0);
call($ems, 'maybeStartChargeCurve', [1, $e + ['measuredMaxPowerW' => 5000]]);
check('Schon gemessen: keine neue Messung', count($GLOBALS['CURVE']) === 2);
call($ems, 'maybeStartChargeCurve', [1, $e + ['calibrating' => true]]);
check('Messung laeuft schon: nicht erneut', count($GLOBALS['CURVE']) === 2);
attr('Live_WB1_ChargeMode', 2);
call($ems, 'maybeStartChargeCurve', [1, $e]);
check('Andere Ladeart als preisgesteuert (nur PV): keine Messung', count($GLOBALS['CURVE']) === 2);
attr('Live_WB1_ChargeMode', 0);
call($ems, 'maybeStartChargeCurve', [1, ['source' => 'ocpphub', 'instanceID' => 5, 'plugStateID' => $plugId]]);
check('Nur ChargerHub kann messen', count($GLOBALS['CURVE']) === 2);
attr('WB1CurveTried', 0);
attr('WB1WinSent', '');
