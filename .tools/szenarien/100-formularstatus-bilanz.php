<?php
// Kapitel des EMS-Pruefstands: Formular-Statuszeilen, Sondereffekte, Fehlbetrag, KI-Assistent, Selbstbilanz.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8x) Formular zeigt, was gelernt/verbunden wurde\n";
$ems = freshEms(); prop('EMS_Max_Power_W', 34500);
$f0 = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Ohne InverterHub: Formular sagt, dass nichts gelernt wird und warum', strpos($f0, 'Es wird nichts gelernt, weil der Wechselrichter nicht über den InverterHub') !== false);
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB; $wr();
call($ems, 'learnChargeObserved', [90.0, 21.5]);
$f1 = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Mit InverterHub und gelernter Stufe: Formular nennt Stufe und kW', strpos($f1, 'Ladeleistung je Ladestand (gelernt') !== false && strpos($f1, '90–95 %: 21.5 kW') !== false, (preg_match('/[✅ℹ️] Ladeleistung je Ladestand[^"]{0,200}/u', $f1, $mm) ? $mm[0] : 'keine Zeile'));
unset($GLOBALS['INSTMOD'][IHUB_IID]);

echo "\n8y) Statuszeilen folgen der Auswahl im offenen Formular (onChange), nicht dem Speicherstand\n";
$ems = freshEms(); prop('BAT_Capacity_kWh', 40.0);
$GLOBALS['FORMFIELD'] = [];
$ems->CycleCostChanged(0.0, 9998.0, 8000);
check('Verschleisskosten: Eingabe im offenen Formular (Preis 9998, 8000 Zyklen) ergibt sofort 3,12 ct in der Zeile, obwohl nichts gespeichert ist',
    strpos((string)($GLOBALS['FORMFIELD']['CycleCostStatusLabel']['caption'] ?? ''), '3.12 ct/kWh') !== false, json_encode($GLOBALS['FORMFIELD']));
$ems->CycleCostChanged(4.5, 9998.0, 8000);
check('Direkt eingetragener Wert im Formular gilt sofort in der Zeile', strpos((string)$GLOBALS['FORMFIELD']['CycleCostStatusLabel']['caption'], '4.50 ct/kWh') !== false);
$GLOBALS['INSTMOD'][7301] = GUID_POWERPRICE; obj(7301, 1, 'Strompreis A', 0); $mdv = [];
$dd = strtotime('today'); for ($h = 0; $h < 24; $h++) { $mdv[] = ['start' => $dd + $h * 3600, 'end' => $dd + ($h + 1) * 3600, 'price' => 25.0]; }
vari('Marktdaten', 7301, 'MarketData', json_encode($mdv), 3);
$GLOBALS['INSTMOD'][7302] = GUID_POWERPRICE; obj(7302, 1, 'Strompreis B', 0); vari('Marktdaten', 7302, 'MarketData', '[]', 3);
$ems->PriceSourceChanged(7301);
check('Preisquelle: Auswahl der Instanz mit Daten im offenen Formular ergibt 🔗 mit Namen', strpos((string)($GLOBALS['FORMFIELD']['PT15MStatusLabel']['caption'] ?? ''), 'Strompreis A') !== false && strpos((string)$GLOBALS['FORMFIELD']['PT15MStatusLabel']['caption'], '🔗') !== false, json_encode($GLOBALS['FORMFIELD']['PT15MStatusLabel'] ?? null));
$ems->PriceSourceChanged(0);
check('Zwei Instanzen ohne Auswahl im Formular: Hinweis "bitte auswaehlen" statt raten', strpos((string)$GLOBALS['FORMFIELD']['PT15MStatusLabel']['caption'], 'Mehrere Symcon-Strompreis-Instanzen') !== false, (string)$GLOBALS['FORMFIELD']['PT15MStatusLabel']['caption']);
unset($GLOBALS['INSTMOD'][7301], $GLOBALS['INSTMOD'][7302]);
$fj = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Die Zeilen haben Namen, damit UpdateFormField sie erreicht', strpos($fj, 'PT15MStatusLabel') !== false && strpos($fj, 'CycleCostStatusLabel') !== false);

echo "\n8z) EMS_GetSpecialEvents 1.1: mehr Ereignistypen mit betroffener Groesse (affects)\n";
$ems = freshEms();
call($ems, 'trackSpecialEvents', [state(['grid_rewards' => true])]);
$ev = call($ems, 'GetSpecialEvents', [0, 0]);
check('Vertrag 1.1, Grid Rewards: Ereignis mit affects=load', $ev['contractVersion'] === '1.1' && count($ev['events']) === 1 && $ev['events'][0]['type'] === 'grid_rewards' && $ev['events'][0]['affects'] === ['load'], json_encode($ev));
attr('BatteryBoostUntil', time() + 600);
call($ems, 'trackSpecialEvents', [state(['grid_rewards' => true])]);
$types = array_column(call($ems, 'GetSpecialEvents', [0, 0])['events'], 'type');
check('Batterie-Boost wird als Ereignis (load) gefuehrt', in_array('boost', $types, true), json_encode($types));
$GLOBALS['INSTMOD'][300] = GUID_STEUERBOXHUB;
$GLOBALS['SBH_STATE'] = ['contractVersion' => '1.0', 'loadDimmActive' => false, 'feedInDimmActive' => true, 'feedInLimitPercent' => 30, 'loadPMin' => 4.2];
call($ems, 'trackSpecialEvents', [state(['grid_rewards' => true])]);
$evs = call($ems, 'GetSpecialEvents', [0, 0])['events']; $byType = array_column($evs, 'affects', 'type');
check('Netzbetreiber-Einspeisereduktion: Ereignis mit affects=pv (PV-Kalibrierung ueberspringt es)', ($byType['einspeisung_netzbetreiber'] ?? null) === ['pv'], json_encode($byType));
$GLOBALS['SBH_STATE'] = ['contractVersion' => '1.0', 'loadDimmActive' => true, 'feedInDimmActive' => false, 'feedInLimitPercent' => 100, 'loadPMin' => 4.2];
call($ems, 'trackSpecialEvents', [state(['grid_rewards' => false])]);
$evs = call($ems, 'GetSpecialEvents', [0, 0])['events'];
$open = array_filter($evs, fn($e) => $e['to'] === null); $openTypes = array_column($open, 'type');
check('§14a-Lastbegrenzung wird gefuehrt, beendete Ereignisse (Grid Rewards, Einspeisereduktion) haben ein Ende', in_array('lastbegrenzung_14a', $openTypes, true) && !in_array('grid_rewards', $openTypes, true) && !in_array('einspeisung_netzbetreiber', $openTypes, true), json_encode($evs));
call($ems, 'trackSpecialEvents', [[]]);
$open2 = array_column(array_filter(call($ems, 'GetSpecialEvents', [0, 0])['events'], fn($e) => $e['to'] === null), 'type');
check('Aufruf ohne Zustand (EMS aus) beendet keine offenen Grid-Rewards-Ereignisse und schreibt die anderen Typen weiter', in_array('lastbegrenzung_14a', $open2, true));
$GLOBALS['SBH_STATE'] = null; unset($GLOBALS['INSTMOD'][300]);

echo "   Preis-Sperre der Heizungs-Automatik (HeishaMon, Quelle 'heisha-price-block', 18.10.2026)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][7201] = GUID_HEISHAMON; obj(7201, 1, 'HeishaMon', 0);
attr('PartnerCache', json_encode(['heishamon' => [['instanceID' => 7201]]]));
$typesNo = array_column(call($ems, 'GetSpecialEvents', [0, 0])['events'], 'type');
check('Ohne HEISHA_GetHeatingAutomation (aeltere HeishaMon-Version): keine Sperr-Ereignisse, nichts bricht', !in_array('heisha-price-block', $typesNo, true));
$GLOBALS['HEISHA_HA'] = null;
if (!function_exists('HEISHA_GetHeatingAutomation')) { function HEISHA_GetHeatingAutomation($iid) { if ($GLOBALS['HEISHA_HA'] === 'throw') { throw new Exception('boom'); } return $GLOBALS['HEISHA_HA']; } }
$now = time();
$GLOBALS['HEISHA_HA'] = ['price' => ['blockWindows' => [['from' => $now - 7200, 'to' => $now - 5400], ['from' => $now - 600, 'to' => 0]]]];
$evs = array_values(array_filter(call($ems, 'GetSpecialEvents', [0, 0])['events'], fn($e) => $e['type'] === 'heisha-price-block'));
check('Zwei Sperrfenster werden als Sondereffekt (affects=load) uebernommen, das laufende hat to=null', count($evs) === 2 && $evs[0]['affects'] === ['load'] && $evs[0]['to'] === $now - 5400 && $evs[1]['to'] === null && strpos($evs[0]['reason'], 'Preis-Sperre') !== false, json_encode($evs));
$evs2 = array_values(array_filter(call($ems, 'GetSpecialEvents', [$now - 3600, 0])['events'], fn($e) => $e['type'] === 'heisha-price-block'));
check('Zeitfilter: ab $now-3600 faellt das beendete Fenster weg, das laufende bleibt', count($evs2) === 1 && $evs2[0]['to'] === null, json_encode($evs2));
$GLOBALS['HEISHA_HA'] = ['price' => ['blockWindows' => [['from' => 0, 'to' => 0]]]];
check('Fenster ohne Beginn (from=0) wird ignoriert', count(array_filter(call($ems, 'GetSpecialEvents', [0, 0])['events'], fn($e) => $e['type'] === 'heisha-price-block')) === 0);
$GLOBALS['HEISHA_HA'] = 'throw';
check('Wirft die Gegenseite eine Ausnahme: ignoriert, die uebrigen Ereignisse bleiben', is_array(call($ems, 'GetSpecialEvents', [0, 0])['events']));
$GLOBALS['HEISHA_HA'] = null; unset($GLOBALS['INSTMOD'][7201]); attr('PartnerCache', '{}');

echo "\n8ab) Wert kommt automatisch: Eingabefeld wird ausgeblendet, Zeile zeigt 🔗 (eigene Angabe bleibt sichtbar)\n";
$findItem = function ($node, $name) use (&$findItem) {
    if (is_array($node)) { if (($node['name'] ?? null) === $name && isset($node['type'])) { return $node; } foreach ($node as $v) { $r = $findItem($v, $name); if ($r !== null) { return $r; } } }
    return null;
};
$ems = freshEms(); prop('BAT_Active', true);
$f = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$soc = $findItem($f, 'VAR_BAT1_SOC');
check('Ohne InverterHub: Batterie-SOC-Feld bleibt sichtbar (wird gebraucht)', $soc !== null && ($soc['visible'] ?? true) !== false);
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB; $socV = vari('SOC', IHUB_IID, 'soc', 60.0); $wr(['socID' => $socV]);
$f = json_decode(emsFormDe($ems->GetConfigurationForm()), true); $soc = $findItem($f, 'VAR_BAT1_SOC'); $txt = json_encode($f, JSON_UNESCAPED_UNICODE);
check('Mit InverterHub: Batterie-SOC-Feld ausgeblendet, Zeile "🔗 Automatisch übernommen"', $soc !== null && ($soc['visible'] ?? true) === false && strpos($txt, '🔗 Automatisch übernommen: InverterHub') !== false, json_encode($soc));
check('Der Satz "Felder unten werden ignoriert" ist weg (das Feld ist ja nicht mehr da)', strpos($txt, 'Felder unten werden ignoriert') === false || strpos($txt, '🔗 Automatisch übernommen: InverterHub #') !== false);
$man = vari('eigener SOC', 0, '', 55.0); prop('VAR_BAT1_SOC', $man);
$f = json_decode(emsFormDe($ems->GetConfigurationForm()), true); $soc = $findItem($f, 'VAR_BAT1_SOC');
$greenOk = false; $walk = function ($n) use (&$walk, &$greenOk) { if (is_array($n)) { if (($n['type'] ?? '') === 'Label' && strpos((string)($n['caption'] ?? ''), '🔗 Automatisch übernommen') === 0 && ($n['color'] ?? null) === EMS_COLOR_AUTO) { $greenOk = true; } foreach ($n as $v) { $walk($v); } } };
$walk($f);
check('🔗-Zeile ist grün (color gesetzt)', $greenOk);
check('Eigene Angabe im Feld: Feld bleibt sichtbar, die eigene Angabe hat Vorrang', $soc !== null && ($soc['visible'] ?? true) !== false);
prop('VAR_BAT1_SOC', 0);
$prices = $findItem(json_decode(emsFormDe($ems->GetConfigurationForm()), true), 'VAR_TIB_PT15M_Today');
check('Ohne Preisquelle: Preisfeld sichtbar', $prices !== null && ($prices['visible'] ?? true) !== false);
unset($GLOBALS['INSTMOD'][IHUB_IID]);

echo "\n8ac) Formular zeigt die Verbindung zum Boersenpreis-Modul (Negativpreis-Pflicht)\n";
$ems = freshEms(); prop('EMS_Risk_Acknowledged', true); $GLOBALS['SPOT_CURVE'] = [];
$fx = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Ohne Boersenpreis-Quelle: ℹ️ "Keine Börsenpreis-Quelle gefunden" im Formular', strpos($fx, 'Keine Börsenpreis-Quelle gefunden') !== false && strpos($fx, 'SpotStatusLabel') !== false, 'Label: ' . (strpos($fx, 'SpotStatusLabel') !== false ? 'da' : 'fehlt') . ', Text: ' . (preg_match('/Börsenpreis[^"]{0,80}/u', $fx, $mx) ? $mx[0] : '-'));
$GLOBALS['INSTMOD'][7401] = GUID_SPOTPRICE; obj(7401, 1, 'Börsenpreis Test', 0);
$dd = strtotime('today'); $nqS = (int)((strtotime('tomorrow') - $dd) / 900); // 92/96/100 je nach Zeitumstellung
$curveS = []; for ($i = 0; $i < $nqS; $i++) { $curveS[] = ['start' => $dd + $i * 900, 'end' => $dd + ($i + 1) * 900, 'price' => 5.0, 'aufloesung' => 900]; }
$GLOBALS['SPOT_CURVE'] = $curveS;
$fx = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Mit Boersenpreis-Modul: ✅ mit Instanz, Name, Umfang und aktuellem Wert', strpos($fx, '✅ Börsenpreis #7401') !== false && strpos($fx, 'Börsenpreis Test') !== false && strpos($fx, $nqS . ' Viertelstunden') !== false && strpos($fx, 'aktuell 5.00 ct') !== false, (preg_match('/[✅⚠️ℹ️] ?Börsenpreis[^"]{0,200}/u', $fx, $mm) ? $mm[0] : 'keine Zeile'));
$GLOBALS['INSTMOD'][7402] = GUID_SPOTPRICE; obj(7402, 1, 'Zweite', 0);
$fx = json_encode(json_decode(emsFormDe($ems->GetConfigurationForm()), true), JSON_UNESCAPED_UNICODE);
check('Zwei Boersenpreis-Instanzen: ⚠️ (EMS nutzt die erste) statt still zu raten', strpos($fx, '⚠️ Mehrere Börsenpreis-Instanzen gefunden') !== false);
unset($GLOBALS['INSTMOD'][7401], $GLOBALS['INSTMOD'][7402]); $GLOBALS['SPOT_CURVE'] = [];

echo "\n8ad) Fehlbetrag guenstiger aus dem Netz als aus der Batterie (Dietmar 27.09.2026)\n";
$ems = freshEms();
prop('BAT_Capacity_kWh', 40.0); prop('BAT_Price_EUR', 9998.0); prop('BAT_Cycles', 8000); // -> 3.124375 ct/kWh Zykluskosten
$cc = call($ems, 'cycleCostCt');
check('Zykluskosten wie in 8v: 3,12 ct/kWh', abs($cc - 3.124375) < 1e-6, (string)$cc);
$ctxFB = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 400.0, 'houseLoadSlots' => [10940.0],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 25.0, 'dischargeKw' => 25.0, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03, 'restwert' => false];
// Preis 18ct: mit 5% Abstand (18,9ct) noch unter den 3,12ct? Nein -- Zykluskosten sind ct/kWh, nicht ct*Faktor,
// der Vergleich ist price*100*1.05 < cycleCostCt() in ct/kWh, bei 3,12ct greift die Regel nur unter ca. 2,98ct Bezug.
// Realistisches Beispiel mit sehr guenstigem Nachtpreis (Vergleich zu 8n/8t-Faellen):
$rCheap = call($ems, 'simulateDaySlot', [10, 0.0280, 4979.0, 50.0, [], $ctxFB, 0.0]); // 2,80ct: *1.05=2,94ct < 3,12ct Zykluskosten
check('Guenstiger Bezug (2,80ct) unter Zykluskosten+5%: Batterie laedt exklusiv aus PV, kein Akku->Haus', $rCheap['plan']['op'] === EMS_OP_PV_SELFUSE && $rCheap['plan']['gw'] === GW_MODE_CHARGE_PV, json_encode($rCheap['plan']));
check('SOC steigt trotz Fehlbetrag (laedt aus PV, nicht aus dem Fehlbetrag betroffen)', $rCheap['soc'] > 50.0, (string)$rCheap['soc']);
$rExpensive = call($ems, 'simulateDaySlot', [10, 0.1800, 4979.0, 50.0, [], $ctxFB, 0.0]); // 18ct: *1.05=18,9ct > 3,12ct -- Regel greift NICHT
check('Bezug (18ct) ueber Zykluskosten+5%: alte Automatik/Entladen-Logik unveraendert (nicht PV_SELFUSE mit Xmax=0 fuer den Fehlbetrag)', !($rExpensive['plan']['op'] === EMS_OP_PV_SELFUSE && $rExpensive['plan']['gw'] === GW_MODE_CHARGE_PV && strpos($rExpensive['plan']['reason'], 'Zykluskosten') !== false), json_encode($rExpensive['plan']));
prop('BAT_Cycles', 0); prop('BAT_Price_EUR', 0.0); // Zykluskosten unbekannt -> Regel greift nie
check('Ohne Zykluskosten-Angabe: Regel greift nicht, auch bei sehr guenstigem Preis', abs(call($ems, 'cycleCostCt')) < 1e-9);
$rNoCost = call($ems, 'simulateDaySlot', [10, 0.0100, 4979.0, 50.0, [], $ctxFB, 0.0]);
check('Ohne Zykluskosten kein "Zykluskosten"-Grund im Ergebnis dieses Zweigs', strpos($rNoCost['plan']['reason'] ?? '', 'Zykluskosten <') === false);
// Live-Sicherheitsnetz in applyPlanSlot(): dieselbe Regel muss auch ohne realen PV-Ueberschuss aktiv bleiben duerfen.
prop('BAT_Capacity_kWh', 40.0); prop('BAT_Price_EUR', 9998.0); prop('BAT_Cycles', 8000);
$sFB = ['bat_active' => true, 'bat_soc' => 50.0, 'wb_active' => false, 'wb_count' => 0, 'wb1_cable' => 0, 'wb1_error' => 0, 'wb2_cable' => 0, 'wb2_error' => 0,
    'tib_price_eff' => 0.0280, 'pv_total_w' => 4979.0, 'house_pow_w' => 10940.0, 'wb1_pow_kw' => 0.0, 'wb2_pow_kw' => 0.0];
$GLOBALS['ATTR'][EMS_IID]['DayPlan'] = json_encode(array_fill(0, 96, ['op' => EMS_OP_PV_SELFUSE, 'gw' => GW_MODE_CHARGE_PV, 'power' => 0, 'reason' => 'Bezug 2.80ct + 5% Abstand < Zykluskosten 3.12ct: Batterie lädt exklusiv aus PV', 'price' => 0.028, 'soc' => 50.0]));
$applied = call($ems, 'applyPlanSlot', [$sFB]);
check('Live-Sicherheitsnetz laesst Xmax=0 auch ohne realen PV-Ueberschuss (10940W Last > 4979W PV) zu, wenn guenstig genug', $applied !== null && $applied['gw_mode'] === GW_MODE_CHARGE_PV && $applied['gw_enable'] === true, json_encode($applied));

// ===========================================================================
echo "\n8ae) PV-Ueberschuss in den Akku ueber die Automatik, nicht Laden-Solar (Dietmar 02.11.2026)\n";
$ems = freshEms();
prop('BAT_Capacity_kWh', 40.0); prop('BAT_Price_EUR', 9998.0); prop('BAT_Cycles', 8000);
$ctxPv = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 2700.0, 'houseLoadSlots' => [2700.0],
    'fcMinPower' => 100.0, 'socTargetDay' => 86.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 25.0, 'dischargeKw' => 25.0, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03, 'restwert' => false];
$rPv = call($ems, 'simulateDaySlot', [56, 0.4270, 3400.0, 60.0, [], $ctxPv, 0.0]); // Live 02.11.: PV 3,4 kW, Haus 2,7 kW, 42,7 ct
check('PV-Ueberschuss unter Tagesziel bei 42,7 ct: Automatik, nicht Laden-Solar (Haus zuerst aus PV)', $rPv['plan']['op'] === EMS_OP_AUTO && $rPv['plan']['gw'] === GW_MODE_AUTO, json_encode($rPv['plan']));
check('Simulierter SOC steigt trotzdem (Ueberschuss geht in den Akku)', $rPv['soc'] > 60.0, (string)$rPv['soc']);

echo "\n8af) KI-Assistent: erklaert nur, Schluessel im Attribut, Anfrage/Antwort (0.95.0, Dietmar 04.11.2026)\n";
$ems = freshEms();
check('KI aus: Ask antwortet ohne Netzzugriff mit Hinweis', strpos(call($ems, 'Ask', ['x']), 'ist aus') !== false);
prop('AI_Provider', 1);
check('Claude ohne Schluessel: Hinweis statt Anfrage', strpos(call($ems, 'Ask', ['x']), 'Schlüssel') !== false);
call($ems, 'SetAiKey', ['  sk-test  ']);
check('Schluessel landet getrimmt im Attribut, nicht in einer Property', ($GLOBALS['ATTR'][EMS_IID]['AI_ApiKey'] ?? '') === 'sk-test' && !isset($GLOBALS['PROP'][EMS_IID]['AI_ApiKey']));
$req = call($ems, 'buildAiRequest', [1, 'Warum?', '{"a":1}']);
$body = json_decode($req['body'], true);
check('Claude-Anfrage: Endpunkt, Version, Schluessel-Header, Standardmodell', $req['url'] === 'https://api.anthropic.com/v1/messages' && in_array('anthropic-version: 2023-06-01', $req['headers'], true) && in_array('x-api-key: sk-test', $req['headers'], true) && $body['model'] === EMS_AI_DEFAULT_CLAUDE, $req['body']);
check('Claude-Anfrage: kein temperature, Kontext und Frage in der Nutzernachricht', !isset($body['temperature']) && strpos($body['messages'][0]['content'], '{"a":1}') !== false && strpos($body['messages'][0]['content'], 'Warum?') !== false);
prop('AI_Model', 'claude-haiku-5-5');
check('Eigenes Modell wird verwendet', json_decode(call($ems, 'buildAiRequest', [1, 'q', '{}'])['body'], true)['model'] === 'claude-haiku-5-5');
prop('AI_Model', ''); prop('AI_OllamaUrl', 'http://nas:11434/');
$o = call($ems, 'buildAiRequest', [2, 'q', '{}']);
check('Ollama: lokale Adresse ohne doppelten Schraegstrich, kein Schluessel im Header', $o['url'] === 'http://nas:11434/api/chat' && strpos(implode('|', $o['headers']), 'sk-test') === false, $o['url']);
check('Antwort Claude: Textbloecke werden zusammengesetzt, Denkbloecke ignoriert', call($ems, 'parseAiResponse', [1, 200, '{"stop_reason":"end_turn","content":[{"type":"thinking","thinking":""},{"type":"text","text":"Akku lädt."}]}']) === 'Akku lädt.');
check('Antwort Claude: Ablehnung wird gemeldet', strpos(call($ems, 'parseAiResponse', [1, 200, '{"stop_reason":"refusal","content":[]}']), 'abgelehnt') !== false);
check('Antwort: 401 meldet falschen Schluessel', strpos(call($ems, 'parseAiResponse', [1, 401, '{"error":{"message":"invalid x-api-key"}}']), '401') !== false);
check('Antwort Ollama: message.content', call($ems, 'parseAiResponse', [2, 200, '{"message":{"content":" Hallo "}}']) === 'Hallo');
$ctx = json_decode(call($ems, 'GetExplainContext'), true);
check('Kontext enthaelt keinen Schluessel und die Vorzeichen-Erklaerung', strpos(json_encode($ctx), 'sk-test') === false && isset($ctx['hinweis_vorzeichen'], $ctx['anlage'], $ctx['plan_naechste_6h']), json_encode($ctx));

echo "\n8ag) Selbstbilanz: Ist gegen reine WR-Automatik (0.96.0, Dietmar 07.11.2026)\n";
$ems = freshEms();
$par = ['eta' => 0.95, 'capKwh' => 40.0, 'socMinPct' => 10.0, 'feed' => 0.1836, 'chKwh' => 6.0, 'disKwh' => 6.0];
// 4 Viertelstunden: Haus 4 kW, keine PV. Ist: Netzladen 8 kW (Netz -12 kW) in Slot 0 bei 10 ct, dann Akku deckt bei 50 ct.
$r = call($ems, 'selfBalanceCompute', [[0,0,0,0], [-8000, 4000, 4000, 0], [-12000, 0, 0, -4000], [0.10, 0.50, 0.50, 0.50], 50.0, $par]);
check('Ist = gemessener Bezug x Preis (3 kWh x 0,10 + 1 kWh x 0,50 = 0,80 EUR)', abs($r['ist'] - 0.80) < 0.01, json_encode($r));
check('Automatik deckt das Haus aus dem Akku (50 % von 40 kWh reichen), Kosten 0', abs($r['auto']) < 0.01, json_encode($r));
check('Bezug in kWh wird mitgezaehlt', abs($r['imp'] - 4.0) < 0.01, json_encode($r));
// Akku leer (Start 10 %): Automatik muss alles kaufen
$r2 = call($ems, 'selfBalanceCompute', [[0,0], [0,0], [-4000,-4000], [0.40, 0.60], 10.0, $par]);
check('Leerer Akku: Automatik kauft wie Ist (1x0,40 + 1x0,60 = 1,00)', abs($r2['auto'] - 1.00) < 0.01 && abs($r2['ist'] - 1.00) < 0.01, json_encode($r2));
// PV-Ueberschuss: Automatik speichert ohne Wandlungsverlust, Ist hat eingespeist
$r3 = call($ems, 'selfBalanceCompute', [[8000, 0], [0, 0], [4000, -4000], [0.30, 0.50], 10.0, $par]);
check('Ueberschuss wird in der Automatik gespeichert und spaeter genutzt (guenstiger als Ist)', $r3['auto'] < $r3['ist'], json_encode($r3));
// 0.99.3: PV -> Akku traegt in der Automatik denselben Wandlungsverlust wie das Entladen
$r4 = call($ems, 'selfBalanceCompute', [[8000, 0], [0, 0], [4000, -4000], [0.30, 0.50], 10.0, $par]);
// Slot 0: 1 kWh Ueberschuss -> 0,95 kWh im Akku; Slot 1: 1 kWh Bedarf -> Akku liefert 0,95 x 0,95 = 0,9025, Rest 0,0975 kWh x 0,50
check('Automatik zahlt den PV->Akku-Verlust (Rest 0,0975 kWh x 0,50 = 0,05 EUR statt 0,025)', abs($r4['auto'] - 0.04875) < 0.006, json_encode($r4));
check('Ladestand der Automatik: 4 kWh + 0,95 - 0,95 = 4 kWh (10 %)', abs($r4['endSocAuto'] - 10.0) < 0.1, json_encode($r4));
$r5 = call($ems, 'selfBalanceCompute', [[8000], [0], [4000], [0.30], 99.0, $par]);
check('Grenzfall voller Akku: Automatik speist den Rest ein, ueberlaedt nicht', $r5['endSocAuto'] <= 100.0 && $r5['auto'] < 0, json_encode($r5));
check('Ist rechnet Einspeisung mit Verguetung (1 kWh x 0,1836 Gutschrift)', abs($r3['ist'] - (0.50 - 0.1836)) < 0.01, json_encode($r3));
check('Summe ueber Tage: nur Tage mit Werten, hoechstens N', call($ems, 'selfBalanceSum', [['2026-11-02' => ['ist' => 2, 'auto' => 1], '2026-11-01' => ['error' => 'x'], '2026-10-31' => ['ist' => 3, 'auto' => 5]], 30]) === ['ist' => 5.0, 'auto' => 6.0, 'days' => 2, 'fair' => 0.0, 'fairDays' => 0, 'reward' => 0.0]);

echo "\n8ah) Simulation ueber dem Tagesziel + gelernte Prognose-Korrektur (0.96.1, Dietmar 07.11.2026)\n";
$ems = freshEms();
$ctxT = ['enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 1000.0, 'houseLoadSlots' => [1000.0],
    'fcMinPower' => 100.0, 'socTargetDay' => 50.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0,
    'socTargetNight' => 100.0, 'capKwh' => 40.0, 'chargeKw' => 25.0, 'dischargeKw' => 25.0, 'maxW' => 34500.0,
    'feedTariff' => 0.1836, 'thCharge' => 0.15, 'thDischarge' => 0.25, 'spread' => 0.03, 'restwert' => false];
$rT = call($ems, 'simulateDaySlot', [48, 0.44, 5000.0, 65.0, [], $ctxT, 0.0]); // SOC 65 % ueber Tagesziel 50 %, 4 kW Ueberschuss
check('Ueber dem Tagesziel: Automatik laedt den Ueberschuss weiter (SOC steigt um 1 kWh = 2,5 %)', $rT['plan']['gw'] === GW_MODE_AUTO && abs($rT['soc'] - 67.5) < 0.05, json_encode($rT));
$rF = call($ems, 'simulateDaySlot', [48, 0.44, 5000.0, 100.0, [], $ctxT, 0.0]);
check('Voller Akku: Ueberschuss wird eingespeist, SOC bleibt 100', abs($rF['soc'] - 100.0) < 0.01 && strpos($rF['plan']['reason'], 'eingespeist') !== false, json_encode($rF));
$f0 = call($ems, 'forecastFactors');
check('Ohne Lerndaten: PV x1,0 (p10 ist schon vorsichtig, 0.99.3), Last x1,15', $f0['pv'] == 1.0 && $f0['load'] == 1.15 && $f0['learned'] === false, json_encode($f0));
$GLOBALS['ATTR'][EMS_IID]['FcLearn'] = json_encode([
  '2026-11-06' => ['pvFc' => 10, 'loadFc' => 30, 'pvAct' => 4, 'loadAct' => 36],
  '2026-11-05' => ['pvFc' => 10, 'loadFc' => 30, 'pvAct' => 5, 'loadAct' => 33],
  '2026-11-04' => ['pvFc' => 10, 'loadFc' => 30, 'pvAct' => 6, 'loadAct' => 36],
  '2026-11-07' => ['pvFc' => 10, 'loadFc' => 30]]);
$f1 = call($ems, 'forecastFactors');
check('Gelernt aus 3 Tagen: Last 105/90 = 1,167, PV-Faktor erst ab 7 Tagen (bleibt 1,0)', $f1['learned'] === true && abs($f1['pv'] - 1.0) < 0.001 && abs($f1['load'] - 1.167) < 0.001 && $f1['days'] === 3, json_encode($f1));
$fl7 = [];
for ($dd = 1; $dd <= 7; $dd++) { $fl7[sprintf('2026-10-%02d', $dd)] = ['pvFc' => 10, 'loadFc' => 30, 'pvAct' => 5, 'loadAct' => 30]; }
$fcKeep = $GLOBALS['ATTR'][EMS_IID]['FcLearn'];
$GLOBALS['ATTR'][EMS_IID]['FcLearn'] = json_encode($fl7);
$f7 = call($ems, 'forecastFactors');
check('Ab 7 gelernten Tagen wirkt der PV-Faktor (35/70 = 0,5)', abs($f7['pv'] - 0.5) < 0.001 && $f7['days'] === 7, json_encode($f7));
$GLOBALS['ATTR'][EMS_IID]['FcLearn'] = $fcKeep;
call($ems, 'forecastLearnActual', ['2026-11-07', 3.0, 40.0]);
$fl = json_decode($GLOBALS['ATTR'][EMS_IID]['FcLearn'], true);
check('Ist-Werte werden zum Tag mit Prognose eingetragen', $fl['2026-11-07']['pvAct'] == 3.0 && $fl['2026-11-07']['loadAct'] == 40.0);
$GLOBALS['ATTR'][EMS_IID]['FcLearn'] = '{}';
call($ems, 'forecastLearnRecord', [array_fill(0, 192, 2000.0), array_fill(0, 192, 1000.0)]);
$fr = json_decode($GLOBALS['ATTR'][EMS_IID]['FcLearn'], true); $today = date('Y-m-d');
check('Prognose des Tages wird beim ersten Plan festgehalten (PV 48 kWh, Last 24 kWh)', isset($fr[$today]) && $fr[$today]['pvFc'] == 48.0 && $fr[$today]['loadFc'] == 24.0, json_encode($fr));
call($ems, 'forecastLearnRecord', [array_fill(0, 192, 500.0), array_fill(0, 192, 500.0)]);
$fr2 = json_decode($GLOBALS['ATTR'][EMS_IID]['FcLearn'], true);
check('Spaetere Plaene ueberschreiben die Tagesprognose nicht', $fr2[$today]['pvFc'] == 48.0);

echo "\n8ai) Selbstbilanz: wohin ging das Geld (0.97.0)\n";
$ems = freshEms();
$par = ['eta' => 0.95, 'capKwh' => 40.0, 'socMinPct' => 10.0, 'feed' => 0.1836, 'chKwh' => 6.0, 'disKwh' => 6.0];
// Slot0: Netzladen 8 kW bei 30 ct; Slot1: Halten (Akku 0 W, Haus 2 kW aus Netz) bei 40 ct; Slot2: PV 6 kW, Haus 1 kW, Akku 0 W, 5 kW Einspeisung bei SOC 60 %; Slot3: Akku entlaedt 4 kW, Haus 1 kW, 3 kW ins Netz
$r = call($ems, 'selfBalanceCompute', [[0, 0, 6000, 0], [-8000, 0, 0, 4000], [-8000, -2000, 5000, 3000], [0.30, 0.40, 0.40, 0.50], 50.0, $par, [50, 70, 60, 60]]);
$c = $r['cat'];
check('Netzladen erkannt: 2 kWh zu 0,60 EUR', abs($c['gridChargeKwh'] - 2.0) < 0.01 && abs($c['gridChargeEur'] - 0.60) < 0.01, json_encode($c));
check('Halten erkannt: 0,5 kWh zu 0,20 EUR', abs($c['holdKwh'] - 0.5) < 0.01 && abs($c['holdEur'] - 0.20) < 0.01, json_encode($c));
check('PV-Einspeisung trotz Platz (SOC 60 %): 1,25 kWh, Verlust geschaetzt > 0', abs($c['pvExportKwh'] - 1.25) < 0.01 && $c['pvExportLossEur'] > 0, json_encode($c));
check('Akku ins Netz: 0,75 kWh', abs($c['batExportKwh'] - 0.75) < 0.01, json_encode($c));
check('Klartext nennt alle vier Posten', substr_count(call($ems, 'selfBalanceCatText', [$c]), ' · ') === 3, call($ems, 'selfBalanceCatText', [$c]));
$r2 = call($ems, 'selfBalanceCompute', [[0], [0], [-2000], [0.40], 50.0, $par]);
check('Ohne Ladestand-Reihe kein Halten-Befund (keine Annahme)', $r2['cat']['holdKwh'] == 0.0, json_encode($r2['cat']));

echo "\n8aj) Faire Tagesbewertung mit Akkuenergie (0.97.1)\n";
$ems = freshEms();
$par = ['eta' => 0.95, 'capKwh' => 40.0, 'socMinPct' => 0.0, 'feed' => 0.1836, 'chKwh' => 6.0, 'disKwh' => 6.0];
// 2 Slots je 0,40 EUR, Haus 4 kW, keine PV. Ist: Akku schont (0 W), Haus aus Netz (2 kWh = 0,80 EUR), SOC bleibt 50.
// Automatik: Akku deckt das Haus (2 kWh / 0,95 = 2,105 kWh aus dem Akku), Kosten 0, Akku leerer.
$r = call($ems, 'selfBalanceCompute', [[0, 0], [0, 0], [-4000, -4000], [0.40, 0.40], 50.0, $par, [50, 50]]);
check('Ohne Bewertung sieht Schonen wie -0,80 EUR aus', abs(($r['auto'] - $r['ist']) + 0.80) < 0.01, json_encode($r));
check('Mit Bewertung der Akkuenergie (2,105 kWh x 0,38 EUR) nahezu ausgeglichen', abs($r['fair']) < 0.02, json_encode($r));
$r2 = call($ems, 'selfBalanceCompute', [[0], [0], [-4000], [0.40], 50.0, $par]);
check('Ohne Ladestand-Reihe keine faire Bewertung (null statt Annahme)', $r2['fair'] === null, json_encode($r2));
check('Summe nutzt die faire Bewertung, wenn vorhanden', call($ems, 'selfBalanceSum', [['a' => ['ist' => 2, 'auto' => 1, 'fair' => 0.5], 'b' => ['ist' => 1, 'auto' => 1]], 30])['fair'] == 0.5);

echo "\n8ak) Grid-Rewards-Verguetung in der Bilanz (0.97.2)\n";
$ems = freshEms();
check('Summe zaehlt die Grid-Rewards-Verguetung je Tag zusammen', abs(call($ems, 'selfBalanceSum', [['a' => ['ist' => 1, 'auto' => 1, 'reward' => 0.25], 'b' => ['ist' => 1, 'auto' => 1, 'reward' => 0.5], 'c' => ['ist' => 1, 'auto' => 1]], 30])['reward'] - 0.75) < 0.001);
check('Ohne Tibber-Instanz liefert die Verguetung null (keine Annahme)', call($ems, 'gridRewardEarnedEur', [1, strtotime('today'), strtotime('tomorrow')]) === null);

