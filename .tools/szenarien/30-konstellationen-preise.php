<?php
// Kapitel des EMS-Pruefstands: Andere Anlagen, Zeitumstellung, Prognose-Aufloesung, mehrere WR, Preisschwellen, Tagesplan-/Planvertraege.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8e) Andere Nutzer, andere Konstellationen: Netzladen ohne feste Einspeiseverguetung\n";
$ems = freshEms();
prop('ANL_Verguetung_ct', 0.0); // Verguetung unbekannt
$tagU = array_fill(0, 96, 0.30); for ($i = 0; $i < 24; $i++) { $tagU[$i] = 0.12; } for ($i = 72; $i < 88; $i++) { $tagU[$i] = 0.36; }
check('Verguetung unbekannt: Arbitrage aus dem Preisverlauf (12 ct gegen Abendspitze 36 ct), nicht aus dem Platzhalter', call($ems, 'hasArbitrageInPrices', [$tagU]) === true);
check('Verguetung unbekannt, flacher Tag (30 ct): keine Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.30)]) === false);
$rp = call($ems, 'replacementPriceEur', [$tagU, 0]);
check('Ersatz-Bezugspreis = Mittel des teuersten Viertels (16 Slots 36 ct + 8 Slots 30 ct = 34 ct)', abs($rp - 0.34) < 0.001, (string)$rp);
$ctxU = ['capKwh' => 40.0, 'chargeKw' => 10.0, 'maxW' => 20000, 'socTargetNight' => 100.0, 'feedTariff' => 0.0, 'refMode' => 1, 'replacePrice' => 0.36, 'spread' => 0.03, 'cycleCost' => 0.0];
check('Referenz 1: Grenze = 36 ct x 0,9025 x 0,9 Sicherheit - 3 ct Mindestspanne = 26,24 ct (0.99.3)', abs(call($ems, 'gridChargeLimitEur', [$ctxU]) - 0.26241) < 0.0005);
prop('NETZLADUNG_Sicherheit_Pct', 100.0);
check('Sicherheitsfaktor 100 % = alte Grenze 29,49 ct', abs(call($ems, 'gridChargeLimitEur', [$ctxU]) - 0.29490) < 0.0005);
prop('NETZLADUNG_Sicherheit_Pct', 10.0);
check('Sicherheitsfaktor unter 50 % wird auf 50 % begrenzt', abs(call($ems, 'gridChargeLimitEur', [$ctxU]) - (0.36 * 0.9025 * 0.5 - 0.03)) < 0.0005);
prop('NETZLADUNG_Sicherheit_Pct', 90.0);
check('Referenz 0 (Verguetung) bleibt ohne Sicherheitsfaktor', abs(call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.08, 'refMode' => 0]]) - 0.076) < 0.0005);
check('Referenz 1 ohne Preisvergleichswert: kein Netzladen (Grenze unerreichbar)', call($ems, 'gridChargeLimitEur', [array_merge($ctxU, ['replacePrice' => null])]) < -1e9);
prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6); prop('PLAN_NightGrid_ExtendHours', 0);
$nwU = call($ems, 'nightWindowPlan', [$tagU, 0, 0.0, $ctxU]);
check('Nutzer ohne Verguetung laedt trotzdem in den 12-ct-Slots nach (kein Sperren wegen Verguetung 0)', $nwU !== null && count($nwU['charge']) > 0 && max(array_keys($nwU['charge'])) < 24, json_encode($nwU));
$slU = null; for ($i = 0; $i < $nwU['end']; $i++) { if (!isset($nwU['charge'][$i])) { $slU = $i; break; } }
check('Referenz Ersatzpreis: es gibt einen Nicht-Ladeslot im Fenster (Testvoraussetzung)', $slU !== null, json_encode($nwU));
check('Referenz Ersatzpreis (Nutzer ohne Verguetung): uebriger Slot im Fenster (12 ct < 36 ct x 0,95 - 3 ct = 31,2 ct) -> Akku halten, Haus aus dem Netz, Begruendung nennt den Ersatzpreis', ($hU = call($ems, 'nightWindowSlot', [$slU, 0.12, 60.0, $nwU, $ctxU])) !== null && $hU['plan']['op'] === EMS_OP_HOLD && $hU['plan']['gw'] === GW_MODE_STANDBY && strpos($hU['plan']['reason'], 'Ersatzpreis') !== false, json_encode($hU['plan'] ?? null));
check('Referenz Ersatzpreis: Halte-Grenze 36 x 0,95 x 0,9 - 3 = 27,78 ct (27 ct noch halten, 28 ct schon Akku)', call($ems, 'nightWindowSlot', [$slU, 0.27, 60.0, $nwU, $ctxU]) !== null && call($ems, 'nightWindowSlot', [$slU, 0.28, 60.0, $nwU, $ctxU]) === null);
$hr = call($ems, 'nightWindowSlot', [$slU, 0.27, 60.0, $nwU, $ctxU]);
check('Halte-Begruendung nennt Faktor und Ersatzpreis', strpos($hr['plan']['reason'], '0.85 ×') !== false && strpos($hr['plan']['reason'], '36.00ct') !== false, $hr['plan']['reason']);
if (method_exists('EMS', 'gridChargeSkipNote')) {
// 0.99.3: Netzladen ausgelassen -- Hinweis mit Grund (Befund 03./07.11.: 44 ct geladen bei 46 ct Ersatzwert)
$ctxS = array_merge($ctxU, ['replacePrice' => 0.46, 'socTargetNight' => 100.0]);
$nwS = ['end' => 24, 'charge' => [], 'n' => 0, 'cand' => 0];
$note = call($ems, 'gridChargeSkipNote', [4, 0.44, 50.0, $nwS, $ctxS]);
check('Netzladen bei 44 ct gegen 46 ct ausgelassen, Hinweis nennt beide Werte und die Grenze', strpos($note, '44.00ct') !== false && strpos($note, '34.36ct') !== false && strpos($note, '46.00ct') !== false, $note);
check('Grenzfall: knapp unter der Grenze kein Hinweis (Laden waere erlaubt)', call($ems, 'gridChargeSkipNote', [4, 0.3435, 50.0, $nwS, $ctxS]) === '');
check('Grenzfall: genau auf der Grenze ausgelassen', call($ems, 'gridChargeSkipNote', [4, 0.46 * 0.9025 * 0.9 - 0.03, 50.0, $nwS, $ctxS]) !== '');
check('Kein Hinweis, wenn der Akku das Nachtziel schon hat', call($ems, 'gridChargeSkipNote', [4, 0.44, 99.8, $nwS, $ctxS]) === '');
check('Kein Hinweis ausserhalb des Nachtfensters', call($ems, 'gridChargeSkipNote', [30, 0.44, 50.0, $nwS, $ctxS]) === '');
check('Kein Hinweis bei Referenz Verguetung', call($ems, 'gridChargeSkipNote', [4, 0.44, 50.0, $nwS, array_merge($ctxS, ['refMode' => 0])]) === '');
$wn = call($ems, 'withGridChargeSkipNote', [['plan' => ['op' => EMS_OP_AUTO, 'reason' => 'x']], 4, 0.44, 50.0, $nwS, $ctxS]);
check('Hinweis wird an die Begruendung angehaengt', strpos($wn['plan']['reason'], 'x · ') === 0, $wn['plan']['reason']);
$hS = call($ems, 'nightWindowSlot', [4, 0.40, 50.0, $nwS, $ctxS]);
check('Halten ausgelassen ueber der Schwelle (40 ct > 46 x 0,855 - 3 = 36,33 ct)', $hS === null, json_encode($hS));
check('Halten unter der Schwelle (36 ct)', call($ems, 'nightWindowSlot', [4, 0.36, 50.0, $nwS, $ctxS]) !== null);
}
check('Referenz Ersatzpreis ohne Preisvergleichswert: kein Halten', call($ems, 'nightWindowSlot', [$slU, 0.12, 60.0, $nwU, array_merge($ctxU, ['replacePrice' => null])]) === null);
// 0.97.4: "Was ist Neu" nur seit der zuletzt bestaetigten Version (SUITE.md NEWS_VERSIONS)
$labels = function ($panel) { return $panel === null ? array() : array_values(array_filter(array_map(function ($i) { return $i['type'] === 'Label' ? $i['caption'] : null; }, $panel['items']))); };
attr('SeenNews', '0.97.3');
$nb = call($ems, 'newsBanner');
check('News: gesehen 0.97.3 -> nur 0.109.0 bis 0.97.4, neueste oben', $nb !== null && $nb['caption'] === '🆕 Neu bis Version 0.109.0' && count(array_filter($labels($nb), function ($c) { return strpos($c, 'Version ') === 0; })) === 10 && strpos($labels($nb)[0], 'Version 0.109.0') === 0, json_encode($labels($nb), JSON_UNESCAPED_UNICODE));
attr('SeenNews', '0.106.7');
$nb = call($ems, 'newsBanner');
check('News: gesehen 0.106.7 -> nur die Zeile von 0.109.0, ohne Versionsueberschrift', $nb !== null && count($labels($nb)) === 1 && strpos($labels($nb)[0], 'Version ') !== 0, json_encode($labels($nb), JSON_UNESCAPED_UNICODE));
attr('SeenNews', '0.109.0-beta.1');
check('News: gesehen 0.109.0-beta.1 (Beta-Zusatz) -> nichts Neues', call($ems, 'newsBanner') === null);
attr('SeenNews', '');
$nb = call($ems, 'newsBanner');
check('News: Neuinstallation (nichts gesehen) -> alles, inkl. Altblock', $nb !== null && count($labels($nb)) > 50);
attr('SeenNews', '0.97.2');
// 0.93.0: Nachtfenster nach Bedarf bis zur naechsten Nacht (Befund Pauls Anlage 25.10.2026)
prop('BAT_SOC_Safety_Margin_Pct', 10);
$ctxN = array_merge($ctxU, ['capKwh' => 100.0, 'socMin' => 10.0, 'socReserve' => 10.0, 'avgHouseW' => 500.0, 'dischargeKw' => 10.0, 'houseLoadSlots' => []]);
$pvN = array_fill(0, 192, 0.0); $ldN = array_fill(0, 192, 500.0);
$needN = call($ems, 'nightNeedSoc', [24, 96, $pvN, $ldN, $ctxN]);
check('0.97.3: ohne PV-Ueberschuss am Folgetag (Winter) Nachtziel 100 % statt nur Bedarf 39 % (Preise der uebernaechsten Nacht unbekannt)', $needN !== null && abs($needN - 100.0) < 0.01, (string)$needN);
check('0.97.3: PV-Platz ohne PV = 100 %', abs(call($ems, 'nightPvHeadroomSoc', [24, 96, $pvN, $ldN, $ctxN]) - 100.0) < 0.01);
$pvSun = $pvN; for ($i = 40; $i < 64; $i++) { $pvSun[$i] = 5000.0; }
$needSun = call($ems, 'nightNeedSoc', [24, 96, $pvSun, $ldN, $ctxN]);
$hrSun = call($ems, 'nightPvHeadroomSoc', [24, 96, $pvSun, $ldN, $ctxN]);
check('0.97.3: mit PV 10-16 Uhr (27 kWh Ueberschuss, vorher 2 kWh Last) bleibt Platz frei: Ziel ~75 %', $needSun !== null && $needSun > 70.0 && $needSun < 80.0 && abs($needSun - $hrSun) < 0.01, $needSun . ' / ' . $hrSun);
$pvBig = $pvN; for ($i = 36; $i < 72; $i++) { $pvBig[$i] = 20000.0; }
check('0.97.3: PV fuellt den Akku sicher von ganz unten: Ziel = Bedarf (Untergrenze), nicht weniger', ($nb = call($ems, 'nightNeedSoc', [24, 96, $pvBig, $ldN, $ctxN])) !== null && $nb >= 20.0 && $nb < 40.0, (string)$nb);
check('Bedarf groesser als der volle Akku (20 kW Last): nicht bestimmbar, festes Nachtziel bleibt', call($ems, 'nightNeedSoc', [24, 96, $pvN, array_fill(0, 192, 20000.0), $ctxN]) === null);
$ctxNB = array_merge($ctxU, ['socTargetNight' => 39.0, 'nightNeedBased' => true]);
check('Akku 86 % ueber dem Bedarf 39 %: kein Halten, Akku versorgt das Haus', call($ems, 'nightWindowSlot', [$slU, 0.21, 86.0, $nwU, $ctxNB]) === null);
check('Akku 30 % unter dem Bedarf 39 %: Halten wie bisher', ($hN = call($ems, 'nightWindowSlot', [$slU, 0.21, 30.0, $nwU, $ctxNB])) !== null && $hN['plan']['op'] === EMS_OP_HOLD);
$nwN = call($ems, 'nightWindowPlan', [$tagU, 0, 86.0, $ctxNB]);
check('Akku 86 % ueber dem Bedarf: keine Ladeviertelstunde im Nachtfenster', $nwN !== null && $nwN['n'] === 0, json_encode($nwN));
$nwN2 = call($ems, 'nightWindowPlan', [$tagU, 0, 20.0, $ctxNB]);
check('Akku 20 %, Bedarf 39 %: nur so viel laden wie fehlt (19 % von 40 kWh = 7,6 kWh, 1 Viertelstunde bei 10 kW... hoechstens 4)', $nwN2 !== null && $nwN2['n'] >= 1 && $nwN2['n'] <= 4, json_encode($nwN2));
check('Referenz Ersatzpreis: Halten unabhaengig von der Einspeiseverguetung (Verguetung 18,36 ct, Preis 30 ct, Ersatz 50 ct -> halten)', ($hV = call($ems, 'nightWindowSlot', [$slU, 0.30, 60.0, $nwU, array_merge($ctxU, ['feedTariff' => 0.1836, 'replacePrice' => 0.50])])) !== null && $hV['plan']['op'] === EMS_OP_HOLD, json_encode($hV['plan'] ?? null));
// Zweisprachigkeit Schritt 3: Begruendungen des Tagesplans englisch ohne Deutsch, Codefelder (op/gw) sprachneutral
$deWort = '/[äöüÄÖÜß]|\b(und|oder|nicht|nur|wird|aus dem|Akku|Batterie|Netz|Nachtfenster|Haus|Bezug|Einspeis\w*|Automatik|laden|Ziel|Rang|Viertelstunde)\b/u';
$memoR = new ReflectionProperty('EMS', 'emsTextMemo');
$GLOBALS['EMS_LANG_EN'] = true; $memoR->setValue(null, []);
$enR = [call($ems, 'nightWindowSlot', [$slU, 0.12, 60.0, $nwU, $ctxU]), call($ems, 'nightWindowSlot', [$slU, 0.12, 60.0, $nwU, array_merge($ctxU, ['refMode' => 0, 'feedTariff' => 0.20])])];
$GLOBALS['EMS_LANG_EN'] = false; $memoR->setValue(null, []);
$deR = call($ems, 'nightWindowSlot', [$slU, 0.12, 60.0, $nwU, $ctxU]);
$enTexte = array_map(fn($x) => (string)($x['plan']['reason'] ?? ''), $enR);
check('Englisch: Nachtfenster-Begruendungen ohne Deutsch, op/gw wie deutsch', $enR[0] !== null && $enR[1] !== null && count(preg_grep($deWort, $enTexte)) === 0 && $enR[0]['plan']['op'] === $deR['plan']['op'] && $enR[0]['plan']['gw'] === $deR['plan']['gw'] && strpos($enTexte[0], 'Night window until') === 0, json_encode($enTexte, JSON_UNESCAPED_UNICODE));
prop('ANL_Verguetung_ct', 8.0); // niedrige Verguetung (z. B. Ue20/Direktvermarktung)
check('Verguetung 8 ct, Referenz Verguetung: Netzladen sehr streng (Grenze 7,6 ct)', abs(call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.08, 'refMode' => 0]]) - 0.076) < 0.0005);
prop('NETZLADUNG_Referenz', 1);
check('Verguetung 8 ct, aber Referenz Ersatz-Bezugspreis gewaehlt: Modus 1', call($ems, 'chargeReferenceMode') === 1);

echo "\n8f) Konstellationen: berechnete Verguetung, Netzanschluss-Budget, Wechselrichter ohne Stellglieder\n";
$ems = freshEms();
prop('ANL_Verguetung_ct', 0.0); prop('ANL_IBN_Datum', '2012-10-24'); prop('ANL_kWp_Manuell', 9.18);
check('aus Inbetriebnahme und Groesse berechnete Verguetung ist BEKANNT und gilt fuer Planung (Referenz 0)', call($ems, 'planFeedTariffEur') > 0.15 && call($ems, 'chargeReferenceMode') === 0, json_encode(call($ems, 'getFeedTariffEur')));
$ems = freshEms(); prop('SITE_Max_Grid_Import_W', 12000); prop('WB_Active', true); prop('WB_Count', 1); prop('WB1_Max_Power_W', 11000);
prop('WB1_Mode', 2); // Modus "EMS steuert": nur solche Wallboxen drosselt das Budget (ohne ChargerHub im Prüfstand ist 'automatisch' = nur beobachten)
$dW = ['wb1_enable' => true, 'wb2_enable' => false];
call($ems, 'enforceGridImportBudget', [&$dW, state(['grid_total_w' => -6000.0, 'wb1_pow_kw' => 0.0, 'wb_count' => 1])]);
check('Netzbezug 6 kW + neue Wallbox 11 kW ueberschreitet 12 kW: Wallbox wird gedrosselt (Vorzeichen: Bezug negativ)', $dW['wb1_enable'] === false, json_encode($dW));
$dW2 = ['wb1_enable' => true, 'wb2_enable' => false];
call($ems, 'enforceGridImportBudget', [&$dW2, state(['grid_total_w' => 5000.0, 'wb1_pow_kw' => 0.0, 'wb_count' => 1])]);
check('5 kW Einspeisung + Wallbox 11 kW = nur 6 kW Bezug: Wallbox bleibt frei (keine unnoetige Drosselung)', $dW2['wb1_enable'] === true, json_encode($dW2));
// Wechselrichter mit anderen Variablen, aber ohne EMS-Stellglieder: nichts schreiben
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
foreach (array_keys($GLOBALS['OBJ']) as $oid) { if (($GLOBALS['OBJ'][$oid]['ParentID'] ?? -1) === IHUB_IID && strpos($GLOBALS['OBJ'][$oid]['ObjectIdent'], 'ctl_ems') === 0) { $GLOBALS['OBJ'][$oid]['ObjectIdent'] = 'x_' . $GLOBALS['OBJ'][$oid]['ObjectIdent']; } }
$GLOBALS['OBJ'][IHUB_IID]['HasChildren'] = true;
vari('SOC', IHUB_IID, 'soc', 50, 1);
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [11, 5000, true]);
check('WR ohne ctl_ems_*-Stellglieder (anderer Hersteller): keine Schreibzugriffe', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));

echo "\n8g) Zeitumstellung: 25-Stunden-Tag (Oktober) und 23-Stunden-Tag (Maerz)\n";
$ems = freshEms();
function lastSunday(int $year, int $month): string { $d = new DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month + 1 > 12 ? 1 : $month + 1)); if ($month + 1 > 12) { $d->modify('+1 year'); } $d->modify('-1 day'); while ($d->format('N') != 7) { $d->modify('-1 day'); } return $d->format('Y-m-d'); }
$curve = function (string $date, int $n) { $t0 = strtotime($date . ' 00:00:00'); $o = []; for ($i = 0; $i < $n; $i++) { $o[] = ['start' => $t0 + $i * 900, 'end' => $t0 + ($i + 1) * 900, 'price' => 10.0 + $i]; } return json_encode($o); };
$offsetFor = function (string $date) { return (int)round((strtotime($date . ' 12:00:00') - strtotime('today 12:00:00')) / 86400); };
$dOkt = lastSunday(2030, 10); $nOkt = (int)((strtotime($dOkt . ' +1 day 00:00:00') - strtotime($dOkt . ' 00:00:00')) / 900);
check('Testdatum Oktober ist ein 25-Stunden-Tag (100 Viertelstunden)', $nOkt === 100, "$dOkt $nOkt");
$pOkt = call($ems, 'parsePT15M', [$curve($dOkt, $nOkt), $offsetFor($dOkt)]);
check('25-Stunden-Tag: genau 96 Slots, Index nach Wanduhr', count($pOkt) === 96);
check('25-Stunden-Tag: 12:00 Uhr (Slot 48) hat den Preis der Viertelstunde 12:00, nicht den einer Stunde davor/danach', abs($pOkt[48] - (0.10 + 52 * 0.01)) < 1e-9, (string)$pOkt[48]);
check('25-Stunden-Tag: vor der Umstellung (Slot 4 = 01:00) exakt', abs($pOkt[4] - (0.10 + 4 * 0.01)) < 1e-9);
check('25-Stunden-Tag: doppelte Stunde 02-03 (Slots 8-11): der erste Preis gilt', abs($pOkt[8] - (0.10 + 8 * 0.01)) < 1e-9 && abs($pOkt[11] - (0.10 + 11 * 0.01)) < 1e-9);
check('25-Stunden-Tag: 23:45 (Slot 95) hat einen Preis', $pOkt[95] !== null && abs($pOkt[95] - (0.10 + 99 * 0.01)) < 1e-9, (string)$pOkt[95]);
$dMrz = lastSunday(2031, 3); $nMrz = (int)((strtotime($dMrz . ' +1 day 00:00:00') - strtotime($dMrz . ' 00:00:00')) / 900);
check('Testdatum Maerz ist ein 23-Stunden-Tag (92 Viertelstunden)', $nMrz === 92, "$dMrz $nMrz");
$pMrz = call($ems, 'parsePT15M', [$curve($dMrz, $nMrz), $offsetFor($dMrz)]);
check('23-Stunden-Tag: Slots 8-11 (02:00-03:00, gibt es nicht) bleiben leer, 12:00 (Slot 48) hat den Preis der Viertelstunde 12:00', $pMrz[8] === null && $pMrz[11] === null && abs($pMrz[48] - (0.10 + 44 * 0.01)) < 1e-9, json_encode([$pMrz[8], $pMrz[48]]));
check('23-Stunden-Tag: vor der Umstellung exakt (01:00 = Slot 4)', abs($pMrz[4] - (0.10 + 4 * 0.01)) < 1e-9);

echo "\n8h) Prognose-Aufloesung: 24/48/96 Slots werden auf 96 Viertelstunden umgerechnet\n";
$ems = freshEms();
$hourly = array_fill(0, 24, 0.0); $hourly[12] = 4800.0; $hourly[13] = 4800.0; $hourly[11] = 2400.0; $hourly[14] = 2400.0;
$q = call($ems, 'resampleTo96', [$hourly]);
check('stuendliche Reihe (24) -> 96 Werte', count($q) === 96);
check('12:00-Stunde bleibt mittags hoch (Slot 50 = 12:30 ~ 4800 W), Nacht bleibt 0', abs($q[50] - 4800.0) < 1.0 && $q[8] === 0.0, json_encode([$q[50], $q[8]]));
check('Energie bleibt erhalten (Summe der Viertelstunden/4 ~ Summe der Stunden)', abs(array_sum($q) / 4 - array_sum($hourly)) < 0.02 * array_sum($hourly), (string)(array_sum($q) / 4) . ' vs ' . array_sum($hourly));
$half = array_map(fn($i) => (float)$i, range(0, 47));
check('48 Slots -> 96, Rand konstant, streng steigend', count(call($ems, 'resampleTo96', [$half])) === 96 && call($ems, 'resampleTo96', [$half])[95] >= 46.9);
$r96 = range(0, 95);
check('96 Slots bleiben unveraendert', call($ems, 'resampleTo96', [$r96]) === $r96);
$withNull = call($ems, 'resampleTo96', [array_merge(array_fill(0, 12, 100.0), array_fill(0, 12, null))]);
check('fehlende Werte (null) werden nicht interpoliert', $withNull[10] === 100.0 && $withNull[90] === null);

echo "\n8i) Mehrere Wechselrichter: der mit Batterie wird gesteuert, die uebrigen zaehlen zur PV-Summe\n";
$ems = freshEms();
attr('PartnerCache', json_encode(['inverterhub' => [
    ['instanceID' => 101, 'controlAuthority' => 'none', 'controllable' => false, 'pvPowerID' => 5001, 'batPowerID' => 0, 'socID' => 0],
    ['instanceID' => 102, 'controlAuthority' => 'ems', 'controllable' => true, 'pvPowerID' => 5002, 'batPowerID' => 5003, 'socID' => 5004],
]]));
$invM = call($ems, 'getInverterEntry');
check('Hybrid-Wechselrichter mit Batterie (#102) wird gewaehlt, nicht der erste (#101)', $invM['instanceID'] === 102 && $invM['batPowerID'] === 5003, json_encode($invM));
check('PV-Leistung des zweiten Wechselrichters wird zur Summe addiert (extraPvIDs)', $invM['extraPvIDs'] === [5001], json_encode($invM['extraPvIDs']));

echo "\n8j) Wallbox ohne lesbaren Freigabe-Status: sperren geht trotzdem\n";
$ems = freshEms();
$GLOBALS['ACTIONS'] = [];
$entryNoRead = ['instanceID' => 777, 'source' => 'chargerhub', 'chargeEnableID' => 0, 'maxCurrent' => 16];
call($ems, 'controlWallboxViaChargerHub', [1, $entryNoRead, true]);
$freigabe = count($GLOBALS['ACTIONS']);
attr('LastWB1Switch', 0);
$GLOBALS['ACTIONS'] = [];
call($ems, 'controlWallboxViaChargerHub', [1, $entryNoRead, false]);
check('Wallbox ohne chargeEnableID: erst freigeben, danach wird das Sperren wirklich gesendet', $freigabe > 0 && count($GLOBALS['ACTIONS']) > 0 && end($GLOBALS['ACTIONS'])[1] === 'ctl_enable' && end($GLOBALS['ACTIONS'])[2] === false, json_encode($GLOBALS['ACTIONS']));

echo "\n8k) Preisschwellen: eingetragen gilt, 0 = automatisch aus dem Preisverlauf\n";
$ems = freshEms();
$pTh = []; for ($i = 0; $i < 96; $i++) { $pTh[] = 0.10 + $i * 0.004; } // 10 ct ... 48 ct steigend
$th = call($ems, 'priceThresholds', [$pTh]);
check('automatisch: charge ~ unteres Viertel, wb ~ Median, discharge ~ oberes Viertel', $th['charge'] < $th['wb'] && $th['wb'] < $th['discharge'] && abs($th['wb'] - 0.10 - 47.5 * 0.004) < 0.006, json_encode($th));
$thFlat = call($ems, 'priceThresholds', [array_fill(0, 96, 0.30)]);
check('flacher Tarif (Festpreis): keine Preissteuerung (Wallbox immer erlaubt, nichts gilt als teuer)', $thFlat['wb'] >= 1e8 && $thFlat['discharge'] >= 1e8 && $thFlat['charge'] >= 1e8, json_encode($thFlat));
$thNone = call($ems, 'priceThresholds', [array_fill(0, 96, null)]);
check('ohne Preise: keine Preissteuerung', $thNone['wb'] >= 1e8);
prop('TIB_Threshold_Charge', 0.15); prop('TIB_Threshold_Discharge', 0.25); prop('TIB_Threshold_WB', 0.20);
$thFix = call($ems, 'priceThresholds', [$pTh]);
check('eingetragene Schwellen gelten unveraendert (0,15 / 0,25 / 0,20)', $thFix === ['charge' => 0.15, 'discharge' => 0.25, 'wb' => 0.20], json_encode($thFix));
prop('TIB_Threshold_WB', 0.0);
$thMix = call($ems, 'priceThresholds', [$pTh]);
check('gemischt: eingetragen bleibt, 0 wird automatisch', $thMix['charge'] === 0.15 && $thMix['wb'] > 0.2 && $thMix['wb'] < 0.3, json_encode($thMix));

echo "\n8k2) Preisschwellen: Automatik rechnet wirtschaftlich (Dietmar 20.10.2026)\n";
$ems = freshEms();
prop('BAT_Capacity_kWh', 40.0); prop('BAT_Price_EUR', 9998.0); prop('BAT_Cycles', 8000); prop('OPT_Arbitrage_Min_Spread_ct', 3.0);
// a) Winter-Preise 30..55 ct, Referenz Ersatzpreis: Quartile bleiben, wirtschaftliche Grenze (rund 39 ct) lockert nie
$pW = []; for ($i = 0; $i < 96; $i++) { $pW[] = 0.30 + $i * (0.25 / 95); }
prop('NETZLADUNG_Referenz', 1);
$info = []; $thW = call($ems, 'priceThresholdsCore', [$pW, &$info]);
check('Winter 30-55 ct, Referenz Ersatzpreis: wirtschaftliche Grenze (~36 ct mit Sicherheitsfaktor 0,9) liegt jetzt unter dem unteren Viertel und gilt (0.99.3)', $thW['charge'] < $pW[(int)round(0.25 * 95)] && $info['charge'] === 'auto_econ_replace', json_encode([$thW, $info]));
// b) enger Verlauf 30..34 ct: Verschleiss 3,1 ct + Mindestspanne 3 ct + Wandlungsverluste machen Laden unwirtschaftlich
$pN = []; for ($i = 0; $i < 96; $i++) { $pN[] = 0.30 + $i * (0.04 / 95); }
$info = []; $thN = call($ems, 'priceThresholdsCore', [$pN, &$info]);
check('enger Verlauf 30-34 ct: Ladeschwelle wird auf die wirtschaftliche Grenze (unter 30 ct) gesenkt, nichts gilt als guenstig', $thN['charge'] < 0.30 && $thN['charge'] < $pN[(int)round(0.25 * 95)] && in_array($info['charge'], ['auto_econ_replace', 'auto_econ_feed'], true), json_encode([$thN, $info]));
// c) eingetragen gilt unveraendert, auch wenn unwirtschaftlich
prop('TIB_Threshold_Charge', 0.07);
$info = []; $thE = call($ems, 'priceThresholdsCore', [$pN, &$info]);
check('eingetragene Ladeschwelle (7 ct) gilt unveraendert und wird als eingetragen gemeldet', $thE['charge'] === 0.07 && $info['charge'] === 'eingetragen', json_encode([$thE, $info]));
prop('TIB_Threshold_Charge', 0.0);
// d) Entladeschwelle: guenstiger Tarif 10..18 ct, Verguetung 18,36 ct: Einspeisen lohnt mehr als Entladen unter ~20,4 ct
prop('ANL_Verguetung_ct', 18.36);
$pC = []; for ($i = 0; $i < 96; $i++) { $pC[] = 0.10 + $i * (0.08 / 95); }
$info = []; $thC = call($ems, 'priceThresholdsCore', [$pC, &$info]);
$floorD = 0.1836 * 0.95 + 0.0312;
check('billiger Tarif: Entladeschwelle mindestens Verguetung x 0,95 + Verschleiss (~20,5 ct) statt oberes Viertel (16 ct)', abs($thC['discharge'] - $floorD) < 0.001 && $info['discharge'] === 'auto_floor_feed', json_encode([$thC, $info]));
// e) teurer Tarif: oberes Viertel liegt ueber der Untergrenze, bleibt unveraendert
$info = []; $thH = call($ems, 'priceThresholdsCore', [$pW, &$info]);
check('Winter-Tarif: Entladeschwelle bleibt oberes Viertel (Untergrenze 20,5 ct lockert nicht)', abs($thH['discharge'] - $pW[(int)round(0.75 * 95)]) < 1e-9 && $info['discharge'] === 'auto_high', json_encode([$thH, $info]));
// f) ohne bekannte Verguetung keine Untergrenze fuer Entladen
prop('ANL_Verguetung_ct', 0.0);
$thNF = call($ems, 'priceThresholds', [$pC]);
check('ohne bekannte Einspeiseverguetung: keine Untergrenze, Entladeschwelle = oberes Viertel', abs($thNF['discharge'] - $pC[(int)round(0.75 * 95)]) < 1e-9, json_encode($thNF));
// g) flacher Tarif: unveraendert keine Preissteuerung
$infoF = []; $thFl = call($ems, 'priceThresholdsCore', [array_fill(0, 96, 0.30), &$infoF]);
$lineF = call($ems, 'priceThresholdLine', ['charge', array_fill(0, 96, 0.30)]);
check('flacher Tarif: weiter keine Preissteuerung, Statuszeile sagt es', $thFl['charge'] >= 1e8 && $infoF['charge'] === 'auto_flat' && $lineF === 'ℹ️ Wirksam: keine Preissteuerung (flacher Preisverlauf).', json_encode([$thFl, $infoF]));
// h) Referenz Verguetung (Standard bei bekannter Verguetung): Ladegrenze = Verguetung x 0,95 - Verschleiss (~14,3 ct)
prop('ANL_Verguetung_ct', 18.36); prop('NETZLADUNG_Referenz', 0);
$info = []; $thV = call($ems, 'priceThresholdsCore', [$pW, &$info]);
check('Referenz Verguetung: Ladeschwelle auf Verguetung x 0,95 - Verschleiss (~14,3 ct) begrenzt', abs($thV['charge'] - (0.1836 * 0.95 - 0.0312)) < 0.001 && $info['charge'] === 'auto_econ_feed', json_encode([$thV, $info]));
// i) Statuszeile fuers Formular
prop('NETZLADUNG_Referenz', 1); prop('ANL_Verguetung_ct', 0.0);
$line = call($ems, 'priceThresholdLine', ['charge', $pW]);
check('Statuszeile nennt den wirksamen Wert in ct', is_string($line) && strpos($line, 'Wirksam:') !== false && strpos($line, 'ct/kWh') !== false, (string)$line);
prop('OPT_Arbitrage_Min_Spread_ct', 3.0);

echo "\n8k3) EMS_GetDayPlan 1.3: planAvailable und reason\n";
$ems = freshEms(); attr('DayPlan', '[]'); attr('DayPlanTomorrow', '[]');
prop('BAT_Active', false); prop('TIBBER_Active', false);
$gE = call($ems, 'GetDayPlan');
check('ohne Plan: planAvailable false, Vertrag 1.3, Grund nennt den ausgeschalteten Batteriespeicher', $gE['contractVersion'] === '1.3' && $gE['planAvailable'] === false && $gE['slots'] === [] && strpos($gE['reason'], 'Batteriespeicher') !== false, json_encode($gE['reason']));
prop('BAT_Active', true);
$gT = call($ems, 'GetDayPlan');
check('Batteriespeicher an, Preisoptimierung aus: Grund nennt die Preisoptimierung', $gT['planAvailable'] === false && strpos($gT['reason'], 'Preisoptimierung') !== false, json_encode($gT['reason']));
prop('TIBBER_Active', true);
$gP = call($ems, 'GetDayPlan');
check('beides an, aber keine Preise: Grund nennt fehlende Viertelstunden-Preise (nicht "EMS installieren")', $gP['planAvailable'] === false && (strpos($gP['reason'], 'Viertelstunden-Preise') !== false || strpos($gP['reason'], 'berechnet') !== false), json_encode($gP['reason']));
check('alle drei Gruende sind verschieden', count(array_unique([$gE['reason'], $gT['reason'], $gP['reason']])) === 3 || count(array_unique([$gE['reason'], $gT['reason']])) === 2, json_encode([$gE['reason'], $gT['reason'], $gP['reason']]));

echo "\n8k4) EMS_GetManualPlan / EMS_SetManualPlan (Vertrag manualplan 1.0)\n";
$ems = freshEms(); prop('PLAN_Manual', '[]');
$g0 = call($ems, 'GetManualPlan');
check('Get ohne Eintraege: Vertrag 1.0, leere Liste, drei Aktionen, Revision und maxEntries', $g0['contractVersion'] === '1.0' && $g0['entries'] === [] && count($g0['actions']) === 3 && is_string($g0['revision']) && $g0['maxEntries'] === 50, json_encode($g0));
$tmrDe = date('d.m.Y', strtotime('tomorrow')); $tmrIso = date('Y-m-d', strtotime('tomorrow'));
$r1 = call($ems, 'SetManualPlan', [json_encode(['revision' => $g0['revision'], 'entries' => [
    ['date' => $tmrDe, 'from' => '0:00', 'to' => '06:00', 'action' => EMS_OP_NET_CHARGE, 'power' => 20000],
    ['date' => '', 'from' => '22:00', 'to' => '', 'action' => EMS_OP_HOLD, 'power' => 0],
]])]);
check('Set mit gueltiger Liste: ok, keine Fehler, zwei Eintraege', $r1['ok'] === true && $r1['errors'] === [] && count($r1['plan']['entries']) === 2, json_encode($r1['errors']));
$stored = json_decode(live('PLAN_Manual'), true);
check('gespeichert wird normalisiert: Datum JJJJ-MM-TT, Zeit HH:MM, Aktion/Leistung als Zahl', $stored[0]['Date'] === $tmrIso && $stored[0]['From'] === '00:00' && $stored[0]['To'] === '06:00' && $stored[0]['Action'] === EMS_OP_NET_CHARGE && $stored[0]['Power'] === 20000 && $stored[1]['Date'] === '' && $stored[1]['To'] === '', json_encode($stored));
$g1 = call($ems, 'GetManualPlan');
check('Get danach: Status morgen bzw. jeden Tag, beide ok, Text vorhanden, neue Revision', $g1['entries'][0]['state'] === 'tomorrow' && $g1['entries'][1]['state'] === 'everyDay' && $g1['entries'][0]['ok'] && $g1['entries'][0]['text'] !== '' && $g1['revision'] !== $g0['revision'], json_encode($g1['entries']));
$before = live('PLAN_Manual');
$rOld = call($ems, 'SetManualPlan', [json_encode(['revision' => $g0['revision'], 'entries' => []])]);
check('veraltete Revision: abgelehnt, nichts geaendert', $rOld['ok'] === false && $rOld['errors'][0]['field'] === 'revision' && live('PLAN_Manual') === $before, json_encode($rOld['errors']));
$rBad = call($ems, 'SetManualPlan', [json_encode(['entries' => [
    ['date' => '21.10.2023', 'from' => '00:00', 'to' => '06:00', 'action' => EMS_OP_NET_CHARGE, 'power' => 1000],
    ['date' => '', 'from' => '25:00', 'to' => '06:00', 'action' => EMS_OP_NET_CHARGE, 'power' => 0],
    ['date' => '', 'from' => '10:00', 'to' => '09:00', 'action' => EMS_OP_HOLD, 'power' => 0],
    ['date' => '', 'from' => '10:00', 'to' => '11:00', 'action' => 5, 'power' => 0],
    ['date' => '', 'from' => '10:00', 'to' => '11:00', 'action' => EMS_OP_AUTO, 'power' => 200000],
    ['date' => 'irgendwann', 'from' => '10:00', 'to' => '11:00', 'action' => EMS_OP_AUTO, 'power' => 0],
]])]);
$fields = array_column($rBad['errors'], 'field');
// 0.89.1: Liste mit vergangenem + neuem gueltigem Eintrag wird gespeichert, der vergangene still entfernt (Befund Paul 25.10.2026)
$gR = call($ems, 'GetManualPlan', []); $keepPlan = live('PLAN_Manual');
$rMix = call($ems, 'SetManualPlan', [json_encode(['revision' => $gR['revision'], 'entries' => [
    ['date' => '21.10.2023', 'from' => '11:45', 'to' => '16:04', 'action' => EMS_OP_NET_CHARGE, 'power' => 18000],
    ['date' => '', 'from' => '10:00', 'to' => '11:00', 'action' => EMS_OP_HOLD, 'power' => 0],
]])]);
$savedMix = json_decode(live('PLAN_Manual'), true);
check('Set mit vergangenem + neuem Eintrag: gespeichert, vergangener entfernt und gemeldet', $rMix['ok'] === true && count($savedMix) === 1 && $savedMix[0]['From'] === '10:00' && count($rMix['removed']) === 1 && strpos($rMix['removed'][0]['message'], '21.10.2023') !== false, json_encode([$rMix['ok'], $rMix['errors'], $savedMix]));
attr('Live_PLAN_Manual', $keepPlan);
check('Set mit Fehlern: ok false, ein Fehler je Problem mit Pfad (Zeit, Ende, Aktion, Leistung, Datum unlesbar); vergangenes Datum ist kein Fehler, sondern entfernt', $rBad['ok'] === false && !in_array('entries[0].date', $fields, true) && ($rBad['removed'][0]['field'] ?? '') === 'entries[0].date' && in_array('entries[1].from', $fields, true) && in_array('entries[2].to', $fields, true) && in_array('entries[3].action', $fields, true) && in_array('entries[4].power', $fields, true) && in_array('entries[5].date', $fields, true), json_encode($fields));
check('bei Fehlern bleibt der gespeicherte Plan unveraendert', live('PLAN_Manual') === $before);
$rOv = call($ems, 'SetManualPlan', [json_encode(['entries' => [
    ['date' => $tmrIso, 'from' => '01:00', 'to' => '03:00', 'action' => EMS_OP_NET_CHARGE, 'power' => 0],
    ['date' => '', 'from' => '02:00', 'to' => '04:00', 'action' => EMS_OP_HOLD, 'power' => 0],
    ['date' => $tmrIso, 'from' => '03:00', 'to' => '05:00', 'action' => EMS_OP_HOLD, 'power' => 0],
]])]);
check('Ueberschneidung (auch "jeden Tag" gegen ein Datum) wird gemeldet, direkt aufeinanderfolgende Zeiten sind erlaubt', $rOv['ok'] === false && count($rOv['errors']) === 1 && $rOv['errors'][0]['field'] === 'entries[1]' && strpos($rOv['errors'][0]['message'], 'Überschneidet') !== false, json_encode($rOv['errors']));
$rMany = call($ems, 'SetManualPlan', [json_encode(['entries' => array_fill(0, 51, ['date' => '', 'from' => '10:00', 'to' => '11:00', 'action' => EMS_OP_AUTO, 'power' => 0])])]);
check('mehr als 50 Eintraege: abgelehnt', $rMany['ok'] === false && $rMany['errors'][0]['field'] === 'entries', json_encode(array_slice($rMany['errors'], 0, 1)));
$rJson = call($ems, 'SetManualPlan', ['kein json']);
check('ungueltiges JSON: ok false mit verstaendlichem Fehler, nichts geaendert', $rJson['ok'] === false && $rJson['errors'][0]['field'] === 'entries' && live('PLAN_Manual') === $before);
$gNow = call($ems, 'GetManualPlan');
$rGood = call($ems, 'SetManualPlan', [json_encode(['revision' => $gNow['revision'], 'entries' => []])]);
check('Set mit aktueller Revision und leerer Liste loescht alles', $rGood['ok'] === true && $rGood['plan']['entries'] === [] && live('PLAN_Manual') === '[]');
prop('PLAN_Manual', json_encode([['Date' => '21.10.2023', 'From' => '00:00', 'To' => '06:00', 'Action' => EMS_OP_NET_CHARGE, 'Power' => 20000]]));
$gPast = call($ems, 'GetManualPlan');
check('Get zeigt einen vergangenen Eintrag aus dem Formular als past/nicht ok mit Text', $gPast['entries'][0]['state'] === 'past' && $gPast['entries'][0]['ok'] === false && strpos($gPast['entries'][0]['text'], 'Vergangenheit') !== false, json_encode($gPast['entries'][0]));
prop('PLAN_Manual', '[]');

