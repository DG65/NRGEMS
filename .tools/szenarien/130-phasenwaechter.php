<?php
// Kapitel des EMS-Pruefstands: Phasenwaechter (0.107.0, Ausfall Paul 15.11.2026: L1 57,7 A an 50-A-SLS, Fault 1011).

echo "\n8an) Phasenwaechter: Hauptsicherung je Phase schuetzen (0.107.0)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][990] = GUID_WEBFRONT; prop('NOTIFY_Visualization_ID', 990); $GLOBALS['PUSH'] = [];
$ph = [91001, 91002, 91003];
$setI = function (array $a, int $age = 5) use ($ph) {
    foreach ($ph as $i => $id) { $GLOBALS['VAR'][$id] = ['VariableUpdated' => time() - $age, 'VariableType' => 2]; $GLOBALS['VAL'][$id] = $a[$i]; }
};
foreach ($ph as $i => $id) { prop('VAR_Phase_L' . ($i + 1) . '_Current', $id); }
$laden = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_BUY, 'gw_power_w' => 24000, 'gw_enable' => true, 'wb1_enable' => true, 'wb2_enable' => false, 'reason' => 'Plan'];
$setI([57.7, 40.2, 39.7]);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
check('Aus per Standard (SITE_Fuse_A = 0): greift nie ein', $d === $laden, fmt($d));
prop('SITE_Fuse_A', 50); prop('SITE_Fuse_Margin_Pct', 90);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
// Ueberstrom 12,7 A x 3 x 230 = 8763 W -> 24000 - 8763 = 15237 W
check('Fall 15.11.: L1 57,7 A > 45 A, Netzladen 24 kW -> 15,237 kW', $d['gw_power_w'] === 15237, fmt($d));
check('Wallbox bleibt frei, weil Drosseln reicht', $d['wb1_enable'] === true);
check('Push genau einmal mit Phase und Grenze', count($GLOBALS['PUSH']) === 1 && strpos($GLOBALS['PUSH'][0][1], 'L1') !== false && strpos($GLOBALS['PUSH'][0][1], '45') !== false, json_encode($GLOBALS['PUSH']));
$setI([38.0, 37.0, 36.0]);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
check('Haltezeit: Grenze bleibt, obwohl Strom 7 A unter der Grenze', $d['gw_power_w'] === 15237, fmt($d));
check('kein zweiter Push in derselben Episode', count($GLOBALS['PUSH']) === 1);
attr('PhaseCapUntil', time() - 1);
$setI([44.0, 40.0, 39.0]);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
check('nur 1 A Abstand: nicht lockern', $d['gw_power_w'] === 15237, fmt($d));
$setI([38.0, 37.0, 36.0]);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
check('nach Haltezeit mit >= 3 A Abstand: um 1 kW lockern', $d['gw_power_w'] === 16237, fmt($d));
attr('PhaseCapW', 23500);
$d = call($ems, 'applyPhaseGuard', [$laden, state()]);
check('Grenze erreicht Sollwert: aufgehoben, Episode beendet', $d === $laden && $ems->ReadAttributeInteger('PhaseCapW') === -1 && $ems->ReadAttributeInteger('PhaseGuardSince') === 0, fmt($d));

echo "\n   Stufe 2: Wallboxen\n";
$ems = freshEms(); $GLOBALS['PUSH'] = []; prop('NOTIFY_Visualization_ID', 990);
foreach ($ph as $i => $id) { prop('VAR_Phase_L' . ($i + 1) . '_Current', $id); }
prop('SITE_Fuse_A', 50); prop('SITE_Fuse_Margin_Pct', 90);
$ohne = array_merge($laden, ['gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0]);
$setI([52.0, 30.0, 30.0]);
$d = call($ems, 'applyPhaseGuard', [$ohne, state()]);
check('kein Netzladen, L1 ueber Grenze: Wallboxen aus', $d['wb1_enable'] === false && $d['wb2_enable'] === false && $d['gw_mode'] === GW_MODE_AUTO, fmt($d));
check('Push nennt Wallboxen', strpos($GLOBALS['PUSH'][0][1] ?? '', 'Wallbox') !== false, json_encode($GLOBALS['PUSH']));
$ems = freshEms();
foreach ($ph as $i => $id) { prop('VAR_Phase_L' . ($i + 1) . '_Current', $id); }
prop('SITE_Fuse_A', 50); prop('SITE_Fuse_Margin_Pct', 90);
$setI([70.0, 40.0, 40.0]);
$d = call($ems, 'applyPhaseGuard', [array_merge($laden, ['gw_power_w' => 5000]), state()]);
check('Drosseln reicht nicht (25 A zu viel bei 5 kW Netzladen): Netzladen 0 und Wallboxen aus', $d['gw_power_w'] === 0 && $d['wb1_enable'] === false, fmt($d));

echo "\n   Negativfaelle\n";
$ems = freshEms();
foreach ($ph as $i => $id) { prop('VAR_Phase_L' . ($i + 1) . '_Current', $id); }
prop('SITE_Fuse_A', 50);
$setI([57.7, 40.2, 39.7], 600);
check('Messung 10 min alt: kein Eingriff', call($ems, 'applyPhaseGuard', [$laden, state()]) === $laden);
$setI([44.9, 44.0, 43.0]);
check('knapp unter 45 A: kein Eingriff', call($ems, 'applyPhaseGuard', [$laden, state()]) === $laden);
$setI([-57.7, 10.0, 10.0]);
check('negatives Vorzeichen (Einspeisung) zaehlt als Betrag', call($ems, 'applyPhaseGuard', [$laden, state()])['gw_power_w'] < 24000);
$ems = freshEms(); prop('SITE_Fuse_A', 50);
check('keine Stromquelle zugeordnet: kein Eingriff', call($ems, 'applyPhaseGuard', [$laden, state()]) === $laden);
attr('PartnerCache', json_encode(['meterhub' => [['instanceID' => 700, 'phaseCurrentIDs' => $ph]]]));
$setI([57.7, 40.2, 39.7]);
$dbg = call($ems, 'readPhaseCurrents'); check('Vertragsfeld phaseCurrentIDs (MeterHub) wird ohne manuelle Zuordnung genutzt', call($ems, 'applyPhaseGuard', [$laden, state()])['gw_power_w'] === 15237, json_encode($dbg));
