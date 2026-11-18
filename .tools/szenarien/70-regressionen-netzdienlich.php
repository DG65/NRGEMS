<?php
// Kapitel des EMS-Pruefstands: Regressionen 12.09., Einspeise-Ueberwachung, netzdienliche Bausteine (B1).
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n9) Regression 12.09.2026 -- Tagesplan darf die Batterie nicht per Sollwert-Modus ins Netz ziehen\n";
$ems = freshEms();
$ctx = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 300.0, 'houseLoadSlots' => [],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 48.0, 'dischargeKw' => 48.156, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25];
$r = call($ems, 'simulateDaySlot', [80, 0.47, 0.0, 86.0, [], $ctx, 0.0]); // 20:00, 47ct, keine PV
check('teurer Abend-Slot: Plan = WR-Automatik, KEIN erzwungener Modus 3 mit 48 kW', $r['plan']['op'] === EMS_OP_AUTO && $r['plan']['gw'] === GW_MODE_AUTO && (int)$r['plan']['power'] === 0, json_encode($r['plan']));
check('SOC-Simulation laeuft trotzdem weiter (Batterie deckt die Last)', $r['soc'] < 86.0, 'soc=' . $r['soc']);
pricesToday(0.05); // echte Arbitrage -> applyPlanSlot() wird erreicht
dayPlanAll(EMS_OP_EXPORT, GW_MODE_AC_EXPORT, 5854); // Prognose: 5854 W PV
$d = call($ems, 'optimize', [state(['pv_total_w' => 2000.0, 'house_pow_w' => 300.0])]);
check('Export-Sollwert auf den GEMESSENEN Ueberschuss begrenzt (2000-300 = 1700 W statt Prognose 5854 W)', $d['op_mode'] === EMS_OP_EXPORT && (int)$d['gw_power_w'] === 1700, fmt($d));
$d = call($ems, 'optimize', [state(['pv_total_w' => 200.0, 'house_pow_w' => 300.0])]);
check('Export ohne echten Ueberschuss: Sollwert 0 W (Batterie wird nicht angezapft)', (int)$d['gw_power_w'] === 0, fmt($d));

// ===========================================================================
echo "\n10) Regression 12.09.2026 -- Moduswechsel nur ueber 0 W, nie alter Modus mit neuer Leistung oder umgekehrt\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$rb = vari('EMS Leistungsmodus', IHUB_IID, 'ctl_ems_mode', GW_MODE_DISCHARGE, 1); // vom Geraet zurueckgelesener Ist-Modus
vari('EMS Leistung', IHUB_IID, 'ctl_ems_power', 0, 1); vari('EMS Steuerung', IHUB_IID, 'ctl_ems_enable', false, 0); // Stellglieder wie beim echten GoodWe-Treiber
$seq = function () { return array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']); };

$GLOBALS['VAL'][$rb] = GW_MODE_DISCHARGE; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_IMPORT, 7400, true]);   // Modus 3/500 -> Grid Rewards 4/7400
check('3 -> 4/7400: Leistung 0, Modus 4, Leistung 7400, enable zuletzt', $seq() === ['ctl_ems_power=0', 'ctl_ems_mode=4', 'ctl_ems_power=7400', 'ctl_ems_enable=true'], json_encode($seq()));

$GLOBALS['VAL'][$rb] = GW_MODE_AC_IMPORT; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 34500, true]);  // Grid Rewards 4/7400 -> Boost 3/34500
check('4/7400 -> 3/34500: nie Modus 3 mit altem 7400-W-Wert (erst 0 W)', $seq() === ['ctl_ems_power=0', 'ctl_ems_mode=3', 'ctl_ems_power=34500', 'ctl_ems_enable=true'], json_encode($seq()));

$GLOBALS['VAL'][$rb] = GW_MODE_AC_IMPORT; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_IMPORT, 5200, true]);   // gleicher Modus, Reassert mit neuer Leistung
check('gleicher Modus 4 (Reassert/Nachfuehren): KEIN Null-Schritt, kein Flackern', $seq() === ['ctl_ems_mode=4', 'ctl_ems_power=5200', 'ctl_ems_enable=true'], json_encode($seq()));

$GLOBALS['VAL'][$rb] = GW_MODE_AC_IMPORT; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);          // Grid Rewards -> Automatik
check('4/7400 -> Automatik: Leistung 0, Modus 1, enable=false (kein Leistungsschritt > 0)', $seq() === ['ctl_ems_power=0', 'ctl_ems_mode=1', 'ctl_ems_enable=false'], json_encode($seq()));

$GLOBALS['VAL'][$rb] = 255; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);          // Ist-Modus unbekannt (Totmann 255)
check('Ist-Modus 255 (unbekannt): gilt als Wechsel -> Null-Schritt (sichere Richtung)', $seq()[0] === 'ctl_ems_power=0', json_encode($seq()));

check('enable wird in jedem Fall als LETZTES geschrieben', end($GLOBALS['ACTIONS'])[1] === 'ctl_ems_enable', json_encode($seq()));

// ===========================================================================
echo "\n11) Regression 12.09.2026 -- Tagesplan-Rueckschau (Plan/Archiv-Vergleich)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$dayStart = strtotime('today');
$nowSlot = (int)(((int)date('G') * 60 + (int)date('i')) / 15); // Wanduhr-Slot wie im EMS (am 25-Stunden-Tag != vergangene Sekunden / 900)
$socVar = vari('SOC', IHUB_IID, '', 0.0);
$GLOBALS['ARCHIVE'][$socVar] = [[$dayStart - 1200, 96.0]]; // letzter Wert gestern 23:40, heute noch keine Aenderung
$a = call($ems, 'getArchivedSlotsToday', [$socVar]);
check('kein Eintrag heute: Slot 0 = letzter Wert von gestern (96), nicht null', $a[0] === 96.0, var_export($a[0], true));
check('... und bis "jetzt" durchgehend 96', $a[$nowSlot] === 96.0, var_export($a[$nowSlot], true));
check('Zukunft bleibt null (Startwert wird nicht nach vorn verlaengert)', $nowSlot >= 95 || $a[$nowSlot + 1] === null, var_export($a[min(95, $nowSlot + 1)], true));
$GLOBALS['ARCHIVE'][$socVar][] = [$dayStart + 60, 95.0]; // 00:01 faellt auf 95
$a = call($ems, 'getArchivedSlotsToday', [$socVar]);
check('Aenderung im Slot 0 wird uebernommen (95), der Vortageswert ueberschreibt sie nicht', $a[0] === 95.0, var_export($a[0], true));
$leer = vari('ohne Archiv', IHUB_IID, '', 0.0);
check('ueberhaupt keine Archivdaten: ehrlich null', call($ems, 'getArchivedSlotsToday', [$leer])[0] === null);
unset($GLOBALS['INSTMOD'][500]);

check('Hauslast nachts, Batterie entlaedt 1200 W, Netz 0: 1200 W (vorher 0 W)', call($ems, 'computeHousePowerW', [0.0, 1200.0, 0.0, 0.0]) === 1200.0);
check('Hauslast, Netzbezug 500 W, Batterie steht: 500 W', call($ems, 'computeHousePowerW', [0.0, 0.0, -500.0, 0.0]) === 500.0);
check('Hauslast, PV 5000, Batterie laedt 3000, Einspeisung 1000: 1000 W', call($ems, 'computeHousePowerW', [5000.0, -3000.0, 1000.0, 0.0]) === 1000.0);
check('Hauslast, Wallbox wird abgezogen (PV 0, Bezug 7700, WB 7400): 300 W', call($ems, 'computeHousePowerW', [0.0, 0.0, -7700.0, 7400.0]) === 300.0);
check('Hauslast nie negativ (Messrauschen)', call($ems, 'computeHousePowerW', [0.0, 0.0, 50.0, 0.0]) === 0.0);

$p = array_fill(0, 96, 0.30);
check('Plan-Signatur aendert sich mit dem Slot (Neuausrichtung am echten SOC je Viertelstunde)', call($ems, 'dayPlanSignature', [$p, [], 0.0, 68]) !== call($ems, 'dayPlanSignature', [$p, [], 0.0, 69]));
check('Plan-Signatur im selben Slot stabil (keine Neuberechnung je 30-s-Takt)', call($ems, 'dayPlanSignature', [$p, [], 0.0, 68]) === call($ems, 'dayPlanSignature', [$p, [], 0.0, 68]));

// ===========================================================================
echo "\n12) Wallbox-Leistung per ChargerHub-Discovery (Befund 12.09.2026: Properties leer -> immer 0 kW)\n";
$ems = freshEms();
check('nackt: keine Wallbox, keine Variable -> 0 kW, kein Fehler', call($ems, 'readChargerPowerKw', [1]) === 0.0 && call($ems, 'readChargerCable', [2]) === 0);
$p1 = vari('WB1 Ladeleistung', 600, 'power', 7400.0);
$c1 = vari('WB1 angesteckt', 600, 'vehicle_plugged', true, 0);
$p2 = vari('WB2 Ladeleistung', 601, 'power', 11000.0);
attr('PartnerCache', json_encode(['chargerhub' => [
    ['instanceID' => 600, 'powerID' => $p1, 'plugStateID' => $c1, 'managedBy' => 'none'],
    ['instanceID' => 601, 'powerID' => $p2, 'plugStateID' => 0, 'managedBy' => 'other'],
]]));
check('Discovery: WB1 7400 W -> 7,4 kW', call($ems, 'readChargerPowerKw', [1]) === 7.4);
check('Discovery: WB2 11000 W -> 11 kW (auch fremdgesteuerte Wallbox wird gemessen)', call($ems, 'readChargerPowerKw', [2]) === 11.0);
check('Discovery: WB1 angesteckt = 1, WB2 ohne plugStateID = 0', call($ems, 'readChargerCable', [1]) === 1 && call($ems, 'readChargerCable', [2]) === 0);
$st = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => call($ems, 'readChargerPowerKw', [1])])]);
check('Grid Rewards nutzt die per Discovery gemessene Wallbox-Leistung (7,4 kW im Grund, Xset 7200 W, Modus 9)', strpos($st['reason'], 'Auto lädt mit 7.4 kW') !== false && (int)$st['gw_power_w'] === 7200 && $st['gw_mode'] === GW_MODE_BUY, fmt($st));
$man = vari('manuell kW', 0, '', 3.7);
prop('VAR_WB1_Power', $man);
check('manuell verknuepfte Variable hat Vorrang (kW wie bisher): 3,7 kW', call($ems, 'readChargerPowerKw', [1]) === 3.7);
prop('VAR_WB1_Power', 0);
$GLOBALS['VAR'][$p2]['VariableUpdated'] = time() - 3600;
$GLOBALS['LOG'] = [];
check('Quelle seit 60 min nicht aktualisiert: Wert wird ignoriert (0 kW)', call($ems, 'readChargerPowerKw', [2]) === 0.0);

// ===========================================================================
echo "\n13) Einspeise-Ueberwachung -- Batterie entlaedt, waehrend eingespeist wird (MiSpeL-Bedingung)\n";
$ems = freshEms();
$aktiv = ['op_mode' => EMS_OP_DISCHARGE, 'gw_mode' => GW_MODE_DISCHARGE, 'gw_power_w' => 5000, 'gw_enable' => true,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Tagesplan: Entladen', 'source' => 'tagesplan'];
$auto = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$ueber = state(['bat_pow_w' => 4000.0, 'grid_total_w' => 3500.0]); // entlaedt 4 kW, speist 3,5 kW ein
attr('ExpOvlLastTs', time() - 30);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('1. Zyklus: nur vorgemerkt, Entscheidung unveraendert', $d === $aktiv && $ems->ReadAttributeInteger('ExpOvlSince') > 0);
check('Tageszaehler: 30 s x 3500 W = ~29 Wh, 0,5 min', abs($ems->GetValue('EMS_ExportOverlapToday_Wh') - 29.2) < 0.2 && abs($ems->GetValue('EMS_ExportOverlapToday_Min') - 0.5) < 0.01,
    $ems->GetValue('EMS_ExportOverlapToday_Wh') . ' Wh / ' . $ems->GetValue('EMS_ExportOverlapToday_Min') . ' min');
attr('ExpOvlSince', time() - 120);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('nach 90 s: Rueckfall in WR-Eigenregelung (enable=false, Automatik, 0 W), force', isNativeAuto($d) && !empty($d['force']), fmt($d));
check('Warnvariable EMS_ExportOverlapWarn = true, Haltephase gesetzt', $ems->GetValue('EMS_ExportOverlapWarn') === true && $ems->ReadAttributeInteger('ExpOvlHoldUntil') > time());
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, state()]);
check('Haltephase: aktiver Sollwert wird weiter ueberstimmt (kein Pendeln)', isNativeAuto($d), fmt($d));
$gr = array_merge($aktiv, ['op_mode' => EMS_OP_GRIDREWARDS, 'gw_mode' => GW_MODE_AC_IMPORT, 'source' => 'tibber']);
$d = call($ems, 'applyExportOverlapGuard', [$gr, $ueber]);
check('Haltephase: Grid Rewards bleibt unangetastet', $d === $gr, fmt($d));
attr('ExpOvlHoldUntil', 0);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, state(['bat_pow_w' => 800.0, 'grid_total_w' => -300.0])]);
check('nach der Haltephase, keine Einspeisung mehr: Entscheidung unveraendert, Warnung aufgehoben', $d === $aktiv && $ems->GetValue('EMS_ExportOverlapWarn') === false, fmt($d));

echo "\n   Negativfaelle -- wo die Ueberwachung NICHT eingreifen darf\n";
$ems = freshEms();
$faelle = [
    'WR-Automatik (EMS faehrt keinen Sollwert): nur messen'   => [$auto, $ueber],
    'Grid Rewards (Quelle tibber)'                            => [$gr, $ueber],
    '§14a-Netzbetreiber'                                      => [array_merge($aktiv, ['source' => 'netzbetreiber']), $ueber],
    'unter der Schwelle (80 W Entladung, Messrauschen)'       => [$aktiv, state(['bat_pow_w' => 80.0, 'grid_total_w' => 3000.0])],
    'Batterie laedt, PV speist ein (kein Batteriestrom im Netz)' => [$aktiv, state(['bat_pow_w' => -2000.0, 'grid_total_w' => 1500.0])],
    'keine Batterie konfiguriert'                             => [$aktiv, state(['bat_active' => false, 'bat_pow_w' => 4000.0, 'grid_total_w' => 3500.0])],
];
foreach ($faelle as $label => [$dec, $st]) {
    attr('ExpOvlSince', time() - 600);
    $d = call($ems, 'applyExportOverlapGuard', [$dec, $st]);
    check($label, $d === $dec, fmt($d));
}
check('Automatik mit Ueberschneidung wird trotzdem gezaehlt', $ems->GetValue('EMS_ExportOverlapToday_Min') > 0 || $ems->ReadAttributeInteger('ExpOvlLastTs') > 0);
prop('EXPOVL_Enabled', false);
attr('ExpOvlSince', time() - 600);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('Ueberwachung abgeschaltet: greift nie ein', $d === $aktiv, fmt($d));
$GLOBALS['VAR'][$p2]['VariableUpdated'] = time();
$withSeen = function ($ts) use ($p1, $c1) { attr('PartnerCache', json_encode(['chargerhub' => [
    ['instanceID' => 600, 'powerID' => $p1, 'plugStateID' => $c1, 'managedBy' => 'none', 'contractVersion' => '1.3', 'lastSeenAt' => $ts]]])); };
$withSeen(time() - 30);
check('Vertrag 1.3, Geraet antwortet (lastSeenAt vor 30 s): 7,4 kW', call($ems, 'readChargerPowerKw', [1]) === 7.4);
$withSeen(time() - 3600);
check('Vertrag 1.3, letzte Geraeteantwort vor 60 min, Variable aber frisch geschrieben: Leistung unbekannt (0)', call($ems, 'readChargerPowerKw', [1]) === 0.0);
$withSeen(0);
check('Vertrag 1.3, lastSeenAt = 0 (noch nie geantwortet): Leistung unbekannt (0)', call($ems, 'readChargerPowerKw', [1]) === 0.0);

// ===========================================================================
echo "\n14) Netzdienliche Faehigkeiten des WR (InverterHub-Vertrag 1.3) -- Grundlage der Bausteine\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wr = function (array $o = []) {
    attr('PartnerCache', json_encode(['inverterhub' => [array_merge(['instanceID' => IHUB_IID, 'contractVersion' => '1.3',
        'controlAuthority' => 'ems', 'controllable' => true,
        'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']], $o)]]));
};
attr('PartnerCache', json_encode([]));
check('nackt: kein Wechselrichter -> keine Faehigkeit, kein Fehler', call($ems, 'getGridServiceCapabilities') === []);
$wr();
check('GoodWe, Vertrag 1.3, EMS hat die Steuerhoheit: alle vier Faehigkeiten',
    call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release'], json_encode(call($ems, 'getGridServiceCapabilities')));
check('hasGridService(gridCharge) = true, Ident svc_grid_charge_w', call($ems, 'hasGridService', ['gridCharge']) === true && call($ems, 'gridServiceIdent', ['gridCharge']) === 'svc_grid_charge_w');
$wr(['gridServiceCapabilities' => []]);
check('Treiber ohne diese Befehle (leere Liste, z. B. SMA/Fronius): keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === [] && call($ems, 'hasGridService', ['chargeInhibit']) === false);
$wr(['gridServiceCapabilities' => ['chargeInhibit', 'release']]);
check('Teilmenge (nur Laden sperren + Freigabe): genau diese zwei', call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'release'] && call($ems, 'hasGridService', ['dischargeToGrid']) === false);
$wr(['contractVersion' => '1.2', 'gridServiceCapabilities' => null]);
check('aelterer Vertrag 1.2 ohne das Feld: keine Faehigkeit (Baustein entfaellt, kein Fehler)', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controlAuthority' => 'external']);
check('Steuerhoheit extern (z. B. Sunny Home Manager): keine Faehigkeit, obwohl der Treiber sie kann', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controlAuthority' => 'none']);
check('Steuerhoheit "none": keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controllable' => false]);
check('Treiber ohne Steuerregister (controllable=false): keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['contractVersion' => '2.0']);
check('fremde Vertrags-Major 2.0: vorsichtshalber keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['gridServiceCapabilities' => ['chargeInhibit', 'peakShave', 'release']]);
check('unbekannter Eintrag (kuenftige Faehigkeit) wird verworfen, bekannte bleiben', call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'release']);
$wr(['gridServiceCapabilities' => 'chargeInhibit']);
check('kaputtes Feld (String statt Liste): keine Faehigkeit, kein Absturz', call($ems, 'getGridServiceCapabilities') === []);
check('unbekannte Faehigkeit hat keinen Ident', call($ems, 'gridServiceIdent', ['peakShave']) === '');
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n15) B1 Mittagsspitze aufnehmen -- Entscheidung (reine Funktion, feste Uhrzeit)\n";
echo "    Umgebaut 30.09.2026: Reserve nicht mehr bis Tagesende, sondern nur noch bis zum\n";
echo "    Beginn des (datengetriebenen) Spitzenfensters; ab der Spitze immer freigeben;\n";
echo "    zeitunabhaengiger Preis-Override (Einkauf < Verguetung -. Wandlung/Zyklus).\n";

// Trapezkurve 06:00-18:00: Rand (Slots 24-31/64-71) + Plateau (Slots 32-63, 08:00-16:00).
// Schwelle 80 % vom Tagesmaximum liegt nur im Plateau -> Spitzenfenster 08:00-16:00 (32..63).
$curve = function (float $rand, float $plateau): array {
    $s = array_fill(0, 96, 0.0);
    for ($i = 24; $i < 32; $i++) { $s[$i] = $rand; }
    for ($i = 32; $i < 64; $i++) { $s[$i] = $plateau; }
    for ($i = 64; $i < 72; $i++) { $s[$i] = $rand; }
    return $s;
};
$sonne  = $curve(1000.0, 6000.0);   // Spitzenfenster 08:00-16:00
$trueb  = $curve(400.0,  2500.0);   // dieselbe Lage, schwaechere Prognose
$p10gut = $curve(800.0,  5000.0);
$p10mau = $curve(400.0,  2500.0);

$ems = freshEms();
// 07:00, vor dem Spitzenfenster (08:00). SOC 50 % von 40 kWh: 20 kWh Platz, x1,3 = 26 kWh.
$basis = ['nowSlot' => 28, 'latestSlot' => 90, 'peakFractionPct' => 80, 'pv' => $sonne, 'load' => array_fill(0, 96, null),
    'avgHouseW' => 400.0, 'capKwh' => 40.0, 'soc' => 50.0, 'pvW' => 3000.0, 'houseW' => 400.0, 'marginW' => 300.0,
    'safetyPct' => 130, 'price' => null, 'feedTariff' => 0.0, 'wasActive' => false];
$b1 = fn(array $o = []) => call($ems, 'b1Evaluate', [array_merge($basis, $o)]);
// Zweisprachigkeit Schritt 3: Mittagsspitze-Begruendungen englisch ohne Deutsch, active/basis unveraendert
$memoB = new ReflectionProperty('EMS', 'emsTextMemo');
$deWortB = '/[äöüÄÖÜß]|\b(und|oder|nicht|nur|reicht|Batterie|Resterwartung|Platz|Sicherheit|Spitze|Tagesende|Prognose\w*|Haus|voll)\b/u';
$GLOBALS['EMS_LANG_EN'] = true; $memoB->setValue(null, []);
$b1En = [$b1(), $b1(['soc' => 99.5]), $b1(['pvW' => 100.0]), $b1(['capKwh' => 0.0]), $b1(['nowSlot' => 95]), $b1(['nowSlot' => 50])];
$GLOBALS['EMS_LANG_EN'] = false; $memoB->setValue(null, []);
$b1De = [$b1(), $b1(['soc' => 99.5]), $b1(['pvW' => 100.0]), $b1(['capKwh' => 0.0]), $b1(['nowSlot' => 95]), $b1(['nowSlot' => 50])];
check('Englisch: Mittagsspitze-Begruendungen ohne Deutsch, active/basis wie deutsch', count(preg_grep($deWortB, array_column($b1En, 'reason'))) === 0 && array_column($b1En, 'active') === array_column($b1De, 'active') && array_column($b1En, 'basis') === array_column($b1De, 'basis'), json_encode(array_column($b1En, 'reason'), JSON_UNESCAPED_UNICODE));

echo "\n   Spitzenfenster selbst (peakWindowSlots(), isoliert)\n";
$shoulder = array_fill(0, 96, 0.0);
for ($i = 10; $i < 20; $i++) { $shoulder[$i] = 3000.0; }
for ($i = 20; $i < 30; $i++) { $shoulder[$i] = 6000.0; }
for ($i = 30; $i < 40; $i++) { $shoulder[$i] = 3000.0; }
check('80 %-Schwelle: nur das echte Plateau (Slot 20-29)', call($ems, 'peakWindowSlots', [$shoulder, 80.0]) === [20, 29]);
check('40 %-Schwelle: Schultern zaehlen mit (Slot 10-39)', call($ems, 'peakWindowSlots', [$shoulder, 40.0]) === [10, 39]);
check('Kurve durchgaengig 0: kein Fenster', call($ems, 'peakWindowSlots', [array_fill(0, 96, 0.0), 80.0]) === [null, null]);

echo "\n   Reserve vor dem Spitzenfenster (bis Fensterbeginn, nicht mehr bis Tagesende)\n";
check('keine PV-Prognose: nie sperren', $b1(['pv' => []])['active'] === false && $b1(['pv' => array_fill(0, 96, 0.0)])['active'] === false);
check('nach dem spaetesten Freigabezeitpunkt (manuelle Notbremse): freigeben', $b1(['nowSlot' => 25, 'latestSlot' => 24])['active'] === false);
check('Batterie voll: nicht sperren', $b1(['soc' => 99.5])['active'] === false);
$r = $b1(['pvW' => 600.0]);
check('gemessene PV nur 200 W ueber Haus (Wolke): freigeben, als Sicherheitsausstieg', $r['active'] === false && !empty($r['safety']), $r['reason']);
check('bei laufendem B1 reicht die halbe Marge (150 W < 200 W): bleibt gesperrt', $b1(['pvW' => 600.0, 'wasActive' => true])['active'] === true);
// Reserve 07:15-16:00 (Slot 29-63): 45,25 kWh >= 26 kWh Bedarf -> sperren
check('sonniger Vormittag, Reserve bis Spitzenbeginn 45,25 kWh >= 26 kWh: Laden sperren', $b1()['active'] === true, $b1()['reason']);
check('truebe Prognose, Reserve 16,8 kWh < 26 kWh: nicht sperren (Batterie soll voll werden)', $b1(['pv' => $trueb])['active'] === false, $b1(['pv' => $trueb])['reason']);
check('Lastprognose wird genutzt: 5,5 kW Last je Slot -> Reserve nur 4 kWh, nicht sperren', $b1(['load' => array_fill(0, 96, 5500.0)])['active'] === false);
check('Kapazitaet unbekannt (0 kWh): nicht sperren', $b1(['capKwh' => 0.0])['active'] === false);
// p10 (vorsichtige Prognose): 20 kWh Platz x 1,1 = 22 kWh
$r = $b1(['pv10' => $p10gut, 'safetyP10Pct' => 110]);
check('p10 vorhanden und ausreichend (37,1 kWh >= 22 kWh): sperren, Basis p10', $r['active'] === true && ($r['basis'] ?? '') === 'p10', $r['reason']);
$r = $b1(['pv10' => $p10mau, 'safetyP10Pct' => 110]);
check('unsicherer Tag: Median reicht (45,25 kWh), p10 nicht (16,8 kWh < 22 kWh) -> NICHT sperren', $r['active'] === false && ($r['basis'] ?? '') === 'p10', $r['reason']);
$r = $b1(['pv10' => [], 'safetyP10Pct' => 110]);
check('p10 fehlt: Rueckfall auf Median mit 130 %, Basis p50', $r['active'] === true && ($r['basis'] ?? '') === 'p50', $r['reason']);
check('p10 nur Nullen (nicht geliefert): Rueckfall auf Median', ($b1(['pv10' => array_fill(0, 96, 0.0)])['basis'] ?? '') === 'p50');
// Prognoseguete (PVF_GetAccuracy 1.0). Basisfall p50: Reserve 45,25 kWh, Bedarf 20 x 1,3 = 26 kWh
$acc = fn(array $o = []) => array_merge(['contractVersion' => '1.0', 'days' => 9, 'bias' => -12.58, 'mape' => 16.75,
    'byDaylightFraction' => [['from' => 0, 'to' => 0.125, 'factor' => 0.497, 'n' => 62]]], $o);
$g = ['maxMape' => 30, 'minDays' => 5];
check('Guete fehlt (Schnittstelle nicht da): unveraendert sperren', $b1(array_merge($g, ['accuracy' => null]))['active'] === true);
check('Dietmars Livewerte (9 Tage, 16,8 % Fehler, Bias -12,6 %): unveraendert sperren, Guete im Grund',
    ($r = $b1(array_merge($g, ['accuracy' => $acc()])))['active'] === true && strpos($r['reason'], 'Güte') !== false, $r['reason']);
check('Fehlerquote 45 % > 30 %: B1 setzt aus', ($r = $b1(array_merge($g, ['accuracy' => $acc(['mape' => 45.0])])))['active'] === false && strpos($r['reason'], 'Prognosegüte') !== false, $r['reason']);
check('zu wenig Tage (3 < 5): Guete wird ignoriert, auch bei 45 %', $b1(array_merge($g, ['accuracy' => $acc(['mape' => 45.0, 'days' => 3])]))['active'] === true);
check('Prognose zuletzt 100 % zu HOCH (bias +100): Zuschlag verdoppelt, 52 kWh Bedarf > 45,25 kWh Reserve -> nicht sperren',
    ($r = $b1(array_merge($g, ['accuracy' => $acc(['bias' => 100.0])])))['active'] === false, $r['reason']);
check('Prognose zu NIEDRIG (bias -40): kein Abschlag, unveraendert sperren', $b1(array_merge($g, ['accuracy' => $acc(['bias' => -40.0])]))['active'] === true);
check('fremde Vertrags-Major 2.0: Guete ignoriert', $b1(array_merge($g, ['accuracy' => $acc(['contractVersion' => '2.0', 'mape' => 90.0])]))['active'] === true);
check('Tageszeit-Faktoren werden NICHT erneut angewendet (0,497 am Morgen aendert nichts)',
    $b1(array_merge($g, ['accuracy' => $acc()]))['active'] === $b1(array_merge($g, ['accuracy' => $acc(['byDaylightFraction' => []])]))['active']);

echo "\n   Uebergang ins/aus dem Spitzenfenster (unabhaengig von der Reserve)\n";
$r = $b1(['nowSlot' => 32]); // 08:00, Fensterbeginn
check('Fensterbeginn (08:00): immer freigeben, egal wie viel Reserve fehlen wuerde', $r['active'] === false && strpos($r['reason'], 'Spitzenfenster erreicht') === 0, $r['reason']);
$r = $b1(['nowSlot' => 63]); // 15:45, letzter Slot im Fenster
check('letzter Spitzen-Slot (15:45): weiterhin freigeben', $r['active'] === false && strpos($r['reason'], 'Spitzenfenster erreicht') === 0, $r['reason']);
$r = $b1(['nowSlot' => 64]); // 16:00, Fenster vorbei
check('nach dem Fenster (16:00): freigeben, "Spitzenfenster vorbei"', $r['active'] === false && strpos($r['reason'], 'Spitzenfenster vorbei') === 0, $r['reason']);
check('truebe Prognose waere vor der Spitze eh schon nicht gesperrt -- am Fensterende trotzdem "erreicht"',
    $b1(['pv' => $trueb, 'nowSlot' => 32])['active'] === false);

echo "\n   Preis-Override (Einkauf inkl. Wandlung/Zyklus guenstiger als Verguetung -> immer sperren)\n";
// Basis (vor der Spitze): ohne Preis/Verguetung unveraendert wie oben (sperren, da Reserve reicht).
check('kein Preis/keine Verguetung uebergeben: Override inaktiv, normale Reserve-Pruefung', $b1()['active'] === true);
// Truebe Prognose wuerde OHNE Preis-Override nicht sperren (Reserve reicht nicht) --
// mit guenstigem Einkauf (10 ct < 18,36 ct x 0,95 = 17,44 ct, keine Zykluskosten) doch.
$r = $b1(['pv' => $trueb, 'price' => 0.10, 'feedTariff' => 0.1836]);
check('Einkauf 10 ct < Verguetung x Wandlung 17,44 ct: sperren, obwohl Reserve fuer sich allein nicht reichen wuerde',
    $r['active'] === true && strpos($r['reason'], 'Einkauf') !== false, $r['reason']);
$r = $b1(['pv' => $trueb, 'price' => 0.30, 'feedTariff' => 0.1836]);
check('Einkauf 30 ct > Verguetung x Wandlung 17,44 ct: Override greift nicht, Reserve entscheidet (nicht sperren)',
    $r['active'] === false && strpos($r['reason'], 'Einkauf') === false, $r['reason']);
check('Verguetung 0 (nicht bekannt): Override inaktiv, kein Fehler', $b1(['pv' => $trueb, 'price' => 0.05, 'feedTariff' => 0.0])['active'] === false);
// Zykluskosten senken die Grenze: 9.998 EUR / 8.000 Zyklen / 40 kWh = 3,124375 ct -> Grenze 17,44 - 3,12 = 14,32 ct
prop('BAT_Capacity_kWh', 40.0); prop('BAT_Price_EUR', 9998.0); prop('BAT_Cycles', 8000);
$r = $b1(['pv' => $trueb, 'price' => 0.15, 'feedTariff' => 0.1836]);
check('mit Zykluskosten (Grenze 14,32 ct): 15 ct ist NICHT mehr guenstiger, Override greift nicht',
    $r['active'] === false && strpos($r['reason'], 'Einkauf') === false, $r['reason']);
$r = $b1(['pv' => $trueb, 'price' => 0.14, 'feedTariff' => 0.1836]);
check('mit Zykluskosten (Grenze 14,32 ct): 14 ct ist guenstiger, Override greift', $r['active'] === true && strpos($r['reason'], 'Einkauf') !== false, $r['reason']);
prop('BAT_Capacity_kWh', 0.0); prop('BAT_Price_EUR', 0.0); prop('BAT_Cycles', 0);
// Override wirkt auch WAEHREND/NACH der Spitze (zeitunabhaengig) -- sonst wuerde die
// Spitzen-Freigabe die Batterie trotzdem laden lassen, obwohl Einkauf billiger waere.
$r = $b1(['nowSlot' => 40, 'price' => 0.10, 'feedTariff' => 0.1836]); // 10:00, mitten im Fenster
check('guenstiger Einkauf WAEHREND des Spitzenfensters: trotzdem sperren (Override zeitunabhaengig)',
    $r['active'] === true && strpos($r['reason'], 'Einkauf') !== false, $r['reason']);

echo "\n16) B1 im Zusammenspiel -- Vorrang, Faehigkeit, Steuerpfad svc_* statt ctl_*\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wr();                                                    // GoodWe, Vertrag 1.3, alle Faehigkeiten
// Trapezkurve mit Spitzenfenster 10:00-16:00 (Slot 40-63, >=80 % des Maximums), davor/danach
// eine schwaechere Schulter (06:00-10:00/16:00-20:00) -- ueberall reichlich Ueberschuss, aber
// mit einer echten, zeitabhaengigen Spitze (applyGridServiceB1() nutzt die reale Uhrzeit).
$fcSonnig = array_fill(0, 96, 0.0);
for ($i = 24; $i < 40; $i++) { $fcSonnig[$i] = 3000.0; }
for ($i = 40; $i < 64; $i++) { $fcSonnig[$i] = 50000.0; }
for ($i = 64; $i < 80; $i++) { $fcSonnig[$i] = 3000.0; }
attr('FcPvToday', json_encode($fcSonnig));
attr('FcLoadToday', json_encode(array_fill(0, 96, null)));
prop('NETZ_B1_Latest_Hour', 24);
prop('BAT_Capacity_kWh', 40.0);
$sonnig = state(['bat_soc' => 50.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]);
$autoD = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$nowSlotReal = (int)(((int)date('H') * 60 + (int)date('i')) / 15);
$d = call($ems, 'applyGridServiceB1', [$autoD, $sonnig]);
if ($nowSlotReal < 40) {
    check('vor der Spitze (10:00): B1 sperrt das Laden (svc, Quelle netzdienlich)', ($d['svc'] ?? '') === 'chargeInhibit' && $d['source'] === 'netzdienlich', fmt($d) . " (Slot $nowSlotReal)");
} else {
    check('in/nach der Spitze: B1 greift nicht, Automatik laedt', empty($d['svc']) && $d['op_mode'] === EMS_OP_AUTO, fmt($d) . " (Slot $nowSlotReal)");
}
$planD = array_merge($autoD, ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 8000, 'gw_enable' => true, 'source' => 'tagesplan']);
check('Tagesplan-Sollwert (Netzladen, Preis sticht): B1 greift nicht', call($ems, 'applyGridServiceB1', [$planD, $sonnig]) === $planD);
$grD = array_merge($planD, ['op_mode' => EMS_OP_GRIDREWARDS, 'source' => 'tibber']);
check('Grid Rewards: B1 greift nicht', call($ems, 'applyGridServiceB1', [$grD, $sonnig]) === $grD);
$nbD = array_merge($autoD, ['source' => 'netzbetreiber']);
check('§14a-Netzbetreiber (auch wenn Automatik): B1 greift nicht', call($ems, 'applyGridServiceB1', [$nbD, $sonnig]) === $nbD);
$wr(['gridServiceCapabilities' => []]);
check('WR ohne Faehigkeit (z. B. SMA): B1 greift nicht, kein Fehler', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
$wr(['gridServiceCapabilities' => ['chargeInhibit']]);
check('WR kann sperren, aber nicht freigeben: B1 greift nicht (kein Weg zurueck)', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
$wr();
prop('NETZ_Aktiv', false);
check('netzdienliche Bausteine abgeschaltet: B1 greift nicht', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
prop('NETZ_Aktiv', true);
attr('FcPvToday', '[]');
$d = call($ems, 'applyGridServiceB1', [$autoD, $sonnig]);
check('keine PV-Prognose im Zwischenspeicher: B1 greift nicht, Grund sichtbar angehaengt',
    empty($d['svc']) && $d['source'] === 'ems' && strpos($d['reason'], 'Automatik | 🌞 Mittagsspitze nicht aktiv: keine PV-Prognose') === 0, $d['reason']);
$d = call($ems, 'applyGridServiceB1', [$autoD, state(['bat_soc' => 50.0, 'pv_total_w' => 500.0, 'house_pow_w' => 400.0])]);
check('Wolke (PV kaum ueber Haus): Grund "deckt die Hauslast nicht sicher" sichtbar, Entscheidung sonst unveraendert',
    empty($d['svc']) && $d['op_mode'] === EMS_OP_AUTO && $d['gw_enable'] === false && strpos($d['reason'], 'Mittagsspitze nicht aktiv') !== false, $d['reason']);
$d = call($ems, 'applyGridServiceB1', [$autoD, state(['bat_soc' => 50.0, 'pv_total_w' => 0.0, 'house_pow_w' => 400.0])]);
check('Nachts ohne PV: kein Mittagsspitzen-Hinweis in der Begruendung (0.106.1)', $d === $autoD, $d['reason'] ?? '');
attr('FcPvToday', json_encode(array_fill(0, 96, 50000.0)));

echo "\n   Steuerpfad in applyDecision()\n";
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true,
    'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']]]]));
$b1D = array_merge($autoD, ['svc' => 'chargeInhibit', 'source' => 'netzdienlich', 'reason' => 'B1 Test']);
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$b1D, state()]);
$ids = array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
check('B1 an: genau svc_charge_inhibit=true, kein ctl_* im selben Zyklus', $ids === ['svc_charge_inhibit=true'], json_encode($ids));
check('Zustand gemerkt (B1Active), Quelle netzdienlich sichtbar', $ems->ReadAttributeBoolean('B1Active') === true && $ems->ReadAttributeString('LastDecisionSource') === 'netzdienlich');
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$b1D, state()]);
check('B1 bleibt an: kein erneutes Schreiben (Modus haelt mit enable=false)', $GLOBALS['ACTIONS'] === [], json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$autoD, state()]);
$ids = array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
check('B1 endet: erst svc_release, kein ctl_* im selben Zyklus', $ids === ['svc_release=true'] && $ems->ReadAttributeBoolean('B1Active') === false, json_encode($ids));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$autoD, state()]);
check('naechster Zyklus: wieder normaler ctl-Pfad', (bool)array_filter($GLOBALS['ACTIONS'], fn($x) => $x[1] === 'ctl_ems_enable'), json_encode($GLOBALS['ACTIONS']));
attr('B1Active', true);
$GLOBALS['ACTIONS'] = [];
$forced = array_merge($autoD, ['force' => true, 'reason' => 'Waechter']);
call($ems, 'applyDecision', [$forced, state()]);
check('Waechter-Rueckfall waehrend B1: ebenfalls zuerst svc_release', array_column($GLOBALS['ACTIONS'], 1) === ['svc_release'], json_encode($GLOBALS['ACTIONS']));
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
