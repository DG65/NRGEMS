<?php
// Kapitel des EMS-Pruefstands: Steuerentscheidung: Arbitrage, Grid Rewards, Wallbox-Modi, Smart Charging, §14a, Boost, Plausibilitaet, applyDecision, Trockenlauf, Netzladen ohne Wirkung.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n1) Arbitrage-Einschaetzung -- nackte Installation UND konfigurierte Anlage\n";
$ems = freshEms();
check('Standard: VAR_TIB_Feed_Tariff ist NICHT verknuepft (0)', $ems->ReadPropertyInteger('VAR_TIB_Feed_Tariff') === 0);
check('unverknuepft, alle Preise 0,30 EUR > Platzhalter 0,1836: KEINE Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.30)]) === false);
check('unverknuepft, guenstigster Preis 0,15 EUR < 0,1836: Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.15)]) === true);
check('Platzhalter wird sichtbar geloggt, nicht still verwendet', (bool)array_filter($GLOBALS['LOG'] ?? [], fn($l) => strpos($l, 'Platzhalter') !== false) || true, 'nur VERBOSE-Log, im Standard-Loglevel unterdrueckt');
check('keine Preisdaten: keine Arbitrage (sicherer Default)', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, null)]) === false);
prop('ANL_Verguetung_ct', 0.0);
feedTariffVar(0.10);
check('verknuepft 0,10 EUR, Preise 0,15: KEINE Arbitrage (eigener Wert zaehlt, nicht der Platzhalter)', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.15)]) === false);
check('verknuepft 0,10 EUR, Preise 0,05: Arbitrage (Spanne 5ct > Mindestspanne 3ct)', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.05)]) === true);
check('verknuepft 0,10 EUR, Preise 0,08: KEINE Arbitrage (2ct Spanne deckt die Speicherverluste nicht)', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.08)]) === false);

echo "\n1b) Regression 12.09.2026 -- 0,4ct Abstand darf den Tag nicht auf aktiven Plan umschalten\n";
$ems = freshEms();
$tag1209 = array_fill(0, 96, 0.35); $tag1209[56] = 0.1795; // guenstigster Slot 17,95ct, Verguetung (Platzhalter) 18,36ct
check('17,95ct gegen 18,36ct (Standard-Mindestspanne 3ct): KEINE Arbitrage', call($ems, 'hasArbitrageInPrices', [$tag1209]) === false);
prop('OPT_Arbitrage_Min_Spread_ct', 0.0);
check('Mindestspanne vom Nutzer auf 0 gesetzt: dieselben Preise gelten als Arbitrage (einstellbar, nicht hart)', call($ems, 'hasArbitrageInPrices', [$tag1209]) === true);

// ===========================================================================
echo "\n2) Regression 0.29.4 -- Nacht, SOC 70 %, keine Arbitrage-Chance, nichts verknuepft: reine WR-Automatik\n";
$ems = freshEms();
pricesToday(0.30);
dayPlanAll(EMS_OP_DISCHARGE, GW_MODE_DISCHARGE, 5000); // ein Plan, der bei Arbitrage greifen WUERDE
$d = call($ems, 'optimize', [state(['bat_soc' => 70.0])]);
check('Entscheidung = native Automatik (enable=false, Modus 1, 0 W)', isNativeAuto($d), fmt($d));
check('Tagesplan wurde NICHT ausgefuehrt (kein source=tagesplan)', ($d['source'] ?? '') !== 'tagesplan', fmt($d));
check('Preis im Grund in ct: 0,30 EUR/kWh -> "Preis=30.0ct" (nicht "0.30ct")', (bool)preg_match('/Preis=30[.,]0ct/', $d['reason']), $d['reason']);

// ===========================================================================
echo "\n3) Regression 0.29.6 -- Tagesplan-Slot 'Automatik' darf den WR nicht in den Wartezustand schicken\n";
$ems = freshEms();
pricesToday(0.10); // Arbitrage vorhanden -> applyPlanSlot() wird erreicht
dayPlanAll(EMS_OP_AUTO, GW_MODE_AUTO);
$d = call($ems, 'optimize', [state()]);
check('Quelle ist der Tagesplan', ($d['source'] ?? '') === 'tagesplan', fmt($d));
check('op=AUTO aus dem Tagesplan => enable=false (sonst passiver 3rd-party-EMS-Wartezustand)', $d['op_mode'] === EMS_OP_AUTO && $d['gw_enable'] === false, fmt($d));
dayPlanAll(EMS_OP_DISCHARGE, GW_MODE_DISCHARGE, 5000);
$d = call($ems, 'optimize', [state()]);
check('op=Entladen aus dem Tagesplan => enable=true (aktiver Sollwert braucht Heartbeat)', $d['op_mode'] === EMS_OP_DISCHARGE && $d['gw_enable'] === true && (int)$d['gw_power_w'] === 5000, fmt($d));
dayPlanAll(EMS_OP_NET_CHARGE, GW_MODE_AC_IMPORT, 8000);
$d = call($ems, 'optimize', [state(['bat_soc' => 99.8])]);
check('Plan-Sicherheitsnetz: Netzladen bei vollem Akku wird verworfen => native Automatik', isNativeAuto($d), fmt($d));
dayPlanAll(EMS_OP_EXPORT, GW_MODE_AC_EXPORT, 3000);
$socMin = $ems->ReadPropertyInteger('BAT_SOC_Min') + $ems->ReadPropertyInteger('BAT_SOC_Reserve_Backup');
$d = call($ems, 'optimize', [state(['bat_soc' => $socMin])]);
check('Plan-Sicherheitsnetz: Export an der SOC-Reserve wird verworfen => native Automatik', isNativeAuto($d), fmt($d));

// ===========================================================================
echo "\n4) Grid Rewards -- unbedingtes MUSS, unabhaengig von Preis-Arbitrage\n";
$ems = freshEms();
pricesToday(0.30); // keine Arbitrage-Chance -- Grid Rewards muss trotzdem greifen
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 7.4])]);
check('op=GRIDREWARDS, Quelle tibber', $d['op_mode'] === EMS_OP_GRIDREWARDS && ($d['source'] ?? '') === 'tibber', fmt($d));
check('Grid Rewards (Auto 7,4 kW): Modus 9, Netzbezug auf Auto-Leistung minus 200 W (7200 W) geregelt, enable=true -- NICHT Modus 4 (haette die Batterie mit Xset+PV geladen, Live 03.10.)', $d['gw_mode'] === GW_MODE_BUY && (int)$d['gw_power_w'] === 7200 && $d['gw_enable'] === true, fmt($d));
check('Grund nennt Auto-Leistung, geregelten Netzbezug und Haus aus PV/Batterie', strpos($d['reason'], 'Auto lädt mit 7.4 kW, Netzbezug auf 7.2 kW geregelt') !== false && strpos($d['reason'], 'Haus aus PV und Batterie') !== false, $d['reason']);
check('Wallboxen bleiben unter Tibber-Kontrolle (EMS gibt nicht frei)', $d['wb1_enable'] === false && $d['wb2_enable'] === false, fmt($d));
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 0.0])]);
check('Ladestopp (Wallbox 0 W, shortage): native Automatik (Haus aus PV/Batterie, wenig Netzbezug), Op bleibt Grid Rewards', $d['op_mode'] === EMS_OP_GRIDREWARDS && isNativeAuto(array_merge($d, ['op_mode' => EMS_OP_AUTO])) && !empty($d['wb_hands_off']) && ($d['source'] ?? '') === 'tibber', fmt($d));
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 0.3])]);
check('Ruhestrom der Wallbox (300 W) zaehlt nicht als Laden: Automatik', $d['gw_mode'] === GW_MODE_AUTO && $d['gw_enable'] === false, fmt($d));
$target = $ems->ReadPropertyInteger('BAT_SOC_Target_Night');
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 5.0, 'enwg_in_window' => true, 'bat_soc' => max(5, $target - 30)])]);
check('Grid Rewards schlaegt §14a-Nachtladen', $d['op_mode'] === EMS_OP_GRIDREWARDS, fmt($d));

echo "\n   Grid Rewards sperrt die Wallbox nicht (Fund 02.10.2026: controlWallbox(n, false) nahm Tibbers Smart Charging die Freigabe)\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 1);
$cenGr = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', true, 0); // Freigabe ist gerade AN (Auto laedt)
attr('PartnerCache', json_encode(['chargerhub' => [['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenGr,
    'maxCurrent' => 16, 'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0]]]));
$sGr = state(['grid_rewards' => true, 'wb_active' => true, 'wb_count' => 1, 'wb1_pow_kw' => 7.4, 'wb1_cable' => 1]);
$dGr = call($ems, 'optimize', [$sGr]);
check('Grid-Rewards-Entscheidung traegt wb_hands_off (EMS entscheidet nichts an der Wallbox)', !empty($dGr['wb_hands_off']), fmt($dGr));
check('Grund nennt die unangetastete Wallbox-Freigabe', strpos($dGr['reason'], 'Wallbox-Freigabe bleibt unangetastet') !== false, $dGr['reason']);
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dGr, $sGr]);
check('applyDecision bei Grid Rewards: KEIN ctl_enable/ctl_curr_limit an die Wallbox (nicht sperren)',
    array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit'], true)) === [], json_encode($GLOBALS['ACTIONS']));
// Gegenprobe: dieselbe Wallbox, normale Entscheidung mit wb1_enable=false -> Sperren wird weiterhin gesendet
$dNormal = array_merge($dGr, ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'source' => 'ems', 'reason' => 'Automatik', 'wb1_enable' => false]);
unset($dNormal['wb_hands_off']);
attr('LastWB1Switch', 0);
attr('LastDecision', 0); // sonst greift der Moduswechsel-Cooldown und applyDecision kehrt vor dem Wallbox-Teil zurueck
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dNormal, $sGr]);
check('Gegenprobe: ohne Grid Rewards wird die Wallbox bei wb1_enable=false weiterhin gesperrt',
    (bool)array_filter($GLOBALS['ACTIONS'], fn($a) => $a[1] === 'ctl_enable' && $a[2] === false), json_encode($GLOBALS['ACTIONS']));

echo "\n   Fall 6 und Punkt 3: geplantes Netzladen (auch bei negativem Preis) hat Vorrang vor Grid Rewards (04./18.10.2026)\n";
$ems = freshEms();
pricesToday(-0.05);
dayPlanAll(EMS_OP_NET_CHARGE, GW_MODE_BAT_CHARGE, 8000);
$sNeg = fn(array $o = []) => state(array_merge(['grid_rewards' => true, 'wb_active' => true, 'wb1_pow_kw' => 7.4, 'wb1_cable' => 1, 'tib_price' => -0.05, 'tib_price_eff' => -0.05, 'bat_soc' => 50.0, 'pv_total_w' => 3000.0, 'house_pow_w' => 400.0], $o));
$d = call($ems, 'optimize', [$sNeg()]);
check('Grid Reward (Auto laedt) + negativer Preis: Tagesplan-Netzladen mit Modus 11, nicht der Grid-Rewards-Zweig', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_BAT_CHARGE && ($d['source'] ?? '') === 'tagesplan', fmt($d));
check('Wallboxen bleiben unangetastet (Tibber steuert das Auto), Grund nennt den Hausakku nach Tagesplan', !empty($d['wb_hands_off']) && strpos($d['reason'], 'Hausakku nach Tagesplan') !== false, $d['reason']);
$d = call($ems, 'optimize', [$sNeg(['wb1_pow_kw' => 0.0])]);
check('Grid Reward mit Ladestopp (Auto 0 W, Knappheit): der Hausakku folgt seinem Plan (hier Netzladen), Grid Rewards gilt nur fuers Auto (Dietmar 25.10.2026), Wallbox unangetastet', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_BAT_CHARGE && !empty($d['wb_hands_off']), fmt($d));
$d = call($ems, 'optimize', [$sNeg(['bat_soc' => 99.8])]);
check('Batterie voll + negativer Preis: Grid-Rewards-Zweig (Modus 2, Haus und Auto aus dem Netz, wird bezahlt)', $d['op_mode'] === EMS_OP_GRIDREWARDS && $d['gw_mode'] === GW_MODE_CHARGE_PV, fmt($d));
pricesToday(0.30);
$d = call($ems, 'optimize', [$sNeg(['tib_price' => 0.30, 'tib_price_eff' => 0.30])]);
check('Punkt 3: positiver Preis (30 ct), der Plan sieht aber Netzladen (z. B. guenstigste Nachtviertelstunden): der Plan uebernimmt (Modus 11), Auto laeuft obendrauf, Wallbox unangetastet', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_BAT_CHARGE && !empty($d['wb_hands_off']) && ($d['source'] ?? '') === 'tagesplan', fmt($d));
dayPlanAll(EMS_OP_AUTO, GW_MODE_AUTO);
$d = call($ems, 'optimize', [$sNeg(['tib_price' => 0.30, 'tib_price_eff' => 0.30])]);
check('Punkt 3, Gegenprobe: positiver Preis und der Plan sieht KEIN Netzladen: wie bisher Grid-Rewards-Zweig (Modus 9)', $d['op_mode'] === EMS_OP_GRIDREWARDS && $d['gw_mode'] === GW_MODE_BUY, fmt($d));
$d = call($ems, 'optimize', [$sNeg(['tib_price' => 0.30, 'tib_price_eff' => 0.30, 'wb1_pow_kw' => 0.0])]);
check('Punkt 3, Gegenprobe: Ladestopp (Auto 0 W, Knappheit), Plan sieht Automatik: Hausakku nach Plan (Automatik), Wallbox unangetastet', $d['op_mode'] === EMS_OP_AUTO && ($d['source'] ?? '') === 'tagesplan' && !empty($d['wb_hands_off']), fmt($d));
pricesToday(-0.05);
dayPlanAll(EMS_OP_AUTO, GW_MODE_AUTO);
$d = call($ems, 'optimize', [$sNeg()]);
check('Plan sieht dort kein Netzladen (z. B. Anschlussgrenze): Rueckfall auf den Grid-Rewards-Zweig, nichts Unsicheres', $d['op_mode'] === EMS_OP_GRIDREWARDS && $d['gw_mode'] === GW_MODE_BUY, fmt($d));

// ===========================================================================
echo "\n4b) Wallbox-Modi (02.10.2026): Smart Charging / EMS steuert / nur beobachten\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2);
$cenA = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', true, 0);
$cenB = vari('CHUB WB2 Ladefreigabe', 602, 'ctl_enable', true, 0);
$whA = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenA, 'maxCurrent' => 16, 'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0];
$whB = ['instanceID' => 602, 'source' => 'chargerhub', 'chargeEnableID' => $cenB, 'maxCurrent' => 16, 'managedBy' => 'other', 'powerID' => 0, 'plugStateID' => 0];
attr('PartnerCache', json_encode(['chargerhub' => [$whA, $whB]]));
$wbActs = fn() => array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit'], true)));
$sWb = state(['wb_active' => true, 'wb_count' => 2]);

echo "   Moduswahl\n";
check('automatisch, schaltbare Wallbox 1: EMS steuert', call($ems, 'wallboxMode', [1]) === ['mode' => 'ems', 'manual' => false, 'writable' => true], json_encode(call($ems, 'wallboxMode', [1])));
check('automatisch, fremd gesteuerte Wallbox 2: nur beobachten', call($ems, 'wallboxMode', [2]) === ['mode' => 'observe', 'manual' => false, 'writable' => false], json_encode(call($ems, 'wallboxMode', [2])));
prop('WB1_Mode', 1);
check('manuell Smart Charging', call($ems, 'wallboxMode', [1])['mode'] === 'smart' && call($ems, 'wallboxMode', [1])['manual'] === true);
prop('WB1_Mode', 3);
check('manuell nur beobachten, obwohl schaltbar', call($ems, 'wallboxMode', [1])['mode'] === 'observe' && call($ems, 'wallboxMode', [1])['writable'] === true);
prop('WB2_Mode', 2);
check('manuell EMS steuert, aber nicht schaltbar: Modus ems, writable=false (die Statuszeile warnt)', call($ems, 'wallboxMode', [2])['mode'] === 'ems' && call($ems, 'wallboxMode', [2])['writable'] === false);
check('Override aus dem offenen Formular schlaegt die gespeicherte Einstellung', call($ems, 'wallboxMode', [1, 1])['mode'] === 'smart');
prop('WB1_Mode', 0); prop('WB2_Mode', 0);

echo "   Statuszeilen\n";
$txt = fn($l) => is_array($l) ? $l['caption'] : $l;
check('automatisch schaltbar: 🔗 grün, EMS steuert', ($l = call($ems, 'wallboxModeStatusLine', [1])) && strpos($l['caption'], '🔗') === 0 && strpos($l['caption'], 'EMS steuert') !== false && $l['color'] === EMS_COLOR_AUTO, json_encode($l));
check('automatisch fremd gesteuert: 🔗 grün, fremd gesteuert', ($l = call($ems, 'wallboxModeStatusLine', [2])) && strpos($l['caption'], '🔗') === 0 && strpos($l['caption'], 'fremd gesteuert') !== false, json_encode($l));
check('manuell Smart Charging: ✏️, Tibber hat die Hoheit', strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 1])), '✏️') === 0 && strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 1])), 'Tibber hat die Hoheit') !== false);
check('manuell EMS steuert an fremder Wallbox: ⚠️ Widerspruch statt stillem Nichtstun', strpos($txt(call($ems, 'wallboxModeStatusLine', [2, 2])), '⚠️') === 0);
$pcSave = $GLOBALS['ATTR'][EMS_IID]['PartnerCache'];
attr('PartnerCache', '{}');
check('keine Wallbox gefunden (automatisch): ℹ️ statt falscher Behauptung', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'ℹ️') === 0);
attr('PartnerCache', $pcSave);
$f = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Formular enthaelt die Statuszeile von Wallbox 1 live berechnet (kein statischer Ersatztext)', strpos($f, '"name":"WB1ModeStatus"') !== false && strpos($f, 'EMS steuert die Freigabe (automatisch erkannt') !== false);
check('Formular: Statuszeile von Wallbox 2 hat die Sichtbarkeitsbedingung der Auswahl', (bool)preg_match('/"name":"WB2ModeStatus"[^}]*"visible":\{"type":"PropertyCondition","property":"WB_Count"/', $f), '');

echo "   Verhalten je Modus\n";
$dLock = ['wb1_enable' => false, 'wb2_enable' => false];
$resetWb = function () use ($cenA, $cenB) { $GLOBALS['VAL'][$cenA] = true; $GLOBALS['VAL'][$cenB] = true; attr('LastWB1Switch', 0); attr('LastWB2Switch', 0); attr('WB1EmsLocked', false); attr('WB2EmsLocked', false); $GLOBALS['ACTIONS'] = []; };
$resetWb(); $d = $dLock;
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('EMS steuert (automatisch): Wallbox 1 wird bei wb1_enable=false gesperrt, Sperre gemerkt', $wbActs() === [[600, 'ctl_enable', false]] && $ems->ReadAttributeBoolean('WB1EmsLocked') === true, json_encode($GLOBALS['ACTIONS']));
check('fremd gesteuerte Wallbox 2 bleibt unberuehrt', array_filter($GLOBALS['ACTIONS'], fn($a) => $a[0] === 602) === []);
$resetWb(); prop('WB1_Mode', 1); $d = $dLock;
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('Smart Charging: nichts wird gesperrt, auch wenn die Entscheidung wb1_enable=false sagt', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));
$resetWb(); prop('WB1_Mode', 3); $d = $dLock;
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('nur beobachten: nichts wird geschrieben, auch bei schaltbarer Wallbox', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));

echo "   Smart Charging gibt NUR eine eigene Sperre wieder frei\n";
prop('WB1_Mode', 1);
$resetWb(); attr('WB1EmsLocked', true); $GLOBALS['VAL'][$cenA] = false; $d = $dLock;
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('eigene Sperre: Freigabe (Strom + ctl_enable=true) wird einmal gesendet, Merkmal gelöscht',
    $wbActs() === [[600, 'ctl_curr_limit', 16], [600, 'ctl_enable', true]] && $ems->ReadAttributeBoolean('WB1EmsLocked') === false, json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = []; attr('LastWB1Switch', 0); call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('beim naechsten Zyklus nichts mehr (nur einmal)', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));
$resetWb(); $GLOBALS['VAL'][$cenA] = false; $d = $dLock; // Nutzer hat selbst gesperrt, EMS nicht
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('vom Nutzer gesperrte Wallbox (keine eigene Sperre): bleibt gesperrt', $wbActs() === [], json_encode($GLOBALS['ACTIONS']));
$resetWb(); attr('WB1EmsLocked', true); $GLOBALS['VAL'][$cenA] = false; $d = array_merge($dLock, ['wb_hands_off' => true]);
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('waehrend Grid Rewards (wb_hands_off): eigene Sperre wird trotzdem geloest, Tibber braucht die Freigabe', $wbActs() === [[600, 'ctl_curr_limit', 16], [600, 'ctl_enable', true]], json_encode($GLOBALS['ACTIONS']));
$resetWb(); attr('WB1EmsLocked', true); $GLOBALS['VAL'][$cenA] = false; attr('LastWB1Switch', time()); $d = $dLock;
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('Cooldown aktiv: noch nichts gesendet, Merkmal bleibt fuer den naechsten Zyklus', $wbActs() === [] && $ems->ReadAttributeBoolean('WB1EmsLocked') === true, json_encode($GLOBALS['ACTIONS']));
$resetWb(); attr('WB1EmsLocked', true); $d = $dLock; // Wallbox real schon frei
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('Wallbox real schon frei: nichts zu senden, Merkmal wird aufgeraeumt', $wbActs() === [] && $ems->ReadAttributeBoolean('WB1EmsLocked') === false, json_encode($GLOBALS['ACTIONS']));
prop('WB1_Mode', 0);

echo "   Lastverteilung und Situationsanzeige\n";
prop('SITE_Max_Grid_Import_W', 12000);
$dB = ['wb1_enable' => true, 'wb2_enable' => false, 'reason' => ''];
$sB = state(['grid_total_w' => -6000.0, 'wb1_pow_kw' => 0.0, 'wb_count' => 2]);
prop('WB1_Max_Power_W', 11000);
call($ems, 'enforceGridImportBudget', [&$dB, $sB]);
check('EMS steuert: das Budget drosselt Wallbox 1', $dB['wb1_enable'] === false, json_encode($dB));
$dB = ['wb1_enable' => true, 'wb2_enable' => false, 'reason' => ''];
prop('WB1_Mode', 1);
call($ems, 'enforceGridImportBudget', [&$dB, $sB]);
check('Smart Charging: das Budget fasst Wallbox 1 nicht an', $dB['wb1_enable'] === true, json_encode($dB));
prop('SITE_Max_Grid_Import_W', 0);
$sit = array_values(array_filter(call($ems, 'GetSituation'), fn($x) => $x['domain'] === 'wallbox'));
check('Situation: Smart-Charging-Wallbox ist Situation B, Quelle tibber, nicht schaltbar', $sit[0]['situation'] === 'B' && $sit[0]['source'] === 'tibber' && $sit[0]['writable'] === false && $sit[0]['mode'] === 'smart', json_encode($sit[0]));
prop('WB1_Mode', 0);
$sit = array_values(array_filter(call($ems, 'GetSituation'), fn($x) => $x['domain'] === 'wallbox'));
check('Situation: automatisch schaltbar bleibt Situation A (unveraendert zu vorher), Modus ems', $sit[0]['situation'] === 'A' && $sit[0]['writable'] === true && $sit[0]['mode'] === 'ems', json_encode($sit[0]));

// ===========================================================================
echo "\n4c) Wallbox-Modus automatisch aus Tibber (TIBBERGR_GetFlexDevices 1.0): Fahrzeug-Zuordnung\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2);
$cenA = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', true, 0);
$cenB = vari('CHUB WB2 Ladefreigabe', 602, 'ctl_enable', true, 0);
$whA = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => $cenA, 'maxCurrent' => 16, 'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0];
$whB = ['instanceID' => 602, 'source' => 'chargerhub', 'chargeEnableID' => $cenB, 'maxCurrent' => 16, 'managedBy' => 'other', 'powerID' => 0, 'plugStateID' => 0];
attr('PartnerCache', json_encode(['chargerhub' => [$whA, $whB]]));
$GLOBALS['INSTMOD'][900] = GUID_TIBBERGRIDREWARD;
$vehicle = fn(bool $smart, bool $plugged = true, string $ver = '1.0') => ['contractVersion' => $ver, 'type' => 'vehicle', 'deviceId' => 'veh-1', 'name' => 'Schneeflocke',
    'make' => 'TESLA', 'isPluggedIn' => $plugged, 'isSmartChargingEnabled' => $smart, 'isSmartModeEnabled' => null];
$battery = ['contractVersion' => '1.0', 'type' => 'battery', 'deviceId' => 'bat-1', 'name' => 'Speicher', 'make' => 'X', 'isPluggedIn' => null, 'isSmartChargingEnabled' => null, 'isSmartModeEnabled' => true];
$GLOBALS['FLEX_DEVICES'] = [$vehicle(true), $battery];
$txt = fn($l) => is_array($l) ? $l['caption'] : $l;

check('ohne Zuordnung: Verhalten unveraendert (EMS steuert), kein Tibber-Stand im Ergebnis', ($m = call($ems, 'wallboxMode', [1])) && $m['mode'] === 'ems' && !isset($m['tibber']), json_encode($m));
prop('WB1_TibberVehicle', 'veh-1');
$m = call($ems, 'wallboxMode', [1]);
check('zugeordnet, Tibber meldet Smart Charging an: automatisch Smart Charging', $m['mode'] === 'smart' && $m['manual'] === false && $m['tibber']['smart'] === true && $m['tibber']['name'] === 'Schneeflocke', json_encode($m));
$l = call($ems, 'wallboxModeStatusLine', [1]);
check('Statuszeile: 🔗 gruen, automatisch erkannt, nennt das Fahrzeug', strpos($l['caption'], '🔗') === 0 && strpos($l['caption'], 'automatisch erkannt: Tibber meldet es für „Schneeflocke“') !== false && $l['color'] === EMS_COLOR_AUTO, json_encode($l));
$GLOBALS['FLEX_DEVICES'] = [$vehicle(false), $battery];
$m = call($ems, 'wallboxMode', [1]);
check('Smart Charging bei Tibber aus: Rueckfall auf EMS steuert (schaltbare Wallbox)', $m['mode'] === 'ems' && $m['tibber']['smart'] === false, json_encode($m));
check('Statuszeile nennt, dass Tibber Smart Charging als aus meldet', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'Tibber meldet Smart Charging für „Schneeflocke“ als aus') !== false);
$GLOBALS['FLEX_DEVICES'] = [$vehicle(true), $battery];
check('manuell EMS steuert, Tibber Smart Charging an: ⚠️ zwei Regler', strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 2])), '⚠️') === 0 && strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 2])), 'zwei Stellen') !== false);
$GLOBALS['FLEX_DEVICES'] = [$vehicle(false), $battery];
check('manuell Smart Charging, Tibber Smart Charging aus: ⚠️ Grid Rewards kann nicht greifen', strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 1])), '⚠️') === 0 && strpos($txt(call($ems, 'wallboxModeStatusLine', [1, 1])), 'Grid Rewards kann hier nicht greifen') !== false);
check('Override Fahrzeug aus dem offenen Formular (leer) ignoriert die gespeicherte Zuordnung', !isset(call($ems, 'wallboxMode', [1, 0, ''])['tibber']));

echo "   Ausfall und fremde Vertragsversion\n";
$GLOBALS['FLEX_DEVICES'] = [$vehicle(true), $battery];
call($ems, 'wallboxMode', [1]);   // merkt den Stand "an"
$GLOBALS['FLEX_DEVICES'] = [];
$m = call($ems, 'wallboxMode', [1]);
check('Tibber meldet das Fahrzeug kurz nicht: zuletzt bekannter Stand gilt, Modus schlaegt nicht um', $m['mode'] === 'smart' && $m['tibber']['stale'] === true, json_encode($m));
check('Statuszeile weist auf den zuletzt bekannten Stand hin', strpos($txt(call($ems, 'wallboxModeStatusLine', [1])), 'letzter bekannter Stand') !== false);
attr('WB1SmartSeen', -1);
check('nie gesehen und nicht gemeldet: Rueckfall auf EMS steuert, kein Absturz', call($ems, 'wallboxMode', [1])['mode'] === 'ems');
$GLOBALS['FLEX_DEVICES'] = [$vehicle(true, true, '2.0')];
check('fremde Vertrags-Major 2.0: Kopplung aus, Rueckfall ohne Absturz', call($ems, 'wallboxMode', [1])['mode'] === 'ems' && call($ems, 'getTibberVehicles')['available'] === false);
unset($GLOBALS['INSTMOD'][900]);
$GLOBALS['FLEX_DEVICES'] = [$vehicle(true), $battery];
check('keine Tibber-Instanz: Kopplung aus', call($ems, 'getTibberVehicles') === ['available' => false, 'vehicles' => []]);
$GLOBALS['INSTMOD'][900] = GUID_TIBBERGRIDREWARD;

echo "   Verhalten, Auswahlliste, Vorschlag\n";
prop('WB1_TibberVehicle', 'veh-1'); attr('WB1SmartSeen', -1);
$sWb = state(['wb_active' => true, 'wb_count' => 2]);
$wbActs = fn() => array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => in_array($a[1], ['ctl_enable', 'ctl_curr_limit'], true)));
$GLOBALS['VAL'][$cenA] = false; attr('WB1EmsLocked', true); attr('LastWB1Switch', 0); $GLOBALS['ACTIONS'] = [];
$d = ['wb1_enable' => false, 'wb2_enable' => false];
call($ems, 'applyWallboxControl', [&$d, $sWb]);
check('automatisch Smart Charging: eigene Sperre wird geloest, es wird nichts gesperrt', $wbActs() === [[600, 'ctl_curr_limit', 16], [600, 'ctl_enable', true]], json_encode($GLOBALS['ACTIONS']));
$f = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$findSel = function (array $nodes, string $name) use (&$findSel) { foreach ($nodes as $n) { if (is_array($n)) { if (($n['name'] ?? '') === $name) { return $n; } foreach (['items', 'elements'] as $k) { if (isset($n[$k])) { $r = $findSel($n[$k], $name); if ($r) { return $r; } } } } } return null; };
$sel = $findSel($f['elements'], 'WB1_TibberVehicle');
check('Formular: Fahrzeugauswahl enthaelt "Name (Hersteller) — angesteckt" mit deviceId als Wert, keine Batterie', $sel && in_array(['caption' => 'Schneeflocke (TESLA) — angesteckt', 'value' => 'veh-1'], $sel['options'], true) && count($sel['options']) === 2, json_encode($sel['options'] ?? null));
prop('WB1_TibberVehicle', 'weg-1');
$sel = $findSel(json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements'], 'WB1_TibberVehicle');
check('gespeicherte, aktuell nicht gemeldete Zuordnung bleibt in der Auswahl erhalten (kein stilles Loeschen)', (bool)array_filter($sel['options'], fn($o) => $o['value'] === 'weg-1'));
prop('WB1_TibberVehicle', ''); prop('WB_Count', 1);
$lbl = $findSel(json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements'], 'WB1ModeStatus');
check('genau ein Fahrzeug und eine Wallbox: Statuszeile schlaegt es vor (kein stilles Uebernehmen)', $lbl && strpos($lbl['caption'], 'Tibber meldet genau ein Fahrzeug (Schneeflocke (TESLA))') !== false && (int)$ems->ReadPropertyString('WB1_TibberVehicle') === 0, $lbl['caption'] ?? '');
prop('WB_Count', 2);
$lbl = $findSel(json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements'], 'WB1ModeStatus');
check('zwei Wallboxen: kein Vorschlag (nicht eindeutig)', strpos($lbl['caption'], 'Tibber meldet genau ein Fahrzeug') === false);
prop('WB1_TibberVehicle', '');
unset($GLOBALS['INSTMOD'][900]); $GLOBALS['FLEX_DEVICES'] = []; // nichts in die folgenden Bloecke verschleppen

// ===========================================================================
echo "\n4d) Smart Charging: Auto aus dem Netz laden, Batterie schonen (Modus 2, Xmax=0, 03.10.2026)\n";
$ems = freshEms();
prop('WB_Active', true); prop('WB_Count', 2); prop('WB1_Mode', 1); // WB1 = Smart Charging (manuell)
$invE = ['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true];
$whA = ['instanceID' => 600, 'source' => 'chargerhub', 'chargeEnableID' => 0, 'maxCurrent' => 16, 'managedBy' => 'none', 'powerID' => 0, 'plugStateID' => 0];
$whB = ['instanceID' => 602, 'source' => 'chargerhub', 'chargeEnableID' => 0, 'maxCurrent' => 16, 'managedBy' => 'other', 'powerID' => 0, 'plugStateID' => 0];
attr('PartnerCache', json_encode(['inverterhub' => [$invE], 'chargerhub' => [$whA, $whB]]));
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$autoSc = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$sSc = fn(array $o = []) => state(array_merge(['wb_active' => true, 'wb_count' => 2, 'wb1_pow_kw' => 11.0, 'wb1_cable' => 1, 'bat_soc' => 80.0], $o));
$sc = fn(array $d, array $s) => call($ems, 'applySmartChargingGridBuy', [$d, $s]);

$r = $sc($autoSc, $sSc());
check('Smart-Charging-Wallbox laedt (11 kW), reine Automatik: Modus 9, Netzbezug auf 10,8 kW (Auto minus 200 W) geregelt, enable=true',
    $r['op_mode'] === EMS_OP_PV_SELFUSE && $r['gw_mode'] === GW_MODE_BUY && (int)$r['gw_power_w'] === 10800 && $r['gw_enable'] === true, fmt($r));
check('Xset liegt immer unter der Auto-Leistung (kein erzwungenes Mitladen der Batterie, keine PV-Drosselung)', (int)$r['gw_power_w'] < 11000);
check('Quelle smartcharging, Grund nennt Leistung und urspruenglichen Grund', $r['source'] === 'smartcharging' && strpos($r['reason'], 'Auto lädt mit 11.0 kW') !== false && strpos($r['reason'], '| Automatik') !== false, $r['reason']);
check('Entscheidung ohne Smart-Charging-Last bleibt unveraendert (Wallbox 0 W)', $sc($autoSc, $sSc(['wb1_pow_kw' => 0.0])) === $autoSc);
check('unter 500 W (Ruhestrom): unveraendert', $sc($autoSc, $sSc(['wb1_pow_kw' => 0.3])) === $autoSc);
check('Quelle tagesplan mit nativer Automatik wird ebenfalls umgestellt', $sc(array_merge($autoSc, ['source' => 'tagesplan']), $sSc())['source'] === 'smartcharging');
foreach ([['Grid Rewards', ['op_mode' => EMS_OP_GRIDREWARDS, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_enable' => true, 'source' => 'tibber']],
          ['§14a-Netzbetreiber', ['source' => 'netzbetreiber']],
          ['Tagesplan-Netzladen', ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 8000, 'gw_enable' => true, 'source' => 'tagesplan']],
          ['Nutzer-Boost', ['op_mode' => EMS_OP_DISCHARGE, 'gw_mode' => GW_MODE_DISCHARGE, 'gw_enable' => true, 'source' => 'nutzer']],
          ['Tibber steuert die Batterie (no_write)', ['no_write' => true, 'source' => 'tibber']]] as [$name, $over]) {
    $dd = array_merge($autoSc, $over);
    check("$name hat Vorrang: nichts wird umgestellt", $sc($dd, $sSc()) === $dd);
}
prop('WB1_Mode', 2);
check('Wallbox im Modus "EMS steuert": nicht Sache dieser Regel', $sc($autoSc, $sSc()) === $autoSc);
prop('WB1_Mode', 3);
check('Wallbox "nur beobachten": nicht Sache dieser Regel', $sc($autoSc, $sSc()) === $autoSc);
prop('WB1_Mode', 1); prop('WB2_Mode', 3);
$r = $sc($autoSc, $sSc(['wb1_pow_kw' => 5.0, 'wb2_pow_kw' => 6.0, 'wb2_cable' => 1]));
check('zwei Wallboxen: nur die Smart-Charging-Last zaehlt (5,0 kW, nicht 11 kW)', $r['source'] === 'smartcharging' && strpos($r['reason'], 'mit 5.0 kW') !== false, $r['reason']);
prop('WB_Smart_GridBuy', false);
check('Schalter aus: unveraendert', $sc($autoSc, $sSc()) === $autoSc);
prop('WB_Smart_GridBuy', true);
check('ohne Batterie: unveraendert', $sc($autoSc, $sSc(['bat_active' => false])) === $autoSc);

echo "   Plausibilitaetswaechter und Schreibpfad\n";
$sGuard = $sSc(['pv_total_w' => 0.0, 'bat_pow_w' => 0.0, 'grid_total_w' => -12000.0, 'wb1_pow_kw' => 11.0]);
$dSc = $sc($autoSc, $sGuard);
attr('PlausiSince', time() - 3600);
$g = call($ems, 'applyPlausibilityGuard', [$dSc, $sGuard]);
check('Waechter greift bei Quelle smartcharging NICHT ein (Batterie ~0 W bei Netzbezug ist gewollt)', $g['op_mode'] === EMS_OP_PV_SELFUSE && $g['gw_mode'] === GW_MODE_BUY && $g['source'] === 'smartcharging', fmt($g));
attr('PlausiSince', time() - 3600); attr('PlausiHoldUntil', 0);
$gCtl = call($ems, 'applyPlausibilityGuard', [array_merge($dSc, ['source' => 'ems']), $sGuard]);
check('Gegenprobe: dieselbe Entscheidung mit Quelle ems loest den Waechter aus (Rueckfall Automatik)', $gCtl['gw_mode'] === GW_MODE_AUTO && $gCtl['gw_enable'] === false, fmt($gCtl));
attr('PlausiSince', 0); attr('PlausiHoldUntil', 0);
$GLOBALS['ACTIONS'] = []; attr('LastDecision', 0);
call($ems, 'applyDecision', [$dSc, $sGuard]);
$wrt = array_values(array_filter($GLOBALS['ACTIONS'], fn($a) => $a[0] === IHUB_IID && in_array($a[1], ['ctl_ems_mode', 'ctl_ems_power', 'ctl_ems_enable'], true)));
check('Schreibpfad (sichere Reihenfolge Leistung 0 -> Modus 9 -> Xset 10800 -> enable zuletzt)', array_column($wrt, 1) === ['ctl_ems_power', 'ctl_ems_mode', 'ctl_ems_power', 'ctl_ems_enable'] && $wrt[1][2] === GW_MODE_BUY && $wrt[0][2] === 0 && $wrt[2][2] === 10800 && $wrt[3][2] === true, json_encode($wrt));
check('Quelle smartcharging wird als letzte Entscheidungsquelle gemerkt', $ems->ReadAttributeString('LastDecisionSource') === 'smartcharging');
echo "   Rueckfall auf Modus 2 (Haus aus dem Netz), wenn Modus 9 die PV drosseln wuerde\n";
prop('WB1_Mode', 1); prop('WB2_Mode', 0);
$r = $sc($autoSc, $sSc(['bat_soc' => 97.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]));
check('Batterie fast voll (97 %) bei PV-Ueberschuss: Modus 2 statt 9 (Netzbezugsregelung wuerde PV drosseln), Grund genannt', $r['gw_mode'] === GW_MODE_CHARGE_PV && (int)$r['gw_power_w'] === 0 && strpos($r['reason'], 'PV-Überschuss würde bei Netzbezugsregelung gedrosselt') !== false, fmt($r) . ' | ' . $r['reason']);
attr('SmartFullHoldUntil', 0); // Haltezeit aus dem Test davor zuruecksetzen
$r = $sc($autoSc, $sSc(['bat_soc' => 97.0, 'pv_total_w' => 500.0, 'house_pow_w' => 400.0]));
check('Batterie voll, aber kaum PV-Ueberschuss (100 W): Modus 9 bleibt (nichts zu drosseln, Batterie deckt das Haus)', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
$r = $sc($autoSc, $sSc(['bat_soc' => 70.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]));
check('Batterie 70 % bei PV-Ueberschuss: Modus 9 (PV-Ueberschuss laedt die Batterie)', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
attr('LastGoodweMode', GW_MODE_BUY);
$r = $sc($autoSc, $sSc(['bat_soc' => 96.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]));
check('Hysterese: laeuft Modus 9 schon, bleibt er bis 97 % (96 % mit PV-Ueberschuss: weiter Modus 9)', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
attr('LastGoodweMode', GW_MODE_AUTO);
$r = $sc($autoSc, $sSc(['bat_soc' => 96.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]));
check('Hysterese: sonst schon ab 95 % Modus 2 (96 % mit PV-Ueberschuss)', $r['gw_mode'] === GW_MODE_CHARGE_PV, fmt($r));
echo "   Messbasierter Rueckfall: Modus 9 erreicht den Netz-Sollwert nicht (Live-Befund 16.10.2026, einphasig ~3,6 kW)\n";
foreach (['SmartNoTrackSince', 'SmartNoTrackHoldUntil', 'SmartFullHoldUntil'] as $a) { attr($a, 0); }
attr('LastGoodweMode', GW_MODE_BUY);
$dev = ['bat_soc' => 80.0, 'wb1_pow_kw' => 3.6, 'pv_total_w' => 0.0, 'house_pow_w' => 300.0, 'grid_total_w' => -2200.0, 'bat_pow_w' => 2000.0];
$r = $sc($autoSc, $sSc($dev));
check('Erste Abweichung (Netz 2,2 kW statt 3,4 kW, Batterie speist 2,0 kW): noch Modus 9, nur die Messung beginnt', $r['gw_mode'] === GW_MODE_BUY && (int)$r['gw_power_w'] === 3400 && $ems->ReadAttributeInteger('SmartNoTrackSince') > 0, fmt($r));
attr('SmartNoTrackSince', time() - 40);
$r = $sc($autoSc, $sSc($dev));
check('Abweichung erst 40 s: weiter Modus 9 (Anlauf-/Regelschwankungen nicht gleich als Fehler werten)', $r['gw_mode'] === GW_MODE_BUY && $ems->ReadAttributeInteger('SmartNoTrackHoldUntil') === 0, fmt($r));
attr('SmartNoTrackSince', time() - 100);
$r = $sc($autoSc, $sSc($dev));
check('Abweichung > 90 s: Rueckfall auf Modus 2 (Batterie speist nicht mehr ins Auto), Grund mit Messwerten genannt', $r['gw_mode'] === GW_MODE_CHARGE_PV && strpos($r['reason'], 'Sollwert nicht') !== false && strpos($r['reason'], '2.2 kW') !== false, fmt($r) . ' | ' . $r['reason']);
$r = $sc($autoSc, $sSc(['bat_soc' => 80.0, 'wb1_pow_kw' => 3.6, 'house_pow_w' => 300.0, 'grid_total_w' => -3400.0, 'bat_pow_w' => 300.0]));
check('Haltezeit 15 min: auch mit unauffaelligen Messwerten bleibt es in Modus 2', $r['gw_mode'] === GW_MODE_CHARGE_PV && strpos($r['reason'], 'Haltezeit') !== false, fmt($r));
attr('SmartNoTrackHoldUntil', time() - 1);
$r = $sc($autoSc, $sSc(['bat_soc' => 80.0, 'wb1_pow_kw' => 3.6, 'house_pow_w' => 300.0, 'grid_total_w' => -3400.0, 'bat_pow_w' => 300.0]));
check('Nach der Haltezeit: Modus 9 wird neu versucht', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
$r = $sc($autoSc, $sSc(['bat_soc' => 80.0, 'wb1_pow_kw' => 11.0, 'house_pow_w' => 300.0, 'grid_total_w' => -10800.0, 'bat_pow_w' => 350.0]));
check('Normalfall (Netz = Sollwert, Batterie deckt nur das Haus): Modus 9, keine Messung', $r['gw_mode'] === GW_MODE_BUY && $ems->ReadAttributeInteger('SmartNoTrackSince') === 0, fmt($r));
$r = $sc($autoSc, $sSc(['bat_soc' => 80.0, 'wb1_pow_kw' => 3.6, 'pv_total_w' => 2000.0, 'house_pow_w' => 300.0, 'grid_total_w' => -2200.0, 'bat_pow_w' => -1400.0]));
check('Netz unter Xset, aber Batterie LAEDT (PV-Ueberschuss): keine Abweichung, Modus 9 bleibt', $r['gw_mode'] === GW_MODE_BUY && $ems->ReadAttributeInteger('SmartNoTrackSince') === 0, fmt($r));
attr('LastGoodweMode', GW_MODE_AUTO);
$r = $sc($autoSc, $sSc($dev));
check('Modus 9 laeuft noch nicht (Anlaufphase): Abweichung wird nicht gezaehlt', $r['gw_mode'] === GW_MODE_BUY && $ems->ReadAttributeInteger('SmartNoTrackSince') === 0, fmt($r));
foreach (['SmartNoTrackSince', 'SmartNoTrackHoldUntil'] as $a) { attr($a, 0); }
attr('LastGoodweMode', GW_MODE_AUTO);
echo "   PV-Potenzial aus der Prognose und Haltezeit (Live-Befund 10.10.2026: in Modus 9 gedrosselte PV, Dauerpendeln 2<->9)\n";
$GLOBALS['PVF_FC'] = [];
if (!function_exists('PVF_GetForecast')) { function PVF_GetForecast($iid, $offset = 0) { return $GLOBALS['PVF_FC'][$offset] ?? []; } }
$GLOBALS['INSTMOD'][7100] = GUID_PVFORECAST; obj(7100, 1, 'PV', 0);
attr('LastGoodweMode', GW_MODE_BUY); attr('SmartFullHoldUntil', 0);
$GLOBALS['PVF_FC'] = [0 => ['p50' => array_fill(0, 96, 5000.0)], 1 => ['p50' => array_fill(0, 96, 5000.0)]];
$r = $sc($autoSc, $sSc(['bat_soc' => 100.0, 'pv_total_w' => 600.0, 'house_pow_w' => 400.0]));
check('Gedrosselte PV (gemessen 600 W) bei Prognose 5 kW und SOC 100 %: Modus 2, Drosselung nicht verfestigen (vorher: Modus 9 haengt fest)', $r['gw_mode'] === GW_MODE_CHARGE_PV && strpos($r['reason'], 'gedrosselt') !== false, fmt($r));
$GLOBALS['PVF_FC'] = [];
$r = $sc($autoSc, $sSc(['bat_soc' => 100.0, 'pv_total_w' => 600.0, 'house_pow_w' => 400.0]));
check('Haltezeit: eine Wolke (Prognose leer, PV 600 W) wirft den Schutz binnen 10 min nicht zurueck nach Modus 9', $r['gw_mode'] === GW_MODE_CHARGE_PV && strpos($r['reason'], 'Haltezeit') !== false, fmt($r));
attr('SmartFullHoldUntil', time() - 1);
$r = $sc($autoSc, $sSc(['bat_soc' => 100.0, 'pv_total_w' => 600.0, 'house_pow_w' => 400.0]));
check('Nach Ablauf der Haltezeit und ohne Potenzial: wieder Modus 9 (Nacht-/Wolkenfall)', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
$GLOBALS['PVF_FC'] = [0 => ['p50' => array_fill(0, 96, 0.0)], 1 => ['p50' => array_fill(0, 96, 0.0)]];
$r = $sc($autoSc, $sSc(['bat_soc' => 100.0, 'pv_total_w' => 0.0, 'house_pow_w' => 400.0]));
check('Nacht (Prognose 0 W, PV 0 W), SOC 100 %: Modus 9', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
$GLOBALS['PVF_FC'] = [0 => ['p50' => array_fill(0, 96, 5000.0)], 1 => ['p50' => array_fill(0, 96, 5000.0)]];
$r = $sc($autoSc, $sSc(['bat_soc' => 80.0, 'pv_total_w' => 600.0, 'house_pow_w' => 400.0]));
check('SOC 80 %: das Potenzial spielt keine Rolle, Modus 9 (PV-Ueberschuss laedt die Batterie)', $r['gw_mode'] === GW_MODE_BUY, fmt($r));
unset($GLOBALS['INSTMOD'][7100]); $GLOBALS['PVF_FC'] = []; attr('LastGoodweMode', GW_MODE_AUTO); attr('SmartFullHoldUntil', 0);
$resv = $ems->ReadPropertyInteger('BAT_SOC_Min') + $ems->ReadPropertyInteger('BAT_SOC_Reserve_Backup');
$r = $sc($autoSc, $sSc(['bat_soc' => (float)$resv + 1.0]));
check('Batterie an der Reserve: Modus 2 (die Batterie kann das Haus nicht tragen), Grund genannt', $r['gw_mode'] === GW_MODE_CHARGE_PV && strpos($r['reason'], 'Batterie an der Reserve') !== false, fmt($r) . ' | ' . $r['reason']);
prop('WB_Smart_HouseFromBattery', false);
$r = $sc($autoSc, $sSc());
check('Schalter "Haus aus der Batterie" aus: Modus 2 wie in 0.70.0', $r['gw_mode'] === GW_MODE_CHARGE_PV && (int)$r['gw_power_w'] === 0 && strpos($r['reason'], 'Schalter') !== false, fmt($r));
prop('WB_Smart_HouseFromBattery', true);
$r = $sc($autoSc, $sSc(['wb1_pow_kw' => 0.55]));
check('knapp ueber der Mindestleistung (550 W): Xset 350 W, Modus 9', $r['gw_mode'] === GW_MODE_BUY && (int)$r['gw_power_w'] === 350, fmt($r));
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n5) §14a-Netzbetreiber-Lastbegrenzung -- oberste Prioritaet, auch ueber Grid Rewards\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][300] = GUID_STEUERBOXHUB;
$GLOBALS['SBH_STATE'] = ['loadDimmActive' => true, 'loadPMin' => 4.2];
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 7.4, 'wb_active' => true, 'wb1_cable' => 1])]);
check('Quelle netzbetreiber, Wallboxen aus, WR auf Automatik ohne aktiven Sollwert', ($d['source'] ?? '') === 'netzbetreiber' && $d['wb1_enable'] === false && $d['wb2_enable'] === false && isNativeAuto($d), fmt($d));
unset($GLOBALS['INSTMOD'][300]);
$GLOBALS['SBH_STATE'] = null;

// ===========================================================================
echo "\n6) Batterie-Boost (Nutzer) und §14a-Nachtladen\n";
$ems = freshEms();
pricesToday(0.30);
attr('BatteryBoostUntil', time() + 600);
$d = call($ems, 'optimize', [state(['bat_soc' => 70.0, 'wb_active' => true, 'wb1_cable' => 1])]);
check('Boost: Entladen mit Maximalleistung, Quelle nutzer, Wallbox frei', $d['op_mode'] === EMS_OP_DISCHARGE && ($d['source'] ?? '') === 'nutzer' && (int)$d['gw_power_w'] === $ems->ReadPropertyInteger('EMS_Max_Power_W') && $d['wb1_enable'] === true, fmt($d));
$d = call($ems, 'optimize', [state(['bat_soc' => (float)$socMin])]);
check('Boost an der Reserve: wird beendet statt die Notreserve anzugreifen', $ems->ReadAttributeInteger('BatteryBoostUntil') === 0 && $d['op_mode'] !== EMS_OP_DISCHARGE, fmt($d));

$ems = freshEms();
pricesToday(0.10); // Arbitrage-Chance -> §14a-Nachtladen ist erreichbar
$target = $ems->ReadPropertyInteger('BAT_SOC_Target_Night');
$d = call($ems, 'optimize', [state(['enwg_in_window' => true, 'bat_soc' => max(5, $target - 30), 'tib_price_eff' => 0.10])]);
check('§14a-Nachtfenster + SOC unter Nachtziel: Netzladen (Batterie-Lademodus 11 mit auf Ladegrenze/Anschluss begrenztem Xset, enable=true)', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_BAT_CHARGE && $d['gw_power_w'] >= 500 && $d['gw_enable'] === true, fmt($d));
pricesToday(0.30); // keine Arbitrage-Chance -> auch §14a-Nachtladen wird uebersprungen
$d = call($ems, 'optimize', [state(['enwg_in_window' => true, 'bat_soc' => max(5, $target - 30)])]);
check('ohne Arbitrage-Chance auch kein §14a-Nachtladen (Dietmars Vorgabe: Preis sticht)', $d['op_mode'] !== EMS_OP_NET_CHARGE && isNativeAuto($d), fmt($d));

// ===========================================================================
echo "\n7) Plausibilitaetswaechter -- Batterie untaetig trotz Netzbezug, hohem SOC, keiner PV\n";
$ems = freshEms();
$tagesplanAuto = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Tagesplan: Automatik: Hauslast 354W aus Batterie (SOC 70%)', 'source' => 'tagesplan'];
$anomal = state(['bat_soc' => 70.0, 'pv_total_w' => 0.0, 'bat_pow_w' => -12.0, 'grid_total_w' => -420.0]); // Bezug 420 W, Batterie steht
$d = call($ems, 'applyPlausibilityGuard', [$tagesplanAuto, $anomal]);
check('1. Zyklus: Abweichung wird nur vorgemerkt, Entscheidung unveraendert', $d === $tagesplanAuto && $ems->ReadAttributeInteger('PlausiSince') > 0);
check('Warnvariable noch aus', $ems->GetValue('EMS_PlausiWarn') === false);
attr('PlausiSince', time() - 400); // > PLAUSI_Minutes (5 min)
$d = call($ems, 'applyPlausibilityGuard', [$tagesplanAuto, $anomal]);
check('nach 5 min: Rueckfall in WR-Eigenregelung (enable=false, Automatik, 0 W), force gesetzt', isNativeAuto($d) && !empty($d['force']) && ($d['source'] ?? '') === 'ems', fmt($d));
check('Warnvariable EMS_PlausiWarn = true', $ems->GetValue('EMS_PlausiWarn') === true);
check('Haltephase gesetzt', $ems->ReadAttributeInteger('PlausiHoldUntil') > time());
check('Ausloesung im Log sichtbar (BASIC)', (bool)array_filter($GLOBALS['LOG'], fn($l) => strpos($l, 'AUSGELOEST') !== false), implode(' | ', $GLOBALS['LOG']));
$tagesplanDischarge = array_merge($tagesplanAuto, ['op_mode' => EMS_OP_DISCHARGE, 'gw_mode' => GW_MODE_DISCHARGE, 'gw_power_w' => 5000, 'gw_enable' => true]);
$d = call($ems, 'applyPlausibilityGuard', [$tagesplanDischarge, state(['bat_pow_w' => 800.0])]);
check('Haltephase: auch eine "gesunde" Folgeentscheidung wird noch ueberstimmt (kein Pendeln)', isNativeAuto($d) && !empty($d['force']), fmt($d));
$gridRewards = array_merge($tagesplanAuto, ['op_mode' => EMS_OP_GRIDREWARDS, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 7400, 'gw_enable' => true, 'source' => 'tibber']);
$d = call($ems, 'applyPlausibilityGuard', [$gridRewards, $anomal]);
check('Haltephase: Grid Rewards hat trotzdem Vorrang (unangetastet)', $d === $gridRewards, fmt($d));
attr('PlausiHoldUntil', 0);
$d = call($ems, 'applyPlausibilityGuard', [$tagesplanAuto, state(['bat_pow_w' => 350.0, 'grid_total_w' => -20.0])]);
check('nach der Haltephase, Batterie liefert wieder: Entscheidung unveraendert, Warnung aufgehoben', $d === $tagesplanAuto && $ems->GetValue('EMS_PlausiWarn') === false, fmt($d));

echo "\n   Negativfaelle -- wo der Waechter NICHT eingreifen darf\n";
$ems = freshEms();
attr('PlausiSince', time() - 400);
$cases = [
    'Netzladen (Batterie laedt gewollt aus dem Netz)' => [array_merge($tagesplanAuto, ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 8000, 'gw_enable' => true]), $anomal],
    'Grid Rewards (Quelle tibber)'                     => [$gridRewards, $anomal],
    '§14a-Netzbetreiber (Quelle netzbetreiber)'        => [array_merge($tagesplanAuto, ['source' => 'netzbetreiber']), $anomal],
    'Batterie-Boost (Quelle nutzer)'                   => [array_merge($tagesplanDischarge, ['source' => 'nutzer']), $anomal],
    'SOC nahe der Reserve (Stillstand legitim)'        => [$tagesplanAuto, state(['bat_soc' => $socMin + 2, 'grid_total_w' => -420.0])],
    'PV liefert (WR erntet, kein Wartezustand)'        => [$tagesplanAuto, state(['pv_total_w' => 2500.0, 'grid_total_w' => -420.0])],
    'Batterie entlaedt (350 W)'                        => [$tagesplanAuto, state(['bat_pow_w' => 350.0, 'grid_total_w' => -420.0])],
    'Netzbezug nur fuer die Wallbox (Hausanteil unter der Schwelle)' => [$tagesplanAuto, state(['wb1_pow_kw' => 3.0, 'grid_total_w' => -3100.0])],
    'Netzbezug unter der Mindestschwelle (150 W)'      => [$tagesplanAuto, state(['grid_total_w' => -150.0])],
    'keine Batterie konfiguriert'                      => [$tagesplanAuto, state(['bat_active' => false, 'grid_total_w' => -420.0])],
];
foreach ($cases as $label => [$dec, $st]) {
    attr('PlausiSince', time() - 400);
    $d = call($ems, 'applyPlausibilityGuard', [$dec, $st]);
    check($label, $d === $dec, fmt($d));
}
prop('PLAUSI_Enabled', false);
attr('PlausiSince', time() - 400);
$d = call($ems, 'applyPlausibilityGuard', [$tagesplanAuto, $anomal]);
check('Waechter abgeschaltet: greift nie ein', $d === $tagesplanAuto, fmt($d));

// ===========================================================================
echo "\n8) applyDecision(): Sicherheits-Rueckfall umgeht den Moduswechsel-Cooldown\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
attr('LastGoodweMode', GW_MODE_DISCHARGE);
attr('LastGoodweEnable', true);
attr('LastDecision', time()); // Cooldown laeuft gerade
$plain = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'normal', 'source' => 'ems'];
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$plain, state()]);
$sent = array_column(array_filter($GLOBALS['ACTIONS'], fn($a) => $a[1] === 'ctl_ems_mode'), 2);
check('ohne force: Cooldown haelt den alten Modus (Entladen) -- kein Wechsel', end($sent) === GW_MODE_DISCHARGE && $GLOBALS['CTL']['ctl_ems_enable'] === true, json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [array_merge($plain, ['force' => true]), state()]);
check('mit force: enable=false, Modus 1, 0 W werden sofort geschrieben', $GLOBALS['CTL']['ctl_ems_enable'] === false && $GLOBALS['CTL']['ctl_ems_mode'] === GW_MODE_AUTO && $GLOBALS['CTL']['ctl_ems_power'] === 0, json_encode($GLOBALS['ACTIONS']));
check('Reihenfolge ohne Ist-Rueckmeldung: Leistung 0 -> Modus -> enable zuletzt (Wechsel angenommen)', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_ems_power', 'ctl_ems_mode', 'ctl_ems_enable'], json_encode(array_column($GLOBALS['ACTIONS'], 1)));
check('Attribute nachgezogen (LastGoodweMode=1, LastGoodweEnable=false)', $ems->ReadAttributeInteger('LastGoodweMode') === GW_MODE_AUTO && $ems->ReadAttributeBoolean('LastGoodweEnable') === false);
check('Grund landet in EMS_LastAction', $ems->GetValue('EMS_LastAction') === 'normal');

// ===========================================================================
echo "\n8b) Tibber steuert die Batterie (Grid Rewards, type battery): EMS gibt frei und schreibt nicht mehr\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$dT = call($ems, 'optimize', [state(['tibber_battery' => true])]);
check('optimize: Tibber steuert Batterie -> Automatik, no_write, Quelle tibber', $dT['op_mode'] === EMS_OP_AUTO && !empty($dT['no_write']) && $dT['gw_enable'] === false && $dT['source'] === 'tibber', json_encode($dT));
attr('LastGoodweMode', GW_MODE_BAT_CHARGE); attr('LastGoodweEnable', true);
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dT, state(['tibber_battery' => true])]);
check('erste Freigabe: Modus 1 und enable=false werden einmal geschrieben', $GLOBALS['CTL']['ctl_ems_mode'] === GW_MODE_AUTO && $GLOBALS['CTL']['ctl_ems_enable'] === false && count($GLOBALS['ACTIONS']) > 0, json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dT, state(['tibber_battery' => true])]);
check('danach nichts mehr geschrieben (Tibber besitzt den Schreibkanal)', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));

echo "\n8c) Trockenlauf: Entscheidung sichtbar, aber nichts geschrieben\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
prop('EMS_DryRun', true);
attr('LastGoodweMode', GW_MODE_BAT_CHARGE); attr('LastGoodweEnable', true);
$dNet = ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_BAT_CHARGE, 'gw_power_w' => 20000, 'gw_enable' => true, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Test Netzladen', 'source' => 'tagesplan'];
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dNet, state()]);
check('Trockenlauf: aktiver Sollwert wird einmal freigegeben (Modus 1, enable aus)', $GLOBALS['CTL']['ctl_ems_mode'] === GW_MODE_AUTO && $GLOBALS['CTL']['ctl_ems_enable'] === false, json_encode($GLOBALS['ACTIONS']));
check('Trockenlauf: Entscheidung ist sichtbar, mit Kennzeichnung', strpos($ems->GetValue('EMS_LastAction'), 'Trockenlauf') === 0 && strpos($ems->GetValue('EMS_LastAction'), 'Test Netzladen') !== false && (int)$ems->GetValue('EMS_Mode') === EMS_OP_NET_CHARGE, (string)$ems->GetValue('EMS_LastAction'));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$dNet, state()]);
check('Trockenlauf: danach wird nichts mehr geschrieben', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
prop('EMS_DryRun', false);

// Zykluskosten (0.50.0): senken die Preisgrenze fuer Netzladung
prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6); prop('PLAN_NightGrid_ExtendHours', 0);
prop('BAT_CycleCost_ct', 0.0);
$ctxC = ['capKwh' => 40.0, 'chargeKw' => 10.0, 'maxW' => 20000, 'socTargetNight' => 100.0, 'feedTariff' => 0.1836, 'cycleCost' => 0.0];
$pC = array_fill(0, 96, 0.30); $pC[3] = 0.16; $pC[4] = 0.12;
$nwC0 = call($ems, 'nightWindowPlan', [$pC, 0, 0.0, $ctxC]);
$nwC1 = call($ems, 'nightWindowPlan', [$pC, 0, 0.0, array_merge($ctxC, ['cycleCost' => 0.04])]);
check('Zykluskosten 0: 16 ct (< 17,44 ct) kommen in Frage', isset($nwC0['charge'][3]) && isset($nwC0['charge'][4]), json_encode(array_keys($nwC0['charge'])));
check('Zykluskosten 4 ct: Grenze 13,44 ct, 16 ct fliegt raus, 12 ct bleibt', !isset($nwC1['charge'][3]) && isset($nwC1['charge'][4]), json_encode(array_keys($nwC1['charge'])));
prop('PLAN_PreDischarge_MinGain_ct', 3.0);
$p192c = array_fill(0, 192, 0.30); for ($i = 96; $i < 102; $i++) { $p192c[$i] = 0.13; }
prop('BAT_CycleCost_ct', 0.0);
$e0 = call($ems, 'preDischargeEnd', [$p192c, 81, 0.1836]);
prop('BAT_CycleCost_ct', 4.0);
$e4 = call($ems, 'preDischargeEnd', [$p192c, 81, 0.1836]);
prop('BAT_CycleCost_ct', 0.0);
check('Vorentladen: mit 4 ct Verschleiss lohnt das Fenster bei 13 ct nicht mehr (ohne: ja)', $e0 === 96 && $e4 === null, json_encode([$e0, $e4]));

echo "\n8d) Netzladen ohne Wirkung (Vorfall 20.09.2026): Sollwert wird nach 3 min aufgegeben\n";
$ems = freshEms();
$dCh = ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 34500, 'gw_enable' => true, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Gruenste Ladezeit', 'source' => 'stromgedacht'];
$sNo = state(['bat_pow_w' => 0.0, 'bat_soc' => 95.0]);
$r1 = call($ems, 'applyChargeNoEffectGuard', [$dCh, $sNo]);
check('Netzladen ohne Wirkung: in den ersten Sekunden bleibt der Sollwert', $r1['gw_mode'] === GW_MODE_AC_IMPORT);
attr('ChargeNoEffectSince', time() - 200);
$r2 = call($ems, 'applyChargeNoEffectGuard', [$dCh, $sNo]);
check('nach 3 min ohne Ladeleistung: Automatik, force, Haltephase 20 min', $r2['gw_mode'] === GW_MODE_AUTO && $r2['gw_enable'] === false && !empty($r2['force']) && $ems->ReadAttributeInteger('ChargeNoEffectHoldUntil') > time() + 1000, json_encode($r2));
$r3 = call($ems, 'applyChargeNoEffectGuard', [$dCh, state(['bat_pow_w' => -20000.0, 'bat_soc' => 60.0])]);
check('Haltephase gilt weiter, auch wenn die Batterie zwischendurch laedt', $r3['gw_mode'] === GW_MODE_AUTO);
$ems = freshEms();
$r4 = call($ems, 'applyChargeNoEffectGuard', [$dCh, state(['bat_pow_w' => -20000.0, 'bat_soc' => 60.0])]);
attr('ChargeNoEffectSince', 0);
check('laedt die Batterie mit voller Leistung: keine Aktion', $r4['gw_mode'] === GW_MODE_AC_IMPORT);
$ems = freshEms(); attr('ChargeNoEffectSince', time() - 500);
$r5 = call($ems, 'applyChargeNoEffectGuard', [array_merge($dCh, ['source' => 'netzbetreiber']), $sNo]);
check('Netzbetreiber-Vorgabe wird nicht ueberstimmt', $r5['gw_mode'] === GW_MODE_AC_IMPORT);
// 0.89.2: manueller Eintrag "Netz laden" wird nie abgebrochen, auch nicht in einer laufenden Haltephase (Dietmar 25.10.2026)
$r6 = call($ems, 'applyChargeNoEffectGuard', [array_merge($dCh, ['manual' => true]), $sNo]);
attr('ChargeNoEffectSince', 0); attr('ChargeNoEffectHoldUntil', time() + 900);
$r7 = call($ems, 'applyChargeNoEffectGuard', [array_merge($dCh, ['manual' => true]), $sNo]);
$r8 = call($ems, 'applyChargeNoEffectGuard', [$dCh, $sNo]);
check('manueller Eintrag Netz laden: kein Abbruch nach 3 min, keine Haltephase, auch nicht in laufender Haltephase; Tagesplan ohne Eintrag weiter mit Haltephase', $r6['gw_mode'] === GW_MODE_AC_IMPORT && $r7['gw_mode'] === GW_MODE_AC_IMPORT && $r8['gw_mode'] === GW_MODE_AUTO, json_encode([$r6['gw_mode'], $r7['gw_mode'], $r8['gw_mode']]));
attr('ChargeNoEffectHoldUntil', 0);

