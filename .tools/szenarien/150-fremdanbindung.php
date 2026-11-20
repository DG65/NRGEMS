<?php
// Kapitel des EMS-Pruefstands: Fremdanbindung (0.110.0) -- Wallboxen fremder Module ueber <PRAEFIX>_GetFunctions.
// Wird von .tools/test-scenarios.php im selben Gueltigkeitsbereich geladen; einzeln nicht lauffaehig.

echo "\n15) Fremdanbindung: Wallbox eines fremden Moduls (Praefix EASEE, charger-Vertrag 1.x)\n";
const EXT_GUID = '{AAAA0000-1111-2222-3333-444455556666}';
const EXT_IID = 4811;
$GLOBALS['MODULES'][EXT_GUID] = ['ModuleType' => 3, 'Prefix' => 'EASEE', 'ModuleName' => 'Easee Wallbox'];
$GLOBALS['INSTMOD'][EXT_IID] = EXT_GUID;
obj(EXT_IID, 1, 'Easee Carport', 0);
$GLOBALS['OBJ'][EXT_IID]['HasChildren'] = true;
$extPow = vari('Ladeleistung', EXT_IID, 'power', 7.2, 2);
$GLOBALS['EASEE'] = ['contractType' => 'charger', 'contractVersion' => '1.13', 'function' => 'charger', 'label' => 'Easee Carport',
    'powerID' => $extPow, 'deviceSerial' => 'EH123456', 'managedBy' => 'none', 'minCurrent' => 6, 'maxCurrent' => 16,
    'failsafeSec' => 120, 'lastSeenAt' => time()];
if (!function_exists('EASEE_GetFunctions')) {
    function EASEE_GetFunctions($id) { return $GLOBALS['EASEE_THROW'] ?? false ? throw new Exception('kaputt') : json_encode([$GLOBALS['EASEE']]); }
}
$ems = freshEms();
$found = call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB', 'OHUB', 'CHEEB', 'EMS', 'MHUB', 'IHUB']]);
check('fremdes Modul mit EASEE_GetFunctions wird gefunden, source ext:EASEE', count($found) === 1 && $found[0]['source'] === 'ext:EASEE' && $found[0]['instanceID'] === EXT_IID, json_encode($found));
$GLOBALS['EASEE']['contractVersion'] = '2.0';
$found = call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB']]);
check('fremde Major-Version 2: nicht angenommen, Grund vermerkt', count($found) === 0 && strpos($ems->ReadAttributeString('ExtDiscoveryErrors'), 'Vertragsversion 2.0') !== false, $ems->ReadAttributeString('ExtDiscoveryErrors'));
$GLOBALS['EASEE']['contractVersion'] = '1.13';
$found = call($ems, 'discoverForeign', ['GetFunctions', 'heatpump', ['CHUB']]);
check('anderer Vertragstyp (heatpump gesucht): Wallbox-Eintrag wird nicht genommen', count($found) === 0);
$GLOBALS['EASEE_THROW'] = true;
$found = call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB']]);
check('Modul wirft: uebersprungen, kein Absturz, Fehler vermerkt', count($found) === 0 && strpos($ems->ReadAttributeString('ExtDiscoveryErrors'), 'kaputt') !== false);
unset($GLOBALS['EASEE_THROW']);
$found = call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['EASEE']]);
check('eigenes/bekanntes Praefix ausgenommen', count($found) === 0);

// Liste und Schreibrecht
$entry = call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB']])[0];
attr('PartnerCache', json_encode(['extcharger' => [$entry], 'chargerhub' => [], 'ocpphub' => [], 'chargereebushub' => []]));
$list = call($ems, 'getChargerList');
check('Fremd-Wallbox zaehlt in der Wallbox-Liste (lesen sofort)', count($list) === 1 && $list[0]['source'] === 'ext:EASEE', json_encode(array_column($list, 'source')));
check('ohne Freigabe: nicht beschreibbar', call($ems, 'getWritableChargers') === array());
prop('EXT_Write_Allowed', json_encode([['InstanceID' => EXT_IID]]));
check('mit Freigabe und failsafeSec 120: beschreibbar', count(call($ems, 'getWritableChargers')) === 1);
$noFs = $entry; $noFs['failsafeSec'] = 0;
check('mit Freigabe, aber ohne Rueckfall (failsafeSec 0): nie beschreibbar', call($ems, 'chargerEmsWritable', [$noFs]) === false);
$old = $entry; $old['lastSeenAt'] = time() - 3600;
check('mit Freigabe, aber Daten 1 h alt: nicht beschreibbar', call($ems, 'chargerEmsWritable', [$old]) === false);
$ext = $entry; $ext['managedBy'] = 'other';
check('managedBy anderer Regler: nicht beschreibbar', call($ems, 'chargerEmsWritable', [$ext]) === false);
check('Quellbezeichnung nennt das Praefix', strpos(call($ems, 'chargerSourceLabel', [$entry]), 'EASEE') === 0, call($ems, 'chargerSourceLabel', [$entry]));
// Doppel: gleiche Seriennummer ueber ChargerHub -> Fremdeintrag zaehlt nicht
attr('PartnerCache', json_encode(['extcharger' => [$entry], 'chargerhub' => [['instanceID' => 777, 'deviceSerial' => 'EH123456', 'label' => 'Easee via ChargerHub']], 'ocpphub' => [], 'chargereebushub' => []]));
$list = call($ems, 'getChargerList');
check('gleiche Seriennummer ueber ChargerHub: nur einmal (ChargerHub hat Vorrang)', count($list) === 1 && $list[0]['source'] === 'chargerhub', json_encode(array_column($list, 'source')));
// Nachschreiben
check('Nachschreiben faellig nach failsafeSec/2 (60 s)', call($ems, 'extKeepAliveDue', [$entry, time() - 61]) === true && call($ems, 'extKeepAliveDue', [$entry, time() - 30]) === false);
check('eigenes Modul MIT Totmann (z. B. OCPPHub 0.9.0, failsafeSec 300): ebenfalls nachschreiben', call($ems, 'extKeepAliveDue', [['source' => 'ocpphub', 'failsafeSec' => 300], time() - 151]) === true);
check('eigenes Modul ohne Totmann: nie Nachschreib-Pflicht', call($ems, 'extKeepAliveDue', [['source' => 'chargerhub'], 0]) === false);
$md = $entry; $md['failsafeMode'] = 'maxDuration';
check('failsafeMode maxDuration (Modul beendet selbst): nie nachschreiben', call($ems, 'extKeepAliveDue', [$md, time() - 3600]) === false);
check('failsafeMode maxDuration bleibt mit Freigabe beschreibbar (Rueckfall vorhanden)', (function () use ($ems, $md) { prop('EXT_Write_Allowed', json_encode([['InstanceID' => EXT_IID]])); $r = call($ems, 'extWritable', [$md]); prop('EXT_Write_Allowed', '[]'); return $r; })() === true);
$nv = $entry; $nv['failsafeVerified'] = false;
check('failsafeVerified false (Rueckfall nicht nachgewiesen): auch mit Freigabe nicht beschreibbar', (function () use ($ems, $nv) { prop('EXT_Write_Allowed', json_encode([['InstanceID' => EXT_IID]])); $r = call($ems, 'extWritable', [$nv]); prop('EXT_Write_Allowed', '[]'); return $r; })() === false);
$ocpp = ['source' => 'ocpphub', 'instanceID' => 999, 'managedBy' => 'none', 'failsafeSec' => 0, 'failsafeVerified' => false];
check('eigenes Modul (OCPPHub, bekannte GUID) mit failsafeVerified false: bleibt wie bisher beschreibbar (Regel gilt nur fuer Fremdmodule)', call($ems, 'chargerEmsWritable', [$ocpp]) === true);
prop('EXT_Write_Allowed', '[]');
// Nachschreiben im Schreibweg
prop('EXT_Write_Allowed', json_encode([['InstanceID' => EXT_IID]]));
attr('WB1LastSentEnable', true); attr('LastWB1Switch', time() - 5); attr('WB1ExtKaAt', time() - 100); attr('WB1CurrentSentA', 100); $GLOBALS['ACTIONS'] = [];
call($ems, 'controlWallboxViaChargerHub', [1, $entry, true]);
check('Ein/Aus-Weg: Fremd-Wallbox mit Rueckfall bekommt Strom (10 A) und Freigabe vor Ablauf erneut (auch im Cooldown)', in_array(['4811', 'ctl_curr_limit', 10], array_map(fn($a) => [(string)$a[0], $a[1], $a[2]], $GLOBALS['ACTIONS']), true) && in_array(['4811', 'ctl_enable', true], array_map(fn($a) => [(string)$a[0], $a[1], $a[2]], $GLOBALS['ACTIONS']), true), json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'controlWallboxViaChargerHub', [1, $entry, true]);
check('gleich danach (Zeitstempel vom Nachschreiben gemerkt): kein erneutes Nachschreiben', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
attr('WB1CurrentSentA', 100); attr('WB1CurrentSentAt', time() - 100); $GLOBALS['ACTIONS'] = [];
call($ems, 'applyWallboxChargePlan', [1, ['entry' => $entry, 'currentA' => 10.0, 'enable' => true, 'on' => true, 'text' => 'x']]);
check('Ladeart-Weg: gleicher Strom, aber Rueckfallzeit halb um -> Freigabe und Strom erneut', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_enable', 'ctl_curr_limit'], json_encode($GLOBALS['ACTIONS']));
$own = $entry; $own['source'] = 'chargerhub'; unset($own['failsafeSec']); attr('WB1CurrentSentAt', time() - 100); $GLOBALS['ACTIONS'] = [];
call($ems, 'applyWallboxChargePlan', [1, ['entry' => $own, 'currentA' => 10.0, 'enable' => true, 'on' => true, 'text' => 'x']]);
check('eigenes Modul ohne Totmann, gleicher Strom: nichts geschrieben (wie bisher)', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
prop('EXT_Write_Allowed', '[]');

echo "\n15b) Fremdanbindung: Waermepumpen und Zaehler (0.111.0)\n";
const EXT_HP_GUID = '{BBBB0000-1111-2222-3333-444455556666}';
const EXT_HP_IID = 4911;
$GLOBALS['MODULES'][EXT_HP_GUID] = ['ModuleType' => 3, 'Prefix' => 'FREMDWP', 'ModuleName' => 'Fremd-WP'];
$GLOBALS['INSTMOD'][EXT_HP_IID] = EXT_HP_GUID;
obj(EXT_HP_IID, 1, 'Fremd-WP', 0);
$hpRun = vari('Zusatzlauf', EXT_HP_IID, 'run', false, 0);
$GLOBALS['FREMDWP'] = ['Type' => 'heatpump', 'contractVersion' => '1.18', 'Caption' => 'Fremd-WP', 'optionalRunID' => $hpRun, 'controllable' => true, 'managedBy' => 'none', 'failsafeSec' => 900, 'lastSeenAt' => time()];
if (!function_exists('FREMDWP_GetFunctions')) { function FREMDWP_GetFunctions($id) { return [$GLOBALS['FREMDWP']]; } }
$ems = freshEms();
$hpFound = call($ems, 'discoverForeign', ['GetFunctions', 'heatpump', ['HEISHA']]);
check('fremde Waermepumpe (Vertrag 1.18, Feld Type) wird gefunden', count($hpFound) === 1 && $hpFound[0]['source'] === 'ext:FREMDWP', json_encode($hpFound));
check('fremde WP ohne Freigabe: Zusatzlauf nicht schaltbar', call($ems, 'hpOptionalRunEligible', [$hpFound[0]]) === false);
prop('EXT_Write_Allowed', json_encode([['InstanceID' => EXT_HP_IID]]));
check('fremde WP mit Freigabe und failsafeSec: schaltbar', call($ems, 'hpOptionalRunEligible', [$hpFound[0]]) === true);
$hpNoFs = $hpFound[0]; $hpNoFs['failsafeSec'] = 0;
check('fremde WP mit Freigabe, aber ohne Rueckfall: nicht schaltbar', call($ems, 'hpOptionalRunEligible', [$hpNoFs]) === false);
prop('EXT_Write_Allowed', '[]');
// Nachschreiben des Zusatzlauf-Wunschs: eigenes Modul mit Totmann (WPEEBUSHub 0.6.0) ja, maxDuration (HeishaMon) nein
$own = ['instanceID' => 4912, 'optionalRunID' => $hpRun, 'controllable' => true, 'managedBy' => 'none', 'failsafeSec' => 900, 'Caption' => 'WPEEB'];
foreach ([['deadman', true], ['maxDuration', false]] as [$mode, $expect]) {
    $own['failsafeMode'] = $mode;
    attr('PartnerCache', json_encode(['wpeebushub' => [$own]]));
    prop('WP_OptionalRun_Active', true);
    attr('HpOptionalRun', json_encode([$hpRun => ['want' => true, 'onAt' => time() - 1000, 'kaAt' => time() - 500, 'cond' => 0]]));
    $GLOBALS['VARACTIONS'] = [];
    call($ems, 'applyHeatpumpOptionalRun', [['grid_total_w' => 0.0, 'bat_pow_w' => 0.0, 'bat_active' => false]]);
    $wrote = in_array([$hpRun, true], $GLOBALS['VARACTIONS'], true);
    check('WP-Zusatzlauf laeuft, failsafeMode ' . $mode . ': ' . ($expect ? 'Wunsch nach failsafeSec/2 erneuert' : 'nicht nachgeschrieben (Modul beendet selbst)'), $wrote === $expect, json_encode($GLOBALS['VARACTIONS']));
    if ($mode === 'deadman') {
        $GLOBALS['VARACTIONS'] = [];
        call($ems, 'applyHeatpumpOptionalRun', [['grid_total_w' => 0.0, 'bat_pow_w' => 0.0, 'bat_active' => false]]);
        check('gleich danach (Zeitstempel gemerkt): kein erneutes Nachschreiben', !in_array([$hpRun, true], $GLOBALS['VARACTIONS'], true), json_encode($GLOBALS['VARACTIONS']));
    }
}
prop('WP_OptionalRun_Active', false);
// Zaehler: geschaetzte Werte nie fuer Regelung (Phasenstroeme)
$pc = [vari('L1', 0, 'l1', 60.0, 2), vari('L2', 0, 'l2', 1.0, 2), vari('L3', 0, 'l3', 1.0, 2)];
attr('PartnerCache', json_encode(['extmeter' => [['source' => 'ext:FREMDZ', 'measured' => false, 'phaseCurrentIDs' => $pc]], 'meterhub' => [], 'inverterhub' => []]));
check('fremder Zaehler mit measured=false: Phasenstroeme nicht verwendet', call($ems, 'readPhaseCurrents') === null);
attr('PartnerCache', json_encode(['extmeter' => [['source' => 'ext:FREMDZ', 'phaseCurrentIDs' => $pc]], 'meterhub' => [], 'inverterhub' => []]));
check('fremder gemessener Zaehler: Phasenstroeme werden verwendet', (call($ems, 'readPhaseCurrents')[0] ?? null) == 60.0, json_encode(call($ems, 'readPhaseCurrents')));

echo "\n15c) Fremdanbindung nur lesend: Prognose, Preis, Netzsignal (0.112.0)\n";
const EXT_FC_GUID = '{AAAA0000-1111-2222-3333-4444555566AA}';
const EXT_FC_IID = 4931; const EXT_PR_IID = 4932; const EXT_SG_IID = 4933;
$GLOBALS['MODULES'][EXT_FC_GUID] = ['ModuleType' => 3, 'Prefix' => 'MYFC', 'ModuleName' => 'Meine Prognose'];
foreach ([EXT_FC_IID, EXT_PR_IID, EXT_SG_IID] as $iid) { $GLOBALS['INSTMOD'][$iid] = EXT_FC_GUID; obj($iid, 1, 'Fremd ' . $iid, 0); }
$GLOBALS['MYFC_PV'] = function (int $off): array {
    $m = array_fill(0, 24, 0.0); $m[12] = 2000.0;
    return ['contractType' => 'forecast', 'contractVersion' => '1.5', 'kind' => 'pv', 'date' => date('Y-m-d', strtotime("today +$off days")),
        'resolution' => '60min', 'slots' => 24, 'unit' => 'W', 'mean' => $m, 'p50' => $m, 'kwh' => 2.0, 'generated' => time() - 600];
};
if (!function_exists('MYFC_GetForecast')) {
    function MYFC_GetForecast($id, $off = 0) {
        if ($id === EXT_FC_IID) { return ($GLOBALS['MYFC_PV'])((int)$off); }
        return array(); // andere Instanzen: kein Prognosemodul
    }
    function MYFC_GetPriceCurve($id) {
        if ($id !== EXT_PR_IID) { return array(); }
        $d = strtotime('today'); $out = [];
        for ($h = 0; $h < 24; $h++) { $out[] = ['start' => $d + $h * 3600, 'end' => $d + ($h + 1) * 3600, 'price' => 30.0 + $h, 'basis' => $GLOBALS['MYFC_BASIS'] ?? 'endkunde',
            'netzentgelt' => $GLOBALS['MYFC_NE'] ?? 'enthalten', 'level' => null, 'contractVersion' => '1.2', 'contractType' => 'price']; }
        return $out;
    }
    function MYFC_GetState($id) {
        return $id === EXT_SG_IID ? ['contractType' => 'gridsignal', 'contractVersion' => '1.2', 'region' => '12345', 'state' => 1, 'updated' => time()] : array();
    }
}
$ems = freshEms();
$fc = call($ems, 'discoverForeignReadSources', ['GetForecast', 'forecast', ['PVF', 'LFC'], [0], ['kind']]);
check('Fremd-Prognose gefunden: eine Instanz, kind pv', count($fc) === 1 && $fc[0]['instanceID'] === EXT_FC_IID && $fc[0]['kind'] === 'pv', json_encode($fc));
$pr = call($ems, 'discoverForeignReadSources', ['GetPriceCurve', 'price', ['TIBBERGR', 'SPOT'], [], ['basis', 'netzentgelt']]);
check('Fremd-Preis: 24 Eintraege ergeben EINEN Datensatz je Instanz', count($pr) === 1 && $pr[0]['instanceID'] === EXT_PR_IID && $pr[0]['basis'] === 'endkunde', json_encode($pr));
$sg = call($ems, 'discoverForeignReadSources', ['GetState', 'gridsignal', ['SGW', 'SBH', 'SBHE'], [], ['region']]);
check('Fremd-Netzsignal gefunden, region uebernommen', count($sg) === 1 && $sg[0]['region'] === '12345', json_encode($sg));
attr('PartnerCache', json_encode(['extforecast' => $fc, 'extprice' => $pr, 'extgridsignal' => $sg]));
$slots = call($ems, 'getPvfSlotsWatt');
check('ohne eigenes PVF: PV-Prognose aus dem Fremdmodul, 24 Werte auf 96 Viertelstunden umgerechnet', count($slots) === 192 && $slots[50] > 1500 && $slots[10] == 0, sprintf('n=%d s50=%s', count($slots), $slots[50] ?? '-'));
check('Fremd-Prognose kind pv wird nicht als Lastprognose genommen', call($ems, 'getLoadForecastSlots', [0, 0]) === array_fill(0, 96, null));
$pvStart = call($ems, 'getPvStartTomorrowTs');
check('PV-Start morgen aus 24-Stunden-Raster richtig (nicht Stunde 12 als Viertelstunde 12 = 3 Uhr)', $pvStart >= strtotime('tomorrow 10:00') && $pvStart <= strtotime('tomorrow 12:00'), date('Y-m-d H:i', (int)$pvStart));
$GLOBALS['MYFC_PV_SAVE'] = $GLOBALS['MYFC_PV'];
$GLOBALS['MYFC_PV'] = function (int $off): array { $f = ($GLOBALS['MYFC_PV_SAVE'])($off); $f['generated'] = time() - 7 * 3600; return $f; };
check('Fremd-Prognose aelter als 6 h: nicht genutzt', call($ems, 'getPvfSlotsWatt') === array());
$GLOBALS['MYFC_PV'] = function (int $off): array { $f = ($GLOBALS['MYFC_PV_SAVE'])($off); $f['contractVersion'] = '2.0'; return $f; };
check('Fremd-Prognose mit Major 2: nicht genutzt', call($ems, 'getPvfSlotsWatt') === array());
$GLOBALS['MYFC_PV'] = function (int $off): array { $f = ($GLOBALS['MYFC_PV_SAVE'])($off); $f['date'] = '2000-01-01'; return $f; };
check('Fremd-Prognose fuer falschen Kalendertag: nicht genutzt', call($ems, 'getPvfSlotsWatt') === array());
$GLOBALS['MYFC_PV'] = $GLOBALS['MYFC_PV_SAVE'];
$prices = call($ems, 'parsePT15M', [call($ems, 'getPT15MTodayJson')]);
check('ohne Tibber: Endkundenpreis inkl. Netzentgelt aus dem Fremdmodul (ct -> EUR wie Tibber)', abs(($prices[0] ?? 0) - 0.30) < 0.001 && abs(($prices[95] ?? 0) - 0.53) < 0.001, json_encode([$prices[0], $prices[95]]));
$GLOBALS['MYFC_NE'] = 'fehlt';
$pr2 = call($ems, 'discoverForeignReadSources', ['GetPriceCurve', 'price', ['TIBBERGR', 'SPOT'], [], ['basis', 'netzentgelt']]);
attr('PartnerCache', json_encode(['extforecast' => $fc, 'extprice' => $pr2]));
check('Endkundenpreis OHNE Netzentgelt: nicht fuer die Planung genutzt', call($ems, 'getTibberCombinedCurveJson') === '');
unset($GLOBALS['MYFC_NE']); $GLOBALS['MYFC_BASIS'] = 'spot';
$pr3 = call($ems, 'discoverForeignReadSources', ['GetPriceCurve', 'price', ['TIBBERGR', 'SPOT'], [], ['basis', 'netzentgelt']]);
attr('PartnerCache', json_encode(['extprice' => $pr3]));
$spot = call($ems, 'getSpotCurveLive');
check('Fremd-Boersenpreis (basis spot): Kurve fuer die Negativpreis-Pflicht, Quelle ext:MYFC', count($spot) === 24 && $spot[0]['quelle'] === 'ext:MYFC' && $spot[0]['aufloesung'] === 3600, json_encode($spot[0] ?? null));
check('Spot-Preis wird nicht als Endkundenpreis genommen', call($ems, 'getTibberCombinedCurveJson') === '');
check('Statuszeile nennt das Fremdmodul', strpos(call($ems, 'getSpotStatusLine'), 'MYFC') !== false, call($ems, 'getSpotStatusLine'));
unset($GLOBALS['MYFC_BASIS']);
unset($GLOBALS['INSTMOD'][EXT_PR_IID]);
attr('PartnerCache', json_encode(['extprice' => $pr]));
check('Fremd-Instanz geloescht: keine Preisquelle mehr', call($ems, 'getTibberCombinedCurveJson') === '');
$GLOBALS['INSTMOD'][EXT_PR_IID] = EXT_FC_GUID;
attr('PartnerCache', '{}');

echo "\n15d) Fremdsuche robust (0.112.1)\n";
const EXT_SIG_GUID = '{AAAA0000-1111-2222-3333-4444555566BB}';
$GLOBALS['MODULES'][EXT_SIG_GUID] = ['ModuleType' => 3, 'Prefix' => 'DREIARG', 'ModuleName' => 'Andere Signatur'];
$GLOBALS['INSTMOD'][4941] = EXT_SIG_GUID; obj(4941, 1, 'Dreiarg', 0);
if (!function_exists('DREIARG_GetForecast')) { function DREIARG_GetForecast($id, $von, $bis) { return []; } }
$ems = freshEms(); $GLOBALS['LOG'] = [];
$fc = call($ems, 'discoverForeign', ['GetForecast', 'forecast', ['PVF', 'LFC'], [0]]);
check('gleichnamige Funktion mit anderer Signatur (wie SGW_GetForecast): kein Treffer, kein Fehler', strpos($ems->ReadAttributeString('ExtDiscoveryErrors'), 'DREIARG') === false, $ems->ReadAttributeString('ExtDiscoveryErrors'));
$GLOBALS['EASEE']['contractVersion'] = '2.0';
$logCount = function () { return count(array_filter((array)($GLOBALS['LOG'] ?? []), function ($l) { return strpos(json_encode($l), 'Vertragsversion 2.0') !== false; })); };
$before = $logCount();
call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB']]);
call($ems, 'discoverForeign', ['GetFunctions', 'meter', ['CHUB']]);
call($ems, 'discoverForeign', ['GetFunctions', 'charger', ['CHUB']]);
call($ems, 'discoverForeign', ['GetFunctions', 'meter', ['CHUB']]);
check('gleicher Fehler bei abwechselnden Suchen: nur einmal geloggt', $logCount() - $before === 1, 'Logeintraege: ' . ($logCount() - $before));
$GLOBALS['EASEE']['contractVersion'] = '1.13';
