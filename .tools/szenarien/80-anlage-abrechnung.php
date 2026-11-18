<?php
// Kapitel des EMS-Pruefstands: Anlagendaten, Boersenpreis, Einspeisegrenze, Bezugspreis-Historie, Einstandspreis, OCPPHub.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n17) Anlagendaten -- Verguetung, EEG-Fassung, Foerderende, Pflichten (EMS_GetPlantInfo)\n";
$ems = freshEms();
$tab = ['zeitraeume' => [
    ['von' => '2012-10-01', 'bis' => '2012-10-31', 'kategorie' => 'gebaeude', 'klassen' => [
        ['bis_kwp' => 10, 'teil' => 18.36, 'voll' => null], ['bis_kwp' => 40, 'teil' => 17.42, 'voll' => null],
        ['bis_kwp' => 1000, 'teil' => 15.53, 'voll' => null]]],
    ['von' => '2026-08-01', 'bis' => '2027-01-31', 'kategorie' => 'gebaeude', 'klassen' => [
        ['bis_kwp' => 10, 'teil' => 7.70, 'voll' => 12.22], ['bis_kwp' => 40, 'teil' => 6.66, 'voll' => 10.24],
        ['bis_kwp' => 100, 'teil' => 5.44, 'voll' => 10.24]]],
]];
$lk = fn($ibn, $kwp, $voll = false) => call($ems, 'lookupEegTariffCt', [$tab, $ibn, $kwp, $voll]);
check('IBN 24.10.2012, 9,18 kWp: 18,36 ct (Dietmars Anlage)', $lk('2012-10-24', 9.18) === 18.36, var_export($lk('2012-10-24', 9.18), true));
check('IBN 10/2012, 15 kWp: Mischsatz (10x18,36 + 5x17,42)/15 = 18,05 ct', $lk('2012-10-15', 15.0) === 18.05, var_export($lk('2012-10-15', 15.0), true));
check('IBN 09/2026, 8 kWp Volleinspeisung: 12,22 ct', $lk('2026-09-13', 8.0, true) === 12.22);
check('IBN 09/2026, 8 kWp Teileinspeisung: 7,70 ct', $lk('2026-09-13', 8.0) === 7.70);
check('kein passender Zeitraum in der Tabelle: null (nicht raten)', $lk('2011-05-01', 9.18) === null);
check('leere Tabelle: null', call($ems, 'lookupEegTariffCt', [[], '2012-10-24', 9.18, false]) === null);
check('Volleinspeisung ohne Voll-Satz (vor 2022): Teil-Satz gilt', $lk('2012-10-24', 9.18, true) === 18.36);
check('Anlage groesser als die Tabelle abdeckt (2000 kWp): null statt falschem Satz', $lk('2012-10-24', 2000.0) === null);

check('EEG-Fassung 24.10.2012: PV-Novelle 2012', call($ems, 'eegFassung', ['2012-10-24']) === 'EEG 2012 (PV-Novelle)');
check('EEG-Fassung 01.03.2025: Solarspitzengesetz', call($ems, 'eegFassung', ['2025-03-01']) === 'EEG 2023 mit Solarspitzengesetz');
check('EEG-Fassung 2027: Regelung offen (noch kein Gesetz)', strpos(call($ems, 'eegFassung', ['2027-02-01']), 'offen') !== false);
check('Foerderende IBN 24.10.2012: 31.12.2032', call($ems, 'foerderende', ['2012-10-24']) === '2032-12-31');

$codes = fn($ibn, $kwp, $o = []) => array_column(call($ems, 'plantObligations', [$ibn, $kwp, $o]), 'code');
$c = $codes('2012-10-24', 9.18, ['einspeisemanagement' => 2]);
check('Dietmar (2012, 9,18 kWp, Rundsteuerempfaenger): KEIN Solarspitzengesetz, KEINE 70 %', !in_array('negativpreis', $c) && !in_array('einspeisung60', $c) && !in_array('einspeisung70', $c), json_encode($c));
check('Bestand 2012 mit 70-%-Kappung angegeben: Hinweis 70 %', in_array('einspeisung70', $codes('2012-10-24', 9.18, ['einspeisemanagement' => 1])));
$c = $codes('2025-06-01', 9.0);
check('Neuanlage 06/2025, 9 kWp, ohne Smart Meter: Negativpreis-Regel + 60-%-Grenze', in_array('negativpreis', $c) && in_array('einspeisung60', $c), json_encode($c));
check('Neuanlage mit Smart Meter UND Steuerbox: 60-%-Grenze entfaellt', !in_array('einspeisung60', $codes('2025-06-01', 9.0, ['iMSys' => true, 'steuerbox' => true])));
check('Steckersolar (1,6 kWp, 2025): weder Negativpreis-Regel noch 60 %', array_diff($codes('2025-06-01', 1.6), ['marktstammdaten']) === []);
check('Bestand, freiwillig ins neue Modell: Negativpreis-Regel gilt', in_array('negativpreis', $codes('2012-10-24', 9.18, ['neuesModell' => true])));
check('IBN 2003 (Foerderende 2023 vorbei): Ue20-Hinweis', in_array('ue20', $codes('2003-06-01', 5.0)));

// Verguetungs-Reihenfolge: eingetragen > Variable > Tabelle > Platzhalter
prop('ANL_Verguetung_ct', 0.0); prop('VAR_TIB_Feed_Tariff', 0); prop('ANL_IBN_Datum', ''); prop('ANL_kWp_Manuell', 0.0);
check('nichts angegeben: Platzhalter 0,1836, Quelle sichtbar, aber NICHT bekannt', call($ems, 'getFeedTariffEur') === ['eur' => 0.1836, 'quelle' => 'platzhalter', 'bekannt' => false], json_encode(call($ems, 'getFeedTariffEur')));
check('Platzhalter steuert nie: Planwert 0, Referenz = Ersatz-Bezugspreis', call($ems, 'planFeedTariffEur') === 0.0 && call($ems, 'chargeReferenceMode') === 1);
feedTariffVar(0.20);
check('verknuepfte Variable: 0,20 EUR, Quelle variable', call($ems, 'getFeedTariffEur') === ['eur' => 0.20, 'quelle' => 'variable', 'bekannt' => true]);
prop('ANL_Verguetung_ct', 12.5);
check('eingetragener Wert hat Vorrang: 12,5 ct -> 0,125 EUR, Quelle eingetragen', call($ems, 'getFeedTariffEur') === ['eur' => 0.125, 'quelle' => 'eingetragen', 'bekannt' => true]);
prop('ANL_Verguetung_Keine', true);
check('ausdruecklich keine Verguetung: 0 ct, bekannt, Referenz Ersatz-Bezugspreis', call($ems, 'getFeedTariffEur') === ['eur' => 0.0, 'quelle' => 'keine', 'bekannt' => true] && call($ems, 'chargeReferenceMode') === 1);
prop('ANL_Verguetung_Keine', false);
prop('ANL_IBN_Datum', '2012-10-24'); prop('ANL_kWp_Manuell', 9.18); prop('ANL_Einspeisemanagement', 2);
$pi = call($ems, 'GetPlantInfo');
check('GetPlantInfo: Vertrag 1.1, EEG-Fassung, Foerderende, kWp eingetragen, Verguetung 12,5 ct',
    $pi['contractVersion'] === '1.1' && $pi['eegFassung'] === 'EEG 2012 (PV-Novelle)' && $pi['foerderende'] === '2032-12-31'
    && $pi['kwp'] === 9.18 && $pi['kwpQuelle'] === 'eingetragen' && $pi['verguetungCt'] === 12.5 && $pi['verguetungQuelle'] === 'eingetragen'
    && $pi['einspeisemanagement'] === 'rundsteuerempfaenger', json_encode($pi, JSON_UNESCAPED_UNICODE));
echo "\n   Datumsformat deutsch (TT.MM.JJJJ)\n";
check('24.10.2012 -> intern 2012-10-24', call($ems, 'parsePlantDate', ['24.10.2012']) === '2012-10-24');
check('einstellig 1.4.2012 -> 2012-04-01', call($ems, 'parsePlantDate', ['1.4.2012']) === '2012-04-01');
check('ungueltiges Datum 31.02.2012 -> leer (nicht raten)', call($ems, 'parsePlantDate', ['31.02.2012']) === '');
check('altes Format 2012-10-24 wird weiter verstanden', call($ems, 'parsePlantDate', ['2012-10-24']) === '2012-10-24');
check('Unsinn "Oktober 2012" -> leer', call($ems, 'parsePlantDate', ['Oktober 2012']) === '');
check('Anzeige 2032-12-31 -> 31.12.2032', call($ems, 'germanDate', ['2032-12-31']) === '31.12.2032');
$e3 = freshEms(); $GLOBALS['PROP'][EMS_IID]['ANL_IBN_Datum'] = '24.10.2012'; $GLOBALS['PROP'][EMS_IID]['ANL_kWp_Manuell'] = 9.18;
$pi3 = call($e3, 'GetPlantInfo');
check('GetPlantInfo mit deutscher Eingabe: inbetriebnahmeText 24.10.2012, foerderendeText 31.12.2032, Vertrag weiter JJJJ-MM-TT',
    $pi3['inbetriebnahmeText'] === '24.10.2012' && $pi3['foerderendeText'] === '31.12.2032' && $pi3['inbetriebnahme'] === '2012-10-24'
    && $pi3['eegFassung'] === 'EEG 2012 (PV-Novelle)', json_encode($pi3, JSON_UNESCAPED_UNICODE));

echo "\n   mit der echten Verguetungstabelle (EMS/EegTabelle.php)\n";
$echt = call($ems, 'loadEegTable');
check('Tabelle vorhanden und lesbar (> 100 Zeitraeume)', count($echt['zeitraeume'] ?? []) > 100, 'Zeitraeume: ' . count($echt['zeitraeume'] ?? []));
$lr = fn($ibn, $kwp, $voll = false) => call($ems, 'lookupEegTariffCt', [$echt, $ibn, $kwp, $voll]);
check('echt: IBN 24.10.2012, 9,18 kWp -> 18,36 ct (Dietmars Anlage)', $lr('2012-10-24', 9.18) === 18.36, var_export($lr('2012-10-24', 9.18), true));
check('echt: IBN 11/2012, 9 kWp -> 17,90 ct', $lr('2012-11-15', 9.0) === 17.90, var_export($lr('2012-11-15', 9.0), true));
check('echt: IBN 09/2026, 8 kWp -> 7,70 ct Teil / 12,22 ct Voll', $lr('2026-09-13', 8.0) === 7.70 && $lr('2026-09-13', 8.0, true) === 12.22);
check('echt: IBN 09/2026, 15 kWp Mischsatz -> 7,35 ct', $lr('2026-09-13', 15.0) === 7.35, var_export($lr('2026-09-13', 15.0), true));
check('echt: vor EEG 2000 und ab 2027 kein Satz (nicht raten)', $lr('2000-01-01', 5.0) === null && $lr('2027-03-01', 5.0) === null);
check('echt: Zeitraum 10/2012 als geprueft, 2005 als ungeprueft markiert',
    (call($ems, 'findEegPeriod', [$echt, '2012-10-24'])['geprueft'] ?? null) === true && (call($ems, 'findEegPeriod', [$echt, '2005-06-01'])['geprueft'] ?? null) === false);
$e2 = freshEms(); $GLOBALS['PROP'][EMS_IID]['ANL_IBN_Datum'] = '2012-10-24'; $GLOBALS['PROP'][EMS_IID]['ANL_kWp_Manuell'] = 9.18; $GLOBALS['PROP'][EMS_IID]['ANL_Verguetung_ct'] = 0.0; // Wert aus Datum und Groesse berechnen lassen
$pi2 = call($e2, 'GetPlantInfo');
check('GetPlantInfo ohne Eintrag/Variable: 18,36 ct berechnet und geprueft', $pi2['verguetungCt'] === 18.36 && $pi2['verguetungQuelle'] === 'berechnet' && $pi2['verguetungGeprueft'] === true, json_encode($pi2, JSON_UNESCAPED_UNICODE));
$e4 = freshEms();
$GLOBALS['PROP'][EMS_IID]['BAT_Capacity_kWh'] = 40.0;
$pi4 = call($e4, 'GetPlantInfo');
check('GetPlantInfo 1.1: ohne Wechselrichter-Wert speicherKwh 40 aus der Einstellung (Quelle einstellung, NICHT eingetragen)', $pi4['contractVersion'] === '1.1' && $pi4['speicherKwh'] === 40.0 && $pi4['speicherKwhQuelle'] === 'einstellung', json_encode($pi4));
$e5 = freshEms(); $GLOBALS['PROP'][EMS_IID]['BAT_Capacity_kWh'] = 40.0;
$capVar = vari('Kapazitaet', IHUB_IID, 'bat_capacity', 20.4);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'batteryCapacityID' => $capVar]]]));
$pi5 = call($e5, 'GetPlantInfo');
check('GetPlantInfo: gemessene Kapazitaet vom Wechselrichter (20,4) geht vor die Einstellung (40)', $pi5['speicherKwh'] === 20.4 && $pi5['speicherKwhQuelle'] === 'wechselrichter', json_encode($pi5));
$pi6 = call(freshEms(), 'GetPlantInfo');
check('nackt: Standardwert 10 kWh erscheint als "einstellung", nicht als eingetragene Anlage', $pi6['speicherKwhQuelle'] === 'einstellung', json_encode($pi6));
check('GetPlantInfo nackt (nichts angegeben): kein Fehler, leere Felder', ($n = call(freshEms(), 'GetPlantInfo'))['inbetriebnahme'] === '' && $n['eegFassung'] === '' && $n['kwpQuelle'] === 'fehlt' && $n['pflichten'] === [], json_encode($n));

// ===========================================================================
echo "\n18) Boersenpreis -- Sommerzeit, Negativpreis-Pflicht (§ 51 EEG), Einspeisegrenze\n";
$tzAlt = date_default_timezone_get(); date_default_timezone_set('Europe/Berlin');
$ems = freshEms();
$d25 = mktime(0, 0, 0, 10, 25, 2026); $d26 = mktime(0, 0, 0, 10, 26, 2026);
check('25.10.2026 hat 25 Stunden = 100 Viertelstunden', intdiv($d26 - $d25, 900) === 100, (string)intdiv($d26 - $d25, 900));
check('Zeitumstellung (Wanduhr-Slots): beide 02:00 -> Slot 8 (der Preis der ersten gilt, siehe parsePT15M), 03:00 Winterzeit -> Slot 12',
    call($ems, 'slotIndexForTs', [$d25 + 2 * 3600, $d25]) === 8 && call($ems, 'slotIndexForTs', [$d25 + 3 * 3600, $d25]) === 8 && call($ems, 'slotIndexForTs', [$d25 + 4 * 3600, $d25]) === 12);
check('letzte Viertelstunde des 25-Stunden-Tags (23:45) -> Slot 95', call($ems, 'slotIndexForTs', [$d26 - 900, $d25]) === 95);
$d29m = mktime(0, 0, 0, 3, 29, 2026); $d30m = mktime(0, 0, 0, 3, 30, 2026);
check('29.03.2026 hat 23 Stunden = 92 Viertelstunden, letzte (23:45) -> Slot 95', intdiv($d30m - $d29m, 900) === 92 && call($ems, 'slotIndexForTs', [$d30m - 900, $d29m]) === 95);
date_default_timezone_set($tzAlt);

$now = time();
$negKurve = [['start' => $now - 600, 'end' => $now + 300, 'price' => -1.5, 'aufloesung' => 900, 'quelle' => 'boersenpreis']];
$posKurve = [['start' => $now - 600, 'end' => $now + 300, 'price' => 4.2, 'aufloesung' => 900, 'quelle' => 'boersenpreis']];
check('spotPriceAt: Eintrag zur aktuellen Viertelstunde', (call($ems, 'spotPriceAt', [$negKurve, $now])['price'] ?? null) === -1.5);
check('spotPriceAt: keine Angabe -> null', call($ems, 'spotPriceAt', [[], $now]) === null);

// Neuanlage MIT Smart Meter + Steuerbox: Negativpreis-Pflicht gilt, die dauerhafte
// 60-%-Grenze entfaellt -- so prueft Block 18 die Negativpreis-Pflicht allein
// (Kombination beider Grenzen: Block 19).
$neu = function () { prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.0); prop('ANL_iMSys', true); prop('ANL_Steuerbox', true); };
$ems = freshEms(); $neu(); attr('FcSpotCurve', json_encode($negKurve));
$st = call($ems, 'negativePriceStatus');
check('Neuanlage 06/2025 + negativer Boersenpreis: Pflicht und aktiv', $st['pflicht'] === true && $st['active'] === true && $st['price'] === -1.5, json_encode($st));
attr('FcSpotCurve', json_encode($posKurve));
check('Neuanlage, Preis positiv: nicht aktiv', call($ems, 'negativePriceStatus')['active'] === false);
attr('FcSpotCurve', '[]');
check('Neuanlage, keine Boersenpreise: nicht aktiv (kein Signal, kein Fehler)', call($ems, 'negativePriceStatus')['active'] === false);
$ems = freshEms(); prop('ANL_IBN_Datum', '24.10.2012'); prop('ANL_kWp_Manuell', 9.18); attr('FcSpotCurve', json_encode($negKurve));
check('Dietmars Bestandsanlage 2012 + negativer Preis: keine Pflicht, nicht aktiv', call($ems, 'negativePriceStatus')['pflicht'] === false && call($ems, 'negativePriceStatus')['active'] === false);
$ems = freshEms(); $neu(); attr('FcSpotCurve', json_encode($negKurve)); prop('NETZ_Aktiv', false);
check('netzdienliche Bausteine aus: nicht aktiv', call($ems, 'negativePriceStatus')['active'] === false);
$ems = freshEms(); attr('FcSpotCurve', json_encode($negKurve));
check('nackt (keine Anlagendaten): keine Pflicht', call($ems, 'negativePriceStatus')['pflicht'] === false);

echo "\n   Einspeisegrenze (Netzbetreiber-Vorgabe + Negativpreis-Pflicht)\n";
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wrEms = fn() => attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true]]]));
$acts = fn() => array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
$ems = freshEms(); $neu(); $wrEms(); prop('EMS_Active', true); attr('FcSpotCurve', json_encode($negKurve));
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('negativer Preis + Pflicht + EMS aktiv: Einspeisegrenze 0 W', $acts() === ['ctl_export_enable=true', 'ctl_export_limit=0'], json_encode($acts()));
attr('FcSpotCurve', json_encode($posKurve)); $GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Preis wieder positiv: Begrenzung aufgehoben', $acts() === ['ctl_export_enable=false'], json_encode($acts()));
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('danach nichts mehr schreiben (nicht von uns gesetzt)', $acts() === [], json_encode($acts()));
$GLOBALS['INSTMOD'][300] = GUID_STEUERBOXHUB; $GLOBALS['SBH_STATE'] = ['feedInDimmActive' => true, 'feedInLimitPercent' => 60];
attr('FcSpotCurve', json_encode($negKurve)); $GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Netzbetreiber 60 % UND negativer Preis: der strengere Wert (0 W) gilt', end($GLOBALS['ACTIONS'])[1] === 'ctl_export_limit' && end($GLOBALS['ACTIONS'])[2] === 0, json_encode($acts()));
attr('FcSpotCurve', json_encode($posKurve)); $GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
$soll = (int)round(call($ems, 'getPlantKwp') * 1000.0 * 0.6);
check('nur Netzbetreiber 60 %: Grenze 60 % der installierten PV-Leistung (kWp), nicht des Hausanschlusses', end($GLOBALS['ACTIONS'])[2] === $soll, json_encode($acts()));
unset($GLOBALS['INSTMOD'][300]); $GLOBALS['SBH_STATE'] = null;
$ems = freshEms(); $neu(); $wrEms(); prop('EMS_Active', false); attr('FcSpotCurve', json_encode($negKurve));
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('EMS aus: Negativpreis-Pflicht setzt KEINE Grenze (nur Netzbetreiber gilt immer)', $acts() === [], json_encode($acts()));
$ems = freshEms(); $neu(); prop('EMS_Active', true); attr('FcSpotCurve', json_encode($negKurve));
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'external', 'controllable' => true]]]));
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('fremde Steuerhoheit (z. B. Sunny Home Manager): keine Grenze von EMS', $acts() === [], json_encode($acts()));

echo "\n   B1 bei negativem Preis\n";
$ems = freshEms(); $neu(); prop('EMS_Active', true); attr('FcSpotCurve', json_encode($negKurve));
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true,
    'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']]]]));
attr('FcPvToday', json_encode(array_fill(0, 96, 50000.0))); prop('NETZ_B1_Latest_Hour', 24); prop('BAT_Capacity_kWh', 40.0);
$autoN = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$d = call($ems, 'applyGridServiceB1', [$autoN, state(['bat_soc' => 50.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0])]);
check('negativer Preis + Pflicht: B1 sperrt NICHT (Ueberschuss soll in die Batterie), Grund sichtbar', empty($d['svc']) && strpos($d['reason'], 'Mittagsspitze pausiert') !== false, $d['reason']);
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n19) Dauerhafte Einspeisegrenze (60 % Solarspitzengesetz, 70 % Bestand, eingetragen) + Installateur-Wert wiederherstellen\n";
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wrEms19 = fn($auth = 'ems') => attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => $auth, 'controllable' => true]]]));
$acts19 = fn() => array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
$ems = freshEms(); prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.0);
check('Neuanlage 06/2025, 9 kWp, ohne Smart Meter: 60 % = 5400 W', call($ems, 'permanentFeedInLimit')['w'] === 5400, json_encode(call($ems, 'permanentFeedInLimit')));
prop('ANL_iMSys', true); prop('ANL_Steuerbox', true);
check('mit Smart Meter UND Steuerbox: keine dauerhafte Grenze', call($ems, 'permanentFeedInLimit')['w'] === null);
prop('ANL_iMSys', false); prop('ANL_Steuerbox', false); prop('ANL_Einspeisegrenze_Pct', 50);
check('eingetragen 50 % (EEG 2027): 4500 W', call($ems, 'permanentFeedInLimit')['w'] === 4500);
prop('ANL_Einspeisegrenze_Pct', 0);
check('eingetragen Nulleinspeisung: 0 W', call($ems, 'permanentFeedInLimit')['w'] === 0);
prop('ANL_Einspeisegrenze_Pct', 100);
check('eingetragen „keine“: keine Grenze, auch wenn die Pflicht 60 % wäre', call($ems, 'permanentFeedInLimit')['w'] === null);
$ems = freshEms(); prop('ANL_IBN_Datum', '24.10.2012'); prop('ANL_kWp_Manuell', 9.18); prop('ANL_Einspeisemanagement', 2);
check('Dietmar (2012, Rundsteuerempfänger): keine dauerhafte Grenze', call($ems, 'permanentFeedInLimit')['w'] === null);
prop('ANL_Einspeisemanagement', 1);
check('Bestand 2012 mit 70-%-Kappung angegeben: 70 % = 6426 W', call($ems, 'permanentFeedInLimit')['w'] === 6426);
$ems = freshEms(); prop('ANL_IBN_Datum', '01.06.2025');
check('ohne kWp: keine Grenze (nicht raten)', call($ems, 'permanentFeedInLimit')['w'] === null);

echo "\n   im Zusammenspiel mit dem Wechselrichter\n";
$ems = freshEms(); prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.0); $wrEms19(); prop('EMS_Active', false);
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Pflicht 60 % gilt auch bei EMS aus (gesetzlich): Grenze 5400 W', $acts19() === ['ctl_export_enable=true', 'ctl_export_limit=5400'], json_encode($acts19()));
$now19 = time(); prop('EMS_Active', true);
attr('FcSpotCurve', json_encode([['start' => $now19 - 600, 'end' => $now19 + 300, 'price' => -2.0, 'aufloesung' => 900, 'quelle' => 'boersenpreis']]));
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('60 % + negativer Preis: der strengere Wert 0 W', end($GLOBALS['ACTIONS'])[2] === 0, json_encode($acts19()));
attr('FcSpotCurve', '[]'); $GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Preis wieder normal: zurück auf die dauerhaften 5400 W (nicht aufheben)', end($GLOBALS['ACTIONS'])[2] === 5400, json_encode($acts19()));
$ems = freshEms(); prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.0); $wrEms19('external');
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('fremde Steuerhoheit: EMS setzt keine Grenze', $acts19() === [], json_encode($acts19()));

echo "\n   Installateur-Wert im Wechselrichter bleibt erhalten\n";
$ems = freshEms(); prop('ANL_IBN_Datum', '24.10.2012'); prop('ANL_kWp_Manuell', 9.18); prop('ANL_Einspeisemanagement', 2); $wrEms19(); prop('EMS_Active', true);
$veV = vari('Einspeisebegrenzung aktiv', IHUB_IID, 'ctl_export_enable', true, 0);
$vlV = vari('Einspeisegrenze', IHUB_IID, 'ctl_export_limit', 3000, 1);
$GLOBALS['INSTMOD'][300] = GUID_STEUERBOXHUB; $GLOBALS['SBH_STATE'] = ['feedInDimmActive' => true, 'feedInLimitPercent' => 30];
$GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Netzbetreiber-Vorgabe setzt Grenze, vorher gemerkt: Installateur 3000 W aktiv', strpos($ems->ReadAttributeString('FeedInLimitPrev'), '3000') !== false, $ems->ReadAttributeString('FeedInLimitPrev'));
$GLOBALS['SBH_STATE'] = ['feedInDimmActive' => false]; $GLOBALS['ACTIONS'] = []; call($ems, 'applySteuerboxFeedInLimit');
check('Vorgabe endet: Installateur-Grenze 3000 W wiederhergestellt statt ausgeschaltet', $acts19() === ['ctl_export_limit=3000', 'ctl_export_enable=true'], json_encode($acts19()));
unset($GLOBALS['INSTMOD'][300]); $GLOBALS['SBH_STATE'] = null;

echo "\n   B1 an der Einspeisegrenze\n";
$ems = freshEms(); prop('ANL_IBN_Datum', '01.06.2025'); prop('ANL_kWp_Manuell', 9.0); prop('EMS_Active', true);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true,
    'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']]]]));
// Trapezkurve mit Spitzenfenster 10:00-16:00 (Slot 40-63), wie in Block 16 -- eine
// durchgaengig flache Kurve wuerde seit der Spitzenfenster-Logik (30.09.2026) den ganzen
// Tag als "Spitze" zaehlen und B1 nie mehr sperren lassen.
$fc19 = array_fill(0, 96, 0.0);
for ($i = 24; $i < 40; $i++) { $fc19[$i] = 3000.0; }
for ($i = 40; $i < 64; $i++) { $fc19[$i] = 50000.0; }
for ($i = 64; $i < 80; $i++) { $fc19[$i] = 3000.0; }
attr('FcPvToday', json_encode($fc19)); prop('NETZ_B1_Latest_Hour', 24); prop('BAT_Capacity_kWh', 40.0);
$auto19 = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$d = call($ems, 'applyGridServiceB1', [$auto19, state(['bat_soc' => 50.0, 'pv_total_w' => 7000.0, 'house_pow_w' => 400.0])]);
check('Überschuss 6600 W über der Grenze 5400 W: B1 sperrt NICHT, Batterie nimmt auf', empty($d['svc']) && strpos($d['reason'], 'Einspeisegrenze') !== false, $d['reason']);
$nowSlot19 = (int)(((int)date('H') * 60 + (int)date('i')) / 15);
$d = call($ems, 'applyGridServiceB1', [$auto19, state(['bat_soc' => 50.0, 'pv_total_w' => 3000.0, 'house_pow_w' => 400.0])]);
check('Überschuss 2600 W deutlich unter der Grenze: B1 darf sperren (vor der Spitze)', $nowSlot19 >= 40 || ($d['svc'] ?? '') === 'chargeInhibit', $d['reason'] . " (Slot $nowSlot19)");
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n20) Bezugspreis-Historie (EMS_GetPurchasePriceHistory, Ladesitzungskosten)\n";
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$d0 = strtotime('yesterday')  /* gestern: sonst liegen die Slots in der ersten halben Stunde nach Mitternacht in der Zukunft */;
$ems = freshEms();
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 3600]);
check('nackt: Tarifart keiner, 4 Slots, alle Preise null', $r['tarifart'] === 'keiner' && count($r['slots']) === 4 && array_filter(array_column($r['slots'], 'priceCt')) === [], json_encode($r['tarifart']));
check('Vertrag 1.0, Einheit ct/kWh brutto', $r['contractVersion'] === '1.0' && $r['einheit'] === 'ct/kWh brutto');

$ems = freshEms(); prop('BEZ_Festpreis_ct', 32.5);
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 900]);
check('Festpreis ohne Historie (automatisch, kein Tibber): 32,5 ct', $r['tarifart'] === 'fest' && $r['slots'][0]['priceCt'] === 32.5, json_encode($r['slots'][0]));
attr('BezFestHistorie', json_encode([[$d0 + 1800, 30.0], [$d0 + 3600, 35.0]]));
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 5400]);
$p = array_column($r['slots'], 'priceCt');
check('Festpreis-Wechsel: vorher angenommen 30, ab 00:30 30, ab 01:00 35', $p === [30.0, 30.0, 30.0, 30.0, 35.0, 35.0] && $r['slots'][0]['quelle'] === 'fest-angenommen' && $r['slots'][2]['quelle'] === 'fest', json_encode($p));
$ems = freshEms(); prop('BEZ_Festpreis_ct', 30.0);
call($ems, 'recordFixedPriceChange', [$d0]); call($ems, 'recordFixedPriceChange', [$d0 + 60]);
prop('BEZ_Festpreis_ct', 31.0); call($ems, 'recordFixedPriceChange', [$d0 + 120]);
check('ApplyChanges merkt nur echte Änderungen (2 Einträge)', count(json_decode($ems->ReadAttributeString('BezFestHistorie'), true)) === 2, $ems->ReadAttributeString('BezFestHistorie'));

$TIB20 = 510; $GLOBALS['INSTMOD'][$TIB20] = GUID_TIBBERGRIDREWARD;
$cpV = vari('Aktueller Preis', $TIB20, 'CurrentPrice', 0.30, 2);
$GLOBALS['ARCHIVE'][$cpV] = [[$d0 - 86400, 0.25], [$d0 + 905, 0.40], [$d0 + 1805, 0.20]];
$GLOBALS['TIBBER_CURVE'] = [['start' => $d0 + 2700, 'end' => $d0 + 3600, 'price' => 28.5]];
$ems = freshEms(); $GLOBALS['TIBBER_CURVE'] = [['start' => $d0 + 2700, 'end' => $d0 + 3600, 'price' => 28.5]];
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 3600]);
$p = array_column($r['slots'], 'priceCt');
check('Tibber automatisch: Archiv €→ct inkl. Vortageswert, verspätetes Umschreiben (+5 s) richtig zugeordnet, Kurve hat Vorrang',
    $r['tarifart'] === 'tibber' && $p === [25.0, 40.0, 20.0, 28.5] && $r['slots'][0]['quelle'] === 'tibber-archiv' && $r['slots'][3]['quelle'] === 'tibber', json_encode($r['slots']));
$r = call($ems, 'GetPurchasePriceHistory', [time() + 86400 * 3, time() + 86400 * 3 + 900]);
check('Tibber ohne Kurve in der Zukunft: null statt geraten', $r['slots'][0]['priceCt'] === null);

$ems = freshEms(); prop('BEZ_Tarifart', 3); prop('BEZ_Preisvariable', $cpV); prop('BEZ_Preisvariable_Einheit', 1);
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 1800]);
check('eigene Preisvariable in €/kWh: 25 / 40 ct', array_column($r['slots'], 'priceCt') === [25.0, 40.0], json_encode($r['slots']));
prop('BEZ_Preisvariable_Einheit', 0);
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 900]);
check('eigene Preisvariable in ct/kWh: unverändert', $r['slots'][0]['priceCt'] === 0.25);
$ems = freshEms(); prop('BEZ_Tarifart', 1); prop('BEZ_Festpreis_ct', 29.0);
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 900]);
check('Festpreis ausdrücklich gewählt: Tibber wird ignoriert', $r['tarifart'] === 'fest' && $r['slots'][0]['priceCt'] === 29.0);
$r = call($ems, 'GetPurchasePriceHistory', [$d0, $d0 + 100 * 86400]);
check('Zeitraum auf 62 Tage begrenzt', count($r['slots']) === 62 * 96,(string)count($r['slots']));
unset($GLOBALS['INSTMOD'][$TIB20], $GLOBALS['INSTMOD'][500]); $GLOBALS['TIBBER_CURVE'] = [];

// ===========================================================================
echo "\n21) Einstandspreis der Batterie (EMS_GetBatteryCost/-History)\n";
$ems = freshEms();
$t21 = 1000000;
// $n Schritte à 60 s mit gleichbleibenden Werten
$run21 = function ($st, $n, $bat, $grid, $pv, $price, $feed = 8.0, $cap = 0.0, $soc = 0.0) use ($ems, &$t21) {
    for ($i = 0; $i < $n; $i++) { $t21 += 60; $st = call($ems, 'batteryCostStep', [$st, $bat, $grid, $pv, $soc, $t21, $price, $feed, $cap]); }
    return $st;
};
$sum21 = fn($st, $cap = 0.0) => call($ems, 'batteryCostSummary', [$st, $cap]);
$st = call($ems, 'batteryCostStep', [[], 0.0, 0.0, 0.0, 0.0, $t21, 20.0, 8.0, 0.0]);
check('Start leer: noch kein Einstandspreis (Bestand 0)', $sum21($st)['einstandCt'] === null);
$st = $run21($st, 60, -5000.0, -5000.0, 0.0, 20.0);
check('Nachts 1 h aus dem Netz zu 20 ct: 21,05 ct (Wandlungsverluste enthalten), Netzanteil 100 %',
    $sum21($st)['einstandCt'] === 21.05 && $sum21($st)['netzAnteilPct'] === 100.0, json_encode($sum21($st)));
$st = $run21($st, 60, -5000.0, 2000.0, 7000.0, 30.0);
check('Danach 1 h PV-Überschuss (0.100.0: PV-Laden ohne AC-Wandlung, 5 kWh voll im Akku): 1,00 EUR / 9,75 kWh = 10,26 ct, mit entgangener Vergütung 14,36 ct',
    $sum21($st)['einstandCt'] === 10.26 && $sum21($st)['einstandMitVerguetungCt'] === 14.36, json_encode($sum21($st)));
$vor = $sum21($st);
$st = $run21($st, 30, 5000.0, 0.0, 0.0, 30.0);
check('Entladen: Bestand sinkt, Durchschnittspreis bleibt', $sum21($st)['einstandCt'] === $vor['einstandCt'] && $sum21($st)['gespeichertKwh'] < $vor['gespeichertKwh'], json_encode($sum21($st)));
$st = call($ems, 'batteryCostStep', [[], 0.0, 0.0, 0.0, 0.0, $t21, null, 8.0, 0.0]);
$st = $run21($st, 60, -2000.0, -1000.0, 3000.0, 40.0);
check('Gemischt: Bezug 1 kW + PV 3 kW → gespeicherter Netzanteil 0,475 / 1,975 = 24,1 %, 10,13 ct (PV ohne AC-Verlust)', $sum21($st)['netzAnteilPct'] === 24.1 && $sum21($st)['einstandCt'] === 10.13, json_encode($sum21($st)));
$vor = $sum21($st)['einstandCt'];
$st = $run21($st, 30, -2000.0, -2000.0, 0.0, null);
check('Preis unbekannt: zum bisherigen Durchschnitt gebucht, kein Sprung', $vor !== null && $sum21($st)['einstandCt'] !== null && abs($sum21($st)['einstandCt'] - $vor) < 0.01, json_encode($sum21($st)));
$st = call($ems, 'batteryCostStep', [[], 0.0, 0.0, 0.0, 0.0, $t21, null, 8.0, 0.0]);
for ($i = 0; $i < 120; $i++) { $t21 += 30; $st = call($ems, 'batteryCostStep', [$st, -1500.0, -1500.0, 0.0, 0.0, $t21, 25.0, 8.0, 0.0]); }
check('Leere Batterie lädt langsam im 30-s-Takt (1,5 kW, je 0,0125 kWh): Bestand wächst auf ~1,4 kWh, 26,32 ct',
    abs($sum21($st)['gespeichertKwh'] - 1.43) < 0.02 && $sum21($st)['einstandCt'] === 26.32, json_encode($sum21($st)));
$st = call($ems, 'batteryCostStep', [[], 0.0, 0.0, 0.0, 50.0, $t21, 20.0, 8.0, 40.0]);
check('Start mit halbvoller 40-kWh-Batterie: Anfangsbestand als PV (0 ct), nicht eingeschwungen',
    $sum21($st, 40.0)['einstandCt'] === 0.0 && $sum21($st, 40.0)['gespeichertKwh'] === 20.0 && $sum21($st, 40.0)['eingeschwungen'] === false);
$st = $run21($st, 60, 0.0, 0.0, 0.0, 20.0, 8.0, 40.0, 40.0);
check('Bestand gleitet zum gemessenen SOC (40 % = 16 kWh)', abs($sum21($st, 40.0)['gespeichertKwh'] - 16.0) < 1.5, json_encode($sum21($st, 40.0)));
$st = $run21($st, 200, 10000.0, 0.0, 0.0, 20.0);
check('Batterie leer: Bestand und Wert auf 0, kein Preis', $sum21($st)['gespeichertKwh'] === 0.0 && $sum21($st)['einstandCt'] === null, json_encode($sum21($st)));
// 0.101.3, Privat 12./13.11.2026: 154 ct bei SOC 1 % -- Bestand glitt zum SOC, der Wert blieb stehen
prop('BAT_SOC_Min', 0); prop('BAT_SOC_Reserve_Backup', 0);
$st = ['ts' => $t21, 'stock' => 10.0, 'pool' => 2.5, 'opp' => 3.0, 'grid' => 10.0, 'charged' => 50.0, 'since' => $t21];
$st = $run21($st, 120, 0.0, 0.0, 0.0, 25.0, 8.0, 40.0, 2.0); // Zaehler sah kein Entladen, gemessen nur noch 2 % = 0,8 kWh
check('Bestand gleitet zum niedrigeren SOC (0,8 kWh): Wert schrumpft mit, Preis bleibt 25 ct statt ~300 ct (0.101.3)',
    abs($sum21($st, 40.0)['gespeichertKwh'] - 0.8) < 0.1 && abs($sum21($st, 40.0)['einstandCt'] - 25.0) < 0.05, json_encode($sum21($st, 40.0)));
prop('BAT_SOC_Min', 5); prop('BAT_SOC_Reserve_Backup', 0);
$st = ['ts' => $t21, 'stock' => 2.4, 'pool' => 0.6, 'opp' => 0.7, 'grid' => 2.4, 'charged' => 50.0, 'since' => $t21];
$st = $run21($st, 1, 0.0, 0.0, 0.0, 25.0, 8.0, 40.0, 5.0);
check('An der Mindestreserve (SOC 5 % = BAT_SOC_Min): Restwert verworfen, kein Preis (0.101.3)', $sum21($st, 40.0)['einstandCt'] === null, json_encode($sum21($st, 40.0)));
$st = ['ts' => $t21, 'stock' => 2.4, 'pool' => 0.6, 'opp' => 0.7, 'grid' => 2.4, 'charged' => 50.0, 'since' => $t21];
$st = $run21($st, 1, -3000.0, -3000.0, 0.0, 25.0, 8.0, 40.0, 5.0);
check('An der Mindestreserve, aber Akku laedt: nichts verworfen', $sum21($st, 40.0)['einstandCt'] !== null, json_encode($sum21($st, 40.0)));
prop('BAT_SOC_Min', 0);

$ems = freshEms();
attr('BatCostState', json_encode(['ts' => time(), 'stock' => 10.0, 'pool' => 2.0, 'opp' => 2.5, 'grid' => 5.0, 'charged' => 50.0, 'since' => strtotime('2026-09-01 12:00')]));
prop('BAT_Capacity_kWh', 40.0);
$r = call($ems, 'GetBatteryCost');
check('GetBatteryCost 1.0: 20 ct, 25 ct, 50 % Netz, eingeschwungen, Datum deutsch',
    $r['contractVersion'] === '1.0' && $r['einstandCt'] === 20.0 && $r['einstandMitVerguetungCt'] === 25.0 && $r['netzAnteilPct'] === 50.0
    && $r['eingeschwungen'] === true && $r['seitText'] === '01.09.2026 12:00', json_encode($r));
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$d0 = strtotime('yesterday'); // gestern: kurz nach Mitternacht laegen die Slots sonst in der Zukunft
$GLOBALS['ARCHIVE'][call($ems, 'GetIDForIdent', ['EMS_BatCostCt'])] = [[$d0 - 3600, 12.0], [$d0 + 1000, 18.5]];
$r = call($ems, 'GetBatteryCostHistory', [$d0, $d0 + 1800]);
check('GetBatteryCostHistory: Archivwert je Slot-Mitte (12 → 18,5 ct)', array_column($r['slots'], 'einstandCt') === [12.0, 18.5], json_encode($r['slots']));
unset($GLOBALS['INSTMOD'][500]);

// ===========================================================================
echo "\n22) Wallboxen über OCPPHub (dieselben Geräte wie ChargerHub nie doppelt zählen)\n";
$ems = freshEms();
$op2 = vari('OCPP WB2 Ladeleistung', 701, 'power', 3800.0);
$oen = vari('OCPP WB2 Ladefreigabe', 701, 'ctl_enable', false, 0);
$ohubEntry = ['contractVersion' => '1.3', 'instanceID' => 701, 'function' => 'charger', 'label' => 'WB2', 'powerID' => $op2,
    'chargeEnableID' => $oen, 'plugStateID' => 0, 'maxCurrent' => 16, 'managedBy' => 'none', 'transport' => 'ocpp', 'lastSeenAt' => time() - 20];
$GLOBALS['INSTMOD'][700] = GUID_OCPPHUB_SPLITTER; $GLOBALS['OHUB_FUNCS'] = [$ohubEntry];
$r = call($ems, 'discoverOcppHub');
check('Discovery: Eintrag behält die Ladepunkt-ID (701), nicht die Splitter-ID (700)', ($r[0]['instanceID'] ?? 0) === 701 && $r[0]['splitterID'] === 700 && $r[0]['source'] === 'ocpphub', json_encode($r));
$GLOBALS['INSTSTATUS'][700] = 104;
check('ausgeschalteter Splitter (Status 104): keine OCPP-Ladepunkte', call($ems, 'discoverOcppHub') === []);
unset($GLOBALS['INSTSTATUS'][700]);
$GLOBALS['INSTSTATUS'][701] = 104;
check('einzeln deaktivierter Ladepunkt (Status 104): übersprungen', call($ems, 'discoverOcppHub') === []);
unset($GLOBALS['INSTSTATUS'][701]);
$GLOBALS['OHUB_FUNCS'] = [array_merge($ohubEntry, ['active' => false, 'managedBy' => 'other', 'externallyManaged' => true])];
check('extern geregelter Ladepunkt (active=false, managedBy other): wird weiter gemessen', count(call($ems, 'discoverOcppHub')) === 1);
$GLOBALS['OHUB_FUNCS'] = [$ohubEntry];
unset($GLOBALS['INSTMOD'][700]); $GLOBALS['OHUB_FUNCS'] = [];
$chub = ['instanceID' => 600, 'powerID' => $p1, 'plugStateID' => 0, 'managedBy' => 'none'];
attr('PartnerCache', json_encode(['ocpphub' => [array_merge($ohubEntry, ['source' => 'ocpphub'])]]));
check('nur OCPPHub: Wallbox 1 = OCPP-Ladepunkt, 3,8 kW', call($ems, 'readChargerPowerKw', [1]) === 3.8 && !call($ems, 'chargerSourceStatus')['warn']);
attr('PartnerCache', json_encode(['chargerhub' => [$chub], 'ocpphub' => [array_merge($ohubEntry, ['source' => 'ocpphub'])]]));
$GLOBALS['VAR'][$p1]['VariableUpdated'] = time();
check('beide gefunden, automatisch: nur ChargerHub zählt (keine Doppelzählung), Warnung', count(call($ems, 'getChargerList')) === 1
    && call($ems, 'getChargerEntry', [1])['instanceID'] === 600 && call($ems, 'getChargerEntry', [2]) === [] && call($ems, 'chargerSourceStatus')['warn'] === true);
prop('WB_Quelle', 2);
check('Quelle OCPPHub gewählt: nur der OCPP-Ladepunkt, keine Warnung', call($ems, 'getChargerEntry', [1])['instanceID'] === 701 && count(call($ems, 'getChargerList')) === 1 && !call($ems, 'chargerSourceStatus')['warn']);
$GLOBALS['ACTIONS'] = []; call($ems, 'controlWallbox', [1, true]);
check('Schalten geht an den OCPP-Ladepunkt 701 (ctl_curr_limit + ctl_enable)', array_map(fn($a) => $a[0] . ':' . $a[1], $GLOBALS['ACTIONS']) === ['701:ctl_curr_limit', '701:ctl_enable'], json_encode($GLOBALS['ACTIONS']));
prop('WB_Quelle', 3);
check('Beide (verschiedene Geräte): ChargerHub = Wallbox 1, OCPP = Wallbox 2', call($ems, 'getChargerEntry', [1])['instanceID'] === 600 && call($ems, 'getChargerEntry', [2])['instanceID'] === 701);
prop('WB_Quelle', 1);
check('Quelle ChargerHub gewählt: OCPP bleibt außen vor, keine Warnung', count(call($ems, 'getChargerList')) === 1 && !call($ems, 'chargerSourceStatus')['warn']);
$sit = array_values(array_filter(call($ems, 'GetSituation'), fn($x) => $x['domain'] === 'wallbox'));
check('Situationsanzeige nutzt dieselbe Liste (1 Wallbox)', count($sit) === 1 && $sit[0]['instanceID'] === 600, json_encode($sit));

echo "\n   duplicateOf (Nutzerangabe am Quellmodul, CHUB/OHUB 1.4)\n";
$ems = freshEms();
$o701 = array_merge($ohubEntry, ['source' => 'ocpphub']);
$dup600 = array_merge($chub, ['duplicateOf' => ['source' => 'ocpphub', 'instanceID' => 701]]);
attr('PartnerCache', json_encode(['chargerhub' => [$dup600], 'ocpphub' => [$o701]]));
check('Dietmars Fall: ChargerHub-Eintrag als Dublette markiert, automatisch → nur OCPP zählt, keine Warnung',
    array_column(call($ems, 'getChargerList'), 'instanceID') === [701] && !call($ems, 'chargerSourceStatus')['warn'], json_encode(call($ems, 'getChargerList')));
$c602 = ['instanceID' => 602, 'powerID' => $p2, 'plugStateID' => 0, 'managedBy' => 'none'];
attr('PartnerCache', json_encode(['chargerhub' => [$dup600, $c602], 'ocpphub' => [$o701]]));
check('gemischt: eine Dublette markiert, übrige sind verschiedene Geräte → 602 und 701 zählen, keine Warnung',
    array_column(call($ems, 'getChargerList'), 'instanceID') === [602, 701] && !call($ems, 'chargerSourceStatus')['warn']);
prop('WB_Quelle', 1);
check('ChargerHub ausdrücklich gewählt: markierte Dublette 600 zählt trotzdem nicht', array_column(call($ems, 'getChargerList'), 'instanceID') === [602]);
prop('WB_Quelle', 0);
attr('PartnerCache', json_encode(['chargerhub' => [$chub], 'ocpphub' => [$o701]]));
check('ohne Feld (ältere Module): Verhalten wie 0.39.0 (ChargerHub + Warnung)', array_column(call($ems, 'getChargerList'), 'instanceID') === [600] && call($ems, 'chargerSourceStatus')['warn']);

echo "\n   Zählen und Steuern getrennt (Dietmars WB1: OCPP zählt, ChargerHub regelt)\n";
$ems = freshEms();
$cen = vari('CHUB WB1 Ladefreigabe', 600, 'ctl_enable', false, 0);
$o701ext = array_merge($o701, ['managedBy' => 'other', 'externallyManaged' => true]);
$dup600ctl = array_merge($chub, ['chargeEnableID' => $cen, 'maxCurrent' => 16, 'duplicateOf' => ['source' => 'ocpphub', 'instanceID' => 701]]);
attr('PartnerCache', json_encode(['chargerhub' => [$dup600ctl], 'ocpphub' => [$o701ext]]));
check('gemessen wird über OCPP 701 (3,8 kW)', call($ems, 'readChargerPowerKw', [1]) === 3.8);
check('geschaltet wird über die markierte ChargerHub-Anbindung 600 (dort darf EMS schreiben)', (call($ems, 'getControlEntry', [1])['instanceID'] ?? 0) === 600);
$GLOBALS['ACTIONS'] = []; call($ems, 'controlWallbox', [1, true]);
check('Schaltbefehl geht an 600, nicht an 701', array_map(fn($a) => $a[0] . ':' . $a[1], $GLOBALS['ACTIONS']) === ['600:ctl_curr_limit', '600:ctl_enable'], json_encode($GLOBALS['ACTIONS']));
$sit = array_values(array_filter(call($ems, 'GetSituation'), fn($x) => $x['domain'] === 'wallbox'));
check('Situationsanzeige: Wallbox schaltbar (A), obwohl die zählende Quelle fremdgesteuert ist', count($sit) === 1 && $sit[0]['writable'] === true && $sit[0]['situation'] === 'A', json_encode($sit));
attr('PartnerCache', json_encode(['chargerhub' => [array_merge($dup600ctl, ['managedBy' => 'other'])], 'ocpphub' => [$o701ext]]));
check('beide fremdgesteuert: EMS schaltet nicht (kein Schreibrecht)', call($ems, 'getControlEntry', [1]) === null);

echo "\n   Sicherheitsnetz: zwei Regler an einer Wallbox\n";
attr('PartnerCache', json_encode(['chargerhub' => [$dup600ctl], 'ocpphub' => [$o701ext]]));
check('Dietmars WB1 (OCPP „Anderer“, ChargerHub regelt): kein Doppelregler', call($ems, 'doubleWriterPairs') === []);
$o701both = array_merge($o701, ['managedBy' => 'none']);
attr('PartnerCache', json_encode(['chargerhub' => [$dup600ctl], 'ocpphub' => [$o701both]]));
check('beide Anbindungen „Niemand“: als Doppelregler erkannt', count(call($ems, 'doubleWriterPairs')) === 1, json_encode(call($ems, 'doubleWriterPairs')));
$GLOBALS['ACTIONS'] = []; attr('LastWB1Switch', 0); call($ems, 'controlWallbox', [1, true]);
$ziele = array_unique(array_map(fn($a) => $a[0], $GLOBALS['ACTIONS']));
check('EMS schreibt trotzdem nur über EINE Anbindung (die zählende 701)', array_values($ziele) === [701], json_encode($GLOBALS['ACTIONS']));

// ===========================================================================
