<?php
// Kapitel des EMS-Pruefstands: Wallbox: Ladearten, ChargerHub-Vertrag, Stufen, automatische Werte, Dubletten.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8k9) Wallbox-Ladearten (Dietmar 22.10.2026): manuell, nur PV, PV-Anteil, Hybrid\n";
$base = ['minA' => 6, 'maxA' => 16, 'stepA' => 1.0, 'phases' => 3, 'volt' => 230, 'on' => false, 'sinceSwitchS' => 9999, 'cooldownS' => 120, 'minChargeS' => 300, 'shareP' => 50, 'manualA' => 0, 'surplusW' => 0.0];
$plan = fn(array $o) => call($ems, 'wallboxChargePlan', [array_merge($base, $o)]);
$r = $plan(['mode' => 1]);
check('Manuell ohne Wert: Maximalstrom, freigegeben', $r['enable'] === true && $r['currentA'] == 16.0, json_encode($r));
$r = $plan(['mode' => 1, 'manualA' => 10]);
check('Manuell 10 A', $r['enable'] === true && $r['currentA'] == 10.0);
$r = $plan(['mode' => 1, 'manualA' => 40]);
check('Manuell 40 A wird auf den Maximalstrom begrenzt', $r['currentA'] == 16.0);
$r = $plan(['mode' => 1, 'manualA' => 3]);
check('Manuell 3 A wird auf den Mindeststrom angehoben', $r['currentA'] == 6.0);
$r = $plan(['mode' => 2, 'surplusW' => 5520.0]);
check('Nur PV, 3-phasig: 5520 W Ueberschuss = 8 A (5520 / 690)', $r['enable'] === true && $r['currentA'] == 8.0, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 5520.0, 'phases' => 1]);
check('Nur PV, 1-phasig: 5520 W = 16 A (auf den Maximalstrom begrenzt)', $r['enable'] === true && $r['currentA'] == 16.0, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 20000.0]);
check('Nur PV: sehr viel Ueberschuss -> Maximalstrom', $r['currentA'] == 16.0);
$r = $plan(['mode' => 2, 'surplusW' => 4000.0]);
check('Nur PV: 4000 W = 5,8 A unter dem Mindeststrom 6 A: bleibt aus', $r['enable'] === false, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 4140.0]);
check('Nur PV: genau 6 A (4140 W) startet', $r['enable'] === true && $r['currentA'] == 6.0, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 5520.0, 'sinceSwitchS' => 30]);
check('Nur PV: Start erst nach dem Cooldown (30 s von 120 s), Text nennt die Wartezeit', $r['enable'] === false && strpos($r['text'], 'Start in 90 s') !== false, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 1000.0, 'on' => true, 'sinceSwitchS' => 100]);
check('Nur PV: Ueberschuss weg, laeuft seit 100 s (Wartezeit 300 s = Mindestladezeit): weiter mit Mindeststrom statt Stopp', $r['enable'] === true && $r['currentA'] == 6.0 && strpos($r['text'], 'Stopp in 200 s') !== false, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 1000.0, 'on' => true, 'sinceSwitchS' => 400]);
check('Nur PV: Ueberschuss weg, Wartezeit um: Stopp', $r['enable'] === false, json_encode($r));
$r = $plan(['mode' => 3, 'surplusW' => 11040.0, 'shareP' => 50]);
check('PV-Anteil 50 %: von 11040 W bleiben 5520 W = 8 A', $r['enable'] === true && $r['currentA'] == 8.0, json_encode($r));
$r = $plan(['mode' => 3, 'surplusW' => 11040.0, 'shareP' => 0]);
check('PV-Anteil 0 %: nichts fuers Auto', $r['enable'] === false);
$r = $plan(['mode' => 4, 'surplusW' => 0.0]);
check('Hybrid ohne Ueberschuss: Mindeststrom 6 A bleibt freigegeben', $r['enable'] === true && $r['currentA'] == 6.0, json_encode($r));
$r = $plan(['mode' => 4, 'surplusW' => 5520.0]);
check('Hybrid mit 5520 W Ueberschuss: 8 A', $r['enable'] === true && $r['currentA'] == 8.0, json_encode($r));
$r = $plan(['mode' => 2, 'surplusW' => 3700.0, 'minA' => 6, 'stepA' => 0.1, 'phases' => 1]);
check('Schrittweite 0,1 A (Alfen): 3700 W einphasig = 16 A (max), 1500 W = 6,5 A', $plan(['mode' => 2, 'surplusW' => 1500.0, 'stepA' => 0.1, 'phases' => 1])['currentA'] == 6.5, json_encode($plan(['mode' => 2, 'surplusW' => 1500.0, 'stepA' => 0.1, 'phases' => 1])));
$r = $plan(['mode' => 2, 'surplusW' => -500.0]);
check('Negativer Ueberschuss rechnet als 0', $r['enable'] === false);

echo "   Ansteuerung\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2); prop('WB1_Max_Power_W', 11000); prop('WB_Cooldown_Sec', 120); prop('WB_Min_Charge_Min', 5);
$cenA = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', false, 0);
$curA = vari('CHUB WB1 Stromgrenze', 600, 'ctl_curr_limit', 16, 1);
$phA  = vari('CHUB WB1 Phasenmodus', 600, 'ctl_phase_mode', 2, 1);
$whA = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenA, 'currentLimitID' => $curA, 'minCurrent' => 6, 'maxCurrent' => 16, 'currentStepA' => 1.0,
        'phases' => 3, 'phaseModeID' => $phA, 'phaseModeValues' => ['one' => 1, 'three' => 2, 'auto' => 0], 'phaseSwitchPauseSec' => 120,
        'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0];
attr('PartnerCache', json_encode(['chargerhub' => [$whA]]));
$wbActs = fn() => array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit', 'ctl_phase_mode'], true)));
$sPv = fn(array $o = []) => state(array_merge(['wb_active' => true, 'wb_count' => 1, 'pv_total_w' => 7000.0, 'house_pow_w' => 1480.0], $o)); // Ueberschuss 5520 W
$reset = function () use ($cenA) { $GLOBALS['VAL'][$cenA] = false; attr('LastWB1Switch', 0); attr('WB1EmsLocked', false); attr('WB1LastSentEnable', false); attr('WB1CurrentSentA', -1); attr('WB1CurrentSentAt', 0); attr('WB1PhaseSentAt', 0); $GLOBALS['ACTIONS'] = []; };
$d0 = fn() => ['wb1_enable' => false, 'wb2_enable' => false, 'source' => 'tagesplan', 'reason' => ''];

$reset(); prop('WB1_ChargeMode', 0); $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Standard Preisgesteuert: Verhalten wie bisher, wb1_enable=false sperrt (hier schon gesperrt: nichts zu schreiben), keine Ladeart-Schreibzugriffe', $wbActs() === [] && $ems->ReadAttributeString('WB1ChargeText') === '', json_encode($GLOBALS['ACTIONS']));
$reset(); prop('WB1_ChargeMode', 2); $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Nur PV mit 5520 W Ueberschuss: erst 8 A, dann Freigabe, die Preisentscheidung (gesperrt) wird uebergangen', $wbActs() === [[600, 'ctl_curr_limit', 8], [600, 'ctl_enable', true]] && $d['wb1_enable'] === true, json_encode($GLOBALS['ACTIONS']));
check('Panel-Text der Ladeart steht in der Statuszeile', strpos(call($ems, 'wallboxModeStatusLine', [1])['caption'], 'Ladeart: Nur PV: 8 A bei 5520 W') !== false, json_encode(call($ems, 'wallboxModeStatusLine', [1])));
// laeuft, Ueberschuss steigt: nur Strom nachfuehren, hoechstens alle 30 s
$GLOBALS['VAL'][$cenA] = true; attr('WB1LastSentEnable', true); $GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv(['pv_total_w' => 8000.0])]);
check('Strom innerhalb von 30 s nach dem letzten Schreiben nicht neu geschrieben', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));
attr('WB1CurrentSentAt', time() - 31); $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv(['pv_total_w' => 8000.0])]);
check('nach 31 s: neuer Strom 9 A (6520 W), keine erneute Freigabe', $wbActs() === [[600, 'ctl_curr_limit', 9]], json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv(['pv_total_w' => 8000.0])]);
check('gleicher Strom: nichts geschrieben', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));
// Andere Wallbox verbraucht: Ueberschuss sinkt
attr('WB1CurrentSentAt', time() - 31); $GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv(['pv_total_w' => 8000.0, 'wb2_pow_kw' => 2.76])]);
check('die zweite Wallbox zaehlt vom Ueberschuss ab (6520 - 2760 = 3760 W = unter 6 A): Mindeststrom bis Stopp-Wartezeit', $wbActs() === [[600, 'ctl_curr_limit', 6]], json_encode($GLOBALS['ACTIONS']));
// Ueberschuss weg, Wartezeit um: Sperre ueber die alte Schreibstelle
attr('LastWB1Switch', time() - 1000); $GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv(['pv_total_w' => 1500.0])]);
check('Ueberschuss weg und Wartezeit um: ctl_enable=false (kein 0-A-Strom), Sperre gemerkt', $wbActs() === [[600, 'ctl_enable', false]] && $ems->ReadAttributeBoolean('WB1EmsLocked') === true, json_encode($GLOBALS['ACTIONS']));
// Vorrang: Netzbetreiber und Boost
$reset(); $d = array_merge($d0(), ['source' => 'netzbetreiber']); $GLOBALS['VAL'][$cenA] = true; attr('WB1LastSentEnable', true);
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Netzbetreiber-Sperre (§14a) geht vor der Ladeart: Wallbox wird gesperrt', $wbActs() === [[600, 'ctl_enable', false]], json_encode($GLOBALS['ACTIONS']));
$reset(); $d = array_merge($d0(), ['source' => 'nutzer', 'wb1_enable' => true]);
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Batterie-Boost geht vor der Ladeart: Freigabe wie bisher mit Maximalstrom', $wbActs() === [[600, 'ctl_curr_limit', 16], [600, 'ctl_enable', true]], json_encode($GLOBALS['ACTIONS']));
// Smart Charging / beobachten: Ladeart wird nicht angefasst
foreach ([1, 3] as $wm) {
    $reset(); prop('WB1_Mode', $wm); $d = $d0();
    call($ems, 'applyWallboxControl', [&$d, $sPv()]);
    check('Wallbox-Modus ' . $wm . ' (Smart Charging / nur beobachten): Ladeart schreibt nichts', array_filter($wbActs(), fn($a) => $a[1] === 'ctl_curr_limit') === [], json_encode($GLOBALS['ACTIONS']));
}
prop('WB1_Mode', 0);
// Wallbox ohne Stromsollwert: Rueckfall auf Preissteuerung mit Hinweis
$whNo = $whA; unset($whNo['currentLimitID']);
attr('PartnerCache', json_encode(['chargerhub' => [$whNo]]));
$reset(); $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Wallbox ohne Stromsollwert (currentLimitID): kein Ladeart-Schreiben, Hinweis im Text', $wbActs() === [] && strpos($ems->ReadAttributeString('WB1ChargeText'), 'keinen schreibbaren Stromsollwert') !== false, json_encode($GLOBALS['ACTIONS']) . $ems->ReadAttributeString('WB1ChargeText'));
attr('PartnerCache', json_encode(['chargerhub' => [$whA]]));
// Phasen
$reset(); prop('WB1_PhaseSetting', 1); $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Phasenwahl einphasig: ctl_phase_mode=1 (Wert aus phaseModeValues), vor Strom und Freigabe', ($wbActs()[0] ?? null) === [600, 'ctl_phase_mode', 1], json_encode($GLOBALS['ACTIONS']));
check('einphasig gerechnet: 5520 W / 230 V = 16 A (max)', in_array([600, 'ctl_curr_limit', 16], $wbActs(), true), json_encode($GLOBALS['ACTIONS']));
$GLOBALS['VAL'][$phA] = 1; $GLOBALS['ACTIONS'] = []; $d = $d0(); attr('WB1CurrentSentAt', time() - 31);
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Phasenmodus steht schon richtig: nicht erneut geschrieben', array_filter($wbActs(), fn($a) => $a[1] === 'ctl_phase_mode') === [], json_encode($GLOBALS['ACTIONS']));
$GLOBALS['VAL'][$phA] = 2; attr('LastWB1Switch', 0); attr('WB1PhaseSentAt', time() - 30); $GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('Phasenmodus weicht ab, aber unter 120 s seit dem letzten Wechsel: nicht erneut geschrieben', array_filter($wbActs(), fn($a) => $a[1] === 'ctl_phase_mode') === [], json_encode($GLOBALS['ACTIONS']));
attr('WB1PhaseSentAt', time() - 121); attr('LastWB1Switch', 0); $GLOBALS['ACTIONS'] = []; $d = $d0();
call($ems, 'applyWallboxControl', [&$d, $sPv()]);
check('nach 121 s: Phasenmodus wird erneut geschrieben', array_filter($wbActs(), fn($a) => $a[1] === 'ctl_phase_mode') !== [], json_encode($GLOBALS['ACTIONS']));
prop('WB1_PhaseSetting', 0); prop('WB1_ChargeMode', 0);
// 0.91.0: EMS_GetWallboxes -- Zuordnung n <-> ChargerHub-Instanz fuers Dashboard-Cockpit
$gw = call($ems, 'GetWallboxes', []);
$w1 = $gw['wallboxes'][0] ?? [];
check('GetWallboxes: Vertrag 1.0, WB1 = ChargerHub #600, EMS steuert, Ladeart 0', $gw['contractVersion'] === '1.0' && ($w1['n'] ?? 0) === 1 && $w1['instanceID'] === 600 && $w1['source'] === 'chargerhub' && $w1['controlInstanceID'] === 600 && $w1['mode'] === 'ems' && $w1['emsControls'] === true && $w1['chargeMode'] === 0, json_encode($gw));
prop('WB1_Mode', 1);
$w1 = call($ems, 'GetWallboxes', [])['wallboxes'][0];
check('GetWallboxes: Smart Charging (Tibber steuert) -> emsControls false, mode smart, modeManual', $w1['mode'] === 'smart' && $w1['emsControls'] === false && $w1['modeManual'] === true, json_encode($w1));
prop('WB1_Mode', 0); prop('WB_Active', false);
check('GetWallboxes: Wallboxen aus -> leere Liste', call($ems, 'GetWallboxes', [])['wallboxes'] === []);
prop('WB_Active', true);
// 0.92.0: Frist je Wochentag
prop('WB1_TargetKwh', 0.0); prop('WB1_TargetTime', '07:00');
$wt0 = call($ems, 'GetWeekTargets', [1]);
check('Wochen-Frist leer: 7 Tage Mo..So ohne Uhrzeit, inaktiv, Revision', count($wt0['days']) === 7 && $wt0['days'][0]['label'] === 'Montag' && $wt0['days'][6]['day'] === 7 && $wt0['active'] === false && $wt0['revision'] !== '', json_encode($wt0));
$rBad = call($ems, 'SetWeekTargets', [1, json_encode(['days' => [['day' => 8, 'time' => '07:00'], ['day' => 1, 'time' => '25:00'], ['day' => 2, 'time' => '07:00', 'kwh' => -1]]])]);
check('Wochen-Frist: Fehler je Feld (Tag, Uhrzeit, kWh), nichts gespeichert', $rBad['ok'] === false && array_column($rBad['errors'], 'field') === ['days[0].day', 'days[1].time', 'days[2].kwh'] && call($ems, 'GetWeekTargets', [1])['active'] === false, json_encode($rBad['errors']));
$rOld = call($ems, 'SetWeekTargets', [1, json_encode(['revision' => 'x', 'days' => [['day' => 1, 'time' => '07:00']]])]);
check('Wochen-Frist: veraltete Revision abgelehnt', $rOld['ok'] === false && $rOld['errors'][0]['field'] === 'revision');
$rOk = call($ems, 'SetWeekTargets', [1, json_encode(['revision' => $wt0['revision'], 'days' => [['day' => 6, 'time' => '10:00', 'kwh' => 40], ['day' => 1, 'time' => '7:30']]])]);
check('Wochen-Frist gespeichert: Sa 10:00 40 kWh, Mo 07:30 (normiert), aktiv, neue Revision', $rOk['ok'] === true && $rOk['targets']['days'][5]['time'] === '10:00' && $rOk['targets']['days'][5]['kwh'] === 40.0 && $rOk['targets']['days'][0]['time'] === '07:30' && $rOk['targets']['active'] === true && $rOk['revision'] !== $wt0['revision'], json_encode($rOk));
// Fristwahl: Freitag 23.10.2026 20:00 -> naechste Frist Samstag 10:00 (40 kWh)
$fr = mktime(20, 0, 0, 10, 23, 2026);
$e1 = call($ems, 'wallboxEffectiveTarget', [1, $fr]);
check('Fr 20:00 angesteckt: Frist Sa 24.10. 10:00 mit 40 kWh (Wochen-Frist)', $e1['deadline'] === mktime(10, 0, 0, 10, 24, 2026) && $e1['kwh'] === 40.0 && $e1['source'] === 'week', json_encode($e1));
// Samstag 11:00 (Frist vorbei), Sonntag ohne Frist -> Montag 07:30; Montag ohne eigenes Ziel und ohne einheitliches Ziel -> aus mit Hinweis
$sa = mktime(11, 0, 0, 10, 24, 2026);
$e2 = call($ems, 'wallboxEffectiveTarget', [1, $sa]);
check('Sa 11:00: Montag 07:30 ist die naechste Frist, aber ohne Ladeziel -> aus mit Hinweis', $e2['deadline'] === null && strpos($e2['note'], 'Montag 07:30') !== false, json_encode($e2));
prop('WB1_TargetKwh', 20.0);
$e3 = call($ems, 'wallboxEffectiveTarget', [1, $sa]);
check('mit einheitlichem Ladeziel 20 kWh: Mo 26.10. 07:30 mit 20 kWh (ueber die Zeitumstellung, Wanduhr)', $e3['deadline'] === mktime(7, 30, 0, 10, 26, 2026) && $e3['kwh'] === 20.0, json_encode($e3));
prop('WB1_TargetKwh', 0.0);
check('Ladeziel gilt als eingestellt, auch ohne einheitliches Ladeziel (Wochen-Frist aktiv)', call($ems, 'wallboxTargetConfigured', [1]) === true);
prop('WB1_TargetKwh', 20.0);
// Wochen-Frist leeren -> wieder die einheitliche Frist 07:00
call($ems, 'SetWeekTargets', [1, json_encode(['days' => [['day' => 6, 'time' => ''], ['day' => 1, 'time' => '']]])]);
$e4 = call($ems, 'wallboxEffectiveTarget', [1, $sa]);
check('Wochen-Frist geleert: einheitliche Frist 07:00 am naechsten Tag mit 20 kWh', $e4['source'] === 'single' && $e4['deadline'] === mktime(7, 0, 0, 10, 25, 2026) && $e4['kwh'] === 20.0, json_encode($e4));
prop('WB1_TargetKwh', 0.0);
check('ohne Wochen-Frist und ohne Ladeziel: kein Ladeziel eingestellt', call($ems, 'wallboxTargetConfigured', [1]) === false);
prop('WB1_WeekTargets', json_encode([['Label' => 'Montag', 'Time' => '06:15', 'Kwh' => 0], ['Label' => 'Dienstag', 'Time' => '', 'Kwh' => 0]]));
$wtL = call($ems, 'GetWeekTargets', [1]);
check('Liste aus dem Formular ohne Day-Feld: Zuordnung ueber die Zeilenposition (Mo 06:15)', $wtL['days'][0]['time'] === '06:15' && $wtL['days'][1]['time'] === '' && $wtL['active'] === true, json_encode($wtL['days']));
prop('WB1_WeekTargets', '[]');
check('GetWeekTargets(3): ungueltige Wallbox abgelehnt', call($ems, 'GetWeekTargets', [3])['ok'] === false);
// Formular
$f = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Formular enthaelt Ladeart, Stromstaerke, PV-Anteil und Phasen fuer beide Wallboxen', strpos($f, '"name":"WB1_ChargeMode"') !== false && strpos($f, '"name":"WB2_ChargeMode"') !== false && strpos($f, '"name":"WB1_PhaseSetting"') !== false && strpos($f, '"name":"WB2_PvSharePct"') !== false);

echo "\n8k10) ChargerHub-Vertrag 1.9: controlReason/controlSince im Panel Wallboxen (nur bei Beobachten)\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 1);
$cenX = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', true, 0);
$whObs = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenX, 'maxCurrent' => 16, 'managedBy' => 'goe-controller', 'powerID' => 0, 'plugStateID' => 0,
          'controlOwner' => 'goe-controller', 'controlSource' => 'einstellung', 'controlReason' => 'Der go-e Controller regelt das Überschussladen selbst', 'controlSince' => mktime(8, 15, 0)];
attr('PartnerCache', json_encode(['chargerhub' => [$whObs]]));
$txt = fn($l) => is_array($l) ? $l['caption'] : $l;
$l = $txt(call($ems, 'wallboxModeStatusLine', [1]));
check('beobachtete Wallbox: Grund und Zeit von ChargerHub stehen in der Statuszeile', strpos($l, 'fremd gesteuert') !== false && strpos($l, 'Geregelt wird sie laut ChargerHub so: Der go-e Controller regelt das Überschussladen selbst (seit ') !== false, $l);
prop('WB1_Mode', 3);
check('manuell "nur beobachten": ebenfalls', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'laut ChargerHub') !== false);
prop('WB1_Mode', 0);
$whOld = $whObs; unset($whOld['controlReason'], $whOld['controlSince'], $whOld['controlOwner'], $whOld['controlSource']);
attr('PartnerCache', json_encode(['chargerhub' => [$whOld]]));
check('aelterer ChargerHub (Vertrag unter 1.9, keine Felder): Zeile unveraendert, kein Hinweis', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'laut ChargerHub') === false);
$whEmpty = $whObs; $whEmpty['controlReason'] = '   ';
attr('PartnerCache', json_encode(['chargerhub' => [$whEmpty]]));
check('leerer Grund: kein Hinweis', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'laut ChargerHub') === false);
$whLong = $whObs; $whLong['controlReason'] = str_repeat('x', 300); $whLong['controlSince'] = 0;
attr('PartnerCache', json_encode(['chargerhub' => [$whLong]]));
$l = $txt(call($ems, 'wallboxModeStatusLine', [1]));
check('langer Grund wird auf 160 Zeichen gekuerzt, ohne Zeit bei controlSince 0', strpos($l, str_repeat('x', 157) . '...') !== false && strpos($l, str_repeat('x', 158)) === false && strpos($l, '(seit') === false, $l);
$whOld2 = $whObs; $whOld2['controlSince'] = mktime(8, 15, 0, (int)date('n'), (int)date('j') - 3, (int)date('Y')); // Wanduhr, auch ueber eine Zeitumstellung
attr('PartnerCache', json_encode(['chargerhub' => [$whOld2]]));
check('Besitzerwechsel an einem frueheren Tag: mit Datum', (bool)preg_match('/\(seit \d\d\.\d\d\. 08:15\)/', $txt(call($ems, 'wallboxModeStatusLine', [1]))), $txt(call($ems, 'wallboxModeStatusLine', [1])));
$whEms = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenX, 'maxCurrent' => 16, 'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0, 'controlReason' => 'EMS steuert', 'controlSince' => time() - 60];
attr('PartnerCache', json_encode(['chargerhub' => [$whEms]]));
check('Wallbox, die EMS selbst steuert: kein ChargerHub-Hinweis (nur bei Beobachten)', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'laut ChargerHub') === false, $txt(call($ems, 'wallboxModeStatusLine', [1])));

echo "\n8k11) Wallbox Stufe 2: Ladeziel (kWh) mit Frist, ueberstimmt die Ladeart\n";
$ems = freshEms();
$n0 = strtotime('today 22:07'); $dl0 = strtotime('tomorrow 07:00');
$pm = []; for ($t = $n0 - ($n0 % 900); $t < $dl0; $t += 900) { $pm[$t] = 0.30; }
$cheap = [strtotime('tomorrow 02:00'), strtotime('tomorrow 02:15'), strtotime('tomorrow 02:30'), strtotime('tomorrow 02:45'), strtotime('tomorrow 03:00')];
foreach ($cheap as $c) { $pm[$c] = 0.20; }
$tp = fn(array $o) => call($ems, 'wallboxTargetPlan', [array_merge(['targetKwh' => 10.0, 'chargedKwh' => 0.0, 'now' => $n0, 'deadline' => $dl0, 'prices' => $pm, 'powerKw' => 11.0, 'bufferPct' => 20], $o)]);
$r = $tp([]);
check('10 kWh bei 11 kW, 20 % Puffer: 5 Viertelstunden (10/11*1,2*4 = 4,4 -> 5), genau die guenstigen 02:00-03:00', count($r['slots']) === 5 && $r['slots'] === $cheap && $r['state'] === 'wait' && $r['enable'] === false, json_encode([$r['state'], array_map(fn($x) => date('H:i', $x), $r['slots'])]));
check('Text nennt Ziel, Frist, Fenster und Durchschnittspreis', strpos($r['text'], 'Ziel 10,0 kWh bis 07:00') !== false && strpos($r['text'], 'ab 02:00') !== false && strpos($r['text'], 'Ø 20,0 ct') !== false && strpos($r['text'], 'wartet') !== false, $r['text']);
$r = $tp(['now' => strtotime('tomorrow 02:10')]);
check('im geplanten Fenster: laedt jetzt', $r['enable'] === true && $r['state'] === 'charge', json_encode($r));
$r = $tp(['chargedKwh' => 10.0]);
check('Ziel erreicht: done, keine Freigabe', $r['state'] === 'done' && $r['enable'] === false);
$r = $tp(['now' => $dl0 + 60, 'chargedKwh' => 4.0]);
check('Frist vorbei: expired, nennt die fehlenden 6 kWh', $r['state'] === 'expired' && strpos($r['text'], 'es fehlen 6,0 kWh') !== false, $r['text']);
$r = $tp(['now' => strtotime('tomorrow 06:30'), 'targetKwh' => 20.0]);
check('Zeit reicht nicht (20 kWh in 30 min bei 11 kW): laedt sofort und warnt', $r['state'] === 'late' && $r['enable'] === true && strpos($r['text'], 'Frist nicht erreichbar') !== false, $r['text']);
$r = $tp(['prices' => []]);
check('ohne Preise: 5 frueheste Viertelstunden, Text "ohne Preise", aktueller Slot dabei -> laedt jetzt', $r['enable'] === true && count($r['slots']) === 5 && strpos($r['text'], 'ohne Preise') !== false, $r['text']);
$r = $tp(['powerKw' => 0.0]);
check('Ladeleistung unbekannt (0): ganze Zeit bis zur Frist, laedt sofort', $r['enable'] === true);
check('naechstes Auftreten der Uhrzeit: 07:00 nach 22:07 = morgen, 23:00 nach 22:07 = heute', call($ems, 'nextClockTs', ['07:00', $n0]) === $dl0 && call($ems, 'nextClockTs', ['23:00', $n0]) === strtotime('today 23:00') && call($ems, 'nextClockTs', ['7 Uhr', $n0]) === null);

echo "   Ansteuerung\n";
prop('WB_Active', true); prop('WB_Count', 1); prop('WB1_Max_Power_W', 11000); prop('WB_Cooldown_Sec', 120); prop('WB_Min_Charge_Min', 5); prop('WB1_Mode', 0);
$cenT = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', false, 0);
$curT = vari('CHUB WB1 Stromgrenze', 600, 'ctl_curr_limit', 16, 1);
$plT  = vari('CHUB WB1 Stecker', 600, 'plug_state', true, 0);
$seT  = vari('CHUB WB1 Sitzungsenergie', 600, 'session_energy', 0.0, 2);
$whT = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenT, 'currentLimitID' => $curT, 'plugStateID' => $plT, 'sessionEnergyID' => $seT,
        'minCurrent' => 6, 'maxCurrent' => 16, 'currentStepA' => 1.0, 'phases' => 3, 'managedBy' => 'none', 'powerID' => 0];
attr('PartnerCache', json_encode(['chargerhub' => [$whT]]));
$GLOBALS['TIBBER_CURVE'] = [];
$wbActsT = fn() => array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit'], true)));
$sT = state(['wb_active' => true, 'wb_count' => 1, 'pv_total_w' => 0.0, 'house_pow_w' => 500.0]);
$dT = fn() => ['wb1_enable' => false, 'wb2_enable' => false, 'source' => 'tagesplan', 'reason' => ''];
$rstT = function () use ($cenT) { $GLOBALS['VAL'][$cenT] = false; foreach (['LastWB1Switch' => 0, 'WB1EmsLocked' => false, 'WB1LastSentEnable' => false, 'WB1CurrentSentA' => -1, 'WB1CurrentSentAt' => 0, 'WB1TargetSession' => 0, 'WB1TargetDeadline' => 0, 'WB1TargetDone' => false] as $k => $v) { attr($k, $v); } $GLOBALS['ACTIONS'] = []; };
$rstT(); prop('WB1_ChargeMode', 0); prop('WB1_TargetKwh', 0.0); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Ziel aus, Ladeart preisgesteuert: Verhalten wie bisher (gesperrt bleibt gesperrt, nichts geschrieben)', $wbActsT() === [], json_encode($GLOBALS['ACTIONS']));
// Ziel mit Frist in 30 min, 20 kWh: reicht nicht -> sofort laden mit Maximalstrom
$rstT(); prop('WB1_TargetKwh', 20.0); prop('WB1_TargetTime', date('H:i', time() + 1800)); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Ziel 20 kWh, Frist in 30 min: sofort Hoechststrom (11 kW dreiphasig = 15 A) und Freigabe, obwohl die Preisentscheidung sperrt', $wbActsT() === [[600, 'ctl_curr_limit', 15], [600, 'ctl_enable', true]], json_encode($GLOBALS['ACTIONS']));
check('Statuszeile warnt "Frist nicht erreichbar"', strpos($ems->ReadAttributeString('WB1ChargeText'), 'Frist nicht erreichbar') !== false, $ems->ReadAttributeString('WB1ChargeText'));
check('Frist wird beim Anstecken festgelegt (Sitzung und Frist gemerkt)', $ems->ReadAttributeInteger('WB1TargetSession') > 0 && $ems->ReadAttributeInteger('WB1TargetDeadline') > time());
// Sitzungsenergie: Bezugspunkt beim Anstecken, Ruecksprung des Zaehlers
check('Bezugspunkt beim Anstecken gemerkt (Zaehler stand auf 0)', $ems->ReadAttributeFloat('WB1TargetBase') == 0.0);
attr('WB1TargetBase', 12.0); attr('WB1TargetCarry', 0.0); attr('WB1TargetLast', 12.0);
check('Zaehler stand beim Anstecken noch auf dem Endwert der letzten Sitzung (12 kWh): 15 kWh = 3 kWh geladen', abs(call($ems, 'wallboxSessionChargedKwh', [1, 15.0]) - 3.0) < 1e-6);
check('Geraet setzt erst beim Stromfluss zurueck (15 -> 0,5): 3 kWh bleiben, plus 0,5 = 3,5 kWh, nie negativ', abs(call($ems, 'wallboxSessionChargedKwh', [1, 0.5]) - 3.5) < 1e-6 && $ems->ReadAttributeFloat('WB1TargetBase') == 0.0);
check('danach normal weiter: 2,0 -> 5,0 kWh', abs(call($ems, 'wallboxSessionChargedKwh', [1, 2.0]) - 5.0) < 1e-6);
check('kleine Schwankung (2,0 -> 1,98) ist kein Ruecksprung', abs(call($ems, 'wallboxSessionChargedKwh', [1, 1.98]) - 4.98) < 1e-6);
attr('WB1TargetBase', 0.0); attr('WB1TargetCarry', 0.0); attr('WB1TargetLast', 0.0);
// Ziel erreicht -> Ladeart gilt wieder (hier Nur PV ohne Ueberschuss -> Stopp nach Wartezeit)
$GLOBALS['VAL'][$cenT] = true; $GLOBALS['VAL'][$seT] = 20.0; attr('LastWB1Switch', time() - 1000); prop('WB1_ChargeMode', 2); $GLOBALS['ACTIONS'] = []; $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Ziel erreicht: zurueck zur Ladeart (Nur PV ohne Ueberschuss -> gesperrt), Hinweis "erreicht" vor dem Ladeart-Text', $wbActsT() === [[600, 'ctl_enable', false]] && $ems->ReadAttributeBoolean('WB1TargetDone') === true && strpos($ems->ReadAttributeString('WB1ChargeText'), 'erreicht') !== false, json_encode($GLOBALS['ACTIONS']) . $ems->ReadAttributeString('WB1ChargeText'));
// Abstecken setzt zurueck
$GLOBALS['VAL'][$plT] = false; $GLOBALS['ACTIONS'] = []; $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Abstecken: Sitzung, Frist und "erreicht" zurueckgesetzt', $ems->ReadAttributeInteger('WB1TargetSession') === 0 && $ems->ReadAttributeBoolean('WB1TargetDone') === false);
$GLOBALS['VAL'][$plT] = true; $GLOBALS['VAL'][$seT] = 0.0; prop('WB1_ChargeMode', 0);
// Vorrang Netzbetreiber
$rstT(); $GLOBALS['VAL'][$cenT] = true; attr('WB1LastSentEnable', true); $d = array_merge($dT(), ['source' => 'netzbetreiber']);
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('§14a geht vor dem Ziel: gesperrt', $wbActsT() === [[600, 'ctl_enable', false]], json_encode($GLOBALS['ACTIONS']));
// Smart Charging unberuehrt
$rstT(); prop('WB1_Mode', 1); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Smart Charging: Ziel schreibt nichts', array_filter($wbActsT(), fn($a) => $a[1] === 'ctl_curr_limit') === [], json_encode($GLOBALS['ACTIONS']));
prop('WB1_Mode', 0);
// Wallbox ohne Sitzungsenergie
$whN = $whT; unset($whN['sessionEnergyID']); attr('PartnerCache', json_encode(['chargerhub' => [$whN]]));
$rstT(); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Wallbox ohne Sitzungsenergie: Ziel nicht unterstuetzt (Hinweis), kein Schreiben, Preissteuerung bleibt', $wbActsT() === [] && strpos($ems->ReadAttributeString('WB1ChargeText'), 'nicht unterstützt') !== false, $ems->ReadAttributeString('WB1ChargeText'));
attr('PartnerCache', json_encode(['chargerhub' => [$whT]]));
// ungueltige Uhrzeit
$rstT(); prop('WB1_TargetTime', '7 Uhr'); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('ungueltige Frist: Ziel aus mit Hinweis', $wbActsT() === [] && strpos($ems->ReadAttributeString('WB1ChargeText'), 'keine gültige Uhrzeit') !== false, $ems->ReadAttributeString('WB1ChargeText'));
// Anstecken, waehrend der Zaehler noch den Endwert der letzten Sitzung zeigt (12 kWh): Ziel 5 kWh ist NICHT schon erreicht
$rstT(); attr('WB1TargetBase', 0.0); attr('WB1TargetCarry', 0.0); attr('WB1TargetLast', 0.0);
$GLOBALS['VAL'][$seT] = 12.0; prop('WB1_TargetKwh', 5.0); prop('WB1_TargetTime', date('H:i', time() + 1800)); $d = $dT();
call($ems, 'applyWallboxControl', [&$d, $sT]);
check('Zaehler zeigt beim Anstecken noch 12 kWh der Vorsitzung: Ziel 5 kWh gilt als offen (0 geladen), es wird geladen', $ems->ReadAttributeBoolean('WB1TargetDone') === false && in_array([600, 'ctl_enable', true], $wbActsT(), true) && abs($ems->ReadAttributeFloat('WB1TargetBase') - 12.0) < 1e-6, json_encode($GLOBALS['ACTIONS']) . $ems->ReadAttributeString('WB1ChargeText'));
$GLOBALS['VAL'][$seT] = 0.0;
prop('WB1_TargetKwh', 0.0); prop('WB1_TargetTime', '07:00');
$f = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Formular: Ladeziel und Frist fuer beide Wallboxen', strpos($f, '"name":"WB1_TargetKwh"') !== false && strpos($f, '"name":"WB2_TargetTime"') !== false);

echo "\n8k12) Wallbox-Werte automatisch ueber ChargerHub (Vertrag 1.11) und sichtbare Quellzeilen\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 1);
obj(600, 1, 'go-e Garage', 0);
$enL = vari('Freigabe', 600, 'ctl_enable', true, 0);
$pwL = vari('Leistung', 600, 'power', 3680.0, 2);
$plL = vari('Stecker', 600, 'plug', true, 0);
$stL = vari('Ladestatus', 600, 'state', 2, 1);
$erL = vari('Fehlercode', 600, 'error_code', 0, 1);
$whL = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $enL, 'powerID' => $pwL, 'plugStateID' => $plL, 'stateID' => $stL, 'errorCodeID' => $erL, 'maxCurrent' => 16, 'managedBy' => 'none'];
attr('PartnerCache', json_encode(['chargerhub' => [$whL]]));
check('Fehler automatisch aus errorCodeID: 0 = kein Fehler', call($ems, 'readChargerError', [1]) === 0);
$GLOBALS['VAL'][$erL] = 7;
check('Fehlercode 7 von ChargerHub kommt an (sperrt die Freigabe ueber wb1_error)', call($ems, 'readChargerError', [1]) === 7);
check('Status automatisch aus stateID (nur Anzeige)', call($ems, 'readChargerStatus', [1]) === 2);
$whNoErr = $whL; unset($whNoErr['errorCodeID']); attr('PartnerCache', json_encode(['chargerhub' => [$whNoErr]]));
check('Wallbox ohne errorCodeID: 0, der Ladestatus wird NICHT als Fehler gedeutet', call($ems, 'readChargerError', [1]) === 0);
attr('PartnerCache', json_encode(['chargerhub' => [$whL]]));
$manErr = vari('Eigener Fehler', 0, 'my_err', 0, 1); prop('VAR_WB1_Error', $manErr);
check('eigene Fehler-Variable hat Vorrang vor ChargerHub', call($ems, 'readChargerError', [1]) === 0);
prop('VAR_WB1_Error', 0); $GLOBALS['VAL'][$erL] = 7;
$sx = call($ems, 'readState');
check('readState: ChargerHub-Fehlercode landet in wb1_error (damit sperrt die Freigabe), Status in wb1_status', $sx['wb1_error'] === 7 && $sx['wb1_status'] === 2, json_encode([$sx['wb1_error'] ?? null, $sx['wb1_status'] ?? null]));
$GLOBALS['VAL'][$erL] = 0;
// Quellzeilen
$ll = call($ems, 'wallboxLinkLines', [1]);
check('Instanz-Zeile: 🔗 automatisch verbunden ueber ChargerHub «go-e Garage» (#600)', strpos($ll['Instanz']['caption'], '🔗 Wallbox 1: automatisch verbunden über ChargerHub «go-e Garage» (#600)') === 0 && $ll['Instanz']['color'] === EMS_COLOR_AUTO, $ll['Instanz']['caption']);
check('Leistung: 🔗 mit aktuellem Wert in W', strpos($ll['Leistung']['caption'], '🔗 Leistung: automatisch von ChargerHub «go-e Garage» (#600), aktuell 3.680 W') === 0, $ll['Leistung']['caption']);
check('Angesteckt: 🔗 angesteckt', strpos($ll['Angesteckt']['caption'], 'aktuell angesteckt') !== false);
check('Fehler: 🔗 kein Fehler', strpos($ll['Fehler']['caption'], '🔗 Fehler') === 0 && strpos($ll['Fehler']['caption'], 'kein Fehler') !== false);
attr('PartnerCache', json_encode(['chargerhub' => [$whNoErr]]));
$ll = call($ems, 'wallboxLinkLines', [1]);
check('Fehler nicht gemeldet: ⚠️ „Freigabe wird nicht auf Fehler geprueft“', strpos($ll['Fehler']['caption'], '⚠️ Fehler: nicht verbunden') === 0 && strpos($ll['Fehler']['caption'], 'nicht auf Fehler geprüft') !== false, $ll['Fehler']['caption']);
attr('PartnerCache', json_encode(['chargerhub' => [$whL]]));
$manPw = vari('Eigene Leistung', 0, 'my_pw', 2.5, 2); obj($manPw, 2, 'Meine Leistung', 0);
$ll = call($ems, 'wallboxLinkLines', [1, ['Power' => $manPw]]);
check('Auswahl im offenen Formular: ✏️ eigene Variable, manuelle kW in W umgerechnet (2,5 kW = 2.500 W)', strpos($ll['Leistung']['caption'], '✏️ Leistung: eigene Variable #' . $manPw) === 0 && strpos($ll['Leistung']['caption'], '2.500 W') !== false, $ll['Leistung']['caption']);
$ll = call($ems, 'wallboxLinkLines', [1, ['Power' => 99999]]);
check('nicht existierende eigene Variable: ⚠️', strpos($ll['Leistung']['caption'], '⚠️ Leistung: die eigene Variable #99999 existiert nicht') === 0);
attr('PartnerCache', '{}');
$ll = call($ems, 'wallboxLinkLines', [1]);
check('ohne ChargerHub: ⚠️ keine Wallbox gefunden', strpos($ll['Instanz']['caption'], '⚠️ Wallbox 1: keine Wallbox gefunden') === 0);
attr('PartnerCache', json_encode(['chargerhub' => [$whL]]));
// Formular
$GLOBALS['FORMFIELD'] = [];
call($ems, 'WallboxLinkChanged', [1, 0, $manPw, 0, 0, 0]);
check('onChange: Quellzeile folgt der Auswahl vor dem Uebernehmen', strpos($GLOBALS['FORMFIELD']['WB1LinkLeistung']['caption'] ?? '', '✏️ Leistung') === 0, json_encode($GLOBALS['FORMFIELD']));
$fj = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$find = function ($fj, $name) { foreach ($fj['elements'] as $e) { foreach (($e['items'] ?? []) as $it) { if (($it['name'] ?? '') === $name) { return $it; } } } return null; };
check('Formular: leeres VAR_WB1_Power ausgeblendet, weil ChargerHub die Leistung liefert', ($find($fj, 'VAR_WB1_Power')['visible'] ?? true) === false);
check('Formular: leeres VAR_WB1_Error ausgeblendet (errorCodeID vorhanden)', ($find($fj, 'VAR_WB1_Error')['visible'] ?? true) === false);
check('Formular: mit dem Feld wird auch seine Erklaerung ausgeblendet (0.106.6)', ($find($fj, 'VAR_WB1_PowerHint')['visible'] ?? true) === false && ($find($fj, 'VAR_WB1_ErrorHint')['visible'] ?? true) === false);
check('Formular: Phasen-Variable (nie ausgewertet) leer -> ausgeblendet', ($find($fj, 'VAR_WB1_Phases')['visible'] ?? true) === false);
check('Formular: Quellzeilen unter der Instanz-Auswahl, Beschriftung "Fahrzeug angesteckt" statt "Kabel-Leistungsfaehigkeit"', $find($fj, 'WB1LinkLeistung') !== null && $find($fj, 'WB1LinkInstanz') !== null && strpos($find($fj, 'VAR_WB1_Cable')['caption'], 'Fahrzeug angesteckt') !== false);
prop('VAR_WB1_Power', $manPw);
$fj = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
check('gesetzte eigene Variable bleibt sichtbar (nichts Aktives verstecken)', ($find($fj, 'VAR_WB1_Power')['visible'] ?? true) !== false);
prop('VAR_WB1_Power', 0);
attr('PartnerCache', '{}');
$fj = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
check('ohne ChargerHub: manuelle Felder sichtbar', ($find($fj, 'VAR_WB1_Power')['visible'] ?? true) !== false && ($find($fj, 'VAR_WB1_Error')['visible'] ?? true) !== false);
// Preis
$pl = call($ems, 'priceLinkLine', []);
check('Preiszeile ohne Quelle: ⚠️', strpos($pl['caption'], '⚠️ Strompreis: keine Quelle') === 0, $pl['caption']);
$pv = vari('Tibber Preis', 0, 'tp', 0.3125, 2); obj($pv, 2, 'Tibber Preis', 0);
$pl = call($ems, 'priceLinkLine', [$pv]);
check('Preiszeile mit eigener Variable: ✏️ mit Wert in ct', strpos($pl['caption'], '✏️ Strompreis: eigene Variable #' . $pv) === 0 && strpos($pl['caption'], '31,25 ct/kWh') !== false, $pl['caption']);
check('Formular: Preiszeile unter der Preisvariable', $find($fj, 'PriceLinkStatus') !== null);

echo "\n8k13) Wallbox sehen und steuern (Stufe A/B): Plantext, Jetzt laden, WebFront-Bedienung, CHUB_SetPlanInfo\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 1); prop('WB1_Max_Power_W', 11000); prop('WB_Cooldown_Sec', 120); prop('WB1_Mode', 0);
obj(600, 1, 'go-e Garage', 0);
$cenP = vari('Freigabe', 600, 'ctl_enable', false, 0);
$curP = vari('Strom', 600, 'ctl_curr_limit', 16, 1);
$plP  = vari('Stecker', 600, 'plug', true, 0);
$whP = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenP, 'currentLimitID' => $curP, 'plugStateID' => $plP, 'minCurrent' => 6, 'maxCurrent' => 16, 'currentStepA' => 1.0, 'phases' => 3, 'managedBy' => 'none', 'powerID' => 0];
attr('PartnerCache', json_encode(['chargerhub' => [$whP]]));
$sP = state(['wb_active' => true, 'wb_count' => 1, 'wb1_cable' => 1, 'pv_total_w' => 0.0, 'house_pow_w' => 500.0]);
$dP = fn(array $o = []) => array_merge(['wb1_enable' => false, 'wb2_enable' => false, 'source' => 'tagesplan', 'reason' => ''], $o);
$actsP = fn() => array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit'], true)));
$rstP = function () use ($cenP) { $GLOBALS['VAL'][$cenP] = false; foreach (['LastWB1Switch' => 0, 'WB1EmsLocked' => false, 'WB1LastSentEnable' => false, 'WB1CurrentSentA' => -1, 'WB1CurrentSentAt' => 0, 'WB1NowUntil' => 0, 'WB1PlanSent' => '', 'WB1PlanSentAt' => 0] as $k => $v) { attr($k, $v); } $GLOBALS['ACTIONS'] = []; $GLOBALS['PLANINFO'] = []; };
call($ems, 'maintainWallboxControlVariables');
$vPlan = IPS_GetObjectIDByIdent('EMS_WB1_Plan', EMS_IID);
check('Bedien-Variablen fuer Wallbox 1 angelegt (Plan, Ladeart, Ladeziel, Frist, Befehl), keine fuer Wallbox 2 bei WB_Count 1', $vPlan !== false && IPS_GetObjectIDByIdent('EMS_WB1_Command', EMS_IID) !== false && IPS_GetObjectIDByIdent('EMS_WB2_Plan', EMS_IID) === false);
check('Ladeziel-Schieberegler: Float-Variable mit Float-Grenzen (Store-Review Punkt 18)', is_float($GLOBALS['PRES']['EMS_WB1_TargetKwh']['MIN']) && is_float($GLOBALS['PRES']['EMS_WB1_TargetKwh']['MAX']) && is_float($GLOBALS['PRES']['EMS_WB1_TargetKwh']['STEP_SIZE']));
// Plantext preisgesteuert
$rstP(); prop('WB1_ChargeMode', 0); prop('WB1_TargetKwh', 0.0); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('Plantext preisgesteuert: Schwelle und "jetzt gesperrt"', strpos(GetValue($vPlan), 'Preisgesteuert: lädt bei Preis unter') === 0 && strpos(GetValue($vPlan), 'jetzt gesperrt') !== false, GetValue($vPlan));
check('Plan an ChargerHub gemeldet (CHUB_SetPlanInfo), mit Ablaufzeit in der Zukunft', count($GLOBALS['PLANINFO']) === 1 && $GLOBALS['PLANINFO'][0][0] === 600 && $GLOBALS['PLANINFO'][0][1] === GetValue($vPlan) && $GLOBALS['PLANINFO'][0][2] > time(), json_encode($GLOBALS['PLANINFO']));
$GLOBALS['PLANINFO'] = []; $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('gleicher Plan innerhalb des Intervalls: nicht erneut gemeldet', $GLOBALS['PLANINFO'] === []);
attr('WB1PlanSentAt', time() - 100); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('vor Ablauf erneut gemeldet (nach einem Intervall), damit der Text nicht verfaellt', count($GLOBALS['PLANINFO']) === 1);
// nicht angesteckt
$rstP(); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, array_merge($sP, ['wb1_cable' => 0])]);
check('Plantext ohne Fahrzeug beginnt mit "Kein Fahrzeug angesteckt."', strpos(GetValue($vPlan), 'Kein Fahrzeug angesteckt.') === 0, GetValue($vPlan));
// Netzbetreiber
$rstP(); $d = $dP(['source' => 'netzbetreiber']);
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('Plantext §14a', strpos(GetValue($vPlan), '§14a') === 0, GetValue($vPlan));
// Smart Charging bei Grid Rewards (hands off)
$rstP(); prop('WB1_Mode', 1); $d = $dP(['wb_hands_off' => true]);
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('Plantext Smart Charging', strpos(GetValue($vPlan), 'Smart Charging: Tibber steuert') === 0, GetValue($vPlan));
prop('WB1_Mode', 0);
// Ladeart Aus
$r = call($ems, 'wallboxChargePlan', [['mode' => 5, 'surplusW' => 9000.0, 'shareP' => 50, 'manualA' => 0, 'minA' => 6, 'maxA' => 16, 'stepA' => 1.0, 'phases' => 3, 'volt' => 230, 'on' => true, 'sinceSwitchS' => 9999, 'cooldownS' => 120, 'minChargeS' => 300]]);
check('Ladeart 5 "Aus": gesperrt, auch bei viel PV', $r['enable'] === false && strpos($r['text'], 'Aus') === 0);
// Jetzt laden
$rstP(); prop('WB1_NowCurrentA', 10); attr('WB1NowUntil', time() + 3000); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('Jetzt laden: 10 A und Freigabe, obwohl preisgesteuert gesperrt waere', $actsP() === [[600, 'ctl_curr_limit', 10], [600, 'ctl_enable', true]], json_encode($GLOBALS['ACTIONS']));
check('Plantext nennt die Uebersteuerung mit Restzeit', strpos(GetValue($vPlan), 'Manuell: Jetzt laden mit 10 A, noch 50 min') === 0, GetValue($vPlan));
$rstP(); prop('WB1_NowCurrentA', 0); attr('WB1NowUntil', time() + 3000); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('Jetzt laden ohne Stromvorgabe: Hoechststrom (11 kW dreiphasig = 15 A)', in_array([600, 'ctl_curr_limit', 15], $actsP(), true), json_encode($GLOBALS['ACTIONS']));
$rstP(); attr('WB1NowUntil', time() - 10); $d = $dP(['source' => 'netzbetreiber']);
$GLOBALS['VAL'][$cenP] = true; attr('WB1LastSentEnable', true);
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('§14a geht auch vor "Jetzt laden" (und abgelaufene Uebersteuerung zaehlt nicht)', $actsP() === [[600, 'ctl_enable', false]], json_encode($GLOBALS['ACTIONS']));
$rstP(); attr('WB1NowUntil', time() - 10); $d = $dP();
call($ems, 'applyWallboxControl', [&$d, $sP]);
check('abgelaufen: zurueck zur Preissteuerung (nichts erzwungen), Merker geloescht', $actsP() === [] && $ems->ReadAttributeInteger('WB1NowUntil') === 0, json_encode($GLOBALS['ACTIONS']));
// WebFront-Bedienung, eine Quelle
call($ems, 'RequestAction', ['EMS_WB1_ChargeMode', 4]);
check('WebFront Ladeart 4: Einstellung WB1_ChargeMode = 4, Variable folgt', (int)live('WB1_ChargeMode') === 4 && GetValue(IPS_GetObjectIDByIdent('EMS_WB1_ChargeMode', EMS_IID)) === 4);
call($ems, 'RequestAction', ['EMS_WB1_ChargeMode', 9]);
check('ungueltige Ladeart 9: ignoriert', (int)live('WB1_ChargeMode') === 4);
call($ems, 'RequestAction', ['EMS_WB1_TargetKwh', 12.34]);
check('WebFront Ladeziel 12,34: Einstellung 12,3 kWh', abs((float)live('WB1_TargetKwh') - 12.3) < 1e-9);
call($ems, 'RequestAction', ['EMS_WB1_TargetTime', '06:30']);
check('WebFront Frist 06:30 uebernommen', live('WB1_TargetTime') === '06:30');
call($ems, 'RequestAction', ['EMS_WB1_TargetTime', 'morgen frueh']);
check('ungueltige Frist: nicht uebernommen, Variable zeigt wieder die gueltige Frist', live('WB1_TargetTime') === '06:30' && GetValue(IPS_GetObjectIDByIdent('EMS_WB1_TargetTime', EMS_IID)) === '06:30');
if (method_exists('EMS', 'liveAdopt')) {
    echo "\n   Store-Regel 0.101.0: Bedienwerte im Attribut, Property bleibt Grundeinstellung\n";
    check('WebFront-Bedienung schreibt nie die Property', ($GLOBALS['PROP'][EMS_IID]['WB1_TargetTime'] ?? '07:00') !== '06:30' && live('WB1_TargetTime') === '06:30');
    call($ems, 'liveAdopt', []);
    check('Übernehmen ohne geänderte Property: Bedienwert bleibt', live('WB1_TargetTime') === '06:30');
    $GLOBALS['PROP'][EMS_IID]['WB1_TargetTime'] = '05:45'; call($ems, 'liveAdopt', []);
    check('Nutzer ändert die Property und übernimmt: neuer Wert gilt', live('WB1_TargetTime') === '05:45');
    unset($GLOBALS['ATTR'][EMS_IID]['Live_WB2_ChargeMode'], $GLOBALS['ATTR'][EMS_IID]['LiveSrc_WB2_ChargeMode']); $GLOBALS['PROP'][EMS_IID]['WB2_ChargeMode'] = 3; call($ems, 'liveAdopt', []);
    check('erster Lauf nach dem Update: bisheriger Property-Wert wird übernommen', (int)live('WB2_ChargeMode') === 3);
}
SetValue(IPS_GetObjectIDByIdent('EMS_WB1_Command', EMS_IID), 1);
call($ems, 'RequestAction', ['EMS_WB1_Command', 1]);
check('Befehl "Jetzt 1 Stunde laden": Uebersteuerung fuer 60 min, Befehl springt auf "Bereit" zurueck', abs($ems->ReadAttributeInteger('WB1NowUntil') - (time() + 3600)) <= 2 && GetValue(IPS_GetObjectIDByIdent('EMS_WB1_Command', EMS_IID)) === 0);
call($ems, 'RequestAction', ['EMS_WB1_Command', 2]);
check('Befehl "Uebersteuerung beenden"', $ems->ReadAttributeInteger('WB1NowUntil') === 0);
call($ems, 'RequestAction', ['EMS_WB1_Command', 3]);
check('Befehl "Ladeziel loeschen": Einstellung 0 kWh', (float)$GLOBALS['PROP'][EMS_IID]['WB1_TargetKwh'] === 0.0);
prop('WB1_ChargeMode', 0);
// ohne CHUB_SetPlanInfo-Partner (OCPPHub-Eintrag): nichts an ChargerHub
$whO = $whP; $whO['source'] = 'ocpphub'; attr('PartnerCache', json_encode(['ocpphub' => [$whO]])); prop('WB_Quelle', 2);
$GLOBALS['PLANINFO'] = []; attr('WB1PlanSent', ''); call($ems, 'publishWallboxPlan', [1, 'Test']);
check('OCPPHub-Ladepunkt: kein CHUB_SetPlanInfo', $GLOBALS['PLANINFO'] === []);
prop('WB_Quelle', 0); attr('PartnerCache', json_encode(['chargerhub' => [$whP]]));
// EMS aus
prop('EMS_Active', false); $GLOBALS['PLANINFO'] = []; attr('WB1PlanSent', '');
call($ems, 'Update');
check('EMS aus: Plantext "EMS ist ausgeschaltet"', strpos(GetValue($vPlan), 'EMS ist ausgeschaltet') === 0, GetValue($vPlan));

echo "\n8k14) Dublette ohne Original zaehlt (Live-Befund 24.10.2026, WB2 verschwand bei ausgeschaltetem OCPPHub)\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2);
$eA = ['instanceID' => 610, 'source' => 'chargerhub', 'managedBy' => 'none', 'maxCurrent' => 16];
$eB = ['instanceID' => 611, 'source' => 'chargerhub', 'managedBy' => 'other', 'maxCurrent' => 32, 'duplicateOf' => ['source' => 'ocpphub', 'instanceID' => 999]];
attr('PartnerCache', json_encode(['chargerhub' => [$eA, $eB]]));
$lst = call($ems, 'getChargerList');
check('OCPPHub-Original fehlt: die ChargerHub-Dublette zaehlt, Wallbox 2 = #611', count($lst) === 2 && (int)$lst[1]['instanceID'] === 611, json_encode(array_column($lst, 'instanceID')));
check('Quellzeile Wallbox 2: automatisch verbunden statt "keine Wallbox gefunden"', strpos(call($ems, 'wallboxLinkLines', [2])['Instanz']['caption'], '🔗 Wallbox 2: automatisch verbunden') === 0, call($ems, 'wallboxLinkLines', [2])['Instanz']['caption']);
attr('PartnerCache', json_encode(['chargerhub' => [$eA, $eB], 'ocpphub' => [['instanceID' => 999, 'source' => 'ocpphub', 'managedBy' => 'none', 'maxCurrent' => 32]]]));
prop('WB_Quelle', 3);
$lst = call($ems, 'getChargerList');
check('Original vorhanden: Dublette zaehlt nicht doppelt (ChargerHub #610 + OCPPHub #999)', count($lst) === 2 && array_column($lst, 'instanceID') === [610, 999], json_encode(array_column($lst, 'instanceID')));
prop('WB_Quelle', 0);
// Plantext ohne Fahrzeug: kein Widerspruch "unter Schwelle, aber gesperrt"
attr('PartnerCache', json_encode(['chargerhub' => [$eA]])); prop('WB_Count', 1);
$pvv = vari('Preis', 0, 'p', 0.143, 2); prop('VAR_TIB_Price', $pvv); prop('TIB_Threshold_WB', 0.198);
$txt = call($ems, 'wallboxPlanText', [1, 'ems', ['wb1_enable' => false, 'source' => 'tagesplan'], state(['wb1_cable' => 0]), null]);
check('ohne Fahrzeug, Preis unter der Schwelle: "lädt sofort nach dem Anstecken" statt "jetzt gesperrt"', strpos($txt, 'Kein Fahrzeug angesteckt.') === 0 && strpos($txt, 'lädt sofort nach dem Anstecken') !== false && strpos($txt, 'gesperrt') === false, $txt);
$txt = call($ems, 'wallboxPlanText', [1, 'ems', ['wb1_enable' => false, 'source' => 'tagesplan'], state(['wb1_cable' => 1]), null]);
check('mit Fahrzeug und gesperrt: "jetzt gesperrt"', strpos($txt, 'jetzt gesperrt') !== false, $txt);
prop('VAR_TIB_Price', 0); prop('TIB_Threshold_WB', 0.0);

