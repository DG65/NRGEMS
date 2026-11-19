<?php
// Kapitel des EMS-Pruefstands: gemeinsame Fernsteuerung (InverterHub-Vertrag 1.9 remoteControl, EMS 0.109.0).
// Wird von .tools/test-scenarios.php im selben Gueltigkeitsbereich geladen; einzeln nicht lauffaehig.

echo "\n14) Gemeinsame Fernsteuerung (remoteControl, FoxESS-/Deye-artige Adapter, Solis ueber ctl_rc_*)\n";
const RC_IID = 4711;
$ems = freshEms();
$GLOBALS['INSTMOD'][RC_IID] = GUID_INVERTERHUB;
obj(RC_IID, 1, 'InverterHub (Fernsteuerung)', 0);
$GLOBALS['OBJ'][RC_IID]['HasChildren'] = true;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => RC_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
$rcMode = vari('Fernsteuerung Modus', RC_IID, 'ctl_rc_mode', 0, 1);
vari('Fernsteuerung Leistung', RC_IID, 'ctl_rc_w', 0, 1);
vari('Fernsteuerung Totmann', RC_IID, 'ctl_rc_failsafe_sec', 60, 1);
$rcAv = vari('verfuegbar', RC_IID, 'rc_available', true, 0);
$rcTested = vari('getestet', RC_IID, 'rc_tested', false, 0);
$rcModes = vari('Modi', RC_IID, 'rc_modes', '0,2,3', 3);
$rcVendor = vari('Hersteller', RC_IID, 'rc_vendor', 'FoxESS', 3);
$acts = function () { return array_map(fn($a) => $a[1] . '=' . json_encode($a[2]), $GLOBALS['ACTIONS']); };
foreach (['SolisDispLast' => 0, 'SolisDispLastW' => 0, 'SolisDispAt' => 0, 'NoControlReason' => '', 'SolisHoldBlockedUntil' => 0] as $k => $v) { attr($k, $v); }
$ems->SetValue('EMS_PVPower', 500.0); $ems->SetValue('EMS_HousePower', 3500.0); $ems->SetValue('EMS_BatSOC', 60.0);
check('Wechselrichter nur mit ctl_rc_* (kein ctl_ems_*, kein ctl_force_*): Art "remote"', call($ems, 'inverterKind', [RC_IID]) === 'remote');

// ungetestet, nicht freigegeben
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('ungetesteter Adapter ohne Freigabe: nichts geschrieben, Grund "ungetestet" in der Statuszeile', count($acts()) === 0 && strpos(call($ems, 'statusText', ['x']), 'ungetestet') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));
prop('INV_Remote_AllowUntested', true);

$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('freigegeben, Netzladen 5 kW: Totmann in Sekunden (300), +5000 W, Modus 2 zuletzt; ohne Netzlade-/SOC-Felder (nur Solis)', $acts() === ['ctl_rc_failsafe_sec=300', 'ctl_rc_w=5000', 'ctl_rc_mode=2'], json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 7800, true]);
check('Auto aus dem Netz mit Modus 3: Netzbezug -7800 W', in_array('ctl_rc_w=-7800', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 3, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 2000, true]);
check('Einspeisen 2 kW mit Modus 3: +2000 W', in_array('ctl_rc_w=2000', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 3, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten ohne Modus 1 (FoxESS): Fernsteuerung aus, Grund "bietet Modus 1 nicht an"', $acts() === ['ctl_rc_mode=0'] && strpos(call($ems, 'statusText', ['x']), 'Modus 1') !== false, json_encode($acts()) . call($ems, 'statusText', ['x']));

// Deye-artig: Modi 0,1,2, kein Netzpunkt
$GLOBALS['VAL'][$rcModes] = '0,1,2'; $GLOBALS['VAL'][$rcVendor] = 'Deye'; attr('SolisDispLast', 0); attr('SolisDispAt', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BUY, 7800, true]);
check('ohne Modus 3, Auto aus dem Netz: Akku liefert nur das Hausdefizit (3500-500 = -3000 W, Modus 2)', in_array('ctl_rc_w=-3000', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 2, json_encode($acts()));
check('Statussatz: Akku versorgt nur das Haus (kein Netzbezug-Regler)', strpos(call($ems, 'carFromGridText', [8000.0, 7800]), 'nur das Haus') !== false, call($ems, 'carFromGridText', [8000.0, 7800]));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 2000, true]);
check('ohne Modus 3, Einspeisen 2 kW: Modus 2 mit -(2000 + 3000) = -5000 W', in_array('ctl_rc_w=-5000', $acts(), true) && end($GLOBALS['ACTIONS'])[2] === 2, json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten mit Modus 1 (Deye): Modus 1, 0 W', in_array('ctl_rc_mode=1', $acts(), true) && in_array('ctl_rc_w=0', $acts(), true), json_encode($acts()));
$ems->SetValue('EMS_PVPower', 4000.0); $ems->SetValue('EMS_HousePower', 800.0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AC_EXPORT, 0, true]);
check('Halten bei PV-Ueberschuss: aus (PV darf laden)', $acts() === ['ctl_rc_mode=0'], json_encode($acts()));
$ems->SetValue('EMS_PVPower', 500.0); $ems->SetValue('EMS_HousePower', 3500.0);
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Automatik bei schon aus: nichts geschrieben', count($acts()) === 0, json_encode($acts()));

// Fernsteuerung noch nicht bereit (rc_available false nach Update): nichts, Grund ohne Steuerung
$GLOBALS['VAL'][$rcAv] = false; attr('SolisDispLast', 2); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('rc_available false (vor dem ersten Lesezyklus): nichts Neues, eigene Fernsteuerung aus', $acts() === ['ctl_rc_mode=0'], json_encode($acts()));
$GLOBALS['VAL'][$rcAv] = true;

// Freigabe zurueckgenommen, waehrend aktiv
attr('SolisDispLast', 2); prop('INV_Remote_AllowUntested', false); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('Freigabe zurueckgenommen: laufende Fernsteuerung wird ausgeschaltet', $acts() === ['ctl_rc_mode=0'], json_encode($acts()));
$GLOBALS['VAL'][$rcTested] = true; attr('SolisDispAt', 0); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('getesteter Adapter (rc_tested): auch ohne Freigabe gesteuert', end($GLOBALS['ACTIONS'])[2] === 2, json_encode($acts()));

// KeepAlive: Regelzyklus bleibt aus -> aus
prop('EMS_Active', true); prop('EMS_DryRun', false); attr('LastGoodweEnable', true); attr('LastGoodweMode', GW_MODE_BAT_CHARGE); attr('LastGoodweWriteAt', time() - 3000); $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('Regelzyklus bleibt aus: KeepAlive schaltet die Fernsteuerung aus', $acts() === ['ctl_rc_mode=0'], json_encode($acts()));
attr('LastGoodweWriteAt', time()); attr('SolisDispLast', 2); attr('SolisDispLastW', 5000); attr('SolisDispAt', time() - 61); attr('LastGoodwePowerW', 5000); $GLOBALS['ACTIONS'] = [];
call($ems, 'KeepAlive');
check('KeepAlive bei laufendem Zyklus: Modus nach 61 s neu gesendet', end($GLOBALS['ACTIONS'])[1] === 'ctl_rc_mode' && end($GLOBALS['ACTIONS'])[2] === 2, json_encode($acts()));

// Solis mit Vertrag 1.9: ctl_rc_* statt ctl_dispatch_*, mit Netzladen und SOC-Fenster
$ems = freshEms();
$GLOBALS['INSTMOD'][RC_IID] = GUID_INVERTERHUB;
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => RC_IID, 'controlAuthority' => 'ems', 'controllable' => true]]]));
vari('Zwangsmodus', RC_IID, 'ctl_force_mode', 0, 1); vari('Zwangsleistung', RC_IID, 'ctl_force_w', 0, 1);
vari('Dispatch verfuegbar', RC_IID, 'dispatch_available', true, 0); vari('Dispatch Modus', RC_IID, 'ctl_dispatch_mode', 0, 1); vari('Dispatch Leistung', RC_IID, 'ctl_dispatch_w', 0, 1);
vari('SOC min', RC_IID, 'ctl_rc_soc_min', 0, 1); vari('SOC max', RC_IID, 'ctl_rc_soc_max', 100, 1); vari('Netzladen', RC_IID, 'ctl_rc_grid_charge', 0, 1);
$GLOBALS['VAL'][$rcTested] = true; $GLOBALS['VAL'][$rcModes] = '0,1,2,3'; $GLOBALS['VAL'][$rcVendor] = 'Solis'; $GLOBALS['VAL'][$rcAv] = true;
foreach (['SolisDispLast' => 0, 'SolisDispLastW' => 0, 'SolisDispAt' => 0, 'SolisHoldActive' => false, 'SolisGcActive' => false, 'SolisLastMode' => 0, 'NoControlReason' => ''] as $k => $v) { attr($k, $v); }
$ems->SetValue('EMS_PVPower', 0.0); $ems->SetValue('EMS_HousePower', 3000.0); $ems->SetValue('EMS_BatSOC', 60.0);
check('Solis mit Vertrag 1.9 bleibt Art "solis"', call($ems, 'inverterKind', [RC_IID]) === 'solis');
$GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_BAT_CHARGE, 5000, true]);
check('Solis Netzladen ueber ctl_rc_* (nicht ctl_dispatch_*): Totmann s, SOC 0/100, +5000 W, Netzladen erlaubt, Modus 2 zuletzt', $acts() === ['ctl_rc_failsafe_sec=300', 'ctl_rc_soc_min=0', 'ctl_rc_soc_max=100', 'ctl_rc_w=5000', 'ctl_rc_grid_charge=1', 'ctl_rc_mode=2'], json_encode($acts()));
attr('SolisDispAt', time() - 61); $GLOBALS['ACTIONS'] = [];
call($ems, 'setGoodweMode', [GW_MODE_AUTO, 0, false]);
check('Solis Automatik: Fernsteuerung aus ueber beide Vertraege (Uebergang)', in_array('ctl_rc_mode=0', $acts(), true) && in_array('ctl_dispatch_mode=0', $acts(), true), json_encode($acts()));
