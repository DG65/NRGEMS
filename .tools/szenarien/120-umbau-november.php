<?php
// Kapitel des EMS-Pruefstands: Fahrzeug-Vertrag, Schreib-Ueberwachung, Umbau-Schritte 1-4, Referenzlauf, kurze Beschriftungen.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8am) Fahrzeug-Discovery nach Vertrag vehicle 1.0 (0.104.0, SUITE.md 15.11.2026)\n";
if (method_exists('EMS', 'discoverVehicles')) {
$ems = freshEms();
$GLOBALS['MODULES'] = [
    '{B-DEV}'  => ['Prefix' => 'BMWX', 'ModuleType' => 3],
    '{B-CONF}' => ['Prefix' => 'BMWX', 'ModuleType' => 4],
    '{X-DEV}'  => ['Prefix' => 'NOVEH', 'ModuleType' => 3],
];
$GLOBALS['INSTMOD'][71001] = '{B-DEV}'; $GLOBALS['INSTMOD'][71002] = '{B-DEV}'; $GLOBALS['INSTMOD'][71003] = '{B-DEV}';
$GLOBALS['INSTMOD'][71004] = '{B-CONF}'; $GLOBALS['INSTMOD'][71005] = GUID_TESSIEVEHICLE;
$GLOBALS['INSTSTATUS'][71002] = 104;
$GLOBALS['VEHDATA'] = [
    71001 => json_encode(['contractType' => 'vehicle', 'contractVersion' => '1.3', 'provider' => 'BMWX', 'name' => 'i4', 'soc' => 55.5, 'connected' => true, 'charging' => false, 'chargeLimit' => 80, 'atHome' => true, 'batteryCapacityKwh' => 80.7, 'lastSeenAt' => 1794700000]),
    71003 => ['contractType' => 'vehicle', 'contractVersion' => '2.0', 'soc' => 10],
    71004 => ['contractType' => 'vehicle', 'contractVersion' => '1.0', 'soc' => 1],
];
$GLOBALS['TESSIE_STATE'] = [71005 => json_encode(['contractVersion' => '1.6', 'name' => 'Model Y', 'soc' => 70, 'charging' => true, 'chargeLimit' => 90, 'scheduledChargingActive' => true])];
$v = call($ems, 'discoverVehicles');
$byId = []; foreach ($v['active'] as $e) { $byId[$e['instanceID']] = $e; }
check('BMW-Geraet (Typ 3, Status 102, Major 1, Minor 3) wird angenommen', isset($byId[71001]) && $byId[71001]['soc'] == 55.5 && $byId[71001]['provider'] === 'BMWX', json_encode($v));
check('Konfigurator (Typ 4) mit gleichem Praefix wird nie abgefragt', !isset($byId[71004]) && !in_array(71004, array_column($v['inactive'], 'instanceID'), true));
$GLOBALS['INSTMOD'][71006] = '{B-DEV}'; $GLOBALS['INSTSTATUS'][71006] = 203;
$GLOBALS['VEHDATA'][71006] = ['contractType' => 'vehicle', 'contractVersion' => '1.0', 'soc' => 40, 'vehicleStatus' => 'asleep', 'lastSeenAt' => 1794700000];
$v = call($ems, 'discoverVehicles');
$byId = []; foreach ($v['active'] as $e) { $byId[$e['instanceID']] = $e; }
check('Status 203 (schlafendes Auto, Warnung) wird abgefragt und angenommen', isset($byId[71006]), json_encode($v['inactive']));
check('Status 104 wird als inaktiv gefuehrt, nicht abgefragt', !isset($byId[71002]) && in_array(71002, array_column($v['inactive'], 'instanceID'), true));
check('Major 2 wird abgelehnt und als inaktiv gefuehrt', !isset($byId[71003]) && in_array(71003, array_column($v['inactive'], 'instanceID'), true));
check('Alter Tessie ohne GetVehicleData kommt ueber GetVehicleState, lastSeenAt = null', isset($byId[71005]) && array_key_exists('lastSeenAt', $byId[71005]) && $byId[71005]['lastSeenAt'] === null && $byId[71005]['provider'] === 'TESSIE');
$GLOBALS['MODULES'][GUID_TESSIEVEHICLE] = ['Prefix' => 'BMWX', 'ModuleType' => 3]; // Tessie-GUID liefert nun selbst GetVehicleData
$GLOBALS['VEHDATA'][71005] = ['contractType' => 'vehicle', 'contractVersion' => '1.0', 'provider' => 'TESSIE', 'soc' => 71, 'lastSeenAt' => 1794700100];
$v2 = call($ems, 'discoverVehicles');
$cnt = count(array_filter($v2['active'], fn($e) => $e['instanceID'] === 71005));
check('Tessie mit GetVehicleData wird nicht doppelt ueber GetVehicleState gelesen', $cnt === 1, json_encode($v2['active']));
prop('VEH_Home_Radius_Km', 50);
$vHome = ['instanceID' => 1, 'atHome' => true, 'headingHome' => true, 'distanceToHomeKm' => 0, 'expectedHomeArrivalSocPercent' => 55, 'chargeLimit' => 80, 'batteryCapacityKwh' => 55.5, 'lastSeenAt' => time() - 60];
$vWay  = array_merge($vHome, ['atHome' => false, 'distanceToHomeKm' => 20]);
attr('PartnerCache', json_encode(['vehicles' => [$vHome]]));
check('Fahrzeugreserve: Auto schon zuhause mit altem Navi-Ziel Zuhause = 0 kWh', abs(call($ems, 'computeVehicleReserveKwh')) < 1e-9);
attr('PartnerCache', json_encode(['vehicles' => [$vWay]]));
check('Fahrzeugreserve: Auto 20 km auf dem Heimweg = (80-55) % x 55,5 = 13,9 kWh', abs(call($ems, 'computeVehicleReserveKwh') - 0.25 * 55.5) < 1e-6);
attr('PartnerCache', json_encode(['vehicles' => [array_merge($vWay, ['lastSeenAt' => null])]]));
check('Fahrzeugreserve: Heimweg ohne lastSeenAt = 0 kWh', abs(call($ems, 'computeVehicleReserveKwh')) < 1e-9);
attr('PartnerCache', '');
check('vehicleFresh: lastSeenAt null = Altrest', call($ems, 'vehicleFresh', [['lastSeenAt' => null], 3600]) === false);
check('vehicleFresh: 10 min alt = frisch, 2 h alt bei 1 h Grenze = alt', call($ems, 'vehicleFresh', [['lastSeenAt' => time() - 600], 3600]) === true && call($ems, 'vehicleFresh', [['lastSeenAt' => time() - 7200], 3600]) === false);
check('vehicleFresh: alter Tessie-Stand (legacy) gilt als frisch', call($ems, 'vehicleFresh', [['legacy' => true], 3600]) === true);
unset($GLOBALS['MODULES'], $GLOBALS['VEHDATA'], $GLOBALS['TESSIE_STATE']);
foreach ([71001, 71002, 71003, 71004, 71005, 71006] as $i) { unset($GLOBALS['INSTMOD'][$i], $GLOBALS['INSTSTATUS'][$i]); }
}

echo "\n8an) Schreib-Ueberwachung, Zyklus-Luecke, Push bei Netzladen ohne Wirkung (0.105.0, Vorfall Paul 15.11.2026)\n";
if (method_exists('EMS', 'checkWriteStalls')) {
$ems = freshEms();
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$GLOBALS['INSTMOD'][990] = GUID_WEBFRONT; prop('NOTIFY_Visualization_ID', 990);
$fT = vari('fail_time', IHUB_IID, 'ctl_write_fail_time', 0, 1);
$oT = vari('ok_time', IHUB_IID, 'ctl_write_ok_time', 0, 1);
vari('fail_text', IHUB_IID, 'ctl_write_fail_text', 'keine Verbindung (110)', 3);
$now = 1795000000; $GLOBALS['PUSH'] = [];
$GLOBALS['VAL'][$oT] = $now - 30; $GLOBALS['VAL'][$fT] = $now - 600;
check('Erfolg nach dem letzten Fehler = kein Alarm', call($ems, 'checkWriteStalls', [$now]) === [] && empty($GLOBALS['PUSH']));
$GLOBALS['VAL'][$oT] = $now - 300; $GLOBALS['VAL'][$fT] = $now - 120;
check('Fehler juenger als Erfolg, erst seit 2 min = noch kein Alarm', call($ems, 'checkWriteStalls', [$now]) === [] && empty($GLOBALS['PUSH']));
check('Nach 3 min ununterbrochen = Alarm mit Push und Fehlergrund', call($ems, 'checkWriteStalls', [$now + 70]) === [IHUB_IID => 'alarm'] && count($GLOBALS['PUSH']) === 1 && strpos($GLOBALS['PUSH'][0][1], '110') !== false && strpos($GLOBALS['PUSH'][0][1], 'Wechselrichter') !== false, json_encode($GLOBALS['PUSH']));
check('Alarm kommt nur einmal', call($ems, 'checkWriteStalls', [$now + 400]) === [] && count($GLOBALS['PUSH']) === 1);
$GLOBALS['VAL'][$oT] = $now + 410;
check('Erholung wird einmal gemeldet', call($ems, 'checkWriteStalls', [$now + 420]) === [IHUB_IID => 'recovered'] && count($GLOBALS['PUSH']) === 2 && call($ems, 'checkWriteStalls', [$now + 450]) === [] && count($GLOBALS['PUSH']) === 2);
$GLOBALS['INSTMOD'][991] = '{B5B875BB-9B76-45FD-4E67-2607E45B3AC4}'; prop('NOTIFY_Visualization_ID', 991);
$GLOBALS['VAL'][$fT] = $now + 500; call($ems, 'checkWriteStalls', [$now + 500]);
check('Kachel-Visualisierung bekommt den Push ueber VISU_PostNotificationEx', call($ems, 'checkWriteStalls', [$now + 700]) === [IHUB_IID => 'alarm'] && (end($GLOBALS['PUSH'])[2] ?? '') === 'visu', json_encode(end($GLOBALS['PUSH'])));
// 0.105.2: Wallbox ueber ChargerHub, nur wenn das EMS Wallboxen steuert
$GLOBALS['VAL'][$oT] = $now + 800;
call($ems, 'checkWriteStalls', [$now + 800]);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]], 'chargerhub' => [['instanceID' => 402, 'label' => 'WB']]]));
$cF = vari('fail_time', 402, 'ctl_write_fail_time', $now + 900, 1);
vari('ok_time', 402, 'ctl_write_ok_time', $now + 100, 1);
$n0 = count($GLOBALS['PUSH']);
prop('WB_Active', false);
check('Wallbox ohne aktive EMS-Wallboxsteuerung wird nicht ueberwacht', call($ems, 'checkWriteStalls', [$now + 900]) === [] && call($ems, 'checkWriteStalls', [$now + 1200]) === [] && count($GLOBALS['PUSH']) === $n0);
prop('WB_Active', true); prop('WB_Count', 1);
$r = call($ems, 'checkWriteStalls', [$now + 1200]);
check('ChargerHub-Wallbox mit haengendem Schreiben = Alarm "Wallbox 1", Wechselrichter bleibt still', $r === [402 => 'alarm'] && strpos(end($GLOBALS['PUSH'])[1], 'Wallbox 1') !== false, json_encode([$r, end($GLOBALS['PUSH'])]));
$lst = array_column($ems->GetWebFrontInstances(), 'value');
check('Auswahlliste zeigt WebFront und Kachel-Visualisierung (Live-Fund Paul 17.11.2026)', in_array(990, $lst, true) && in_array(991, $lst, true), json_encode($lst));
prop('NOTIFY_Visualization_ID', 0); $n = count($GLOBALS['PUSH']);
call($ems, 'notifyUser', ['x', 'y']);
check('Ohne eingestellte Visualisierung kein Push', count($GLOBALS['PUSH']) === $n);
$ems = freshEms();
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => 401, 'controlAuthority' => 'ems', 'controllable' => true]]]));
check('InverterHub ohne ctl_write_*-Variablen (vor 0.86.0) = still', call($ems, 'checkWriteStalls', [$now]) === []);
check('Erster Durchlauf ohne Vorgaenger = keine Luecke', call($ems, 'checkCycleGap', [$now]) === null);
check('60 s Abstand = keine Luecke', call($ems, 'checkCycleGap', [$now + 60]) === null);
$GLOBALS['KERNEL_START'] = $now + 3000;
$g = call($ems, 'checkCycleGap', [$now + 60 + 72 * 60]);
check('72 min Luecke mit Symcon-Neustart darin wird protokolliert', is_string($g) && strpos($g, '72 min') !== false && strpos($g, 'Neustart: ja') !== false, (string)$g);
$GLOBALS['KERNEL_START'] = $now - 99999;
$g = call($ems, 'checkCycleGap', [$now + 60 + 72 * 60 + 600]);
check('10 min Luecke ohne Neustart: "nein"', is_string($g) && strpos($g, 'Neustart: nein') !== false, (string)$g);
unset($GLOBALS['INSTMOD'][990], $GLOBALS['INSTMOD'][991], $GLOBALS['KERNEL_START']);
}

echo "\n8ao) Umbau-Absicherung: GoodWe-Totmann und Auto aus dem Netz (Verhalten von 0.105.3 festgehalten)\n";
$ems = freshEms();
obj(410, 1, 'GoodWe-Totmann-Test', 0);
$vDm = vari('EMS Modus', 410, 'ctl_ems_mode', 1, 1);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => 410, 'controlAuthority' => 'ems', 'controllable' => true]]]));
prop('WATCHDOG_Deadman_Reaction', 1);
$dmActs = function () { return array_map(fn($a) => $a[1] . '=' . json_encode($a[2]), array_filter($GLOBALS['ACTIONS'], fn($a) => $a[0] === 410)); };
$GLOBALS['ACTIONS'] = []; call($ems, 'handleGoodweDeadman');
check('Totmann: Modus 1 am Geraet = keine Reaktion', $dmActs() === [], json_encode($dmActs()));
$GLOBALS['VAL'][$vDm] = 255; $GLOBALS['ACTIONS'] = []; call($ems, 'handleGoodweDeadman');
check('Totmann: Wechsel auf 255, Reaktion 1 = enable=false, Modus 1, Leistung 0 in dieser Reihenfolge',
    array_values($dmActs()) === ['ctl_ems_enable=false', 'ctl_ems_mode=1', 'ctl_ems_power=0'], json_encode($dmActs()));
$GLOBALS['ACTIONS'] = []; call($ems, 'handleGoodweDeadman');
check('Totmann: bleibt 255 = keine erneute Reaktion (nur die Flanke zaehlt)', $dmActs() === [], json_encode($dmActs()));
prop('WATCHDOG_Deadman_Reaction', 0); $GLOBALS['VAL'][$vDm] = 1; call($ems, 'handleGoodweDeadman');
$GLOBALS['VAL'][$vDm] = 255; $GLOBALS['ACTIONS'] = []; call($ems, 'handleGoodweDeadman');
check('Totmann: Reaktion 0 (Sicherheits-Stopp respektieren) = nichts schreiben', $dmActs() === [], json_encode($dmActs()));
prop('WATCHDOG_Deadman_Reaction', 1); attr('DeadmanTriggerLog', json_encode([time() - 100, time() - 200, time() - 300])); attr('DeadmanBrakeTripped', false);
$GLOBALS['VAL'][$vDm] = 1; call($ems, 'handleGoodweDeadman'); $GLOBALS['VAL'][$vDm] = 255; $GLOBALS['ACTIONS'] = []; call($ems, 'handleGoodweDeadman');
check('Totmann: vierte Ausloesung in einer Stunde = Pendel-Bremse, keine Schreibbefehle mehr',
    $dmActs() === [] && $ems->ReadAttributeBoolean('DeadmanBrakeTripped') === true, json_encode([$dmActs(), $ems->ReadAttributeBoolean('DeadmanBrakeTripped')]));
attr('PartnerCache', '');

$ems = freshEms();
prop('WB_Smart_HouseFromBattery', true); prop('BAT_SOC_Min', 10); prop('BAT_SOC_Reserve_Backup', 0);
$sC = ['bat_soc' => 60.0, 'pv_total_w' => 0.0, 'house_pow_w' => 500.0, 'grid_total_w' => -7900.0, 'bat_pow_w' => 500.0];
$r = call($ems, 'carFromGridSetting', [7400.0, $sC]);
check('Auto aus dem Netz: Normalfall = Modus 9 mit Auto-Leistung minus 200 W', $r['gw_mode'] === GW_MODE_BUY && $r['gw_power_w'] === 7200 && $r['gw_enable'] === true, json_encode($r));
prop('WB_Smart_HouseFromBattery', false);
$r = call($ems, 'carFromGridSetting', [7400.0, $sC]);
check('Auto aus dem Netz: Schalter "Haus aus dem Akku" aus = Modus 2 ohne Leistung', $r['gw_mode'] === GW_MODE_CHARGE_PV && $r['gw_power_w'] === 0, json_encode($r));
prop('WB_Smart_HouseFromBattery', true);
$r = call($ems, 'carFromGridSetting', [400.0, $sC]);
check('Auto aus dem Netz: Auto unter 500 W (Sollwert < 300 W) = Modus 2', $r['gw_mode'] === GW_MODE_CHARGE_PV, json_encode($r));
$r = call($ems, 'carFromGridSetting', [7400.0, array_merge($sC, ['bat_soc' => 11.0])]);
check('Auto aus dem Netz: Akku an der Reserve = Modus 2', $r['gw_mode'] === GW_MODE_CHARGE_PV, json_encode($r));
$r = call($ems, 'carFromGridSetting', [7400.0, array_merge($sC, ['bat_soc' => 96.0, 'pv_total_w' => 3000.0])]);
check('Auto aus dem Netz: Akku fast voll mit PV-Ueberschuss = Modus 2 und 10 min Haltezeit',
    $r['gw_mode'] === GW_MODE_CHARGE_PV && $ems->ReadAttributeInteger('SmartFullHoldUntil') > time() + 500, json_encode($r));

echo "\n8ap) Umbau 1: nachgelagerte Schritte als feste Liste mit Vorrang (vorher Einzelaufrufe in Update())\n";
$ems = freshEms();
check('Reihenfolge der Schritte unveraendert gegenueber 0.105.3 (spaeter = hoeherer Vorrang, Sicherheit zuletzt)',
    array_column(call($ems, 'decisionSteps'), 0) === ['smart_charging_grid_buy', 'grid_service_b1', 'negative_price_note', 'plausibility', 'charge_no_effect', 'export_overlap', 'phase_guard']);
check('Jeder Schritt verweist auf eine vorhandene Methode', count(array_filter(call($ems, 'decisionSteps'), fn($x) => method_exists('EMS', $x[2]))) === 7);
$dP = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Tagesplan: Automatik', 'source' => 'tagesplan'];
$r = call($ems, 'runDecisionSteps', [$dP, state(['bat_soc' => 70.0, 'pv_total_w' => 0.0, 'bat_pow_w' => 400.0, 'grid_total_w' => -20.0])]);
check('Unauffaelliger Zustand: Entscheidung bleibt, kein Schritt protokolliert', $r === $dP && $ems->ReadAttributeString('LastDecisionSteps') === '[]', json_encode([$r, $ems->ReadAttributeString('LastDecisionSteps')]));
attr('PlausiSince', time() - 400);
$r = call($ems, 'runDecisionSteps', [$dP, state(['bat_soc' => 70.0, 'pv_total_w' => 0.0, 'bat_pow_w' => -12.0, 'grid_total_w' => -420.0])]);
check('Plausibilitaetswaechter greift: Rueckfall in WR-Eigenregelung, Schritt "plausibility" protokolliert',
    isNativeAuto($r) && !empty($r['force']) && in_array('plausibility', json_decode($ems->ReadAttributeString('LastDecisionSteps'), true), true), json_encode([$r['reason'] ?? '', $ems->ReadAttributeString('LastDecisionSteps')]));

echo "\n8aq) 0.105.5: Statuszeile Auto aus dem Netz am Solis, Formularzeile letzter Korrekturschritt\n";
$ems = freshEms();
prop('WB_Smart_HouseFromBattery', true); prop('BAT_SOC_Min', 10); prop('BAT_SOC_Reserve_Backup', 0);
obj(420, 1, 'Solis-Test', 0); vari('Zwangsmodus', 420, 'ctl_force_mode', 0, 1); vari('Zwangsleistung', 420, 'ctl_force_w', 0, 1);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => 420, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$r = call($ems, 'carFromGridSetting', [10100.0, ['bat_soc' => 72.0, 'pv_total_w' => 0.0, 'house_pow_w' => 1700.0, 'grid_total_w' => -12100.0, 'bat_pow_w' => 20.0]]);
check('Solis: Text nennt gehaltenen Akku und Haus aus dem Netz (nicht "Haus aus PV und Batterie"), Modus bleibt 9 fuer setSolisMode',
    $r['gw_mode'] === GW_MODE_BUY && strpos($r['text'], 'Akku wird gehalten') !== false && strpos($r['text'], 'Haus ebenfalls aus dem Netz') !== false, json_encode($r, JSON_UNESCAPED_UNICODE));
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => 410, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$r = call($ems, 'carFromGridSetting', [10100.0, ['bat_soc' => 72.0, 'pv_total_w' => 0.0, 'house_pow_w' => 1700.0, 'grid_total_w' => -12100.0, 'bat_pow_w' => 20.0]]);
check('GoodWe (ctl_ems_mode vorhanden): Text unveraendert "Haus aus PV und Batterie"', strpos($r['text'], 'Haus aus PV und Batterie') !== false, json_encode($r, JSON_UNESCAPED_UNICODE));
attr('PartnerCache', '');
attr('LastDecisionSteps', '[]');
check('Formularzeile ohne Eingriff', strpos(call($ems, 'decisionStepsStatusLine'), 'unverändert aus dem Plan') !== false);
attr('LastDecisionSteps', json_encode(['plausibility', 'export_overlap']));
check('Formularzeile nennt die eingreifenden Schritte in Reihenfolge', call($ems, 'decisionStepsStatusLine') === 'Letzte Entscheidung geändert durch: Plausibilitätswächter → Einspeise-Überwachung', call($ems, 'decisionStepsStatusLine'));

echo "\n8ar) Umbau 2: Referenzlauf Planung (golden-planung.json; neu schreiben nur bewusst mit EMS_GOLDEN=write)\n";
$ems = freshEms();
$gold = [];
$rnd = function (int $seed) { mt_srand($seed); };
$base = ['avgHouseW' => 400.0, 'socMin' => 10.0, 'socReserve' => 0.0, 'chargeCurve' => [], 'maxW' => 29900.0, 'houseLoadSlots' => []];
$cases = [
    ['capKwh' => 10.0,  'chargeKw' => 5.0,  'dischargeKw' => 5.0,  'cycleCost' => 0.0,   'feedTariff' => 0.08],
    ['capKwh' => 40.0,  'chargeKw' => 20.0, 'dischargeKw' => 20.0, 'cycleCost' => 0.031, 'feedTariff' => 0.1836],
    ['capKwh' => 40.0,  'chargeKw' => 20.0, 'dischargeKw' => 3.0,  'cycleCost' => 0.0,   'feedTariff' => 0.0],
    ['capKwh' => 114.0, 'chargeKw' => 17.0, 'dischargeKw' => 17.0, 'cycleCost' => 0.015, 'feedTariff' => 0.0],
    ['capKwh' => 114.0, 'chargeKw' => 17.0, 'dischargeKw' => 4.0,  'cycleCost' => 0.0,   'feedTariff' => 0.07, 'socReserve' => 10.0],
    ['capKwh' => 20.0,  'chargeKw' => 10.0, 'dischargeKw' => 10.0, 'cycleCost' => 0.02,  'feedTariff' => 0.12, 'chargeCurve' => [0 => 10.0, 18 => 6.0, 19 => 2.0]],
];
foreach ($cases as $ci => $cx) {
    $ctxG = array_merge($base, $cx);
    foreach ([1, 2] as $variant) {
        $rnd(1000 * $ci + $variant);
        $P = []; $PV = []; $L = [];
        for ($i = 0; $i < 288; $i++) {
            $h = ($i % 96) / 4;
            $P[$i] = round(0.22 + 0.12 * sin(($h - 4) / 24 * 2 * M_PI) + mt_rand(-30, 30) / 1000 + ($variant === 2 && $h > 11 && $h < 14 ? -0.25 : 0.0), 4);
            $PV[$i] = ($h > 7 && $h < 17) ? round(mt_rand(0, 9000) * sin(($h - 7) / 10 * M_PI)) : 0.0;
            $L[$i] = (float)mt_rand(200, 4500);
        }
        $ctxG['houseLoadSlots'] = array_slice($L, 0, 96);
        foreach ([[0, 15.0], [30, 60.0], [70, 98.0]] as $st) {
            $o = call($ems, 'optimizeBattery', [$P, $PV, $L, $st[0], $st[1], $ctxG, [], ($variant === 2 ? 192 : PHP_INT_MAX)]);
            $gold['opt'][$ci . '-' . $variant . '-' . $st[0]] = array_map(fn($x) => $x['act'][0] . round($x['soc'], 1), $o);
        }
        for ($k = 0; $k < 40; $k++) {
            $slot = mt_rand(0, 95); $soc = (float)mt_rand(0, 1000) / 10;
            $r = call($ems, 'simulateAutomatikSlot', [$slot, $PV[$slot], $P[$slot], $soc, $ctxG]);
            $gold['auto'][$ci . '-' . $variant . '-' . $k] = [round($r['soc'], 6), $r['plan']['reason']];
            foreach (['charge', 'hold'] as $act) {
                $r2 = call($ems, 'optimizerPlanSlot', [$slot, $P[$slot], $soc, ['act' => $act, 'soc' => $soc], $ctxG, $PV[$slot]]);
                $gold['plan'][$ci . '-' . $variant . '-' . $k . '-' . $act] = [round($r2['soc'], 6), $r2['plan']['op'], $r2['plan']['power']];
            }
        }
    }
}
$gf = EMS_TOOLS_DIR . '/golden-planung.json';
if (getenv('EMS_GOLDEN') === 'write') { file_put_contents($gf, json_encode($gold, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); echo "  (Referenz neu geschrieben)\n"; }
$ref = is_file($gf) ? json_decode(file_get_contents($gf), true) : null;
$gold = json_decode(json_encode($gold), true); // gleiche Typen wie aus der Datei (60.0 vs 60)
if ($ref !== null) {
    foreach (['opt', 'auto', 'plan'] as $part) {
        $diff = []; foreach ($ref[$part] as $k => $v) { if (($gold[$part][$k] ?? null) !== $v) { $diff[] = $k; } }
        check('Referenzlauf ' . $part . ': ' . count($ref[$part]) . ' Faelle gleich wie gespeichert', $diff === [], count($diff) . ' abweichend, z. B. ' . json_encode(array_slice($diff, 0, 3)));
    }
}

echo "\n8as) Umbau 2b: eine Erkennung der Wechselrichter-Art\n";
$ems = freshEms();
obj(430, 1, 'GoodWe-Art', 0); vari('m', 430, 'ctl_ems_mode', 1, 1); vari('p', 430, 'ctl_ems_power', 0, 1); vari('e', 430, 'ctl_ems_enable', false, 0);
obj(431, 1, 'Solis-Art', 0); vari('f', 431, 'ctl_force_mode', 0, 1); vari('w', 431, 'ctl_force_w', 0, 1);
obj(432, 1, 'Nur-Lesen-Art', 0); vari('x', 432, 'pv_total', 0.0, 2);
obj(433, 1, 'Unvollstaendig', 0); vari('m', 433, 'ctl_ems_mode', 1, 1);
check('Wechselrichter-Art: GoodWe / Solis / nur lesbar / keine Instanz',
    [call($ems, 'inverterKind', [430]), call($ems, 'inverterKind', [431]), call($ems, 'inverterKind', [432]), call($ems, 'inverterKind', [0])] === ['goodwe', 'solis', 'readonly', 'none']);
check('ctl_ems_mode ohne -power/-enable gilt als nur lesbar (wie bisher in setGoodweMode)', call($ems, 'inverterKind', [433]) === 'readonly');
obj(434, 1, 'Beides', 0); vari('m', 434, 'ctl_ems_mode', 1, 1); vari('f', 434, 'ctl_force_mode', 0, 1); vari('w', 434, 'ctl_force_w', 0, 1);
check('Zwangsmodus-Punkte UND ctl_ems_mode: kein Solis (GoodWe-Weg bleibt unberuehrt)', call($ems, 'inverterKind', [434]) !== 'solis');

echo "\n8at) 0.106.6: kurze Beschriftungen, Erklaerung als Label (Rueckmeldung Hannes, SUITE.md Formular-Regel)\n";
$ems = freshEms();
$fj = json_decode(file_get_contents(EMS_TOOLS_DIR . '/../EMS/form.json'), true);
$loc = json_decode(file_get_contents(EMS_TOOLS_DIR . '/../EMS/locale.json'), true)['translations']['de'];
$long = []; $noHint = [];
$walk = function ($o, $sib = []) use (&$walk, &$long, &$noHint, $loc) {
    if (!is_array($o)) { return; }
    if (isset($o['items']) && is_array($o['items'])) {
        $names = array_column(array_filter($o['items'], 'is_array'), 'name');
        foreach ($o['items'] as $it) { $walk($it, $names); }
    }
    foreach ($o as $k => $v) { if ($k !== 'items' && is_array($v)) { $walk($v); } }
    $inputs = ['NumberSpinner', 'ValidationTextBox', 'Select', 'SelectVariable', 'SelectInstance', 'CheckBox', 'PasswordTextBox', 'SelectTime'];
    if (in_array($o['type'] ?? '', $inputs, true) && isset($o['caption'])) {
        $de = $loc[$o['caption']] ?? $o['caption'];
        if (mb_strlen($de) > 40) { $long[] = $o['name'] ?? $o['caption']; }
    }
};
$walk($fj);
check('Keine Eingabefeld-Beschriftung ueber 40 Zeichen (deutsch)', $long === [], json_encode($long, JSON_UNESCAPED_UNICODE));
$hints = 0; $orph = []; $txt = file_get_contents(EMS_TOOLS_DIR . '/../EMS/form.json');
preg_match_all('/"name": "(\w+)Hint"/', $txt, $m);
foreach ($m[1] as $base) { $hints++; if (strpos($txt, '"name": "' . $base . '"') === false) { $orph[] = $base; } }
check('104 Erklaerungs-Labels mit Feld in form.json (92 aus 0.106.6 + 6 aus 0.106.7 + 3 aus 0.107.0 + 1 aus 0.109.0 + 1 aus 0.110.0 + 1 aus 0.113.0; dazu die zwei alten Hinweise GridOverviewHint/InverterFallbackHint ohne Feld)', $hints - count($orph) === 104 && $orph === ['GridOverview', 'InverterFallback'], json_encode([$hints, $orph]));
$h = call($ems, 'hideHintsOfHiddenFields', [[['type' => 'ExpansionPanel', 'items' => [
    ['type' => 'SelectVariable', 'name' => 'VAR_WB1_Power', 'visible' => false], ['type' => 'Label', 'name' => 'VAR_WB1_PowerHint'],
    ['type' => 'SelectVariable', 'name' => 'VAR_WB1_Cable'], ['type' => 'Label', 'name' => 'VAR_WB1_CableHint']]]]]);
check('Ausgeblendetes Feld: seine Erklaerung wird mit ausgeblendet, sichtbare bleiben',
    ($h[0]['items'][1]['visible'] ?? true) === false && !isset($h[0]['items'][3]['visible']), json_encode($h));

