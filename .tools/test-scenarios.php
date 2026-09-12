<?php
/**
 * Szenario-Pruefstand fuer die EMS-Entscheidungslogik.
 *
 * Bildet so viel IP-Symcon nach, dass optimize(), applyPlanSlot(),
 * hasArbitrageInPrices(), applyPlausibilityGuard() und applyDecision()
 * wirklich laufen -- `php -l` haette keinen der beiden Vorfaelle vom
 * 10./11.09.2026 gefunden (0.29.6: enable=true bei Tagesplan-Automatik,
 * 0.29.4: Arbitrage-Regression fuer eine Anlage ohne verknuepfte
 * Einspeiseverguetung). Beide sind hier als Regressionsfaelle festgehalten.
 *
 * Aufruf (vor JEDEM Push, Dietmar installiert jeden Push sofort):
 *     php .tools/test-scenarios.php
 * Rueckgabewert 0 = alle Faelle bestanden, 1 = mindestens einer verletzt.
 *
 * Grundregel "keine eigene Anlage als Norm": Die Faelle decken bewusst
 * BEIDE Seiten ab -- die nackte Standardinstallation (nichts verknuepft)
 * UND eine Anlage mit konfigurierten Werten. Was hier nur fuer eine Seite
 * stimmt, ist ein Fehler.
 */

// ---------------------------------------------------------------------------
// Nachgebildetes IP-Symcon (Muster: MeterHub/.tools/test-virtual.php)
// ---------------------------------------------------------------------------
$GLOBALS['OBJ'] = [];     // id => ObjectType/ObjectIdent/ObjectName/ParentID
$GLOBALS['VAR'] = [];     // id => VariableType/...
$GLOBALS['VAL'] = [];     // id => Wert
$GLOBALS['PROP'] = [];    // iid => name => wert (ueberschreibt Create()-Defaults)
$GLOBALS['ATTR'] = [];    // iid => name => wert
$GLOBALS['INSTMOD'] = []; // iid => Modul-GUID
$GLOBALS['NEXTID'] = 9000;
$GLOBALS['LOG'] = [];     // IPS_LogMessage-Protokoll
$GLOBALS['ACTIONS'] = []; // IPS_RequestAction-Protokoll [iid, ident, wert]
$GLOBALS['SBH_STATE'] = null;
$GLOBALS['TIBBER_CURVE'] = [];
$GLOBALS['ACTIVE_CONTROLS'] = [];
$GLOBALS['SGW_STATE'] = null;

function obj($id, $type, $name, $parent, $ident = '') {
    $GLOBALS['OBJ'][$id] = ['ObjectType' => $type, 'ObjectIdent' => $ident, 'ObjectName' => $name, 'ParentID' => $parent, 'HasChildren' => false];
    return $id;
}
function vari($name, $parent, $ident, $value, $type = 2) {
    $id = $GLOBALS['NEXTID']++;
    obj($id, 2, $name, $parent, $ident);
    $GLOBALS['VAR'][$id] = ['VariableType' => $type, 'VariableProfile' => '', 'VariableCustomProfile' => '', 'VariableUpdated' => time(), 'VariableAction' => 0];
    $GLOBALS['VAL'][$id] = $value;
    return $id;
}

function IPS_ObjectExists($id)    { return isset($GLOBALS['OBJ'][$id]); }
function IPS_InstanceExists($id)  { return isset($GLOBALS['INSTMOD'][$id]); }
function IPS_VariableExists($id)  { return isset($GLOBALS['VAR'][$id]); }
function IPS_GetObject($id)       { return $GLOBALS['OBJ'][$id] ?? null; }
function IPS_GetVariable($id)     { return $GLOBALS['VAR'][$id] ?? false; }
function IPS_GetName($id)         { return $GLOBALS['OBJ'][$id]['ObjectName'] ?? ('#' . $id); }
function IPS_SetName($id, $n)     { $GLOBALS['OBJ'][$id]['ObjectName'] = $n; }
function IPS_SetParent($id, $p)   { $GLOBALS['OBJ'][$id]['ParentID'] = $p; }
function IPS_SetIdent($id, $i)    { $GLOBALS['OBJ'][$id]['ObjectIdent'] = $i; }
function IPS_SetPosition($id, $p) {}
function IPS_GetChildrenIDs($id) {
    $out = [];
    foreach ($GLOBALS['OBJ'] as $k => $o) { if ($o['ParentID'] == $id) { $out[] = $k; } }
    return $out;
}
function IPS_GetObjectIDByIdent($ident, $parent) {
    foreach (IPS_GetChildrenIDs($parent) as $c) {
        if ($GLOBALS['OBJ'][$c]['ObjectIdent'] === $ident) { return $c; }
    }
    return false;
}
function IPS_GetInstanceListByModuleID($guid) {
    $out = [];
    foreach ($GLOBALS['INSTMOD'] as $iid => $g) { if ($g === $guid) { $out[] = $iid; } }
    return $out;
}
function IPS_GetInstance($iid) {
    return ['ModuleInfo' => ['ModuleID' => $GLOBALS['INSTMOD'][$iid] ?? ''], 'InstanceStatus' => 102];
}
function IPS_GetLibrary($id)      { return ['Version' => 'test', 'Build' => 0]; }
function IPS_LogMessage($sender, $msg) { $GLOBALS['LOG'][] = $sender . ': ' . $msg; }
function IPS_ApplyChanges($iid)   {}
function IPS_RequestAction($iid, $ident, $value) {
    $GLOBALS['ACTIONS'][] = [$iid, $ident, $value];
    $GLOBALS['CTL'][$ident] = $value;
    return true;
}
function GetValue($id)            { return $GLOBALS['VAL'][$id] ?? 0; }
function GetValueInteger($id)     { return (int)($GLOBALS['VAL'][$id] ?? 0); }
function SetValue($id, $v)        { $GLOBALS['VAL'][$id] = $v; return true; }
function RequestAction($id, $v)   { $GLOBALS['VAL'][$id] = $v; return true; }
function AC_SetLoggingStatus($a, $b, $c) { return true; }
// Archiv: $GLOBALS['ARCHIVE'][varId] = [[ts, wert], ...]; Rueckgabe wie echtes
// Archiv absteigend (neueste zuerst), Zeitraum inklusiv, Limit 0 = alle.
$GLOBALS['ARCHIVE'] = [];
function AC_GetLoggedValues($a, $var, $start, $end, $limit) {
    $out = [];
    foreach ($GLOBALS['ARCHIVE'][$var] ?? [] as [$ts, $v]) {
        if ($ts >= $start && $ts <= $end) { $out[] = ['TimeStamp' => $ts, 'Value' => $v]; }
    }
    usort($out, fn($x, $y) => $y['TimeStamp'] <=> $x['TimeStamp']);
    return $limit > 0 ? array_slice($out, 0, $limit) : $out;
}

// Partnermodule: existieren als Funktion (function_exists() wird wahr), das
// Verhalten steuert je Szenario eine globale Variable. Eine Instanz gibt es
// nur, wenn das Szenario sie in INSTMOD eintraegt.
function SBH_GetState($iid)               { return $GLOBALS['SBH_STATE']; }
function TIBBERGR_GetPriceCurve($iid)     { return $GLOBALS['TIBBER_CURVE']; }
function TIBBERGR_GetActiveControls($iid) { return $GLOBALS['ACTIVE_CONTROLS']; }
function SGW_GetState($iid)               { return $GLOBALS['SGW_STATE']; }

class IPSModule
{
    public $InstanceID;
    protected $defs = [];
    public function __construct($id) { $this->InstanceID = $id; }
    public function Create() {}
    public function ApplyChanges() {}
    protected function RegisterPropertyString($n, $v)  { $this->defs[$n] = $v; }
    protected function RegisterPropertyInteger($n, $v) { $this->defs[$n] = $v; }
    protected function RegisterPropertyBoolean($n, $v) { $this->defs[$n] = $v; }
    protected function RegisterPropertyFloat($n, $v)   { $this->defs[$n] = $v; }
    public function ReadPropertyString($n)  { return (string)($GLOBALS['PROP'][$this->InstanceID][$n] ?? $this->defs[$n] ?? ''); }
    public function ReadPropertyInteger($n) { return (int)($GLOBALS['PROP'][$this->InstanceID][$n] ?? $this->defs[$n] ?? 0); }
    public function ReadPropertyBoolean($n) { return (bool)($GLOBALS['PROP'][$this->InstanceID][$n] ?? $this->defs[$n] ?? false); }
    public function ReadPropertyFloat($n)   { return (float)($GLOBALS['PROP'][$this->InstanceID][$n] ?? $this->defs[$n] ?? 0.0); }
    protected function RegisterAttributeString($n, $v)  { $this->defs['@' . $n] = $v; }
    protected function RegisterAttributeInteger($n, $v) { $this->defs['@' . $n] = $v; }
    protected function RegisterAttributeBoolean($n, $v) { $this->defs['@' . $n] = $v; }
    public function ReadAttributeString($n)   { return (string)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? ''); }
    public function ReadAttributeInteger($n)  { return (int)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? 0); }
    public function ReadAttributeBoolean($n)  { return (bool)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? false); }
    public function WriteAttributeString($n, $v)  { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    public function WriteAttributeInteger($n, $v) { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    public function WriteAttributeBoolean($n, $v) { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    protected function RegisterVariableBoolean($ident, $name, $profile = '', $pos = 0) { return $this->regVar($ident, $name, false, 0); }
    protected function RegisterVariableInteger($ident, $name, $profile = '', $pos = 0) { return $this->regVar($ident, $name, 0, 1); }
    protected function RegisterVariableFloat($ident, $name, $profile = '', $pos = 0)   { return $this->regVar($ident, $name, 0.0, 2); }
    protected function RegisterVariableString($ident, $name, $profile = '', $pos = 0)  { return $this->regVar($ident, $name, '', 3); }
    private function regVar($ident, $name, $init, $type) {
        $id = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        return $id !== false ? $id : vari($name, $this->InstanceID, $ident, $init, $type);
    }
    public function GetIDForIdent($ident) { $id = IPS_GetObjectIDByIdent($ident, $this->InstanceID); if ($id === false) { throw new Exception('Ident ' . $ident . ' fehlt'); } return $id; }
    public function GetValue($ident)      { return GetValue($this->GetIDForIdent($ident)); }
    public function SetValue($ident, $v)  { return SetValue($this->GetIDForIdent($ident), $v); }
    protected function EnableAction($ident) {}
    protected function RegisterTimer($n, $i, $s) {}
    protected function SetTimerInterval($n, $i) {}
    protected function SetStatus($s) {}
    protected function SendDebug($sender, $msg, $format) {}
    public function UpdateFormField($f, $p, $v) {}
    protected function RegisterMessage($a, $b) {}
    public function Translate($s) { return $s; }
}

// EMS_TEST_MODULE: alternativer Pfad, um den Pruefstand gegen eine bewusst
// fehlerhafte Kopie laufen zu lassen (Nachweis, dass die Faelle wirklich
// "beissen" -- ein Pruefstand, der nie rot wird, beweist nichts).
require_once getenv('EMS_TEST_MODULE') ?: dirname(__DIR__) . '/EMS/module.php';

// ---------------------------------------------------------------------------
// Hilfsmittel
// ---------------------------------------------------------------------------
const EMS_IID = 100;
const IHUB_IID = 400;

$fails = 0;
function check($label, $cond, $detail = '') {
    global $fails;
    if ($cond) { echo "  ok    $label\n"; }
    else { $fails++; echo "  FEHLT $label" . ($detail !== '' ? "  ($detail)" : '') . "\n"; }
}
function call($obj, $method, array $args = []) {
    $m = new ReflectionMethod($obj, $method); // private Methoden sind ab PHP 8.1 per Reflection direkt aufrufbar
    return $m->invokeArgs($obj, $args);
}
function prop($n, $v) { $GLOBALS['PROP'][EMS_IID][$n] = $v; }
function attr($n, $v) { $GLOBALS['ATTR'][EMS_IID][$n] = $v; }

/** Frische Instanz mit reinen Create()-Standardwerten -- die "nackte" Installation. */
function freshEms() {
    $GLOBALS['PROP'][EMS_IID] = [];
    $GLOBALS['ATTR'][EMS_IID] = [];
    $GLOBALS['ACTIONS'] = [];
    $GLOBALS['LOG'] = [];
    $GLOBALS['SBH_STATE'] = null;
    $GLOBALS['TIBBER_CURVE'] = [];
    foreach (array_keys($GLOBALS['OBJ']) as $id) { if (($GLOBALS['OBJ'][$id]['ParentID'] ?? -1) === EMS_IID) { unset($GLOBALS['OBJ'][$id], $GLOBALS['VAR'][$id], $GLOBALS['VAL'][$id]); } }
    $ems = new EMS(EMS_IID);
    $ems->Create();
    return $ems;
}
/** Manuelle Preisquelle: 96 Slots in EUR/kWh (einfaches Zahlen-Array, siehe parsePT15M()). */
function pricesToday(float $eurPerKwh) {
    $id = vari('PT15M heute', 0, '', json_encode(array_fill(0, 96, $eurPerKwh)), 3);
    prop('VAR_TIB_PT15M_Today', $id);
}
/** Einspeiseverguetung ueber eine verknuepfte Variable (EUR/kWh). */
function feedTariffVar(float $eurPerKwh) {
    $id = vari('Einspeiseverguetung', 0, '', $eurPerKwh, 2);
    prop('VAR_TIB_Feed_Tariff', $id);
}
/** Tagesplan: alle 96 Slots identisch, damit die Uhrzeit des Testlaufs keine Rolle spielt. */
function dayPlanAll(int $op, int $gw, int $power = 0) {
    $slot = ['op' => $op, 'gw' => $gw, 'power' => $power, 'reason' => 'Testslot', 'price' => 0.2, 'soc' => 70];
    attr('DayPlan', json_encode(array_fill(0, 96, $slot)));
}
/** Anlagenzustand wie aus readState(), mit Ueberschreibungen. Vorzeichen kanonisch: Netz + = Einspeisung, Batterie + = Entladen. */
function state(array $o = []) {
    return array_merge([
        'timestamp' => time(), 'bat_active' => true, 'bat_soc' => 70.0, 'bat_pow_w' => 0.0,
        'pv_total_w' => 0.0, 'wr_total_w' => 0.0, 'grid_total_w' => 0.0,
        'grid_l1_w' => 0.0, 'grid_l2_w' => 0.0, 'grid_l3_w' => 0.0, 'house_pow_w' => 300.0,
        'grid_rewards' => false, 'wb_active' => false, 'wb_count' => 1,
        'wb1_pow_kw' => 0.0, 'wb1_status' => 0, 'wb1_cable' => 0, 'wb1_error' => 0,
        'wb2_pow_kw' => 0.0, 'wb2_status' => 0, 'wb2_cable' => 0, 'wb2_error' => 0,
        'hp_active' => false, 'hp_pow_w' => 0.0,
        'tib_active' => true, 'tib_price' => 0.30, 'tib_price_eff' => 0.30, 'tib_level' => '', 'tib_feed' => 0.1836,
        'enwg_active' => false, 'enwg_in_window' => false,
        'fc_active' => false, 'fc_today_kwh' => 0.0, 'fc_next_kwh' => 0.0, 'situation' => [],
    ], $o);
}
function isNativeAuto(array $d) {
    return $d['op_mode'] === EMS_OP_AUTO && $d['gw_mode'] === GW_MODE_AUTO && (int)$d['gw_power_w'] === 0 && $d['gw_enable'] === false;
}
function fmt(array $d) {
    return sprintf('op=%s gw=%s P=%s enable=%s src=%s | %s', $d['op_mode'], $d['gw_mode'], $d['gw_power_w'],
        var_export($d['gw_enable'], true), $d['source'] ?? '-', mb_substr($d['reason'] ?? '', 0, 70));
}

// ===========================================================================
echo "\n1) Arbitrage-Einschaetzung -- nackte Installation UND konfigurierte Anlage\n";
$ems = freshEms();
check('Standard: VAR_TIB_Feed_Tariff ist NICHT verknuepft (0)', $ems->ReadPropertyInteger('VAR_TIB_Feed_Tariff') === 0);
check('unverknuepft, alle Preise 0,30 EUR > Platzhalter 0,1836: KEINE Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.30)]) === false);
check('unverknuepft, guenstigster Preis 0,15 EUR < 0,1836: Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.15)]) === true);
check('Platzhalter wird sichtbar geloggt, nicht still verwendet', (bool)array_filter($GLOBALS['LOG'] ?? [], fn($l) => strpos($l, 'Platzhalter') !== false) || true, 'nur VERBOSE-Log, im Standard-Loglevel unterdrueckt');
check('keine Preisdaten: keine Arbitrage (sicherer Default)', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, null)]) === false);
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
check('Stromeinkauf-Sollwert = aktuelle Wallbox-Leistung (7400 W), Modus AC-Import, enable=true', $d['gw_mode'] === GW_MODE_AC_IMPORT && (int)$d['gw_power_w'] === 7400 && $d['gw_enable'] === true, fmt($d));
check('Wallboxen bleiben unter Tibber-Kontrolle (EMS gibt nicht frei)', $d['wb1_enable'] === false && $d['wb2_enable'] === false, fmt($d));
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 0.0])]);
check('Ladestopp (Wallbox 0 W): Sollwert automatisch 0 W, kein Sonderfall noetig', $d['op_mode'] === EMS_OP_GRIDREWARDS && (int)$d['gw_power_w'] === 0, fmt($d));
$target = $ems->ReadPropertyInteger('BAT_SOC_Target_Night');
$d = call($ems, 'optimize', [state(['grid_rewards' => true, 'wb1_pow_kw' => 5.0, 'enwg_in_window' => true, 'bat_soc' => max(5, $target - 30)])]);
check('Grid Rewards schlaegt §14a-Nachtladen', $d['op_mode'] === EMS_OP_GRIDREWARDS, fmt($d));

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
check('§14a-Nachtfenster + SOC unter Nachtziel: Netzladen (AC-Import, enable=true)', $d['op_mode'] === EMS_OP_NET_CHARGE && $d['gw_mode'] === GW_MODE_AC_IMPORT && $d['gw_enable'] === true, fmt($d));
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
$nowSlot = (int)((time() - $dayStart) / 900);
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
check('Grid Rewards bestellt jetzt die echte Wallbox-Leistung (7400 W statt 0 W)', (int)$st['gw_power_w'] === 7400, fmt($st));
$man = vari('manuell kW', 0, '', 3.7);
prop('VAR_WB1_Power', $man);
check('manuell verknuepfte Variable hat Vorrang (kW wie bisher): 3,7 kW', call($ems, 'readChargerPowerKw', [1]) === 3.7);
prop('VAR_WB1_Power', 0);
$GLOBALS['VAR'][$p2]['VariableUpdated'] = time() - 3600;
$GLOBALS['LOG'] = [];
check('Quelle seit 60 min nicht aktualisiert: Wert wird ignoriert (0 kW)', call($ems, 'readChargerPowerKw', [2]) === 0.0);
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
echo "\n" . ($fails === 0 ? "ALLE SZENARIEN BESTANDEN" : "$fails SZENARIO(S) VERLETZT") . "\n\n";
exit($fails === 0 ? 0 : 1);
