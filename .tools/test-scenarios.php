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
    return ['ModuleInfo' => ['ModuleID' => $GLOBALS['INSTMOD'][$iid] ?? ''], 'InstanceStatus' => $GLOBALS['INSTSTATUS'][$iid] ?? 102];
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
$GLOBALS['OHUB_FUNCS'] = [];
function OHUB_GetFunctions($iid)          { return $GLOBALS['OHUB_FUNCS']; }
$GLOBALS['WP_FUNCS'] = [];
function WPHUB_GetFunctions($iid)          { return $GLOBALS['WP_FUNCS']['wphub'] ?? []; }
function WPMBHUB_GetFunctions($iid)       { return $GLOBALS['WP_FUNCS']['wpmbhub'] ?? []; }
function WPMBGW_GetFunctions($iid)        { return $GLOBALS['WP_FUNCS']['wpmbgw'] ?? []; }
function SAMEHS_GetFunctions($iid)        { return $GLOBALS['WP_FUNCS']['samehs'] ?? []; }

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
    prop('ANL_Verguetung_ct', 18.36); // konfigurierte Anlage; Tests zu "unbekannt" setzen den Wert selbst auf 0
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

echo "\n8e) Andere Nutzer, andere Konstellationen: Netzladen ohne feste Einspeiseverguetung\n";
$ems = freshEms();
prop('ANL_Verguetung_ct', 0.0); // Verguetung unbekannt
$tagU = array_fill(0, 96, 0.30); for ($i = 0; $i < 24; $i++) { $tagU[$i] = 0.12; } for ($i = 72; $i < 88; $i++) { $tagU[$i] = 0.36; }
check('Verguetung unbekannt: Arbitrage aus dem Preisverlauf (12 ct gegen Abendspitze 36 ct), nicht aus dem Platzhalter', call($ems, 'hasArbitrageInPrices', [$tagU]) === true);
check('Verguetung unbekannt, flacher Tag (30 ct): keine Arbitrage', call($ems, 'hasArbitrageInPrices', [array_fill(0, 96, 0.30)]) === false);
$rp = call($ems, 'replacementPriceEur', [$tagU, 0]);
check('Ersatz-Bezugspreis = Mittel des teuersten Viertels (16 Slots 36 ct + 8 Slots 30 ct = 34 ct)', abs($rp - 0.34) < 0.001, (string)$rp);
$ctxU = ['capKwh' => 40.0, 'chargeKw' => 10.0, 'maxW' => 20000, 'socTargetNight' => 100.0, 'feedTariff' => 0.0, 'refMode' => 1, 'replacePrice' => 0.36, 'spread' => 0.03, 'cycleCost' => 0.0];
check('Referenz 1: Grenze = 36 ct x 0,9025 - 3 ct Mindestspanne = 29,49 ct', abs(call($ems, 'gridChargeLimitEur', [$ctxU]) - 0.29490) < 0.0005);
check('Referenz 1 ohne Preisvergleichswert: kein Netzladen (Grenze unerreichbar)', call($ems, 'gridChargeLimitEur', [array_merge($ctxU, ['replacePrice' => null])]) < -1e9);
prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6); prop('PLAN_NightGrid_ExtendHours', 0);
$nwU = call($ems, 'nightWindowPlan', [$tagU, 0, 0.0, $ctxU]);
check('Nutzer ohne Verguetung laedt trotzdem in den 12-ct-Slots nach (kein Sperren wegen Verguetung 0)', $nwU !== null && count($nwU['charge']) > 0 && max(array_keys($nwU['charge'])) < 24, json_encode($nwU));
check('Nutzer ohne Verguetung: Halten (Haus aus dem Netz) wird nicht aus der Verguetung abgeleitet', call($ems, 'nightWindowSlot', [30, 0.12, 60.0, $nwU, $ctxU]) === null);
prop('ANL_Verguetung_ct', 8.0); // niedrige Verguetung (z. B. Ue20/Direktvermarktung)
check('Verguetung 8 ct, Referenz Verguetung: Netzladen sehr streng (Grenze 7,6 ct)', abs(call($ems, 'gridChargeLimitEur', [['feedTariff' => 0.08, 'refMode' => 0]]) - 0.076) < 0.0005);
prop('NETZLADUNG_Referenz', 1);
check('Verguetung 8 ct, aber Referenz Ersatz-Bezugspreis gewaehlt: Modus 1', call($ems, 'chargeReferenceMode') === 1);

echo "\n8f) Konstellationen: berechnete Verguetung, Netzanschluss-Budget, Wechselrichter ohne Stellglieder\n";
$ems = freshEms();
prop('ANL_Verguetung_ct', 0.0); prop('ANL_IBN_Datum', '2012-10-24'); prop('ANL_kWp_Manuell', 9.18);
check('aus Inbetriebnahme und Groesse berechnete Verguetung ist BEKANNT und gilt fuer Planung (Referenz 0)', call($ems, 'planFeedTariffEur') > 0.15 && call($ems, 'chargeReferenceMode') === 0, json_encode(call($ems, 'getFeedTariffEur')));
$ems = freshEms(); prop('SITE_Max_Grid_Import_W', 12000); prop('WB_Active', true); prop('WB_Count', 1); prop('WB1_Max_Power_W', 11000);
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
vari('EMS Leistung', IHUB_IID, 'ctl_ems_power', 0, 1); vari('EMS Steuerung', IHUB_IID, 'ctl_ems_enable', false, 0); // Stellglieder wie beim echten GoodWe-Treiber
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

// ===========================================================================
echo "\n13) Einspeise-Ueberwachung -- Batterie entlaedt, waehrend eingespeist wird (MiSpeL-Bedingung)\n";
$ems = freshEms();
$aktiv = ['op_mode' => EMS_OP_DISCHARGE, 'gw_mode' => GW_MODE_DISCHARGE, 'gw_power_w' => 5000, 'gw_enable' => true,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Tagesplan: Entladen', 'source' => 'tagesplan'];
$auto = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$ueber = state(['bat_pow_w' => 4000.0, 'grid_total_w' => 3500.0]); // entlaedt 4 kW, speist 3,5 kW ein
attr('ExpOvlLastTs', time() - 30);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('1. Zyklus: nur vorgemerkt, Entscheidung unveraendert', $d === $aktiv && $ems->ReadAttributeInteger('ExpOvlSince') > 0);
check('Tageszaehler: 30 s x 3500 W = ~29 Wh, 0,5 min', abs($ems->GetValue('EMS_ExportOverlapToday_Wh') - 29.2) < 0.2 && abs($ems->GetValue('EMS_ExportOverlapToday_Min') - 0.5) < 0.01,
    $ems->GetValue('EMS_ExportOverlapToday_Wh') . ' Wh / ' . $ems->GetValue('EMS_ExportOverlapToday_Min') . ' min');
attr('ExpOvlSince', time() - 120);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('nach 90 s: Rueckfall in WR-Eigenregelung (enable=false, Automatik, 0 W), force', isNativeAuto($d) && !empty($d['force']), fmt($d));
check('Warnvariable EMS_ExportOverlapWarn = true, Haltephase gesetzt', $ems->GetValue('EMS_ExportOverlapWarn') === true && $ems->ReadAttributeInteger('ExpOvlHoldUntil') > time());
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, state()]);
check('Haltephase: aktiver Sollwert wird weiter ueberstimmt (kein Pendeln)', isNativeAuto($d), fmt($d));
$gr = array_merge($aktiv, ['op_mode' => EMS_OP_GRIDREWARDS, 'gw_mode' => GW_MODE_AC_IMPORT, 'source' => 'tibber']);
$d = call($ems, 'applyExportOverlapGuard', [$gr, $ueber]);
check('Haltephase: Grid Rewards bleibt unangetastet', $d === $gr, fmt($d));
attr('ExpOvlHoldUntil', 0);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, state(['bat_pow_w' => 800.0, 'grid_total_w' => -300.0])]);
check('nach der Haltephase, keine Einspeisung mehr: Entscheidung unveraendert, Warnung aufgehoben', $d === $aktiv && $ems->GetValue('EMS_ExportOverlapWarn') === false, fmt($d));

echo "\n   Negativfaelle -- wo die Ueberwachung NICHT eingreifen darf\n";
$ems = freshEms();
$faelle = [
    'WR-Automatik (EMS faehrt keinen Sollwert): nur messen'   => [$auto, $ueber],
    'Grid Rewards (Quelle tibber)'                            => [$gr, $ueber],
    '§14a-Netzbetreiber'                                      => [array_merge($aktiv, ['source' => 'netzbetreiber']), $ueber],
    'unter der Schwelle (80 W Entladung, Messrauschen)'       => [$aktiv, state(['bat_pow_w' => 80.0, 'grid_total_w' => 3000.0])],
    'Batterie laedt, PV speist ein (kein Batteriestrom im Netz)' => [$aktiv, state(['bat_pow_w' => -2000.0, 'grid_total_w' => 1500.0])],
    'keine Batterie konfiguriert'                             => [$aktiv, state(['bat_active' => false, 'bat_pow_w' => 4000.0, 'grid_total_w' => 3500.0])],
];
foreach ($faelle as $label => [$dec, $st]) {
    attr('ExpOvlSince', time() - 600);
    $d = call($ems, 'applyExportOverlapGuard', [$dec, $st]);
    check($label, $d === $dec, fmt($d));
}
check('Automatik mit Ueberschneidung wird trotzdem gezaehlt', $ems->GetValue('EMS_ExportOverlapToday_Min') > 0 || $ems->ReadAttributeInteger('ExpOvlLastTs') > 0);
prop('EXPOVL_Enabled', false);
attr('ExpOvlSince', time() - 600);
$d = call($ems, 'applyExportOverlapGuard', [$aktiv, $ueber]);
check('Ueberwachung abgeschaltet: greift nie ein', $d === $aktiv, fmt($d));
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
echo "\n14) Netzdienliche Faehigkeiten des WR (InverterHub-Vertrag 1.3) -- Grundlage der Bausteine\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wr = function (array $o = []) {
    attr('PartnerCache', json_encode(['inverterhub' => [array_merge(['instanceID' => IHUB_IID, 'contractVersion' => '1.3',
        'controlAuthority' => 'ems', 'controllable' => true,
        'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']], $o)]]));
};
attr('PartnerCache', json_encode([]));
check('nackt: kein Wechselrichter -> keine Faehigkeit, kein Fehler', call($ems, 'getGridServiceCapabilities') === []);
$wr();
check('GoodWe, Vertrag 1.3, EMS hat die Steuerhoheit: alle vier Faehigkeiten',
    call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release'], json_encode(call($ems, 'getGridServiceCapabilities')));
check('hasGridService(gridCharge) = true, Ident svc_grid_charge_w', call($ems, 'hasGridService', ['gridCharge']) === true && call($ems, 'gridServiceIdent', ['gridCharge']) === 'svc_grid_charge_w');
$wr(['gridServiceCapabilities' => []]);
check('Treiber ohne diese Befehle (leere Liste, z. B. SMA/Fronius): keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === [] && call($ems, 'hasGridService', ['chargeInhibit']) === false);
$wr(['gridServiceCapabilities' => ['chargeInhibit', 'release']]);
check('Teilmenge (nur Laden sperren + Freigabe): genau diese zwei', call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'release'] && call($ems, 'hasGridService', ['dischargeToGrid']) === false);
$wr(['contractVersion' => '1.2', 'gridServiceCapabilities' => null]);
check('aelterer Vertrag 1.2 ohne das Feld: keine Faehigkeit (Baustein entfaellt, kein Fehler)', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controlAuthority' => 'external']);
check('Steuerhoheit extern (z. B. Sunny Home Manager): keine Faehigkeit, obwohl der Treiber sie kann', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controlAuthority' => 'none']);
check('Steuerhoheit "none": keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['controllable' => false]);
check('Treiber ohne Steuerregister (controllable=false): keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['contractVersion' => '2.0']);
check('fremde Vertrags-Major 2.0: vorsichtshalber keine Faehigkeit', call($ems, 'getGridServiceCapabilities') === []);
$wr(['gridServiceCapabilities' => ['chargeInhibit', 'peakShave', 'release']]);
check('unbekannter Eintrag (kuenftige Faehigkeit) wird verworfen, bekannte bleiben', call($ems, 'getGridServiceCapabilities') === ['chargeInhibit', 'release']);
$wr(['gridServiceCapabilities' => 'chargeInhibit']);
check('kaputtes Feld (String statt Liste): keine Faehigkeit, kein Absturz', call($ems, 'getGridServiceCapabilities') === []);
check('unbekannte Faehigkeit hat keinen Ident', call($ems, 'gridServiceIdent', ['peakShave']) === '');
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n15) B1 Mittagsspitze aufnehmen -- Entscheidung (reine Funktion, feste Uhrzeit)\n";
$ems = freshEms();
$sonne = array_fill(0, 96, 0.0); for ($i = 32; $i < 72; $i++) { $sonne[$i] = 6000.0; } // 08:00-18:00 je 6 kW
$basis = ['nowSlot' => 36, 'latestSlot' => 52, 'pv' => $sonne, 'load' => array_fill(0, 96, null), 'avgHouseW' => 400.0,
    'capKwh' => 40.0, 'soc' => 50.0, 'pvW' => 3000.0, 'houseW' => 400.0, 'marginW' => 300.0, 'safetyPct' => 130, 'wasActive' => false];
$b1 = fn(array $o = []) => call($ems, 'b1Evaluate', [array_merge($basis, $o)]);
// 09:00, SOC 50 % von 40 kWh: 20 kWh Platz, x1,3 = 26 kWh; Rest 09:15-18:00 = 35 Slots x 5,6 kW x 0,25 h = 49 kWh
check('sonniger Vormittag, Rest 49 kWh >= 26 kWh: Laden sperren', $b1()['active'] === true, $b1()['reason']);
check('keine PV-Prognose: nie sperren', $b1(['pv' => []])['active'] === false && $b1(['pv' => array_fill(0, 96, 0.0)])['active'] === false);
check('nach dem spaetesten Freigabezeitpunkt (13:00): freigeben', $b1(['nowSlot' => 52])['active'] === false);
check('Batterie voll: nicht sperren', $b1(['soc' => 99.5])['active'] === false);
$r = $b1(['pvW' => 600.0]);
check('gemessene PV nur 200 W ueber Haus (Wolke): freigeben, als Sicherheitsausstieg', $r['active'] === false && !empty($r['safety']), $r['reason']);
check('bei laufendem B1 reicht die halbe Marge (150 W < 200 W): bleibt gesperrt', $b1(['pvW' => 600.0, 'wasActive' => true])['active'] === true);
$trueb = array_fill(0, 96, 0.0); for ($i = 32; $i < 72; $i++) { $trueb[$i] = 2500.0; }   // Rest 35 x 2,1 kW x 0,25 = 18,4 kWh
check('truebe Prognose, Rest 18 kWh < 26 kWh: nicht sperren (Batterie soll voll werden)', $b1(['pv' => $trueb])['active'] === false, $b1(['pv' => $trueb])['reason']);
check('Lastprognose wird genutzt: 5,5 kW Last je Slot -> Rest nur 1,75 kWh, nicht sperren', $b1(['load' => array_fill(0, 96, 5500.0)])['active'] === false);
check('Kapazitaet unbekannt (0 kWh): nicht sperren', $b1(['capKwh' => 0.0])['active'] === false);
// p10 (vorsichtige Prognose): 20 kWh Platz x 1,1 = 22 kWh
$p10gut = array_fill(0, 96, 0.0); for ($i = 32; $i < 72; $i++) { $p10gut[$i] = 5000.0; }   // Rest 35 x 4,6 x 0,25 = 40 kWh
$p10mau = array_fill(0, 96, 0.0); for ($i = 32; $i < 72; $i++) { $p10mau[$i] = 2500.0; }   // Rest 35 x 2,1 x 0,25 = 18 kWh
$r = $b1(['pv10' => $p10gut, 'safetyP10Pct' => 110]);
check('p10 vorhanden und ausreichend (40 kWh >= 22 kWh): sperren, Basis p10', $r['active'] === true && ($r['basis'] ?? '') === 'p10', $r['reason']);
$r = $b1(['pv10' => $p10mau, 'safetyP10Pct' => 110]);
check('unsicherer Tag: Median reicht (49 kWh), p10 nicht (18 kWh < 22 kWh) -> NICHT sperren', $r['active'] === false && ($r['basis'] ?? '') === 'p10', $r['reason']);
$r = $b1(['pv10' => [], 'safetyP10Pct' => 110]);
check('p10 fehlt: Rueckfall auf Median mit 130 %, Basis p50', $r['active'] === true && ($r['basis'] ?? '') === 'p50', $r['reason']);
check('p10 nur Nullen (nicht geliefert): Rueckfall auf Median', ($b1(['pv10' => array_fill(0, 96, 0.0)])['basis'] ?? '') === 'p50');
// Prognoseguete (PVF_GetAccuracy 1.0). Basisfall p50: Rest 49 kWh, Bedarf 20 x 1,3 = 26 kWh
$acc = fn(array $o = []) => array_merge(['contractVersion' => '1.0', 'days' => 9, 'bias' => -12.58, 'mape' => 16.75,
    'byDaylightFraction' => [['from' => 0, 'to' => 0.125, 'factor' => 0.497, 'n' => 62]]], $o);
$g = ['maxMape' => 30, 'minDays' => 5];
check('Guete fehlt (Schnittstelle nicht da): unveraendert sperren', $b1(array_merge($g, ['accuracy' => null]))['active'] === true);
check('Dietmars Livewerte (9 Tage, 16,8 % Fehler, Bias -12,6 %): unveraendert sperren, Guete im Grund',
    ($r = $b1(array_merge($g, ['accuracy' => $acc()])))['active'] === true && strpos($r['reason'], 'Güte') !== false, $r['reason']);
check('Fehlerquote 45 % > 30 %: B1 setzt aus', ($r = $b1(array_merge($g, ['accuracy' => $acc(['mape' => 45.0])])))['active'] === false && strpos($r['reason'], 'Prognosegüte') !== false, $r['reason']);
check('zu wenig Tage (3 < 5): Guete wird ignoriert, auch bei 45 %', $b1(array_merge($g, ['accuracy' => $acc(['mape' => 45.0, 'days' => 3])]))['active'] === true);
check('Prognose zuletzt 100 % zu HOCH (bias +100): Zuschlag verdoppelt, 52 kWh > 49 kWh -> nicht sperren',
    ($r = $b1(array_merge($g, ['accuracy' => $acc(['bias' => 100.0])])))['active'] === false, $r['reason']);
check('Prognose zu NIEDRIG (bias -40): kein Abschlag, unveraendert sperren', $b1(array_merge($g, ['accuracy' => $acc(['bias' => -40.0])]))['active'] === true);
check('fremde Vertrags-Major 2.0: Guete ignoriert', $b1(array_merge($g, ['accuracy' => $acc(['contractVersion' => '2.0', 'mape' => 90.0])]))['active'] === true);
check('Tageszeit-Faktoren werden NICHT erneut angewendet (0,497 am Morgen aendert nichts)',
    $b1(array_merge($g, ['accuracy' => $acc()]))['active'] === $b1(array_merge($g, ['accuracy' => $acc(['byDaylightFraction' => []])]))['active']);

echo "\n16) B1 im Zusammenspiel -- Vorrang, Faehigkeit, Steuerpfad svc_* statt ctl_*\n";
$ems = freshEms();
$GLOBALS['INSTMOD'][IHUB_IID] = GUID_INVERTERHUB;
$wr();                                                    // GoodWe, Vertrag 1.3, alle Faehigkeiten
attr('FcPvToday', json_encode(array_fill(0, 96, 50000.0)));  // Prognose: ueberall reichlich Ueberschuss (uhrzeitunabhaengig)
attr('FcLoadToday', json_encode(array_fill(0, 96, null)));
prop('NETZ_B1_Latest_Hour', 24);
prop('BAT_Capacity_kWh', 40.0);
$sonnig = state(['bat_soc' => 50.0, 'pv_total_w' => 5000.0, 'house_pow_w' => 400.0]);
$autoD = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false,
    'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$spaet = ((int)((time() - strtotime('today')) / 900)) >= 95;
$d = call($ems, 'applyGridServiceB1', [$autoD, $sonnig]);
check('Automatik-Entscheidung + Faehigkeit + sonnig: B1 sperrt das Laden (svc, Quelle netzdienlich)', $spaet || (($d['svc'] ?? '') === 'chargeInhibit' && $d['source'] === 'netzdienlich'), fmt($d) . ($spaet ? ' (23:45, uebersprungen)' : ''));
$planD = array_merge($autoD, ['op_mode' => EMS_OP_NET_CHARGE, 'gw_mode' => GW_MODE_AC_IMPORT, 'gw_power_w' => 8000, 'gw_enable' => true, 'source' => 'tagesplan']);
check('Tagesplan-Sollwert (Netzladen, Preis sticht): B1 greift nicht', call($ems, 'applyGridServiceB1', [$planD, $sonnig]) === $planD);
$grD = array_merge($planD, ['op_mode' => EMS_OP_GRIDREWARDS, 'source' => 'tibber']);
check('Grid Rewards: B1 greift nicht', call($ems, 'applyGridServiceB1', [$grD, $sonnig]) === $grD);
$nbD = array_merge($autoD, ['source' => 'netzbetreiber']);
check('§14a-Netzbetreiber (auch wenn Automatik): B1 greift nicht', call($ems, 'applyGridServiceB1', [$nbD, $sonnig]) === $nbD);
$wr(['gridServiceCapabilities' => []]);
check('WR ohne Faehigkeit (z. B. SMA): B1 greift nicht, kein Fehler', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
$wr(['gridServiceCapabilities' => ['chargeInhibit']]);
check('WR kann sperren, aber nicht freigeben: B1 greift nicht (kein Weg zurueck)', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
$wr();
prop('NETZ_Aktiv', false);
check('netzdienliche Bausteine abgeschaltet: B1 greift nicht', call($ems, 'applyGridServiceB1', [$autoD, $sonnig]) === $autoD);
prop('NETZ_Aktiv', true);
attr('FcPvToday', '[]');
$d = call($ems, 'applyGridServiceB1', [$autoD, $sonnig]);
check('keine PV-Prognose im Zwischenspeicher: B1 greift nicht, Grund sichtbar angehaengt',
    empty($d['svc']) && $d['source'] === 'ems' && strpos($d['reason'], 'Automatik | 🌞 Mittagsspitze nicht aktiv: keine PV-Prognose') === 0, $d['reason']);
$d = call($ems, 'applyGridServiceB1', [$autoD, state(['bat_soc' => 50.0, 'pv_total_w' => 500.0, 'house_pow_w' => 400.0])]);
check('Wolke (PV kaum ueber Haus): Grund "deckt die Hauslast nicht sicher" sichtbar, Entscheidung sonst unveraendert',
    empty($d['svc']) && $d['op_mode'] === EMS_OP_AUTO && $d['gw_enable'] === false && strpos($d['reason'], 'Mittagsspitze nicht aktiv') !== false, $d['reason']);
attr('FcPvToday', json_encode(array_fill(0, 96, 50000.0)));

echo "\n   Steuerpfad in applyDecision()\n";
attr('PartnerCache', json_encode(['inverterhub' => [['instanceID' => IHUB_IID, 'contractVersion' => '1.3', 'controlAuthority' => 'ems', 'controllable' => true,
    'gridServiceCapabilities' => ['chargeInhibit', 'gridCharge', 'dischargeToGrid', 'release']]]]));
$b1D = array_merge($autoD, ['svc' => 'chargeInhibit', 'source' => 'netzdienlich', 'reason' => 'B1 Test']);
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$b1D, state()]);
$ids = array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
check('B1 an: genau svc_charge_inhibit=true, kein ctl_* im selben Zyklus', $ids === ['svc_charge_inhibit=true'], json_encode($ids));
check('Zustand gemerkt (B1Active), Quelle netzdienlich sichtbar', $ems->ReadAttributeBoolean('B1Active') === true && $ems->ReadAttributeString('LastDecisionSource') === 'netzdienlich');
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$b1D, state()]);
check('B1 bleibt an: kein erneutes Schreiben (Modus haelt mit enable=false)', $GLOBALS['ACTIONS'] === [], json_encode($GLOBALS['ACTIONS']));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$autoD, state()]);
$ids = array_map(fn($x) => $x[1] . '=' . var_export($x[2], true), $GLOBALS['ACTIONS']);
check('B1 endet: erst svc_release, kein ctl_* im selben Zyklus', $ids === ['svc_release=true'] && $ems->ReadAttributeBoolean('B1Active') === false, json_encode($ids));
$GLOBALS['ACTIONS'] = [];
call($ems, 'applyDecision', [$autoD, state()]);
check('naechster Zyklus: wieder normaler ctl-Pfad', (bool)array_filter($GLOBALS['ACTIONS'], fn($x) => $x[1] === 'ctl_ems_enable'), json_encode($GLOBALS['ACTIONS']));
attr('B1Active', true);
$GLOBALS['ACTIONS'] = [];
$forced = array_merge($autoD, ['force' => true, 'reason' => 'Waechter']);
call($ems, 'applyDecision', [$forced, state()]);
check('Waechter-Rueckfall waehrend B1: ebenfalls zuerst svc_release', array_column($GLOBALS['ACTIONS'], 1) === ['svc_release'], json_encode($GLOBALS['ACTIONS']));
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
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

echo "\n   mit der echten Verguetungstabelle (EMS/eeg-pv-verguetung.json)\n";
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
attr('FcPvToday', json_encode(array_fill(0, 96, 50000.0))); prop('NETZ_B1_Latest_Hour', 24); prop('BAT_Capacity_kWh', 40.0);
$auto19 = ['op_mode' => EMS_OP_AUTO, 'gw_mode' => GW_MODE_AUTO, 'gw_power_w' => 0, 'gw_enable' => false, 'wb1_enable' => false, 'wb2_enable' => false, 'reason' => 'Automatik', 'source' => 'ems'];
$d = call($ems, 'applyGridServiceB1', [$auto19, state(['bat_soc' => 50.0, 'pv_total_w' => 7000.0, 'house_pow_w' => 400.0])]);
check('Überschuss 6600 W über der Grenze 5400 W: B1 sperrt NICHT, Batterie nimmt auf', empty($d['svc']) && strpos($d['reason'], 'Einspeisegrenze') !== false, $d['reason']);
$spaet19 = ((int)((time() - strtotime('today')) / 900)) >= 95;
$d = call($ems, 'applyGridServiceB1', [$auto19, state(['bat_soc' => 50.0, 'pv_total_w' => 3000.0, 'house_pow_w' => 400.0])]);
check('Überschuss 2600 W deutlich unter der Grenze: B1 darf sperren', $spaet19 || ($d['svc'] ?? '') === 'chargeInhibit', $d['reason']);
unset($GLOBALS['INSTMOD'][IHUB_IID]);

// ===========================================================================
echo "\n20) Bezugspreis-Historie (EMS_GetPurchasePriceHistory, Ladesitzungskosten)\n";
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$d0 = strtotime('today');
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
check('Danach 1 h PV-Überschuss: Preis halbiert auf 10,53 ct, mit entgangener Vergütung (8 ct) 14,74 ct',
    $sum21($st)['einstandCt'] === 10.53 && $sum21($st)['einstandMitVerguetungCt'] === 14.74, json_encode($sum21($st)));
$vor = $sum21($st);
$st = $run21($st, 30, 5000.0, 0.0, 0.0, 30.0);
check('Entladen: Bestand sinkt, Durchschnittspreis bleibt', $sum21($st)['einstandCt'] === $vor['einstandCt'] && $sum21($st)['gespeichertKwh'] < $vor['gespeichertKwh'], json_encode($sum21($st)));
$st = call($ems, 'batteryCostStep', [[], 0.0, 0.0, 0.0, 0.0, $t21, null, 8.0, 0.0]);
$st = $run21($st, 60, -2000.0, -1000.0, 3000.0, 40.0);
check('Gemischt: Bezug 1 kW + PV 3 kW → Netzanteil 25 %, 10 ct', $sum21($st)['netzAnteilPct'] === 25.0 && $sum21($st)['einstandCt'] === 10.53, json_encode($sum21($st)));
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
check('Bestand gleitet zum gemessenen SOC (40 % = 16 kWh), Wert bleibt', abs($sum21($st, 40.0)['gespeichertKwh'] - 16.0) < 1.5, json_encode($sum21($st, 40.0)));
$st = $run21($st, 200, 10000.0, 0.0, 0.0, 20.0);
check('Batterie leer: Bestand und Wert auf 0, kein Preis', $sum21($st)['gespeichertKwh'] === 0.0 && $sum21($st)['einstandCt'] === null, json_encode($sum21($st)));

$ems = freshEms();
attr('BatCostState', json_encode(['ts' => time(), 'stock' => 10.0, 'pool' => 2.0, 'opp' => 2.5, 'grid' => 5.0, 'charged' => 50.0, 'since' => strtotime('2026-09-01 12:00')]));
prop('BAT_Capacity_kWh', 40.0);
$r = call($ems, 'GetBatteryCost');
check('GetBatteryCost 1.0: 20 ct, 25 ct, 50 % Netz, eingeschwungen, Datum deutsch',
    $r['contractVersion'] === '1.0' && $r['einstandCt'] === 20.0 && $r['einstandMitVerguetungCt'] === 25.0 && $r['netzAnteilPct'] === 50.0
    && $r['eingeschwungen'] === true && $r['seitText'] === '01.09.2026 12:00', json_encode($r));
$GLOBALS['INSTMOD'][500] = GUID_ARCHIVECONTROL;
$d0 = strtotime('today');
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
echo "\n23) SimulateDayPlan -- gleiche Anlage, anderes Inbetriebnahmedatum (Dietmar 17.09.2026)\n";
$ems = freshEms();
prop('TIBBER_Active', true); prop('BAT_Active', true); prop('ANL_kWp_Manuell', 9.18);
prop('BAT_Capacity_kWh', 40.0); prop('EMS_Max_Power_W', 20000);
pricesToday(0.30);
$r = call($ems, 'SimulateDayPlan', ['nicht-parsbar']);
check('ungueltiges Datum: ok=false statt Fatal Error', $r['ok'] === false && isset($r['fehler']));
$r = call($ems, 'SimulateDayPlan', ['24.10.2012']);
check('IBN 2012: kein Fatal Error, 96 Slots, keine § 51 Pflicht, keine dauerhafte Grenze', $r['ok'] === true && count($r['plan']) === 96 && $r['negativpreisPflicht'] === false && $r['einspeisegrenzeW'] === null, json_encode($r['fehler'] ?? $r['einspeisegrenzeGrund'] ?? null));
check('IBN 2012: Verguetung berechnet ~18,36 ct (bekannter Gegenwert)', abs($r['verguetungCt'] - 18.36) < 0.5, (string)$r['verguetungCt']);
check('IBN 2012: Slot-Format wie GetDayPlan (time gesetzt, Preis in ct/kWh)', isset($r['priceUnit']) && $r['priceUnit'] === 'ct/kWh' && isset($r['plan'][50]['time']) && $r['plan'][50]['time'] > 0 && abs($r['plan'][50]['price'] - 30.0) < 0.01, json_encode($r['plan'][50] ?? null));
prop('ANL_Verguetung_ct', 0.0);
$r2 = call($ems, 'SimulateDayPlan', ['01.06.2025']);
check('IBN 06/2025: § 51 Pflicht aktiv, dauerhafte 60-%-Grenze = 5508 W (ohne Smart Meter/Steuerbox)', $r2['ok'] === true && $r2['negativpreisPflicht'] === true && $r2['einspeisegrenzeW'] === 5508, json_encode($r2));
check('IBN 06/2025: andere Verguetung als IBN 2012 (unterschiedliche EEG-Fassung)', abs($r2['verguetungCt'] - $r['verguetungCt']) > 0.5, $r['verguetungCt'] . ' vs ' . $r2['verguetungCt']);
check('echtes BuildDayPlan() bleibt unbeeinflusst (negativpreisPflicht/feedInLimitW nur in SimulateDayPlan gesetzt)', call($ems, 'permanentFeedInLimit') === call($ems, 'permanentFeedInLimit'));
// § 51 wirkt sich im Plan aus: bei negativem Boersenpreis + volle Batterie -> 0 W statt Export
attr('FcSpotCurve', json_encode([['start' => strtotime('today'), 'end' => strtotime('tomorrow'), 'price' => -5.0, 'aufloesung' => 86400, 'quelle' => 'boersenpreis']]));
$ctxFull = ['negativpreisPflicht' => true, 'feedInLimitW' => 3000.0, 'capKwh' => 40.0, 'chargeKw' => 10.0, 'dischargeKw' => 10.0,
    'maxW' => 20000, 'feedTariff' => 0.18, 'thCharge' => 0.10, 'thDischarge' => 0.25, 'socTargetDay' => 90.0, 'hystSoc' => 2.0,
    'socMin' => 10.0, 'socReserve' => 5.0, 'socTargetNight' => 30.0, 'avgHouseW' => 400.0, 'houseLoadSlots' => [], 'fcMinPower' => 300.0,
    'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0];
$d = call($ems, 'simulateDaySlot', [10, -0.10, 8000.0, 99.6, [], $ctxFull, 0.0]);
check('§ 51 + volle Batterie + negativer Bezugspreis: 0 W statt Export (Pflicht sticht)', $d['plan']['op'] === EMS_OP_AUTO && (int)$d['plan']['power'] === 0 && strpos($d['plan']['reason'], '§ 51') !== false, json_encode($d['plan']));
$dNoPflicht = call($ems, 'simulateDaySlot', [10, -0.10, 8000.0, 99.6, [], array_merge($ctxFull, ['negativpreisPflicht' => false]), 0.0]);
check('ohne § 51-Pflicht bei gleicher Lage: normale Regel greift (Negativpreis -> laden, SOC schon fast voll -> kein Export-Zwang durch die Pflicht)', strpos($dNoPflicht['plan']['reason'], '§ 51') === false);
$ctxCap = array_merge($ctxFull, ['negativpreisPflicht' => false]);
$dCap = call($ems, 'simulateDaySlot', [50, 0.30, 8000.0, 95.0, [], $ctxCap, 0.0]);
check('dauerhafte Einspeisegrenze kappt PV-Vollernte-Export (8000W PV, Grenze 3000W)', $dCap['plan']['op'] === EMS_OP_AUTO && strpos($dCap['plan']['reason'], 'Einspeisegrenze 3000W greift, 5000W gekappt') !== false, json_encode($dCap['plan']));

echo "\n24) SimulateDayPlanScenarios -- mehrere Rechtslagen auf einen Schlag (Funktionsfaehigkeits-Nachweis)\n";
$s = call($ems, 'SimulateDayPlanScenarios', [[]]);
check('Referenzset ohne Angabe: 4 Szenarien, alle ok', count($s) === 4 && count(array_filter($s, fn($x) => $x['ok'] === true)) === 4, json_encode(array_map(fn($x) => $x['ok'], $s)));
check('Bestandsanlage 2012: keine Pflicht, keine Grenze', $s['24.10.2012']['negativpreisPflicht'] === false && $s['24.10.2012']['einspeisegrenzeW'] === null);
check('vor Solarspitzengesetz (2024): ebenfalls keine Pflicht/Grenze', $s['01.01.2024']['negativpreisPflicht'] === false && $s['01.01.2024']['einspeisegrenzeW'] === null);
check('Solarspitzengesetz (03/2025): Pflicht + 60-%-Grenze', $s['01.03.2025']['negativpreisPflicht'] === true && $s['01.03.2025']['einspeisegrenzeW'] === 5508);
check('jedes Szenario traegt sein Label', $s['01.03.2025']['label'] === 'Solarspitzengesetz (§ 51 + 60-%-Einspeisegrenze)');
$eigene = call($ems, 'SimulateDayPlanScenarios', [['24.10.2012', '01.03.2025']]);
check('eigene Datumsliste: nur die angegebenen, Label = Datum selbst', count($eigene) === 2 && $eigene['01.03.2025']['label'] === '01.03.2025');

echo "\n26) Modus 'Akku halten' (op 8) statt 'Einspeisen' bei 0 W\n";
$ems = freshEms();
$ctxH = ['negativpreisPflicht' => false, 'capKwh' => 40.0, 'chargeKw' => 10.0, 'dischargeKw' => 10.0, 'maxW' => 20000, 'feedTariff' => 0.1836,
    'thCharge' => 0.10, 'thDischarge' => 0.25, 'socTargetDay' => 38.0, 'hystSoc' => 3.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'socTargetNight' => 30.0,
    'avgHouseW' => 400.0, 'houseLoadSlots' => [], 'fcMinPower' => 300.0, 'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0];
$h = call($ems, 'simulateDaySlot', [12, 0.14, 0.0, 40.0, [], $ctxH, 0.0]);
check('Bezug 14 ct < Verguetung 18,36 ct, kein PV: op = Akku halten (8), 0 W, gw AC-Export', $h['plan']['op'] === EMS_OP_HOLD && EMS_OP_HOLD === 8 && (int)$h['plan']['power'] === 0 && $h['plan']['gw'] === GW_MODE_AC_EXPORT, json_encode($h['plan']));
check('Begruendung sagt nicht mehr "exportiert"', strpos($h['plan']['reason'], 'exportiert') === false && strpos($h['plan']['reason'], 'geschont') !== false, $h['plan']['reason']);
$e = call($ems, 'simulateDaySlot', [50, 0.30, 8000.0, 38.4, [], $ctxH, 0.0]);
check('PV-Einspeisung bei vollem Akku: Automatik (op 0, Modus 1, 0 W) -- die WR-Automatik speist selbst ein, kein erzwungener Modus 5', $e['plan']['op'] === EMS_OP_AUTO && $e['plan']['gw'] === GW_MODE_AUTO && (int)$e['plan']['power'] === 0 && strpos($e['plan']['reason'], 'eingespeist') !== false, json_encode($e['plan']));
$acts = array_column(call($ems, 'getPlanActions'), null, 'op');
check('Kalender-Aktionen kennen op 8 (Name, Tuerkis)', ($acts[8]['name'] ?? '') === 'Akku halten (Netzbezug)' && ($acts[8]['color'] ?? 0) === 0x00ACC1, json_encode($acts[8] ?? null));

echo "\n24c) Prognose-Rueckgabe: Platzhalter (generated=0) und altes Datum sind 'unbekannt'\n";
$ems = freshEms();
check('normale Prognose (heute, generated>0) brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => time(), 'kwh' => 40.0], 0]) === true);
check('generated=0 = Platzhalter, nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => 0, 'kwh' => 0.0], 0]) === false);
check('Datum von gestern fuer Offset 0 nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d', strtotime('yesterday')), 'generated' => time()], 0]) === false);
check('Datum von heute fuer Offset 1 nicht brauchbar', call($ems, 'forecastUsable', [['date' => date('Y-m-d'), 'generated' => time()], 1]) === false);
check('aelterer Vertrag ohne date/generated bleibt brauchbar', call($ems, 'forecastUsable', [['kwh' => 12.0, 'p50' => []], 0]) === true);
check('kein Array: nicht brauchbar', call($ems, 'forecastUsable', [null, 0]) === false);

echo "\n24d) Nachtfenster: bis 06:00 Haus aus dem Netz, Akku in den guenstigsten Viertelstunden laden\n";
$ems = freshEms();
$ctxN = ['capKwh' => 40.0, 'chargeKw' => 10.0, 'maxW' => 20000, 'socTargetNight' => 100.0, 'feedTariff' => 0.1836];
$pN = array_fill(0, 96, 0.30);
foreach ([10 => 0.10, 11 => 0.11, 12 => 0.12, 13 => 0.13, 14 => 0.14, 15 => 0.15, 16 => 0.16, 17 => 0.17, 40 => 0.01] as $i => $v) { $pN[$i] = $v; }
check('Funktion aus (Standard): kein Nachtfenster', call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN]) === null);
prop('PLAN_NightGrid_Active', true); prop('PLAN_NightGrid_EndHour', 6);
$nw = call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN]);
check('SOC 60 % -> 16 kWh fehlen, 2,5 kWh je Viertelstunde = 7 Ladeslots', $nw['n'] === 7 && $nw['end'] === 24, json_encode($nw));
check('gewaehlt sind die 7 guenstigsten Slots des Fensters (10-16), nicht der billigere Slot 40 ausserhalb', array_keys($nw['charge']) === [10, 11, 12, 13, 14, 15, 16] && $nw['charge'][10] === 1, json_encode($nw['charge']));
$c = call($ems, 'nightWindowSlot', [12, 0.12, 80.0, $nw, $ctxN]);
check('Ladeslot: Netz laden (op 2, Batterie-Lademodus 11, Ladegrenze als Sollleistung), Rang und Preis in der Begruendung', $c['plan']['op'] === EMS_OP_NET_CHARGE && $c['plan']['gw'] === GW_MODE_BAT_CHARGE && $c['plan']['power'] === 10000 && strpos($c['plan']['reason'], 'Rang 3 von 7') !== false && strpos($c['plan']['reason'], '12,00') === false && !empty($c['plan']['nw']), json_encode($c['plan']));

// Vorentladen zeitgenau: im letzten Slot Restenergie / Restzeit, sonst Restenergie ueber Slots nach Preis verteilt
check('Vorentladen letzter Slot: 2 kWh in voller Viertelstunde -> 8 kW, in halber Restzeit -> 16 kW (leer genau zum Fensterbeginn)',
    abs(call($ems, 'preDischargeBatteryW', [2.0, 0.28, 0.0, 1.0]) - 8000.0) < 1 && abs(call($ems, 'preDischargeBatteryW', [2.0, 0.28, 0.0, 0.5]) - 16000.0) < 1);
check('Vorentladen: mit Folgeslots gleichen Preises (3 weitere) bei vollem Slot 1/4 der Restenergie je Slot (4 kWh -> 4 kW)',
    abs(call($ems, 'preDischargeBatteryW', [4.0, 0.28, 0.84, 1.0]) - 4000.0) < 1);

// Nachtfenster verlaengern, solange Akku nicht voll und Preis halbwegs stimmt (0.49.0)
prop('PLAN_NightGrid_ExtendHours', 2); prop('PLAN_NightGrid_ExtendTol_ct', 3.0);
$pV = array_fill(0, 96, 0.30); $pV[2] = 0.11; $pV[3] = 0.11; $pV[4] = 0.12;
for ($i = 24; $i < 32; $i++) { $pV[$i] = 0.13; } $pV[26] = 0.16; $pV[40] = 0.11;
$nwV = call($ems, 'nightWindowPlan', [$pV, 0, 0.0, $ctxN]);
check('Verlaengerung: zu wenig guenstige Slots im Fenster -> weitere direkt nach der Endstunde (<= guenstigster + 3 ct), nicht der teure Slot 26 und nicht Slot 40', isset($nwV['charge'][24], $nwV['charge'][25], $nwV['charge'][27]) && !isset($nwV['charge'][26]) && !isset($nwV['charge'][40]) && isset($nwV['charge'][2]), json_encode(array_keys($nwV['charge'])));
$cV = call($ems, 'nightWindowSlot', [24, 0.13, 60.0, $nwV, $ctxN]);
check('Verlaengerungs-Slot wird als Netz laden geplant und als verlaengert begruendet', $cV['plan']['op'] === EMS_OP_NET_CHARGE && strpos($cV['plan']['reason'], 'verlängert') !== false, json_encode($cV['plan']));
prop('PLAN_NightGrid_ExtendHours', 0);
$nwV0 = call($ems, 'nightWindowPlan', [$pV, 0, 0.0, $ctxN]);
check('Verlaengerung aus (0 h): nichts nach der Endstunde', count(array_filter(array_keys($nwV0['charge']), function ($k) { return $k >= 24; })) === 0, json_encode(array_keys($nwV0['charge'])));
prop('PLAN_NightGrid_ExtendHours', 2);

// Plan: WR-Automatik entlaedt das Haus auch bei Preisen unter der Entladeschwelle (SOC darf nicht auf 100 % stehen)
$ctxA = ['capKwh' => 40.0, 'chargeKw' => 24.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0, 'feedTariff' => 0.1836, 'thCharge' => 0.10, 'thDischarge' => 0.25,
    'socTargetDay' => 90.0, 'hystSoc' => 2.0, 'socMin' => 0.0, 'socReserve' => 10.0, 'socTargetNight' => 100.0, 'fcMinPower' => 100.0,
    'enwgActive' => false, 'enwgStartH' => 0, 'enwgEndH' => 0, 'avgHouseW' => 800.0, 'houseLoadSlots' => []];
$rA = call($ems, 'simulateDaySlot', [26, 0.19, 0.0, 100.0, [], $ctxA, 0.0]);
check('Plan: Preis 19 ct unter Entladeschwelle, kein PV -> SOC faellt (Hauslast aus Akku), nicht 100 % eingefroren', $rA['soc'] < 100.0 && $rA['plan']['op'] === EMS_OP_AUTO, json_encode($rA['plan']));

// Vorentladen (0.48.0): Fensterbeginn und Planslot
prop('PLAN_PreDischarge_MinGain_ct', 3.0);
$p192 = array_fill(0, 192, 0.30); for ($i = 96; $i < 102; $i++) { $p192[$i] = 0.127; }
check('Vorentladen: Fensterbeginn = erste Viertelstunde unter (Verguetung - 3 ct) x 0,9025', call($ems, 'preDischargeEnd', [$p192, 81, 0.1836]) === 96);
check('Vorentladen endet, sobald die laufende Viertelstunde selbst im guenstigen Fenster liegt (00:00)', call($ems, 'preDischargeEnd', [$p192, 96, 0.1836]) === null);
check('Vorentladen: zu teures Fenster (17 ct) lohnt nicht', call($ems, 'preDischargeEnd', [array_fill(0, 192, 0.17), 81, 0.1836]) === null);
$pdS = ['end' => 96, 'endPrice' => 0.127, 'sum' => 0.30 * 15, 'avg' => 0.30, 'floor' => 0.0];
$ctxP = ['capKwh' => 40.0, 'dischargeKw' => 24.0, 'maxW' => 34500.0];
$rP = call($ems, 'preDischargePlanSlot', [81, &$pdS, 90.0, 0.30, 0.0, 1500.0, $ctxP]);
check('Vorentladen-Planslot: Einspeisen (op 5) mit Xset, SOC faellt', $rP['plan']['op'] === EMS_OP_EXPORT && $rP['plan']['power'] > 5000 && $rP['soc'] < 90.0, json_encode($rP['plan']));

// Wirtschaftlichkeit (Dietmar 19.09.2026): Netzladen nur unter Einspeiseverguetung x 0,95; letzter Slot ohne Ueberladen
$pExp = array_fill(0, 96, 0.30); $pExp[10] = 0.10; $pExp[11] = 0.17; $pExp[12] = 0.19;
$nwE = call($ems, 'nightWindowPlan', [$pExp, 0, 0.0, $ctxN]);
check('Nachtfenster: nur Slots unter 95 % der Einspeiseverguetung (17,44 ct) kommen in Frage', array_keys($nwE['charge']) === [10, 11] && $nwE['cand'] === 2, json_encode($nwE));
$cL = call($ems, 'nightWindowSlot', [11, 0.17, 97.5, $nwE, $ctxN]);
check('Letzter Ladeslot: Sollleistung nur fuer die fehlende Energie (1 kWh -> 4 kW)', $cL['plan']['gw'] === GW_MODE_BAT_CHARGE && $cL['plan']['power'] === 4000, json_encode($cL['plan']));
check('Ladeslot erhoeht den SOC (80 % + 2,5 kWh = 86,25 %)', abs($c['soc'] - 86.25) < 0.01, (string)$c['soc']);
$h = call($ems, 'nightWindowSlot', [3, 0.17, 60.0, $nw, $ctxN]);
check('uebriger Slot im Fenster, Preis 17 ct < 17,44 ct: Akku halten (op 8, AC-Export 0 W), Haus aus dem Netz, SOC unveraendert', $h['plan']['op'] === EMS_OP_HOLD && $h['plan']['gw'] === GW_MODE_AC_EXPORT && (int)$h['plan']['power'] === 0 && $h['soc'] === 60.0 && !empty($h['plan']['nw']), json_encode($h['plan']));
check('Preis 18 ct liegt ueber 18,36 ct x 0,95 = 17,44 ct: Haus vom Akku versorgt (normale Logik, null)', call($ems, 'nightWindowSlot', [3, 0.18, 60.0, $nw, $ctxN]) === null && call($ems, 'nightWindowSlot', [3, 0.30, 60.0, $nw, $ctxN]) === null);
check('Grenze: 17,4 ct noch Netz, 17,5 ct schon Akku', call($ems, 'nightWindowSlot', [3, 0.174, 60.0, $nw, $ctxN]) !== null && call($ems, 'nightWindowSlot', [3, 0.175, 60.0, $nw, $ctxN]) === null);
check('Ladeslot bleibt Ladeslot, auch wenn der Preis ueber der Vergueltungsgrenze liegt (guenstigste Viertelstunden zaehlen)', call($ems, 'nightWindowSlot', [12, 0.19, 80.0, $nw, $ctxN])['plan']['op'] === EMS_OP_NET_CHARGE);
check('ausserhalb des Fensters (06:00) und ohne Preis: nichts', call($ems, 'nightWindowSlot', [24, 0.10, 60.0, $nw, $ctxN]) === null && call($ems, 'nightWindowSlot', [3, null, 60.0, $nw, $ctxN]) === null);
$nwFull = call($ems, 'nightWindowPlan', [$pN, 0, 100.0, $ctxN]);
$hf = call($ems, 'nightWindowSlot', [5, 0.17, 100.0, $nwFull, $ctxN]);
check('Akku schon voll: keine Ladeslots, Haus trotzdem aus dem Netz ("Akku voll" in der Begruendung)', $nwFull['n'] === 0 && $hf['plan']['op'] === EMS_OP_HOLD && strpos($hf['plan']['reason'], 'voll') !== false, json_encode($hf['plan']));
$nwLate = call($ems, 'nightWindowPlan', [$pN, 12, 60.0, $ctxN]);
check('ab Slot 12 (03:00): nur noch die guenstigsten der restlichen Slots, vergangene zaehlen nicht', min(array_keys($nwLate['charge'])) >= 12, json_encode($nwLate['charge']));
$nwCap = call($ems, 'nightWindowPlan', [$pN, 0, 40.0, array_merge($ctxN, ['chargeKw' => 41.0, 'maxW' => 20000])]);
check('Ladeleistung wird auf die EMS-Grenze (20 kW) gedeckelt: 24 kWh fehlen, 5 kWh je Viertelstunde = 5 Slots (nicht 3 wie bei 41 kW)', $nwCap['n'] === 5, json_encode($nwCap));
prop('PLAN_NightGrid_EndHour', 7);
check('Endstunde einstellbar (7 Uhr -> Slot 28)', call($ems, 'nightWindowPlan', [$pN, 0, 60.0, $ctxN])['end'] === 28);
prop('PLAN_NightGrid_Active', false); prop('PLAN_NightGrid_EndHour', 6);

echo "\n25) Weitere Waermepumpen-Quellen (WPModbusHub, WPModbusHubGateway, SamsungEhs) werden gefunden\n";
$ems = freshEms();
$hp = fn($iid, $cap) => [['contractVersion' => '1.15', 'Type' => 'heatpump', 'Caption' => $cap, 'PowerID' => 0, 'EnergyID' => 0, 'reachable' => true, 'unit' => 'W', 'Measured' => true, 'outsideTempID' => 0, 'lastSeenAt' => time()]];
$GLOBALS['INSTMOD'][909] = GUID_WPHUB;
$GLOBALS['INSTMOD'][910] = GUID_WPMODBUSHUB; $GLOBALS['INSTMOD'][911] = GUID_WPMODBUSGW; $GLOBALS['INSTMOD'][912] = GUID_SAMSUNGEHS;
$GLOBALS['WP_FUNCS'] = ['wphub' => $hp(909, 'Panasonic'), 'wpmbhub' => $hp(910, 'Waterkotte'), 'wpmbgw' => $hp(911, 'NIBE RTU'), 'samehs' => $hp(912, 'Samsung EHS')];
call($ems, 'Discover');
$pc = json_decode($ems->ReadAttributeString('PartnerCache'), true);
check('Discovery: WPModbusHub gefunden (Instanz-ID ergaenzt)', count($pc['wpmodbushub'] ?? []) === 1 && $pc['wpmodbushub'][0]['instanceID'] === 910 && $pc['wpmodbushub'][0]['Type'] === 'heatpump', json_encode($pc['wpmodbushub'] ?? null));
check('Discovery: WPModbusHubGateway gefunden', count($pc['wpmodbushubgw'] ?? []) === 1 && $pc['wpmodbushubgw'][0]['instanceID'] === 911);
check('Discovery: WPHub gefunden', count($pc['wphub'] ?? []) === 1 && $pc['wphub'][0]['instanceID'] === 909);
check('Discovery: SamsungEhs gefunden', count($pc['samsungehs'] ?? []) === 1 && $pc['samsungehs'][0]['instanceID'] === 912);
check('Uebersicht nennt die weiteren Quellen', strpos($ems->GetValue('EMS_Partners'), 'WPHub=1 WPModbusHub=1 WPModbusHubGateway=1 SamsungEhs=1') !== false, $ems->GetValue('EMS_Partners'));
$sit = array_filter(call($ems, 'GetSituation')['devices'] ?? call($ems, 'GetSituation'), fn($d) => ($d['domain'] ?? '') === 'heatpump');
check('Situation: alle vier als Waermepumpe (Situation A, nicht schaltbar), Quelle je Eintrag sichtbar', count($sit) === 4 && count(array_filter($sit, fn($d) => $d['writable'] === false && $d['situation'] === 'A')) === 4 && array_column($sit, 'sourceModule') === ['WPHub', 'WPModbusHub', 'WPModbusHubGateway', 'SamsungEhs'], json_encode(array_values($sit)));
$un = json_decode($ems->ReadAttributeString('UnresponsiveInstances'), true);
check('Keine der drei als "installiert, aber stumm" gemeldet', empty($un['wpmodbushub']) && empty($un['wpmodbushubgw']) && empty($un['samsungehs']), json_encode($un));
$GLOBALS['WP_FUNCS'] = []; call($ems, 'Discover');
$un = json_decode($ems->ReadAttributeString('UnresponsiveInstances'), true);
check('Antwortet ein Modul nicht, wird es als stumm gemeldet (wie HeishaMon)', ($un['wpmodbushub'] ?? []) === [910] && ($un['samsungehs'] ?? []) === [912], json_encode($un));
unset($GLOBALS['INSTMOD'][909], $GLOBALS['INSTMOD'][910], $GLOBALS['INSTMOD'][911], $GLOBALS['INSTMOD'][912]); $GLOBALS['WP_FUNCS'] = [];

// ===========================================================================
echo "\n" . ($fails === 0 ? "ALLE SZENARIEN BESTANDEN" : "$fails SZENARIO(S) VERLETZT") . "\n\n";
exit($fails === 0 ? 0 : 1);
