<?php
// Kapitel des EMS-Pruefstands: Solis: Zwangsladen, Halten, Netzlade-Erlaubnis, Bestandsaufnahme.
// Wird von .tools/test-scenarios.php der Reihe nach im selben Gueltigkeitsbereich geladen (spaetere Kapitel nutzen
// Werte frueherer); einzeln nicht lauffaehig.

echo "\n8k5) Solis: minimale Steuerung (Zwangsladen und Automatik, Totmann-Nachfuehrung)\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
foreach (array_keys($GLOBALS['OBJ']) as $oid) { if (($GLOBALS['OBJ'][$oid]['ParentID'] ?? -1) === IHUB_IID && strpos($GLOBALS['OBJ'][$oid]['ObjectIdent'], 'ctl_ems') === 0) { $GLOBALS['OBJ'][$oid]['ObjectIdent'] = 'x_' . $GLOBALS['OBJ'][$oid]['ObjectIdent']; } }
$GLOBALS['OBJ'][IHUB_IID]['HasChildren'] = true;
vari('SOC', IHUB_IID, 'soc', 50, 1);
$vForce = vari('Zwangsladen/-entladen', IHUB_IID, 'ctl_force_mode', 0, 1);
vari('Zwangsleistung', IHUB_IID, 'ctl_force_w', 0, 1);
$vDev = vari('Zwangsmodus (Einstellung)', IHUB_IID, 'cfg_force_mode', 0, 1);
$vTo = vari('Zeitlimit', IHUB_IID, 'cfg_rc_timeout_min', 3, 1);
vari('Max. Ladeleistung', IHUB_IID, 'bat_charge_max_w', 16900.0, 2);
check('Solis wird erkannt (Zwangsmodus-Punkte, keine ctl_ems_*)', call($ems, 'isSolisDriver', [IHUB_IID]) === true);
check('Wiederholabstand bei 3 min Zeitlimit: 60 s', call($ems, 'solisRewriteIntervalSec', [IHUB_IID]) === 60);
$GLOBALS['VAL'][$vTo] = 1; check('Zeitlimit 1 min: 30 s', call($ems, 'solisRewriteIntervalSec', [IHUB_IID]) === 30);
$GLOBALS['VAL'][$vTo] = 0; check('Zeitlimit 0 oder unlesbar: 60 s', call($ems, 'solisRewriteIntervalSec', [IHUB_IID]) === 60);
$GLOBALS['VAL'][$vTo] = 20; check('Zeitlimit 20 min: nie seltener als alle 60 s', call($ems, 'solisRewriteIntervalSec', [IHUB_IID]) === 60);
$GLOBALS['VAL'][$vTo] = 3;
// Netzladen 12 kW
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Netzladen 12 kW: erst Leistung, dann Zwangsmodus 1 (keine ctl_ems_*-Schreibzugriffe)', array_map(fn($a) => [$a[1], $a[2]], $GLOBALS['ACTIONS']) === [['ctl_force_w', 12000], ['ctl_force_mode', 1]], json_encode($GLOBALS['ACTIONS']));
check('Solis steuerbar: kein "Nur beobachtend"', $ems->ReadAttributeString('NoControlReason') === '' || call($ems, 'statusText', ['x']) === 'OK: x');
// Leistung ueber der gemeldeten Ladegrenze wird begrenzt (16,9 kW), auch in der Anzeige gerundet
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 24000, true]);
check('Sollleistung 24 kW wird auf die Ladegrenze des Wechselrichters (16,9 kW) begrenzt', end($GLOBALS['ACTIONS'])[1] === 'ctl_force_mode' && $GLOBALS['ACTIONS'][0][2] === 16900, json_encode($GLOBALS['ACTIONS']));
// Wiederholung: gleicher Sollwert innerhalb des Intervalls nichts neu schreiben, danach schon
$GLOBALS['VAL'][$vDev] = 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 24000, true]);
check('gleicher Sollwert innerhalb von 60 s: nichts neu geschrieben', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
attr('SolisLastWriteAt', time() - 61);
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 24000, true]);
check('nach 61 s (Zeitlimit 3 min, Abstand 60 s): Sollwert wird neu geschrieben (haelt den Zwangsmodus am Leben)', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_force_w', 'ctl_force_mode'], json_encode($GLOBALS['ACTIONS']));
// Geraet hat zurueckgenommen (Totmann): sofort neu schreiben
$GLOBALS['VAL'][$vDev] = 0; attr('SolisLastWriteAt', time() - 20); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 24000, true]);
check('Geraet meldet Zwangsmodus 0 obwohl Laden gewollt (mind. 10 s nach dem Schreiben): sofort neu geschrieben', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_force_w', 'ctl_force_mode'], json_encode($GLOBALS['ACTIONS']));
// Leistungsaenderung: sofort
$GLOBALS['VAL'][$vDev] = 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 8000, true]);
check('geaenderte Leistung (16,9 -> 8 kW): sofort geschrieben', $GLOBALS['ACTIONS'][0][2] === 8000, json_encode($GLOBALS['ACTIONS']));
// Freigeben
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik: Zwangsmodus 0 wird einmal geschrieben', array_map(fn($a) => [$a[1], $a[2]], $GLOBALS['ACTIONS']) === [['ctl_force_mode', 0]], json_encode($GLOBALS['ACTIONS']));
$GLOBALS['VAL'][$vDev] = 0; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik, Geraet schon frei: nichts mehr geschrieben', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
// Start ohne Vorgeschichte: Geraet frei -> kein unnoetiger Schreibzugriff
attr('SolisLastMode', -1); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('erster Aufruf mit Automatik und freiem Geraet: kein Schreibzugriff (Pauls eigene Einstellungen bleiben)', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
// nicht abbildbare Modi: Automatik + Grund
$GLOBALS['VAL'][$vDev] = 1; attr('SolisLastMode', 1); attr('SolisLastWriteAt', time() - 5); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 8000, true]);
check('nicht abbildbarer Modus (Netzbezugsregelung 9): Zwangsmodus freigegeben, Grund "nicht unterstützt" in der Statuszeile', array_map(fn($a) => [$a[1], $a[2]], $GLOBALS['ACTIONS']) === [['ctl_force_mode', 0]] && strpos(call($ems, 'statusText', ['x']), 'nicht unterstützt') !== false, json_encode($GLOBALS['ACTIONS']) . ' / ' . call($ems, 'statusText', ['x']));
// KeepAlive
prop('EMS_Active', true); prop('EMS_DryRun', false);
attr('LastGoodweMode', GW_MODE_BAT_CHARGE); attr('LastGoodweEnable', true); attr('LastGoodwePowerW', 10000); attr('LastGoodweWriteAt', time());
attr('SolisLastMode', 1); attr('SolisLastPowerW', 10000); attr('SolisLastWriteAt', time() - 5); $GLOBALS['VAL'][$vDev] = 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('KeepAlive (alle 15 s) schreibt beim Solis innerhalb des Intervalls nichts', count($GLOBALS['ACTIONS']) === 0, json_encode($GLOBALS['ACTIONS']));
attr('SolisLastWriteAt', time() - 61);
call($ems, 'KeepAlive');
check('KeepAlive nach 61 s: Zwangsladen wird nachgefuehrt, nur ctl_force_* (nie ctl_ems_*)', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_force_w', 'ctl_force_mode'], json_encode($GLOBALS['ACTIONS']));
// Extern gesetzter Zwangsmodus (Messskript, App/Portal): EMS fasst ihn NIE an (Befund 21.10.2026)
$acts = function () { return array_map(fn($a) => $a[1] . '=' . json_encode($a[2]), $GLOBALS['ACTIONS']); };
attr('SolisLastMode', 0); attr('SolisLastWriteAt', time() - 3600); attr('SolisReleaseTries', 0); attr('SolisHoldActive', false);
$GLOBALS['VAL'][$vDev] = 2; $GLOBALS['ACTIONS'] = [];
foreach ([0, 61, 600] as $dt) { attr('SolisLastWriteAt', time() - 3600 + $dt); call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]); }
check('Zwangsmodus 2 am Geraet, von EMS nicht gesetzt: Automatik-Entscheidung schreibt NIE ctl_force_mode (auch nicht nach 61 s oder 10 min)', count($acts()) === 0, json_encode($acts()));
attr('SolisLastMode', -1); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('auch bei unbekanntem Zustand (SolisLastMode -1, z. B. nach Neuanlage) bleibt ein fremder Zwangsmodus unangetastet', count($acts()) === 0, json_encode($acts()));
// Vom EMS selbst gesetztes Zwangsladen: Freigabe mit Wiederholung bis zur Bestaetigung
attr('SolisLastMode', 1); attr('SolisLastWriteAt', time() - 30); attr('SolisReleaseTries', 0); $GLOBALS['VAL'][$vDev] = 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('von EMS selbst gesetzt: Freigabe wird geschrieben (ctl_force_mode=0), Zaehler 1', $acts() === ['ctl_force_mode=0'] && $ems->ReadAttributeInteger('SolisReleaseTries') === 1, json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Geraet meldet noch 1: innerhalb von 10 s nicht erneut geschrieben', count($acts()) === 0, json_encode($acts()));
attr('SolisLastWriteAt', time() - 11);
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('nach 11 s noch nicht frei: zweiter Versuch', $acts() === ['ctl_force_mode=0'] && $ems->ReadAttributeInteger('SolisReleaseTries') === 2, json_encode($acts()));
attr('SolisReleaseTries', 3); attr('SolisLastWriteAt', time() - 100); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('nach drei Versuchen: aufgeben (Zeitlimit des Geraets gibt frei), kein weiterer Schreibzugriff, Zustand zurueckgesetzt', count($acts()) === 0 && $ems->ReadAttributeInteger('SolisLastMode') === 0, json_encode($acts()));
$GLOBALS['VAL'][$vDev] = 0; attr('SolisLastMode', 1); attr('SolisReleaseTries', 1); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Geraet meldet 0: Freigabe bestaetigt, nichts geschrieben, Zustand zurueckgesetzt', count($acts()) === 0 && $ems->ReadAttributeInteger('SolisLastMode') === 0 && $ems->ReadAttributeInteger('SolisReleaseTries') === 0);
// Trockenlauf: nie ein fremder Zwangsmodus
prop('EMS_DryRun', true); attr('LastGoodweEnable', true); attr('LastGoodweMode', GW_MODE_AUTO); attr('SolisLastMode', 0);
$GLOBALS['VAL'][$vDev] = 2; $GLOBALS['ACTIONS'] = [];
call($ems, 'applyDryRun', [['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'reason' => 'Test']]);
call($ems, 'applyDryRun', [['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'reason' => 'Test']]);
check('Trockenlauf mit fremdem Zwangsmodus 2 am Geraet: EMS schreibt am Solis nichts (auch nicht beim ersten Lauf)', count(array_filter($GLOBALS['ACTIONS'], fn($a) => strpos($a[1], 'ctl_force') === 0)) === 0, json_encode($acts()));
prop('EMS_DryRun', false);

// GoodWe bleibt unberuehrt
$GLOBALS['ACTIONS'] = [];
obj(401, 1, 'GoodWe-Hub', 0); vari('EMS Modus', 401, 'ctl_ems_mode', 1, 1); vari('Zwangsmodus', 401, 'ctl_force_mode', 0, 1); vari('Zwangsleistung', 401, 'ctl_force_w', 0, 1);
check('Wechselrichter mit ctl_ems_mode wird NICHT als Solis erkannt (GoodWe-Pfad bleibt unberuehrt, auch wenn Zwangsmodus-Punkte vorhanden)', call($ems, 'isSolisDriver', [401]) === false);
obj(402, 1, 'Leer', 0);
check('Wechselrichter ganz ohne Stellglieder: kein Solis', call($ems, 'isSolisDriver', [402]) === false);

echo "\n8k6) Solis: Akku halten ueber die Entladesperre (svc_discharge_inhibit, Speicherbetrieb 17)\n";
$vInh = vari('Entladesperre', IHUB_IID, 'svc_discharge_inhibit', false, 0);
$vBk = vari('Reserve-SOC (Einstellung)', IHUB_IID, 'cfg_soc_backup', 80, 1);
$vSt = vari('Speicherbetrieb (Einstellung)', IHUB_IID, 'cfg_storage_mode', 33, 1);
$vPrev = vari('Vorheriger Speicherbetrieb', IHUB_IID, 'svc_prev_storage', 0, 1);
foreach (['SolisHoldActive' => false, 'SolisHoldChangedAt' => 0, 'SolisUserStorage' => -1, 'SolisHoldTries' => 0, 'SolisHoldTryAt' => 0, 'SolisHoldBlockedUntil' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0, 'SolisLastWriteAt' => 0] as $k => $v) { attr($k, $v); }
$GLOBALS['VAL'][$vDev] = 0; // Zwangsmodus am Geraet aus
$ems->SetValue('EMS_PVPower', 0.0); $ems->SetValue('EMS_HousePower', 5000.0); $ems->SetValue('EMS_BatSOC', 47.0);
$acts = function () { return array_map(fn($a) => $a[1] . '=' . json_encode($a[2]), $GLOBALS['ACTIONS']); };
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Akku halten (Modus 5, 0 W) ohne PV: Entladesperre wird gesetzt, Nutzerwert 33 gemerkt', in_array('svc_discharge_inhibit=true', $acts(), true) && $ems->ReadAttributeInteger('SolisUserStorage') === 33 && $ems->ReadAttributeBoolean('SolisHoldActive') === true, json_encode($acts()));
$snap = [$ems->ReadAttributeBoolean('SolisHoldActive'), $ems->ReadAttributeInteger('SolisUserStorage'), $ems->ReadAttributeInteger('SolisHoldChangedAt'), $ems->ReadAttributeInteger('SolisHoldTryAt')];
attr('SolisHoldActive', false); attr('SolisHoldChangedAt', 0); attr('SolisUserStorage', -1); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_STANDBY, 0, true]);
check('Akku halten als Modus 8 (Bereitschaft, EMS 0.87.0): beim Solis ebenfalls Entladesperre', in_array('svc_discharge_inhibit=true', $acts(), true), json_encode($acts()));
foreach ([[GW_MODE_CHARGE_PV, 0, 'Modus 2 (Laden aus PV, nicht entladen)'], [GW_MODE_BUY, 9800, 'Modus 9 (Auto aus dem Netz, Grid Rewards)']] as [$md, $pw, $lbl]) {
    attr('SolisHoldActive', false); attr('SolisHoldChangedAt', 0); attr('SolisUserStorage', -1); $GLOBALS['ACTIONS'] = [];
    call($ems, 'setGoodweMode', [$md, $pw, true]);
    check($lbl . ' gibt es am Solis nicht: Entladesperre statt Automatik, Akku speist nicht ins Auto (0.101.2, Paul 12.11.2026)', in_array('svc_discharge_inhibit=true', $acts(), true) && !preg_grep('/^ctl_force_mode=2/', $acts()), json_encode($acts()));
}
attr('SolisHoldActive', $snap[0]); attr('SolisUserStorage', $snap[1]); attr('SolisHoldChangedAt', $snap[2]); attr('SolisHoldTryAt', $snap[3]);
check('Halten erlaubt: Statuszeile ohne "Nur beobachtend"', call($ems, 'statusText', ['x']) === 'OK: x', call($ems, 'statusText', ['x']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('gleich danach (Befehl noch nicht zurueckgelesen, unter 60 s): nichts neu geschrieben, kein zweiter Merkvorgang', count($acts()) === 0, json_encode($acts()));
$GLOBALS['VAL'][$vSt] = 17; $GLOBALS['VAL'][$vPrev] = 33; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Speicherbetrieb 17 zurueckgelesen: nichts mehr zu schreiben', count($acts()) === 0, json_encode($acts()));
// Befehl kam nicht an: Neuschreiben im Abstand von 60 s, hoechstens dreimal, dann Pause
$GLOBALS['VAL'][$vSt] = 33; $GLOBALS['ACTIONS'] = [];
attr('SolisHoldTryAt', time() - 61); attr('SolisHoldTries', 0);
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Speicherbetrieb blieb 33 (Befehl verschluckt): nach 61 s einmal neu geschrieben', $acts() === ['svc_discharge_inhibit=true'] && $ems->ReadAttributeInteger('SolisHoldTries') === 1, json_encode($acts()));
attr('SolisHoldTries', 3); attr('SolisHoldTryAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('nach drei Fehlversuchen: Sperre aktiv zurueckgenommen (svc_discharge_inhibit=false), Halten pausiert 10 min, Automatik (0.107.2, Paul 19.11.2026)', $acts() === ['svc_discharge_inhibit=false'] && $ems->ReadAttributeBoolean('SolisHoldActive') === false && $ems->ReadAttributeInteger('SolisHoldBlockedUntil') > time() + 500, json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('waehrend der Pause wird nicht erneut versucht, Statuszeile nennt das Pausieren', count($acts()) === 0 && strpos(call($ems, 'statusText', ['x']), 'pausiert') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
attr('SolisHoldBlockedUntil', 0);
// 0.107.2: Rueckmeldung aelter als das letzte Schreiben zaehlt nicht als Fehlversuch (Lesezyklus 5 min, Paul 19.11.2026)
attr('SolisHoldActive', true); attr('SolisHoldTries', 2); attr('SolisHoldTryAt', time() - 61); $GLOBALS['VAL'][$vSt] = 1;
$GLOBALS['VAR'][$vSt]['VariableUpdated'] = time() - 120; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Speicherbetrieb noch nicht neu gelesen (Wert aelter als der Schreibbefehl): kein Neuschreiben, kein Fehlversuch gezaehlt', count($acts()) === 0 && $ems->ReadAttributeInteger('SolisHoldTries') === 2 && $ems->ReadAttributeBoolean('SolisHoldActive') === true, json_encode($acts()));
attr('SolisHoldTries', 3); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('auch beim dritten Versuch: veralteter Wert fuehrt nicht zum Aufgeben', count($acts()) === 0 && $ems->ReadAttributeBoolean('SolisHoldActive') === true, json_encode($acts()));
$GLOBALS['VAR'][$vSt]['VariableUpdated'] = time();
// 0.107.2 Selbstheilung: EMS kennt die Sperre nicht mehr, sie steht aber noch (eigener Schreibzeitpunkt) -> freigeben
$own = time() - 3600;
attr('SolisHoldActive', false); attr('SolisHoldTries', 0); attr('SolisHoldChangedAt', $own); $GLOBALS['VAL'][$vInh] = true; $GLOBALS['VAL'][$vSt] = 17;
$GLOBALS['VAR'][$vInh]['VariableChanged'] = $own + 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Altfall: eigene Sperre steht noch, EMS kennt sie nicht -> Automatik gibt sie frei (svc_discharge_inhibit=false)', in_array('svc_discharge_inhibit=false', $acts(), true), json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Selbstheilung nur einmal, kein Dauerschreiben', !in_array('svc_discharge_inhibit=false', $acts(), true), json_encode($acts()));
attr('SolisHoldChangedAt', $own); $GLOBALS['VAR'][$vInh]['VariableChanged'] = $own + 900; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('fremd gesetzte Sperre (Aenderungszeit passt nicht zu EMS): bleibt stehen', !in_array('svc_discharge_inhibit=false', $acts(), true), json_encode($acts()));
$GLOBALS['VAL'][$vInh] = false; unset($GLOBALS['VAR'][$vInh]['VariableChanged']); attr('SolisHoldChangedAt', 0); $GLOBALS['VAL'][$vSt] = 33;
// PV-Ueberschuss: nicht halten
$GLOBALS['VAL'][$vSt] = 33; $ems->SetValue('EMS_PVPower', 4000.0); $ems->SetValue('EMS_HousePower', 800.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('PV-Ueberschuss 3,2 kW: kein Halten (PV soll die Batterie laden duerfen), Statuszeile nennt das Pausieren', count($acts()) === 0 && strpos(call($ems, 'statusText', ['x']), 'pausiert') !== false, json_encode($acts()));
$ems->SetValue('EMS_PVPower', 0.0); $ems->SetValue('EMS_HousePower', 5000.0);
// Reserve-SOC des Geraets: ueber dem Reserve-SOC haelt Speicherbetrieb 17 nicht (gemessen 22.10.2026)
attr('SolisHoldActive', false); attr('SolisHoldChangedAt', 0); $GLOBALS['VAL'][$vSt] = 33; $GLOBALS['ACTIONS'] = [];
$ems->SetValue('EMS_BatSOC', 95.0);
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Ladestand 95 % ueber dem Reserve-SOC 80 %: kein Halten (17 wuerde nicht halten), Statuszeile nennt den Reserve-SOC', count($acts()) === 0 && strpos(call($ems, 'statusText', ['x']), 'Reserve-SOC') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
$ems->SetValue('EMS_BatSOC', 79.5);
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Ladestand 79,5 % (innerhalb 1 % unter dem Reserve-SOC): kein Halten (Sicherheitsabstand)', count($acts()) === 0, json_encode($acts()));
$ems->SetValue('EMS_BatSOC', 60.0);
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Ladestand 60 % unter dem Reserve-SOC 80 %: Halten wird gesetzt', in_array('svc_discharge_inhibit=true', $acts(), true), json_encode($acts()));
$GLOBALS['OBJ'][$vBk]['ObjectIdent'] = 'x_cfg_soc_backup'; attr('SolisHoldActive', false); attr('SolisHoldChangedAt', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('ohne cfg_soc_backup vom InverterHub (aelterer Stand): nie raten, kein Halten', !in_array('svc_discharge_inhibit=true', $acts(), true), json_encode($acts()));
$GLOBALS['OBJ'][$vBk]['ObjectIdent'] = 'cfg_soc_backup'; $ems->SetValue('EMS_BatSOC', 47.0);
attr('SolisHoldActive', false); attr('SolisHoldChangedAt', 0); $GLOBALS['VAL'][$vSt] = 33; $GLOBALS['ACTIONS'] = [];

// Freigeben: Nutzerwert zurueck (InverterHub hat ihn gemerkt)
attr('SolisHoldActive', true); attr('SolisHoldChangedAt', time() - 120); attr('SolisUserStorage', 33); $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['VAL'][$vPrev] = 33; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik nach Halten: Entladesperre wird freigegeben (svc_discharge_inhibit=false), Zielwert 33 zum Rücklesen gemerkt', in_array('svc_discharge_inhibit=false', $acts(), true) && $ems->ReadAttributeBoolean('SolisHoldActive') === false && $ems->ReadAttributeInteger('SolisRestoreTarget') === 33, json_encode($acts()));
// Freigabe kam nicht an: ausdruecklich auf den gemerkten Wert schreiben
$GLOBALS['VAL'][$vSt] = 17; attr('SolisHoldTryAt', time() - 61); attr('SolisHoldTries', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Speicherbetrieb steht nach der Freigabe noch auf 17: nach 61 s ausdruecklich auf 33 geschrieben', in_array('ctl_storage_mode=33', $acts(), true), json_encode($acts()));
$GLOBALS['VAL'][$vSt] = 33; call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Speicherbetrieb 33 zurueckgelesen: Merkziel geloescht', $ems->ReadAttributeInteger('SolisRestoreTarget') === -1);
// InverterHub hat nichts gemerkt (neu angelegt): EMS schreibt den eigenen Merkwert
attr('SolisHoldActive', true); attr('SolisHoldChangedAt', time() - 120); attr('SolisUserStorage', 35); $GLOBALS['VAL'][$vPrev] = 0; $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('InverterHub ohne gemerkten Wert: EMS schreibt seinen eigenen Merkwert (35) in den Speicherbetrieb', in_array('ctl_storage_mode=35', $acts(), true) && !in_array('svc_discharge_inhibit=false', $acts(), true), json_encode($acts()));
attr('SolisRestoreTarget', -1);
// Netzladen waehrend Halten: erst freigeben, dann laden
attr('SolisHoldActive', true); attr('SolisHoldChangedAt', time() - 120); attr('SolisUserStorage', 33); $GLOBALS['VAL'][$vPrev] = 33; $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['VAL'][$vDev] = 0; attr('SolisLastMode', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 8000, true]);
check('Netzladen waehrend gehaltenem Akku: Entladesperre freigegeben UND Zwangsladen gesetzt', in_array('svc_discharge_inhibit=false', $acts(), true) && in_array('ctl_force_mode=1', $acts(), true), json_encode($acts()));
attr('SolisRestoreTarget', -1);
// Wartezeit zwischen zwei Wechseln (Register fest gespeichert)
attr('SolisHoldActive', false); attr('SolisHoldChangedAt', time() - 10); $GLOBALS['VAL'][$vSt] = 33; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('letzter Wechsel vor 10 s: Halten wird noch nicht erneut gesetzt (mind. 60 s Abstand wegen fester Speicherung)', count($acts()) === 0, json_encode($acts()));
// Ohne Entladesperre-Steuerpunkt: kein Halten
$GLOBALS['OBJ'][$vInh]['ObjectIdent'] = 'x_svc'; attr('SolisHoldChangedAt', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('InverterHub ohne svc_discharge_inhibit (aelterer Stand): kein Halten, Statuszeile "nicht unterstützt"', !in_array('svc_discharge_inhibit=true', $acts(), true) && strpos(call($ems, 'statusText', ['x']), 'nicht unterstützt') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
$GLOBALS['OBJ'][$vInh]['ObjectIdent'] = 'svc_discharge_inhibit';
// KeepAlive: Zyklus bleibt aus -> Entladesperre freigeben
prop('EMS_Active', true); prop('EMS_DryRun', false);
attr('LastGoodweMode', GW_MODE_AC_EXPORT); attr('LastGoodweEnable', true); attr('LastGoodweWriteAt', time() - 3600);
attr('SolisHoldActive', true); attr('SolisHoldChangedAt', time() - 3000); attr('SolisUserStorage', 33); $GLOBALS['VAL'][$vPrev] = 33; $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('Regelzyklus laeuft nicht mehr (Stunden): KeepAlive gibt die Entladesperre frei (kein Totmann am Geraet)', in_array('svc_discharge_inhibit=false', $acts(), true), json_encode($acts()));
attr('SolisRestoreTarget', -1);

echo "\n8k6b) Solis Remote Dispatch (InverterHub 0.91.0, Vertrag 1.8, EMS 0.108.0)\n";
$dAv = vari('Remote Dispatch verfuegbar', IHUB_IID, 'dispatch_available', true, 0);
$dMode = vari('Dispatch Modus', IHUB_IID, 'ctl_dispatch_mode', 0, 1);
vari('Dispatch Leistung', IHUB_IID, 'ctl_dispatch_w', 0, 1);
vari('Dispatch Netzladen', IHUB_IID, 'ctl_dispatch_grid_charge', 0, 1);
vari('Dispatch Totmann', IHUB_IID, 'ctl_dispatch_failsafe_min', 5, 1);
foreach (['SolisDispLast' => 0, 'SolisDispLastW' => 0, 'SolisDispAt' => 0, 'SolisHoldActive' => false, 'SolisHoldBlockedUntil' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0, 'SolisGcActive' => false, 'NoControlReason' => ''] as $k => $v) { attr($k, $v); }
$GLOBALS['VAL'][$vDev] = 0; $GLOBALS['VAL'][$vInh] = false; $GLOBALS['VAL'][$vSt] = 1;
$ems->SetValue('EMS_PVPower', 0.0); $ems->SetValue('EMS_HousePower', 3000.0); $ems->SetValue('EMS_BatSOC', 90.0);
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('Dispatch Netzladen 5 kW: Totmann, +5000 W (Doku: + Laden), Netzladen erlaubt, Modus 2 ZULETZT; kein ctl_force_*', $acts() === ['ctl_dispatch_failsafe_min=5', 'ctl_dispatch_w=5000', 'ctl_dispatch_grid_charge=1', 'ctl_dispatch_mode=2'], json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('gleich danach (unter 60 s, gleiche Leistung): nichts neu', count($acts()) === 0, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('nach 61 s: neu gesendet (Totmann laeuft nie ab, solange EMS laeuft)', $acts() !== [] && end($GLOBALS['ACTIONS'])[1] === 'ctl_dispatch_mode' && end($GLOBALS['ACTIONS'])[2] === 2, json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten bei SOC 90 % UEBER dem Reserved SOC 80 %: Dispatch Standby (Modus 1, 0 W), kein Speicherbetrieb 17', in_array('ctl_dispatch_mode=1', $acts(), true) && in_array('ctl_dispatch_w=0', $acts(), true) && !in_array('svc_discharge_inhibit=true', $acts(), true), json_encode($acts()));
check('Halten per Dispatch: Statuszeile ohne Pausenhinweis', $ems->ReadAttributeString('NoControlReason') === '', $ems->ReadAttributeString('NoControlReason'));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 7800, true]);
check('Auto aus dem Netz (Modus 9, 7,8 kW): Dispatch Modus 3, Netzbezug -7800 W (Doku: - Bezug), Netzladen unveraendert, Modus zuletzt (0.108.1)', $acts() === ['ctl_dispatch_failsafe_min=5', 'ctl_dispatch_w=-7800', 'ctl_dispatch_grid_charge=0', 'ctl_dispatch_mode=3'], json_encode($acts()));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 7850, true]);
check('Auto aus dem Netz, Vorgabe aendert sich um 50 W: nicht neu (Schwelle 100 W)', count($acts()) === 0, json_encode($acts()));
call($ems, 'setGoodweMode', [GW_MODE_BUY, 3600, true]);
check('Auto aus dem Netz, Vorgabe 3,6 kW: neu mit -3600 W', in_array('ctl_dispatch_w=-3600', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 3, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 0, true]);
check('Modus 9 ohne Leistung: Halten (Standby) statt Modus 3', in_array('ctl_dispatch_mode=1', $acts(), true), json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 6400, true]);
check('Einspeisen 6,4 kW (Modus 5, Vorentladen): Dispatch Modus 3 mit +6400 W (Doku: + Einspeisung), kein Netzladen (0.108.2)', $acts() === ['ctl_dispatch_failsafe_min=5', 'ctl_dispatch_w=6400', 'ctl_dispatch_grid_charge=0', 'ctl_dispatch_mode=3'], json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_SELL, 3000, true]);
check('Verkaufen 3 kW (Modus 10): Dispatch Modus 3 mit +3000 W', in_array('ctl_dispatch_w=3000', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 3, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_IMPORT, 9000, true]);
check('Netzbezug 9 kW (Modus 4): Dispatch Modus 3 mit -9000 W und Netzladen erlaubt', $acts() === ['ctl_dispatch_failsafe_min=5', 'ctl_dispatch_w=-9000', 'ctl_dispatch_grid_charge=1', 'ctl_dispatch_mode=3'], json_encode($acts()));
// SOC-Fenster im Geraet (InverterHub mit ctl_dispatch_soc_min/_max)
$dSocMin = vari('Dispatch SOC min', IHUB_IID, 'ctl_dispatch_soc_min', 0, 1); $dSocMax = vari('Dispatch SOC max', IHUB_IID, 'ctl_dispatch_soc_max', 100, 1);
$floorExp = $ems->ReadPropertyInteger('BAT_SOC_Min') + $ems->ReadPropertyInteger('BAT_SOC_Reserve_Backup');
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 6400, true]);
check('Einspeisen: EMS-Untergrenze (' . $floorExp . ' %) als SOC-Untergrenze ins Geraet, Obergrenze 100', in_array('ctl_dispatch_soc_min=' . $floorExp, $acts(), true) && in_array('ctl_dispatch_soc_max=100', $acts(), true) && end($GLOBALS['ACTIONS'])[1] === 'ctl_dispatch_mode', json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 7800, true]);
check('Auto aus dem Netz (Akku versorgt das Haus): ebenfalls SOC-Untergrenze ' . $floorExp . ' %', in_array('ctl_dispatch_soc_min=' . $floorExp, $acts(), true), json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('Netzladen: keine Untergrenze (0), Obergrenze 100', in_array('ctl_dispatch_soc_min=0', $acts(), true) && in_array('ctl_dispatch_soc_max=100', $acts(), true), json_encode($acts()));
$GLOBALS['OBJ'][$dSocMin]['ObjectIdent'] = 'x_ctl_dispatch_soc_min'; $GLOBALS['OBJ'][$dSocMax]['ObjectIdent'] = 'x_ctl_dispatch_soc_max';
$ems->SetValue('EMS_BatSOC', 50.0); attr('SolisDispAt', time() - 61); attr('SolisHoldChangedAt', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten per Dispatch UNTER dem Reserved SOC (50 %): nur Dispatch, nie zusaetzlich Speicherbetrieb 17 (ein Regler pro Stellgroesse)', in_array('ctl_dispatch_mode=1', $acts(), true) && !in_array('svc_discharge_inhibit=true', $acts(), true), json_encode($acts()));
$ems->SetValue('EMS_BatSOC', 90.0);
$ems->SetValue('EMS_PVPower', 4000.0); $ems->SetValue('EMS_HousePower', 800.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten bei PV-Ueberschuss 3,2 kW: Dispatch aus (Standby wuerde PV aussperren), Grund in der Statuszeile', $acts() === ['ctl_dispatch_mode=0'] && strpos(call($ems, 'statusText', ['x']), 'pausiert') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik bei bereits ausgeschaltetem Dispatch: nichts geschrieben', count($acts()) === 0, json_encode($acts()));
$ems->SetValue('EMS_PVPower', 0.0); $ems->SetValue('EMS_HousePower', 3000.0);
attr('SolisDispLast', 2); attr('SolisDispLastW', 5000); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik nach Netzladen: genau einmal ctl_dispatch_mode=0', $acts() === ['ctl_dispatch_mode=0'], json_encode($acts()));
attr('SolisHoldActive', true); attr('SolisHoldChangedAt', time() - 120); attr('SolisUserStorage', 1); $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['VAL'][$vPrev] = 1; $GLOBALS['VAL'][$vInh] = true; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Umstieg auf Dispatch: alte Entladesperre (Speicherbetrieb 17) freigegeben UND Dispatch Standby gesetzt', in_array('svc_discharge_inhibit=false', $acts(), true) && in_array('ctl_dispatch_mode=1', $acts(), true), json_encode($acts()));
$GLOBALS['VAL'][$vSt] = 1; $GLOBALS['VAL'][$vInh] = false; attr('SolisRestoreTarget', -1);
$GLOBALS['VAL'][$dAv] = false; attr('SolisDispLast', 1); attr('SolisLastMode', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('ohne Dispatch-Faehigkeit: Dispatch aus und bisheriger Weg (ctl_force_mode=1)', in_array('ctl_dispatch_mode=0', $acts(), true) && in_array('ctl_force_mode=1', $acts(), true) && !in_array('ctl_dispatch_mode=2', $acts(), true), json_encode($acts()));
$GLOBALS['VAL'][$dAv] = true; attr('SolisLastMode', 0); attr('SolisDispLast', 1); attr('SolisDispAt', time() - 3000);
prop('EMS_Active', true); prop('EMS_DryRun', false); attr('LastGoodweEnable', true); attr('LastGoodweMode', GW_MODE_AC_EXPORT); attr('LastGoodweWriteAt', time() - 3000); $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('Regelzyklus laeuft nicht mehr: KeepAlive schaltet den Dispatch aus', in_array('ctl_dispatch_mode=0', $acts(), true), json_encode($acts()));
$GLOBALS['OBJ'][$dAv]['ObjectIdent'] = 'x_dispatch_available'; $GLOBALS['OBJ'][$dMode]['ObjectIdent'] = 'x_ctl_dispatch_mode'; attr('SolisDispLast', 0); attr('SolisLastMode', 0);

echo "\n8k7b) Solis: Netzlade-Erlaubnis (Bit 5 im Speicherbetrieb) fuer das Zwangsladen (0.90.0, Pauls Anlage 25.10.2026)\n";
$vCtlSt = vari('Speicherbetrieb', IHUB_IID, 'ctl_storage_mode', 33, 1);
foreach (['SolisHoldActive' => false, 'SolisHoldBlockedUntil' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0, 'SolisLastPowerW' => 0, 'SolisLastWriteAt' => 0, 'SolisReleaseTries' => 0, 'SolisGcActive' => false, 'SolisGcSaved' => -1, 'SolisGcTries' => 0, 'SolisGcTryAt' => 0] as $k => $v) { attr($k, $v); }
$GLOBALS['VAL'][$vDev] = 0; $GLOBALS['VAL'][$vSt] = 1; $GLOBALS['VAR'][$vSt]['VariableUpdated'] = time() - 100; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Speicherbetrieb 1 (kein Netzladen) + Netzladen: vorher 33 geschrieben, 1 gemerkt, Zwangsladen trotzdem gesetzt', in_array('ctl_storage_mode=33', $acts(), true) && in_array('ctl_force_mode=1', $acts(), true) && $ems->ReadAttributeInteger('SolisGcSaved') === 1 && $ems->ReadAttributeBoolean('SolisGcActive') === true, json_encode($acts()));
// Rueckmeldung noch alt (Lesezyklus 5 min): kein erneutes Schreiben, auch nach 61 s nicht
attr('SolisGcTryAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Rueckmeldung aelter als das Schreiben: kein erneutes Schreiben des Speicherbetriebs', !in_array('ctl_storage_mode=33', $acts(), true), json_encode($acts()));
// frische Rueckmeldung, immer noch 1: erneut schreiben
$GLOBALS['VAR'][$vSt]['VariableUpdated'] = time(); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('frische Rueckmeldung weiter 1: Netzlade-Erlaubnis erneut geschrieben', in_array('ctl_storage_mode=33', $acts(), true), json_encode($acts()));
// Bit 5 kam an
$GLOBALS['VAL'][$vSt] = 33; $GLOBALS['VAR'][$vSt]['VariableUpdated'] = time() + 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Bit 5 zurueckgelesen: kein weiteres Schreiben des Speicherbetriebs', !in_array('ctl_storage_mode=33', $acts(), true) && $ems->ReadAttributeInteger('SolisGcTries') === 0, json_encode($acts()));
// Laden endet: alter Wert 1 zurueck, Ruecklesen ueber das Merkziel
$GLOBALS['VAL'][$vDev] = 1; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Laden endet: Speicherbetrieb zurueck auf 1, Merkziel 1, Zwangsmodus freigegeben', in_array('ctl_storage_mode=1', $acts(), true) && in_array('ctl_force_mode=0', $acts(), true) && $ems->ReadAttributeBoolean('SolisGcActive') === false && $ems->ReadAttributeInteger('SolisRestoreTarget') === 1, json_encode($acts()));
$GLOBALS['VAL'][$vSt] = 1; $GLOBALS['VAR'][$vSt]['VariableUpdated'] = time() + 2; call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Speicherbetrieb 1 zurueckgelesen: Merkziel geloescht', $ems->ReadAttributeInteger('SolisRestoreTarget') === -1);
// Netzladen schon erlaubt (35 = Bits 0,1,5): nichts am Speicherbetrieb
attr('SolisLastMode', 0); $GLOBALS['VAL'][$vDev] = 0; $GLOBALS['VAL'][$vSt] = 35; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Speicherbetrieb 35 (Netzladen erlaubt): kein Schreiben des Speicherbetriebs, nichts gemerkt', !preg_grep('/^ctl_storage_mode=/', $acts()) && $ems->ReadAttributeBoolean('SolisGcActive') === false, json_encode($acts()));
// andere Bits bleiben: 3 -> 35
$GLOBALS['VAL'][$vSt] = 3; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 12000, true]);
check('Speicherbetrieb 3: Bit 5 dazu = 35, andere Bits bleiben', in_array('ctl_storage_mode=35', $acts(), true), json_encode($acts()));
check('Zielwerte: 1->33, 3->35, 64->96, 17->33 (nie 49), 19->35', call($ems, 'solisGridChargeTarget', [1]) === 33 && call($ems, 'solisGridChargeTarget', [3]) === 35 && call($ems, 'solisGridChargeTarget', [64]) === 96 && call($ems, 'solisGridChargeTarget', [17]) === 33 && call($ems, 'solisGridChargeTarget', [19]) === 35);
// Zyklus bleibt aus: KeepAlive gibt auch die Netzlade-Erlaubnis frei
prop('EMS_Active', true); prop('EMS_DryRun', false);
attr('LastGoodweMode', GW_MODE_BAT_CHARGE); attr('LastGoodweEnable', true); attr('LastGoodweWriteAt', time() - 3600); $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('Regelzyklus bleibt aus: KeepAlive stellt den Speicherbetrieb 3 wieder her', in_array('ctl_storage_mode=3', $acts(), true), json_encode($acts()));
foreach (['SolisGcActive' => false, 'SolisGcSaved' => -1, 'SolisGcTries' => 0, 'SolisGcTryAt' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0] as $k => $v) { attr($k, $v); }
$GLOBALS['VAL'][$vSt] = 33;

// Befund Paul 18.11.2026 (0.106.9): Halten (17) direkt vor dem Netzladen -- gemerkt wird der Nutzerwert 1, nicht 17
foreach (['SolisGcActive' => false, 'SolisGcSaved' => -1, 'SolisGcTries' => 0, 'SolisGcTryAt' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0] as $k => $v) { attr($k, $v); }
attr('SolisHoldActive', true); attr('SolisUserStorage', 1); $GLOBALS['VAL'][$vDev] = 0; $GLOBALS['VAL'][$vSt] = 17; $GLOBALS['ACTIONS'] = [];
call($ems, 'solisGridChargeEnsure', [IHUB_IID, true, time()]);
check('Halten (17) direkt vor dem Netzladen: Nutzerwert 1 gemerkt, nicht der Haltewert (Befund Paul 18.11.2026)', $ems->ReadAttributeInteger('SolisGcSaved') === 1 && in_array('ctl_storage_mode=33', $acts(), true), json_encode([$ems->ReadAttributeInteger('SolisGcSaved'), $acts()]));
$GLOBALS['ACTIONS'] = [];
call($ems, 'solisGridChargeEnsure', [IHUB_IID, false, time()]);
check('Laden endet nach vorherigem Halten: Speicherbetrieb 1 wiederhergestellt, nie 17', in_array('ctl_storage_mode=1', $acts(), true) && !in_array('ctl_storage_mode=17', $acts(), true), json_encode($acts()));
attr('SolisGcActive', true); attr('SolisGcSaved', 17); attr('SolisUserStorage', 1); $GLOBALS['ACTIONS'] = [];
call($ems, 'solisGridChargeEnsure', [IHUB_IID, false, time()]);
check('Altbestand mit gemerkter 17 (vor 0.106.9): beim Laden-Ende trotzdem 1', in_array('ctl_storage_mode=1', $acts(), true) && !in_array('ctl_storage_mode=17', $acts(), true), json_encode($acts()));
attr('SolisHoldActive', false); attr('SolisRestoreTarget', -1); attr('SolisGcActive', false);

echo "\n8k8) Solis: Entladen auf Vorgabe (Zwangsentladen ins Haus, Einspeisewache)\n";
$vDisMax = vari('Max. Entladeleistung', IHUB_IID, 'bat_discharge_max_w', 16900.0, 2);
foreach (['SolisHoldActive' => false, 'SolisHoldBlockedUntil' => 0, 'SolisRestoreTarget' => -1, 'SolisLastMode' => 0, 'SolisLastPowerW' => 0, 'SolisLastWriteAt' => 0, 'SolisReleaseTries' => 0] as $k => $v) { attr($k, $v); }
$GLOBALS['VAL'][$vDev] = 0;
$ems->SetValue('EMS_PVPower', 1000.0); $ems->SetValue('EMS_HousePower', 4000.0); $ems->SetValue('EMS_WB1Power', 0.0); $ems->SetValue('EMS_WB2Power', 0.0); $ems->SetValue('EMS_GridPower', -2850.0); $ems->SetValue('EMS_BatSOC', 60.0);
$acts = function () { return array_map(fn($a) => $a[1] . '=' . json_encode($a[2]), $GLOBALS['ACTIONS']); };
check('Sollleistung: Hauslast 4000 - PV 1000 - Reserve 150 = 2850 W', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_DISCHARGE, 0]) === 2850);
$wbDis = function (int $mode, bool $gr) use ($ems) { prop('WB1_Mode', $mode); $ems->SetValue('EMS_GridRewards', $gr); $ems->SetValue('EMS_WB1Power', 3000.0); $r = call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_DISCHARGE, 0]); $ems->SetValue('EMS_WB1Power', 0.0); $ems->SetValue('EMS_GridRewards', false); prop('WB1_Mode', 0); return $r; };
check('EMS steuert die Wallbox: Wallbox zaehlt zum Bedarf (+3000 W)', $wbDis(2, false) === 5850);
check('Smart Charging (Tibber steuert): Wallbox zaehlt NICHT, Akku speist nicht ins Auto (Paul 12.11.2026)', $wbDis(1, false) === 2850);
check('Beobachten: Wallbox zaehlt NICHT', $wbDis(3, false) === 2850);
check('Grid Rewards aktiv: Wallbox zaehlt NICHT, auch wenn EMS sie sonst steuert', $wbDis(2, true) === 2850);
check('Obergrenze aus dem Plan (Modus 3, 2000 W) begrenzt', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_DISCHARGE, 2000]) === 2000);
check('Modus 12 wird wie Entladen behandelt', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_BAT_DISCH, 0]) === 2850);
check('andere Modi (Export 5, Automatik 1, Laden 11): keine Entladung', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_AC_EXPORT, 3000]) === 0 && call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_AUTO, 0]) === 0 && call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_BAT_CHARGE, 3000]) === 0);
$ems->SetValue('EMS_HousePower', 20000.0);
check('Entladegrenze des Wechselrichters (16,9 kW) begrenzt', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_DISCHARGE, 0]) === 16900);
$ems->SetValue('EMS_HousePower', 1100.0);
check('kein Bedarf (Hauslast 1100 - PV 1000 - Reserve = unter 100 W): 0, keine Einspeisung', call($ems, 'solisDischargeW', [IHUB_IID, GW_MODE_DISCHARGE, 0]) === 0);
$ems->SetValue('EMS_HousePower', 4000.0);
// Schreiben
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Entladen: erst Leistung 2850 W, dann Zwangsmodus 2', $acts() === ['ctl_force_w=2850', 'ctl_force_mode=2'] && $ems->ReadAttributeInteger('SolisLastMode') === 2, json_encode($acts()));
$GLOBALS['VAL'][$vDev] = 2; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('gleiche Vorgabe innerhalb von 30 s: nichts neu geschrieben', count($acts()) === 0, json_encode($acts()));
attr('SolisLastWriteAt', time() - 31);
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('nach 31 s: neu geschrieben (Entladen faellt frueher zurueck als Laden)', $acts() === ['ctl_force_w=2850', 'ctl_force_mode=2'], json_encode($acts()));
$ems->SetValue('EMS_HousePower', 3000.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Hauslast sinkt (Vorgabe 1850 W, Aenderung >= 100 W): sofort neu geschrieben', $acts() === ['ctl_force_w=1850', 'ctl_force_mode=2'], json_encode($acts()));
$GLOBALS['VAL'][$vDev] = 0; attr('SolisLastWriteAt', time() - 20); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Geraet meldet Zwangsmodus 0 obwohl Entladen gewollt: nach 10 s sofort neu', array_column($GLOBALS['ACTIONS'], 1) === ['ctl_force_w', 'ctl_force_mode'], json_encode($acts()));
// Einspeisewache
$GLOBALS['VAL'][$vDev] = 2; attr('SolisLastMode', 2); attr('SolisLastPowerW', 1850); attr('SolisLastWriteAt', time() - 5);
$ems->SetValue('EMS_GridPower', 600.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Einspeisung 600 W (> 200 W) beim Entladen: Vorgabe sinkt um den eingespeisten Betrag auf 1250 W', $acts() === ['ctl_force_w=1250', 'ctl_force_mode=2'], json_encode($acts()));
$ems->SetValue('EMS_GridPower', 150.0); attr('SolisLastPowerW', 1850); attr('SolisLastWriteAt', time() - 5); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Einspeisung 150 W (unter der Schwelle): Vorgabe unveraendert', count($acts()) === 0, json_encode($acts()));
$ems->SetValue('EMS_GridPower', 2000.0); attr('SolisLastPowerW', 1850); attr('SolisLastWriteAt', time() - 5); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Einspeisung groesser als die Vorgabe: Zwangsentladen wird freigegeben, kein Schreiben von ctl_force_w', $acts() === ['ctl_force_mode=0'], json_encode($acts()));
$ems->SetValue('EMS_GridPower', -2850.0);
// Freigabe nach Entladen
attr('SolisLastMode', 2); attr('SolisLastPowerW', 1850); attr('SolisReleaseTries', 0); attr('SolisLastWriteAt', time() - 30); $GLOBALS['VAL'][$vDev] = 2; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik nach selbst gesetztem Zwangsentladen: Freigabe (ctl_force_mode=0)', $acts() === ['ctl_force_mode=0'], json_encode($acts()));
// Fremder Zwangsmodus wird nie angefasst
attr('SolisLastMode', 0); attr('SolisLastWriteAt', time() - 3600); $GLOBALS['VAL'][$vDev] = 2; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('fremd gesetzter Zwangsmodus 2: Automatik schreibt nichts', count($acts()) === 0, json_encode($acts()));
// Pausiert: kein Bedarf -> Automatik mit Grund
$ems->SetValue('EMS_HousePower', 1100.0); attr('SolisLastMode', 0); $GLOBALS['VAL'][$vDev] = 0; $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_DISCHARGE, 0, true]);
check('Entladen ohne Bedarf: nichts geschrieben, Statuszeile nennt "Entladen auf Vorgabe pausiert"', count($acts()) === 0 && strpos(call($ems, 'statusText', ['x']), 'Entladen auf Vorgabe pausiert') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
// Export (Vorentladen, Modus 5 mit Leistung) bleibt unbeantwortet: wuerde einspeisen
$ems->SetValue('EMS_HousePower', 4000.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 5000, true]);
check('Export (Modus 5 mit 5 kW): kein Zwangsentladen, Statuszeile "nicht unterstützt"', count(array_filter($GLOBALS['ACTIONS'], fn($a) => $a[1] === 'ctl_force_mode' && $a[2] === 2)) === 0 && strpos(call($ems, 'statusText', ['x']), 'nicht unterstützt') !== false, json_encode($acts()));

echo "\n8k7) Bestandsaufnahme: Preis, Frische, Schreibfehler, Einfuehrung, Konfigurationspruefung\n";
$ems = freshEms(); prop('TIBBER_Active', true); pricesToday(0.31);
$pi = call($ems, 'currentPriceEur');
check('Preis nicht verknuepft, Preiskurve da: aktueller Preis kommt automatisch aus der Kurve', $pi['eur'] === 0.31 && $pi['source'] === 'Preiskurve', json_encode($pi));
$vpr = vari('Preis', 0, '', 0.45, 2); prop('VAR_TIB_Price', $vpr);
$pi = call($ems, 'currentPriceEur');
check('verknuepfte Preisvariable hat Vorrang (0,45)', $pi['eur'] === 0.45 && $pi['source'] === 'Variable', json_encode($pi));
$ems = freshEms(); prop('TIBBER_Active', true);
$pi = call($ems, 'currentPriceEur');
check('weder Variable noch Kurve: Preis unbekannt (null), nicht stillschweigend 0', $pi['eur'] === null && $pi['source'] === '', json_encode($pi));
// readState: Frische von Netz/PV, Preis unbekannt
$ems = freshEms(); prop('TIBBER_Active', true); prop('BAT_Active', true);
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
foreach (array_keys($GLOBALS['OBJ']) as $oid) { if (($GLOBALS['OBJ'][$oid]['ParentID'] ?? -1) === IHUB_IID) { unset($GLOBALS['OBJ'][$oid], $GLOBALS['VAR'][$oid], $GLOBALS['VAL'][$oid]); } }
$GLOBALS['OBJ'][IHUB_IID]['HasChildren'] = true;
$gId = vari('Netz', IHUB_IID, 'meter_total', 100.0, 2); $pId = vari('PV', IHUB_IID, 'pv_total', 500.0, 2); $sId = vari('SOC', IHUB_IID, 'soc', 50, 1);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'none', 'controllable' => false, 'gridPowerID' => $gId, 'pvPowerID' => $pId, 'socID' => $sId]]]));
$sx = call($ems, 'readState');
check('readState frisch: keine veralteten Messwerte, Preis unbekannt wird markiert', $ems->ReadAttributeString('DataStaleText') === '' && !empty($sx['tib_price_unknown']), $ems->ReadAttributeString('DataStaleText'));
$GLOBALS['VAR'][$gId]['VariableUpdated'] = time() - 1200;
$sx = call($ems, 'readState');
check('Netzleistung seit 20 min nicht aktualisiert: wird gemeldet (Attribut), Statuszeile beginnt mit der Warnung', $ems->ReadAttributeString('DataStaleText') === 'Netzleistung' && strpos(call($ems, 'statusText', ['x']), '⚠️ Messwerte veraltet (Netzleistung)') === 0, call($ems, 'statusText', ['x']));
$GLOBALS['VAR'][$pId]['VariableUpdated'] = time() - 1200;
call($ems, 'readState');
check('Netz und PV veraltet: beide genannt', $ems->ReadAttributeString('DataStaleText') === 'Netzleistung, PV-Leistung', $ems->ReadAttributeString('DataStaleText'));
$GLOBALS['VAR'][$gId]['VariableUpdated'] = time(); $GLOBALS['VAR'][$pId]['VariableUpdated'] = time();
call($ems, 'readState');
check('wieder frisch: Warnung weg', $ems->ReadAttributeString('DataStaleText') === '' && call($ems, 'statusText', ['x']) === 'OK: x');
// Schreibfehler sichtbar
$ems = freshEms();
$GLOBALS['IPSWRITE_FAIL'] = true;
check('fehlgeschlagener Schreibzugriff (false) wird gemeldet: ipsWrite gibt false, Variable EMS_WriteWarn an, Text gemerkt', call($ems, 'ipsWrite', [IHUB_IID, 'ctl_x', 1]) === false && $ems->GetValue('EMS_WriteWarn') === true && strpos($ems->ReadAttributeString('WriteFailText'), 'ctl_x') !== false, $ems->ReadAttributeString('WriteFailText'));
$GLOBALS['IPSWRITE_FAIL'] = false;
check('gelingt der Schreibzugriff danach, bleibt die Warnung noch 10 min (nicht sofort weg)', call($ems, 'ipsWrite', [IHUB_IID, 'ctl_x', 1]) === true && $ems->GetValue('EMS_WriteWarn') === true);
attr('WriteFailAt', time() - 700);
call($ems, 'ipsWrite', [IHUB_IID, 'ctl_x', 1]);
check('nach 10 min ohne Fehler: Warnung geht von selbst weg', $ems->GetValue('EMS_WriteWarn') === false && $ems->ReadAttributeInteger('WriteFailAt') === 0);
$GLOBALS['IPSWRITE_FAIL'] = 'throw';
check('Ausnahme beim Schreiben (z. B. Instanz fehlt): kein Absturz, wird als Fehlschlag gemeldet', call($ems, 'ipsWrite', [IHUB_IID, 'ctl_y', 2]) === false && strpos($ems->ReadAttributeString('WriteFailText'), 'ctl_y') !== false);
$GLOBALS['IPSWRITE_FAIL'] = false;
// Einfuehrungs-Trockenlauf bei neuer Instanz
$ems = freshEms(); prop('EMS_Active', true);
$ems->ApplyChanges();
check('neue Instanz, erste Aktivierung: Einfuehrungs-Trockenlauf an (nichts wird geschrieben)', $ems->ReadAttributeBoolean('DryRunAuto') === true && call($ems, 'isDryRun') === true);
$ems->ApplyChanges();
check('Einfuehrungs-Trockenlauf bleibt bis "Jetzt live schalten" (erneutes Uebernehmen aendert nichts)', call($ems, 'isDryRun') === true);
$msg = $ems->GoLive();
check('"Jetzt live schalten" beendet den Einfuehrungs-Trockenlauf', call($ems, 'isDryRun') === false && strpos($msg, '✅') === 0, $msg);
$ems->ApplyChanges();
check('danach kein zweiter Einfuehrungs-Trockenlauf (EverActive gemerkt)', call($ems, 'isDryRun') === false);
$ems = freshEms(); prop('EMS_Active', true); attr('LastDecision', time() - 100);
$ems->ApplyChanges();
check('bestehende Instanz (hat schon entschieden): kein Einfuehrungs-Trockenlauf, Verhalten unveraendert', call($ems, 'isDryRun') === false);
$ems = freshEms(); prop('EMS_Active', true); prop('EMS_DryRun', true);
$ems->ApplyChanges(); $msg = $ems->GoLive();
check('Trockenlauf im Formular eingeschaltet: "Jetzt live schalten" weist darauf hin, dass er dort auszuschalten ist', strpos($msg, '⚠️') === 0 && call($ems, 'isDryRun') === true, $msg);
// Konfigurationspruefung
$ems = freshEms(); prop('TIBBER_Active', true); attr('PriceSourceNote', ''); prop('BAT_Active', true); prop('TIB_Threshold_Charge', 0.07); prop('TIB_Threshold_WB', 0.20);
$cc = $ems->GetConfigCheck();
$txt = implode(' | ', array_column($cc['findings'], 'text'));
check('Konfigurationspruefung nennt: Preis unbekannt, Kapazitaet 10 kWh (Standard), feste Schwellen, Netzanschluss-Standard', $cc['ok'] === false && strpos($txt, 'aktuelle Preis ist unbekannt') !== false && strpos($txt, '10 kWh') !== false && strpos($txt, 'Laden 0.07') !== false && strpos($txt, '11 kW') !== false, $txt);
$ems = freshEms(); attr('PriceSourceNote', 'Preiskurve'); prop('TIBBER_Active', true); prop('BAT_Active', true); prop('BAT_Capacity_kWh', 40.0); prop('EMS_Max_Power_W', 24000);
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'controlAuthority' => 'none', 'controllable' => false, 'gridPowerID' => 0, 'pvPowerID' => 0, 'socID' => 0]]]));
$cc = $ems->GetConfigCheck();
check('saubere Konfiguration (Kapazitaet gesetzt, Preis aus der Kurve, keine festen Schwellen): keine Warnung, ok', $cc['ok'] === true && $cc['warnings'] === 0, json_encode(array_column($cc['findings'], 'text')));
attr('WriteFailAt', time() - 60); attr('WriteFailText', 'Instanz #1, ctl_x (abgelehnt)');
$cc = $ems->GetConfigCheck();
check('juengster Schreibfehler taucht in der Konfigurationspruefung auf', $cc['ok'] === false && strpos(implode(' ', array_column($cc['findings'], 'text')), 'Schreibzugriff ist fehlgeschlagen') !== false);
$form = json_decode(emsFormDe($ems->GetConfigurationForm()), true);
$caps = array_column($form['elements'], 'caption');
check('Formular enthaelt das Panel Konfigurationspruefung (mit Hinweiszahl in der Ueberschrift)', (bool)array_filter($caps, fn($c) => strpos((string)$c, '🩺 Konfigurationsprüfung') === 0), json_encode($caps));

