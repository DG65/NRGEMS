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
