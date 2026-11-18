<?php
// Kapitel des EMS-Pruefstands: Logging, Formular, Zweisprachigkeit.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8d2) Logging nur auf Klick (0.94.0, Symcon-Review 30.10.2026)\n";
$ems = freshEms(); prop('EMS_Risk_Acknowledged', true); $GLOBALS['LOGGED'] = []; $GLOBALS['LOGSET'] = [];
$archMock = 7700; $GLOBALS['INSTMOD'][$archMock] = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
$vArch = vari('Hauslast', EMS_IID, 'EMS_HousePower', 0.0, 2);
$src = file_get_contents(defined('EMS_TEST_MODULE_PATH') ? EMS_TEST_MODULE_PATH : EMS_TOOLS_DIR . '/../EMS/module.php');
check('ApplyChanges schaltet das Logging nicht mehr selbst ein: kein ensureArchiving-Aufruf', strpos($src, 'ensureArchiving') === false);
if (call($ems, 'getArchiveInstanceId') > 0) {
    check('Variable ohne Logging: Panel mit Knopf erscheint', (($ap = call($ems, 'archivingPanel')) !== null && (end($ap['items'])['onClick'] ?? '') === 'echo EMS_EnableArchiving($id);' && $ap['expanded'] === true));
    $tx = call($ems, 'EnableArchiving');
    check('Knopf schaltet ein und meldet Klartext', !empty($GLOBALS['LOGGED'][call($ems, 'findChildVariableIdByIdent', [EMS_IID, 'EMS_HousePower'])]) && strpos($tx, 'eingeschaltet') !== false, $tx);
    check('alles geloggt: Panel verschwindet', call($ems, 'archivingMissing') === [] || call($ems, 'archivingPanel') === null);
    // 0.97.4: Wechselrichter-Messpunkte der Bilanz gehoeren dazu (Befund Paul 08.11.2026)
    $gI = vari('Netz', 0, 'g', 0.0, 2); $pI = vari('PV', 0, 'p', 0.0, 2); $bI = vari('Akku', 0, 'b', 0.0, 2);
    attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'none', 'controllable' => false, 'gridPowerID' => $gI, 'pvPowerID' => $pI, 'batPowerID' => $bI, 'socID' => 0]]]));
    $GLOBALS['LOGGED'][$pI] = true;
    $mi = call($ems, 'archivingMissing');
    check('0.97.4: fehlendes Logging der WR-Messpunkte Netz und Akku wird erkannt, geloggte PV nicht', in_array($gI, $mi, true) && in_array($bI, $mi, true) && !in_array($pI, $mi, true), json_encode($mi));
    $sbp = json_encode(call($ems, 'selfBalancePanel'), JSON_UNESCAPED_UNICODE);
    check('0.97.4: EMS-Bilanz zeigt den Logging-Knopf und nennt die Variablen', strpos($sbp, 'EMS_EnableArchiving') !== false && strpos($sbp, '#' . $gI) !== false);
    call($ems, 'EnableArchiving');
    check('0.97.4: Knopf schaltet auch die WR-Messpunkte ein, danach kein Knopf mehr in der Bilanz', !empty($GLOBALS['LOGGED'][$gI]) && !empty($GLOBALS['LOGGED'][$bI]) && strpos(json_encode(call($ems, 'selfBalancePanel')), 'EMS_EnableArchiving') === false);
    attr('PartnerCache', '');
} else { check('Testvoraussetzung: Archiv im Mock gefunden', false); }
unset($GLOBALS['INSTMOD'][$archMock]);

echo "\n8d3) Formular: Aufbau (SUITE.md Punkt 3a, 0.97.4)\n";
$ems = freshEms(); prop('EMS_Risk_Acknowledged', true);
$caps = function () use ($ems) { return array_map(function ($e) { return ($e['caption'] ?? '') . (isset($e['visible']) && $e['visible'] === false ? '#hidden' : '') . (isset($e['expanded']) && $e['expanded'] ? '#open' : ''); }, json_decode(emsFormDe($ems->GetConfigurationForm()), true)['elements']); };
$c0 = $caps();
$iA = array_search('🪟  Formular: Aufbau', array_map(function ($c) { return preg_replace('/#.*$/', '', $c); }, $c0));
$iAll = null; foreach ($c0 as $i => $c) { if (strpos($c, '▶️ Betrieb') === 0) { $iAll = $i; } }
$iNot = null; foreach ($c0 as $i => $c) { if (strpos($c, '🔌 Manuelle Zuordnung: Wechselrichter & PV') === 0) { $iNot = $i; } }
check('Aufbau-Panel steht hinter allen Fachpanels (0.106.7: zuletzt die manuelle Zuordnung Wechselrichter) und ist zugeklappt', $iA !== false && $iNot !== null && $iA === $iNot + 1 && strpos($c0[$iA], '#open') === false, json_encode($c0, JSON_UNESCAPED_UNICODE));
prop('FormLayout', json_encode([['Key' => 'general', 'Pos' => 5], ['Key' => 'notify', 'Pos' => 1, 'Opened' => true], ['Key' => 'ai', 'Hidden' => true], ['Key' => 'unbekannt', 'Pos' => 1]]));
$c1 = $caps();
$firstFach = null; foreach ($c1 as $c) { foreach (array_map(function ($t) { return emsLocaleDe()[$t] ?? $t; }, (new ReflectionClass('EMS'))->getConstant('FORM_LAYOUT_TITLES')) as $pre) { if (strpos($c, $pre) === 0) { $firstFach = $c; break 2; } } }
check('Position 1 + Aufgeklappt: Benachrichtigungen ganz vorn unter den Fachpanels und offen', $firstFach !== null && strpos($firstFach, '🔔 Benachrichtigungen') === 0 && strpos($firstFach, '#open') !== false, (string)$firstFach);
$ki = array_values(array_filter($c1, function ($c) { return strpos($c, '🤖 KI-Assistent') === 0; }));
check('Ausgeblendet: KI-Panel bleibt im Formular, aber visible=false; Aufbau-Caption zaehlt es', count($ki) === 1 && strpos($ki[0], '#hidden') !== false && count(array_filter($c1, function ($c) { return strpos($c, '(1 ausgeblendet)') !== false; })) === 1, json_encode($ki, JSON_UNESCAPED_UNICODE));
check('Unbekannter Schluessel stoert nicht, feste Panels oben bleiben oben (Doku vor Allgemein)', array_search('📖 Dokumentation & Hilfe', array_map(function ($c) { return preg_replace('/#.*$/', '', $c); }, $c1)) < $iAll);
$GLOBALS['FORMFIELD'] = [];
$tx = $ems->ResetFormLayout();
$rows = json_decode($GLOBALS['FORMFIELD']['FormLayout']['values'] ?? '[]', true);
check('Reset: alle 14 Fachpanels in Standardreihenfolge ohne Haken, Klartext mit Uebernehmen (0.106.7)', count($rows) === 14 && $rows[0]['Key'] === 'general' && $rows[11]['Key'] === 'notify' && $rows[13]['Key'] === 'inverter' && empty($rows[3]['Hidden']) && strpos($tx, 'Übernehmen') !== false, json_encode($rows));
check('Reset: Spalte Bereich (Listenzelle) selbst uebersetzt (0.106.7: neue Bereichsnamen)', ($rows[0]['Panel'] ?? '') === '▶️ Betrieb' && ($rows[10]['Panel'] ?? '') === '🤖 KI-Assistent' && ($rows[7]['Panel'] ?? '') === '⚡ Netz & Gesetz', json_encode($rows, JSON_UNESCAPED_UNICODE));
prop('FormLayout', '[]');



echo "\n8d4) Zweisprachigkeit: Formular englisch und deutsch, Panels ueber name\n";
$memo = new ReflectionProperty('EMS', 'emsTextMemo');
$ems = freshEms(); prop('EMS_Risk_Acknowledged', true); prop('WB_Active', true);
$GLOBALS['EMS_LANG_EN'] = true; $memo->setValue(null, []);
$fEn = json_decode($ems->GetConfigurationForm(), true);
$GLOBALS['EMS_LANG_EN'] = false; $memo->setValue(null, []);
$fDe = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$panelsEn = array_values(array_filter($fEn['elements'], function ($e) { return ($e['type'] ?? '') === 'ExpansionPanel'; }));
$germanEn = array_filter($panelsEn, function ($e) { return preg_match('/[äöüÄÖÜß]|Allgemein|Tagesplan|Neu bis|Benachrichtig/u', (string)($e['caption'] ?? '')); });
check('Englisch: keine Panel-Ueberschrift mehr deutsch', count($germanEn) === 0, json_encode(array_column($germanEn, 'caption'), JSON_UNESCAPED_UNICODE));
$noName = array_filter($panelsEn, function ($e) { return trim((string)($e['name'] ?? '')) === ''; });
check('Jedes Panel der obersten Ebene hat einen festen name', count($noName) === 0, json_encode(array_column($noName, 'caption'), JSON_UNESCAPED_UNICODE));
$capDe = array_column(array_filter($fDe['elements'], function ($e) { return ($e['type'] ?? '') === 'ExpansionPanel'; }), 'caption', 'name');
$want = ['PanelGeneral' => '▶️ Betrieb', 'PanelPlant' => '🏠 Meine Anlage', 'PanelBattery' => '🔋 Batteriespeicher', 'PanelTariff' => '💰 Strompreis', 'PanelDayplan' => '📅 Planung',
    'PanelWallboxes' => '🚗 Wallboxen', 'PanelHeatpump' => '🌡️ Wärmepumpe', 'PanelGridLaw' => '⚡ Netz & Gesetz', 'PanelSafety' => '🛡️ Sicherheit & Überwachung',
    'PanelGrid' => '🔌 Manuelle Zuordnung: Netzmesspunkte', 'PanelInverter' => '🔌 Manuelle Zuordnung: Wechselrichter & PV',
    'PanelNotify' => '🔔 Benachrichtigungen', 'PanelDoc' => '📖 Dokumentation & Hilfe', 'PanelFederation' => '🔗 Verbund-Status', 'PanelWallboxControl' => '🚗 Wallboxen bedienen',
    'PanelAI' => '🤖 KI-Assistent (erklärt den Plan)', 'PanelSelfBalance' => '📊 EMS-Bilanz (gespart oder verloren?)', 'PanelFormLayout' => '🪟  Formular: Aufbau'];
$diff = []; foreach ($want as $n => $c) { if (($capDe[$n] ?? null) !== $c) { $diff[$n] = $capDe[$n] ?? null; } }
check('Deutsch: Panel-Ueberschriften wie im Formular-Umbau 0.106.7 festgelegt', count($diff) === 0, json_encode($diff, JSON_UNESCAPED_UNICODE));
prop('EMS_Risk_Acknowledged', false);
$fLock = json_decode($ems->GetConfigurationForm(), true);
$vis = []; foreach ($fLock['elements'] as $e) { if (($e['type'] ?? '') === 'ExpansionPanel' && ($e['visible'] ?? true) !== false) { $vis[] = $e['name'] ?? '?'; } }
$gen = null; foreach ($fLock['elements'] as $e) { if (($e['name'] ?? '') === 'PanelGeneral') { $gen = $e; } }
check('Risiko-Sperre ueber name: nur Doku/Verbund/Neu/Benachrichtigungen/Allgemein sichtbar, Allgemein nur Warnung, Checkbox und ihre Erklaerung (0.106.6)',
    count(array_diff($vis, ['PanelDoc', 'PanelFederation', 'NewsPanel', 'PanelNotify', 'PanelGeneral'])) === 0 && in_array('PanelGeneral', $vis, true) && $gen !== null && count($gen['items']) === 3
    && ($gen['items'][1]['name'] ?? '') === 'EMS_Risk_Acknowledged' && ($gen['items'][2]['name'] ?? '') === 'EMS_Risk_AcknowledgedHint', json_encode([$vis, array_column($gen['items'] ?? [], 'name')]));
$txt = json_encode($fEn, JSON_UNESCAPED_UNICODE);
check('Pauschal-Hinweise (Netzmesspunkte/WR) weiter ueber name entfernt', strpos($txt, 'Look at the very top in the panel') === false && strpos($txt, 'Only needed if no partner module (InverterHub)') === false);
$memo->setValue(null, []);
prop('EMS_Risk_Acknowledged', false); prop('WB_Active', false);

echo "\n8d4b) 0.106.7: Formular-Umbau nach Arbeitsablauf (nur Anordnung, nichts verloren)\n";
$ems = freshEms(); prop('EMS_Risk_Acknowledged', true); prop('WB_Active', true); prop('WB_Count', 2); prop('HP_Active', true);
$memo->setValue(null, []);
$GLOBALS['EMS_LANG_EN'] = true; $memo->setValue(null, []);
$fEn = json_decode($ems->GetConfigurationForm(), true);
$GLOBALS['EMS_LANG_EN'] = false; $memo->setValue(null, []);
$fDe = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$inputs = ['NumberSpinner', 'ValidationTextBox', 'Select', 'SelectVariable', 'SelectInstance', 'CheckBox', 'PasswordTextBox', 'SelectTime', 'SelectDate', 'List', 'SelectColor', 'SelectScript', 'SelectEvent', 'SelectObject'];
// alle Elemente mit name (rekursiv), je Element der Pfad (Panel) und die Geschwisterliste
$flat = function ($els, $panel = '') use (&$flat) {
    $out = [];
    foreach ($els as $i => $e) {
        if (!is_array($e)) { continue; }
        $p = (($e['type'] ?? '') === 'ExpansionPanel' && $panel === '') ? (string)($e['name'] ?? '') : $panel;
        $out[] = ['el' => $e, 'panel' => $p, 'sib' => $els, 'idx' => $i];
        if (isset($e['items']) && is_array($e['items'])) { $out = array_merge($out, $flat($e['items'], $p)); }
    }
    return $out;
};
$allEn = $flat($fEn['elements']); $allDe = $flat($fDe['elements']);
$byName = []; foreach ($allEn as $x) { if (isset($x['el']['name'])) { $byName[$x['el']['name']] = $x; } }
// 1. Jedes Eingabefeld aus form.json 0.106.6 ist noch da (Bezug: Stand faf0984)
$old = json_decode((string)shell_exec('git -C ' . escapeshellarg(dirname(EMS_TOOLS_DIR)) . ' show faf0984:EMS/form.json 2>/dev/null'), true);
$oldInputs = []; $oldPanel = [];
if (is_array($old)) {
    foreach ($flat($old['elements']) as $x) { if (in_array($x['el']['type'] ?? '', $inputs, true) && isset($x['el']['name'])) { $oldInputs[] = $x['el']['name']; $oldPanel[$x['el']['name']] = $x['panel']; } }
}
$lost = array_values(array_filter($oldInputs, function ($n) use ($byName) { return !isset($byName[$n]); }));
check('Bezug 0.106.6 (faf0984) gelesen: ueber 200 Eingabefelder', count($oldInputs) > 200, (string)count($oldInputs));
check('Kein Eingabefeld aus 0.106.6 verloren (fertiges Formular)', $lost === [], json_encode($lost));
$typeChg = []; foreach ($flat($old['elements'] ?? []) as $x) { $n = $x['el']['name'] ?? null; if ($n !== null && isset($byName[$n]) && ($byName[$n]['el']['type'] ?? '') !== ($x['el']['type'] ?? '')) { $typeChg[] = $n; } }
check('Kein Feld hat seinen Typ gewechselt', $typeChg === [], json_encode($typeChg));
// 2. Jede ...Hint-Erklaerung steht direkt hinter ihrem Feld (oder hinter der RowLayout-Zeile, in der das Feld steht)
$badHint = [];
foreach ($allEn as $x) {
    $n = (string)($x['el']['name'] ?? '');
    if (substr($n, -4) !== 'Hint' || !isset($byName[substr($n, 0, -4)])) { continue; }
    $prev = $x['sib'][$x['idx'] - 1] ?? null;
    $base = substr($n, 0, -4);
    $ok = is_array($prev) && (($prev['name'] ?? '') === $base || (($prev['type'] ?? '') === 'RowLayout' && in_array($base, array_column($prev['items'] ?? [], 'name'), true)));
    // eine im Code eingefuegte Statuszeile darf zwischen Feld und Erklaerung stehen (z. B. PriceLinkStatus, ThresholdStatus_*)
    // (WB{n}Link… sind mehrere Zeilen, schon so seit 0.106.6)
    for ($j = $x['idx'] - 1; !$ok && $j >= 0 && $j >= $x['idx'] - 6; $j--) {
        $pp = $x['sib'][$j] ?? null;
        if (!is_array($pp)) { break; }
        if (($pp['name'] ?? '') === $base) { $ok = true; break; }
        if (($pp['type'] ?? '') !== 'Label' || substr((string)($pp['name'] ?? ''), -4) === 'Hint') { break; }
    }
    if (!$ok) { $badHint[] = $n; }
}
check('Jede Erklaerung (…Hint) steht direkt hinter ihrem Feld', $badHint === [], json_encode($badHint));
// 3. Statuszeilen der Code-Stellen sind weiterhin da
$need = ['PT15MStatusLabel', 'ThresholdStatus_charge', 'ThresholdStatus_discharge', 'ThresholdStatus_wb', 'HpOptionalRunStatus', 'WB1ModeStatus', 'WB2ModeStatus', 'PriceLinkStatus', 'RoundTripStatusLabel', 'CycleCostStatusLabel'];
$miss = array_values(array_filter($need, function ($n) use ($byName) { return !isset($byName[$n]); }));
check('Statuszeilen vorhanden: PT15M, Preisschwellen, WP-Zusatzlauf, Wallbox-Modus, Preisverknuepfung, Wirkungsgrad, Zykluskosten', $miss === [], json_encode($miss));
$wbLink = array_filter(array_keys($byName), function ($n) { return preg_match('/^WB[12]Link/', $n); });
check('Wallbox-Verbindungszeilen (WB{n}Link…) hinter der Instanzauswahl', count($wbLink) >= 2 && isset($byName['WB1_Instance']), json_encode(array_values($wbLink)));
$statusBefore = [];
foreach (['VAR_SM_Total_Power' => 'PanelGrid', 'VAR_BAT1_SOC' => 'PanelBattery', 'VAR_WR_EMS_Mode' => 'PanelInverter', 'VAR_PV_Total_Power' => 'PanelInverter'] as $n => $pn) {
    $x = $byName[$n] ?? null;
    if ($x === null || $x['panel'] !== $pn || (($x['sib'][$x['idx'] - 1]['type'] ?? '') !== 'Label')) { $statusBefore[] = $n; }
}
check('Status-Zeilen der manuellen Zuordnung (pushAutoField) weiter vor den Feldern', $statusBefore === [], json_encode($statusBefore));
check('Preisschwellen-Statuszeilen stehen jetzt unter "Planung" direkt hinter dem Feld',
    ($byName['ThresholdStatus_wb']['panel'] ?? '') === 'PanelDayplan' && ($byName['ThresholdStatus_wb']['sib'][$byName['ThresholdStatus_wb']['idx'] - 1]['name'] ?? '') === 'TIB_Threshold_WB');
check('PT15M-Statuszeile steht direkt vor VAR_TIB_PT15M_Today (Strompreis)', ($byName['PT15MStatusLabel']['panel'] ?? '') === 'PanelTariff'
    && ($byName['PT15MStatusLabel']['sib'][$byName['PT15MStatusLabel']['idx'] + 1]['name'] ?? '') === 'VAR_TIB_PT15M_Today');
// 4. Zielgliederung: Feld -> Panel (Stichproben aus dem Bauplan)
$where = ['EMS_Active' => 'PanelGeneral', 'EMS_Log_Level' => 'PanelGeneral', 'EMS_Max_Power_W' => 'PanelPlant', 'SITE_Max_Grid_Import_W' => 'PanelPlant',
    'BEZ_Tarifart' => 'PanelTariff', 'NETZLADUNG_Referenz' => 'PanelDayplan', 'FORECAST_Active' => 'PanelDayplan', 'OPT_Weight_Selfuse' => 'PanelDayplan',
    'TIB_Threshold_WB' => 'PanelDayplan', 'OPT_Arbitrage_Min_Spread_ct' => 'PanelDayplan', 'WB_Cooldown_Sec' => 'PanelWallboxes', 'NETZ_B1_Margin_W' => 'PanelGridLaw',
    'EXPORT_Min_Dwell_Minutes' => 'PanelGridLaw', 'ENWG14A_Active' => 'PanelGridLaw', 'WATCHDOG_Deadman_Reaction' => 'PanelSafety', 'PLAUSI_Enabled' => 'PanelSafety',
    'EXPOVL_Hold_Min' => 'PanelSafety', 'EMS_Fallback_Mode' => 'PanelSafety', 'VAR_HP_Power' => 'PanelHeatpump', 'VAR_BAT2_SOC' => 'PanelBattery'];
$wrong = []; foreach ($where as $n => $pn) { if (($byName[$n]['panel'] ?? '') !== $pn) { $wrong[$n] = $byName[$n]['panel'] ?? null; } }
check('Felder stehen im Zielbereich laut Bauplan', $wrong === [], json_encode($wrong));
$top = array_column(array_filter($fEn['elements'], function ($e) { return ($e['type'] ?? '') === 'ExpansionPanel'; }), 'name');
check('Entfallene Panels PanelP14a/PanelPvForecast/PanelOptimizer gibt es nicht mehr', array_intersect($top, ['PanelP14a', 'PanelPvForecast', 'PanelOptimizer']) === []);
$ord = array_values(array_intersect($top, ['PanelGeneral', 'PanelPlant', 'PanelBattery', 'PanelTariff', 'PanelDayplan', 'PanelWallboxes', 'PanelHeatpump', 'PanelGridLaw', 'PanelSafety', 'PanelNotify', 'PanelGrid', 'PanelInverter']));
check('Reihenfolge nach Arbeitsablauf, manuelle Zuordnung ganz unten', $ord === ['PanelGeneral', 'PanelPlant', 'PanelBattery', 'PanelTariff', 'PanelDayplan', 'PanelWallboxes', 'PanelHeatpump', 'PanelGridLaw', 'PanelSafety', 'PanelNotify', 'PanelGrid', 'PanelInverter'], json_encode($ord));
// 5. Beschriftungen hoechstens 40 Zeichen im fertigen Formular, beide Sprachen (auch im Code erzeugte Felder)
$longF = function ($all) use ($inputs) { $l = []; foreach ($all as $x) { $e = $x['el']; if (in_array($e['type'] ?? '', $inputs, true) && ($e['type'] ?? '') !== 'List' && isset($e['caption']) && mb_strlen((string)$e['caption']) > 40) { $l[] = ($e['name'] ?? '?') . ':' . mb_strlen((string)$e['caption']); } } return $l; };
check('Keine Eingabefeld-Beschriftung ueber 40 Zeichen im fertigen Formular (englisch)', $longF($allEn) === [], json_encode($longF($allEn)));
check('Keine Eingabefeld-Beschriftung ueber 40 Zeichen im fertigen Formular (deutsch)', $longF($allDe) === [], json_encode($longF($allDe), JSON_UNESCAPED_UNICODE));
check('Gekuerzte Code-Felder behalten ihren langen Text als Erklaerung', isset($byName['NOTIFY_Grace_MinutesHint']) && isset($byName['FormAiQuestionHint']) && isset($byName['AI_ModelHint']));
// 6. Gespeicherter Aufbau mit alten Schluesseln (p14a/pvforecast/optimizer) laedt ohne Fehler, neue Panels auf Standard
prop('FormLayout', json_encode([['Key' => 'p14a', 'Pos' => 1, 'Opened' => true], ['Key' => 'optimizer', 'Hidden' => true], ['Key' => 'pvforecast', 'Closed' => true], ['Key' => 'grid', 'Pos' => 1]]));
$fOld = json_decode($ems->GetConfigurationForm(), true);
$topO = array_column(array_filter($fOld['elements'] ?? [], function ($e) { return ($e['type'] ?? '') === 'ExpansionPanel'; }), null, 'name');
check('Gespeicherter Aufbau mit alten Schluesseln laedt, neue Panels sichtbar, gueltige Schluessel wirken',
    is_array($fOld) && isset($topO['PanelGridLaw'], $topO['PanelSafety'], $topO['PanelFormLayout']) && ($topO['PanelGridLaw']['visible'] ?? true) !== false
    && array_search('PanelGrid', array_keys($topO), true) < array_search('PanelPlant', array_keys($topO), true));
// 0.106.8: alte Standard-Reihenfolge (nach "Auf Standard zuruecksetzen" gespeichert, Pauls Anlage) haelt die neue Gliederung nicht fest
$oldStd = []; foreach (['general', 'plant', 'grid', 'inverter', 'battery', 'wallboxes', 'heatpump', 'tariff', 'dayplan', 'p14a', 'pvforecast', 'optimizer', 'selfbalance', 'ai', 'notify'] as $i => $k) {
    $oldStd[] = ['Key' => $k, 'Panel' => $k, 'Opened' => false, 'Closed' => false, 'Hidden' => false, 'Pos' => $i + 1]; }
prop('FormLayout', json_encode($oldStd));
$ordP = function () use ($ems) { return array_values(array_intersect(array_column(array_filter(json_decode($ems->GetConfigurationForm(), true)['elements'], function ($e) { return ($e['type'] ?? '') === 'ExpansionPanel'; }), 'name'),
    ['PanelGeneral', 'PanelPlant', 'PanelBattery', 'PanelTariff', 'PanelDayplan', 'PanelWallboxes', 'PanelHeatpump', 'PanelGridLaw', 'PanelSafety', 'PanelNotify', 'PanelGrid', 'PanelInverter'])); };
$stdOrd = ['PanelGeneral', 'PanelPlant', 'PanelBattery', 'PanelTariff', 'PanelDayplan', 'PanelWallboxes', 'PanelHeatpump', 'PanelGridLaw', 'PanelSafety', 'PanelNotify', 'PanelGrid', 'PanelInverter'];
check('0.106.8: gespeicherte alte Standard-Reihenfolge gilt als Standard, neue Gliederung erscheint', $ordP() === $stdOrd, json_encode($ordP()));
$oldStd[2]['Pos'] = 1; prop('FormLayout', json_encode($oldStd));
check('0.106.8: bewusst geaenderte Position bleibt wirksam (Netzmesspunkte auf 1: direkt hinter Betrieb, vor Meine Anlage)', array_slice($ordP(), 0, 3) === ['PanelGeneral', 'PanelGrid', 'PanelPlant'], json_encode($ordP()));
$oldStd[2]['Pos'] = 3; $oldStd[3]['Opened'] = true; prop('FormLayout', json_encode($oldStd));
check('0.106.8: Zeile mit Haken behaelt ihre Position (Wechselrichter aufgeklappt, Pos 4)', array_search('PanelInverter', $ordP(), true) === 4 && array_search('PanelInverter', $ordP(), true) < array_search('PanelDayplan', $ordP(), true), json_encode($ordP()));
prop('FormLayout', '[]');
$memo->setValue(null, []);
prop('EMS_Risk_Acknowledged', false); prop('WB_Active', false); prop('WB_Count', 1); prop('HP_Active', false);


echo "\n8d5) Zweisprachigkeit Schritt 2: Wochentage, Laufzeittexte, Merkliste\n";
$memo = new ReflectionProperty('EMS', 'emsTextMemo');
$ems = freshEms(); prop('WB_Active', true); prop('WB_Count', 1);
// gespeicherte Wochen-Frist aus 0.98.0 (deutsche Bezeichnungen) und eine Liste ohne Day-Feld (nur Spalten, Zeilenposition zaehlt)
prop('WB1_WeekTargets', json_encode([['Day' => 1, 'Label' => 'Montag', 'Time' => '07:00', 'Kwh' => 12.0], ['Day' => 2, 'Label' => 'Dienstag', 'Time' => '', 'Kwh' => 0.0], ['Day' => 3, 'Label' => 'Mittwoch', 'Time' => '', 'Kwh' => 0.0], ['Day' => 4, 'Label' => 'Donnerstag', 'Time' => '', 'Kwh' => 0.0], ['Day' => 5, 'Label' => 'Freitag', 'Time' => '06:30', 'Kwh' => 0.0], ['Day' => 6, 'Label' => 'Samstag', 'Time' => '', 'Kwh' => 0.0], ['Day' => 7, 'Label' => 'Sonntag', 'Time' => '', 'Kwh' => 0.0]]));
prop('WB2_WeekTargets', json_encode([['Label' => 'Montag', 'Time' => '', 'Kwh' => 0.0], ['Label' => 'Dienstag', 'Time' => '08:15', 'Kwh' => 5.0]]));
$GLOBALS['EMS_LANG_EN'] = true; $memo->setValue(null, []);
$wEn = call($ems, 'weekTargets', [1]); $w2En = call($ems, 'weekTargets', [2]);
$GLOBALS['EMS_LANG_EN'] = false; $memo->setValue(null, []);
$wDe = call($ems, 'weekTargets', [1]);
check('Wochen-Frist: gespeicherte deutsche Werte gelten weiter (Zeiten/kWh je Tag unveraendert)', $wEn[0]['Time'] === '07:00' && $wEn[0]['Kwh'] === 12.0 && $wEn[4]['Time'] === '06:30' && $w2En[1]['Time'] === '08:15' && $w2En[1]['Kwh'] === 5.0, json_encode([$wEn, $w2En]));
check('Wochen-Frist: Wochentag in der Anzeigesprache (englisch Monday, deutsch Montag wie bisher)', $wEn[0]['Label'] === 'Monday' && $wEn[6]['Label'] === 'Sunday' && $wDe[0]['Label'] === 'Montag' && $wDe[6]['Label'] === 'Sonntag', json_encode([$wEn[0], $wDe[0]]));
$fW = null; foreach (json_decode($ems->GetConfigurationForm(), true)['elements'] as $e) { foreach ($e['items'] ?? [] as $it) { if (($it['name'] ?? '') === 'WB1_WeekTargets') { $fW = $it; } } }
check('Formular: Wochen-Frist mit eingesetzten Werten (Listenzellen selbst uebersetzt, nicht aus der Konfiguration)', $fW !== null && ($fW['loadValuesFromConfiguration'] ?? true) === false && ($fW['values'][0]['Label'] ?? '') === 'Montag' && ($fW['values'][0]['Time'] ?? '') === '07:00', json_encode($fW['values'][0] ?? null));
check('Wochen-Frist: Frist am Montag wirkt weiterhin (Logik nur ueber Tag/Uhrzeit)', call($ems, 'weekTargetsActive', [1]) === true);
// Englisch: Laufzeittexte enthalten kein Deutsch mehr
$GLOBALS['EMS_LANG_EN'] = true; $memo->setValue(null, []);
$en = [call($ems, 'getDiscoverySummaryLine'), call($ems, 'getBoostStatusLine'), call($ems, 'priceThresholdLine', ['charge', array_fill(0, 96, 0.30)]), call($ems, 'cycleCostText'), call($ems, 'chargeCurveStatusLine'), call($ems, 'getSpotStatusLine'), $ems->GetStatus(), call($ems, 'statusText', ['x']), $ems->EnableArchiving(), $ems->RecomputeSelfBalance(), call($ems, 'getPT15MStatusLine'), call($ems, 'getBatterySocStatusLine'), call($ems, 'getGridFieldStatusLine', ['total'])];
foreach (call($ems, 'configCheckLines') as $l) { $en[] = $l['text']; }
foreach (call($ems, 'wallboxLinkLines', [1]) as $l) { $en[] = $l['caption']; }
$ml = call($ems, 'wallboxModeStatusLine', [1]); $en[] = is_array($ml) ? $ml['caption'] : $ml;
$deLeft = array_values(array_filter(array_map(function ($x) { return is_array($x) ? ($x['caption'] ?? '') : (string)$x; }, $en), function ($x) { return preg_match('/[äöüÄÖÜß]|\b(nicht|keine?|wird|Wallbox gefunden|Preis)\b/u', str_replace('Börsenpreis', '', $x)); }));
check('Englisch: Statuszeilen, Rueckmeldungen und Konfigurationspruefung ohne Deutsch', count($deLeft) === 0, json_encode($deLeft, JSON_UNESCAPED_UNICODE));
$GLOBALS['EMS_LANG_EN'] = false; $memo->setValue(null, []);
// Merkliste (Stolperstein 24): zweiter Regelzyklus fragt Symcon nicht mehr
$GLOBALS['TRANSLATE_CALLS'] = 0; $ems->Update(); json_decode($ems->GetConfigurationForm(), true); $ems->Update(); $first = $GLOBALS['TRANSLATE_CALLS']; // zwei Durchlaeufe zum Fuellen (der erste setzt Zustaende, die der zweite anzeigt)
$GLOBALS['TRANSLATE_CALLS'] = 0; $t0 = microtime(true); $ems->Update(); json_decode($ems->GetConfigurationForm(), true); $dt = microtime(true) - $t0; $second = $GLOBALS['TRANSLATE_CALLS'];
$t1 = microtime(true); for ($i = 0; $i < 10000; $i++) { call($ems, 't', ['OK: %s']); } $per = (microtime(true) - $t1) / 10000 * 1e6;
echo sprintf("        (Merkliste: Fuellen %d Translate-Aufrufe, danach %d; t() je Aufruf %.2f us inkl. Reflection; Zyklus+Formular %.1f ms)\n", $first, $second, $per, $dt * 1000);
check('Merkliste: weiterer Durchlauf (Regelzyklus + Formular) ohne Translate()-Aufruf', $first > 0 && $second === 0, "$first / $second " . ($GLOBALS["TRANSLATE_LAST"] ?? ""));
prop('WB_Active', false); prop('WB_Count', 1); prop('WB1_WeekTargets', json_encode(call($ems, 'defaultWeekTargets'))); prop('WB2_WeekTargets', json_encode(call($ems, 'defaultWeekTargets')));

echo "\n8d6) Zweisprachigkeit Schritt 3: Vertragsdaten, Codefelder sprachneutral\n";
$ems = freshEms();
$memo3 = new ReflectionProperty('EMS', 'emsTextMemo');
$deWort3 = '/[äöüÄÖÜß]|\b(und|oder|nicht|nur|wird|ist|der|die|das|Akku|Batterie|Netz|Einspeis\w*|Vergütung|Automatik|Bereich|Schalter|keine?)\b/u';
prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.18);
$GLOBALS['EMS_LANG_EN'] = true; $memo3->setValue(null, []);
$piEn = $ems->GetPlantInfo(); $cdEn = $ems->GetCurrentDecision(); $unEn = call($ems, 'dayPlanUnavailableReason');
$lbEn = array_map(fn($op) => call($ems, 'addDayPlanSwitching', [['op' => $op, 'gw' => 1, 'power' => 0]]), [EMS_OP_AUTO, EMS_OP_NET_CHARGE, EMS_OP_HOLD, EMS_OP_EXPORT]);
$ioEn = $ems->GetGridImportOutlook(time() + 3600, time(), 0.0, false);
$GLOBALS['EMS_LANG_EN'] = false; $memo3->setValue(null, []);
$piDe = $ems->GetPlantInfo(); $cdDe = $ems->GetCurrentDecision();
$lbDe = array_map(fn($op) => call($ems, 'addDayPlanSwitching', [['op' => $op, 'gw' => 1, 'power' => 0]]), [EMS_OP_AUTO, EMS_OP_NET_CHARGE, EMS_OP_HOLD, EMS_OP_EXPORT]);
$en3 = array_merge(array_column($piEn['pflichten'], 'text'), [$piEn['hinweis'], $piEn['eegFassung'], $unEn, (string)($ioEn['reason'] ?? '')], array_column($lbEn, 'gwModeLabel'));
check('Englisch: Pflichten, Hinweis, EEG-Fassung, Tagesplan-Grund, Schaltbeschriftung, Outlook-Grund ohne Deutsch', count(preg_grep($deWort3, $en3)) === 0, json_encode(array_values(preg_grep($deWort3, $en3)), JSON_UNESCAPED_UNICODE));
check('Codefelder sprachneutral: pflichten.code, Quellen, mode/modeCode/source, gwMode gleich in beiden Sprachen',
    array_column($piEn['pflichten'], 'code') === array_column($piDe['pflichten'], 'code') && $piEn['verguetungQuelle'] === $piDe['verguetungQuelle'] && $piEn['einspeisemanagement'] === $piDe['einspeisemanagement']
    && $cdEn['mode'] === $cdDe['mode'] && $cdEn['modeCode'] === $cdDe['modeCode'] && $cdEn['source'] === $cdDe['source'] && array_column($lbEn, 'gwMode') === array_column($lbDe, 'gwMode'),
    json_encode([$cdEn['mode'], array_column($piEn['pflichten'], 'code')], JSON_UNESCAPED_UNICODE));
check('Deutsch: Schaltbeschriftung und Pflichten wie bisher', array_column($lbDe, 'gwModeLabel') === ['Automatik', 'Netz laden', 'Akku halten', 'Einspeisen'] && in_array('Registrierung im Marktstammdatenregister ist Pflicht.', array_column($piDe['pflichten'], 'text'), true), json_encode(array_column($lbDe, 'gwModeLabel'), JSON_UNESCAPED_UNICODE));
check('Einheit der Preisvertraege bleibt "ct/kWh brutto" (Szenariorechner vergleicht sie)', $ems->GetPurchasePriceHistory(time() - 3600, time())['einheit'] === 'ct/kWh brutto');

