<?php
// Alle Zeit- und Sommerzeitfaelle sind auf deutsche Ortszeit ausgelegt, unabhaengig von der Zeitzone der Maschine.
date_default_timezone_set('Europe/Berlin');
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
define('KL_MESSAGE', 10201); define('KL_NOTIFY', 10202); define('KL_WARNING', 10203); define('KL_ERROR', 10204); define('KL_DEBUG', 10205); define('KL_CUSTOM', 10206);
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
function IPS_GetLocation($id)     { return IPS_GetName($id); }
function IPS_SetName($id, $n)     { $GLOBALS['OBJ'][$id]['ObjectName'] = $n; }
function IPS_SetParent($id, $p)   { $GLOBALS['OBJ'][$id]['ParentID'] = $p; }
function IPS_SetIdent($id, $i)    { $GLOBALS['OBJ'][$id]['ObjectIdent'] = $i; }
function IPS_SetPosition($id, $p) {}
function IPS_GetChildrenIDs($id) {
    $out = [];
    // Nur echte Objekte: Testabschnitte setzen gelegentlich einzelne Schluessel (z. B. HasChildren) an noch nicht angelegten IDs
    foreach ($GLOBALS['OBJ'] as $k => $o) { if (is_array($o) && array_key_exists('ParentID', $o) && $o['ParentID'] == $id) { $out[] = $k; } }
    return $out;
}
function IPS_GetObjectIDByIdent($ident, $parent) {
    foreach (IPS_GetChildrenIDs($parent) as $c) {
        if (($GLOBALS['OBJ'][$c]['ObjectIdent'] ?? null) === $ident) { return $c; }
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
function IPS_GetModuleList()       { return array_keys($GLOBALS['MODULES'] ?? []); }
function IPS_GetModule($guid)      { return $GLOBALS['MODULES'][$guid] ?? []; }
function BMWX_GetVehicleData($iid) { return $GLOBALS['VEHDATA'][$iid] ?? null; }
function TESSIE_GetVehicleState($iid) { return $GLOBALS['TESSIE_STATE'][$iid] ?? null; }
function IPS_GetLibrary($id)      { return ['Version' => 'test', 'Build' => 0]; }
function IPS_LogMessage($sender, $msg) { $GLOBALS['LOG'][] = $sender . ': ' . $msg; }
function IPS_ApplyChanges($iid)   {}
function IPS_SetProperty($iid, $n, $v) { $GLOBALS['PROP'][$iid][$n] = $v; }
function IPS_RequestAction($iid, $ident, $value) {
    if (!empty($GLOBALS['IPSWRITE_FAIL'])) { if ($GLOBALS['IPSWRITE_FAIL'] === 'throw') { throw new Exception('Instance does not exist'); } return false; }
    $GLOBALS['ACTIONS'][] = [$iid, $ident, $value];
    $GLOBALS['CTL'][$ident] = $value;
    return true;
}
foreach (['VARIABLETYPE_BOOLEAN' => 0, 'VARIABLETYPE_INTEGER' => 1, 'VARIABLETYPE_FLOAT' => 2, 'VARIABLETYPE_STRING' => 3,
          'VARIABLE_PRESENTATION_VALUE_PRESENTATION' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}', 'VARIABLE_PRESENTATION_ENUMERATION' => '{52D9E126-D7D2-2CBB-5E62-4CF7BA7C5D82}',
          'VARIABLE_PRESENTATION_SLIDER' => '{6B9CAEEC-5958-C223-30F7-BD36569FC57A}', 'VARIABLE_PRESENTATION_VALUE_INPUT' => '{6A8FC8D2-5B39-D4A1-8D1D-F2E4C7F80F04}'] as $k => $v) { if (!defined($k)) { define($k, $v); } }
function GetValue($id)            { return $GLOBALS['VAL'][$id] ?? 0; }
function GetValueInteger($id)     { return (int)($GLOBALS['VAL'][$id] ?? 0); }
// EMS_DUMP=<Datei>: alle sichtbaren Texte (Variablen, Formular, Rueckgaben, Push) mitschreiben -- Gegenprobe alt/neu auf Deutsch
function emsDump(string $kind, $v): void { if (getenv('EMS_DUMP') && empty($GLOBALS['EMS_LANG_EN']) && (is_string($v) || is_array($v))) { $GLOBALS['DUMP'][] = $kind . ': ' . (is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)); } }
function SetValue($id, $v)        { emsDump('SetValue', $v); $GLOBALS['VAL'][$id] = $v; return true; }
function WFC_PushNotification($id, $title, $text, $sound, $target) { emsDump('Push', $title . ' | ' . $text); $GLOBALS['PUSH'][] = [$title, $text]; return true; }
function VISU_PostNotificationEx($id, $title, $text, $icon, $sound, $target) { emsDump('Push', $title . ' | ' . $text); $GLOBALS['PUSH'][] = [$title, $text, 'visu']; return true; }
function IPS_GetKernelStartTime() { return $GLOBALS['KERNEL_START'] ?? 0; }
function RequestAction($id, $v)   { $GLOBALS['VARACTIONS'][] = [$id, $v]; $GLOBALS['VAL'][$id] = $v; return true; }
function AC_SetLoggingStatus($a, $b, $c) { $GLOBALS['LOGGED'][$b] = $c; $GLOBALS['LOGSET'][] = $b; return true; }
function AC_GetLoggingStatus($a, $b) { return !empty($GLOBALS['LOGGED'][$b]); }
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
function SPOT_GetPriceCurve($iid)         { return $GLOBALS['SPOT_CURVE'] ?? []; }
function TIBBERGR_GetPriceCurve($iid)     { return $GLOBALS['TIBBER_CURVE']; }
function CHUB_SetPlanInfo($iid, $text, $until = 0) { $GLOBALS['PLANINFO'][] = [$iid, $text, $until]; return true; }
function TIBBERGR_GetActiveControls($iid) { return $GLOBALS['ACTIVE_CONTROLS']; }
$GLOBALS['FLEX_DEVICES'] = [];
function TIBBERGR_GetFlexDevices($iid)    { return $GLOBALS['FLEX_DEVICES']; }
function SGW_GetState($iid)               { return $GLOBALS['SGW_STATE']; }
$GLOBALS['OHUB_FUNCS'] = [];
function OHUB_GetFunctions($iid)          { return $GLOBALS['OHUB_FUNCS']; }
$GLOBALS['WP_FUNCS'] = [];
function WPHUB_GetFunctions($iid)          { return $GLOBALS['WP_FUNCS']['wphub'] ?? []; }
function WPMBHUB_GetFunctions($iid)       { return $GLOBALS['WP_FUNCS']['wpmbhub'] ?? []; }
function WPMBGW_GetFunctions($iid)        { return $GLOBALS['WP_FUNCS']['wpmbgw'] ?? []; }
function SAMEHS_GetFunctions($iid)        { return $GLOBALS['WP_FUNCS']['samehs'] ?? []; }
function WPEEB_GetFunctions($iid)         { return $GLOBALS['WP_FUNCS']['wpeeb'][$iid] ?? []; }
$GLOBALS['CHEEB_FUNCS'] = [];
function CHEEB_GetFunctions($iid)         { return $GLOBALS['CHEEB_FUNCS'][$iid] ?? []; }

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
    protected function RegisterAttributeFloat($n, $v)   { $this->defs['@' . $n] = $v; }
    public function ReadAttributeString($n)   { return (string)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? ''); }
    public function ReadAttributeInteger($n)  { return (int)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? 0); }
    public function ReadAttributeBoolean($n)  { return (bool)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? false); }
    public function ReadAttributeFloat($n)    { return (float)($GLOBALS['ATTR'][$this->InstanceID][$n] ?? $this->defs['@' . $n] ?? 0.0); }
    public function WriteAttributeString($n, $v)  { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    public function WriteAttributeInteger($n, $v) { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    public function WriteAttributeBoolean($n, $v) { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
    public function WriteAttributeFloat($n, $v)   { $GLOBALS['ATTR'][$this->InstanceID][$n] = $v; }
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
    protected function MaintainVariable($ident, $name, $type, $pres, $pos, $keep) {
        $id = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if (!$keep) { if ($id !== false) { unset($GLOBALS['OBJ'][$id], $GLOBALS['VAR'][$id], $GLOBALS['VAL'][$id]); } return; }
        if ($id === false) { $this->regVar($ident, $name, [0 => false, 1 => 0, 2 => 0.0, 3 => ''][$type], $type); }
        $GLOBALS['PRES'][$ident] = $pres;
    }
    protected function RegisterTimer($n, $i, $s) {}
    protected function SetTimerInterval($n, $i) {}
    protected function SetStatus($s) {}
    protected function SendDebug($sender, $msg, $format) {}
    protected function LogMessage($msg, $type) { $GLOBALS['LOG'][] = 'LogMessage(' . $type . '): ' . $msg; }
    public function UpdateFormField($f, $p, $v) { emsDump('Form ' . $f . '.' . $p, $v); $GLOBALS['FORMFIELD'][$f][$p] = $v; }
    protected function RegisterMessage($a, $b) {}
    // Zweisprachigkeit: der Pruefstand laeuft wie ein deutsches Symcon ($GLOBALS['EMS_LANG_EN'] = true: Englisch = Quelltext)
    public function Translate($s) { $GLOBALS['TRANSLATE_CALLS'] = ($GLOBALS['TRANSLATE_CALLS'] ?? 0) + 1; $GLOBALS['TRANSLATE_LAST'] = $s; return emsLocaleDe()[$s] ?? $s; }
}

/** Deutsche Fassungen aus EMS/locale.json (nur der Pruefstand liest die Datei, das Modul nie selbst). */
function emsLocaleDe(): array
{
    static $de = null;
    if ($de === null) {
        $de = json_decode((string)file_get_contents(dirname(__DIR__) . '/EMS/locale.json'), true)['translations']['de'] ?? [];
    }
    return !empty($GLOBALS['EMS_LANG_EN']) ? [] : $de;
}

/** Formular so, wie die deutsche Konsole es zeigt: caption/label/Optionen/Spaltenkoepfe ueber locale.json, Listenzellen (values) nicht (SUITE.md Store-Punkt 25). */
function emsFormDe(string $json): string
{
    $de = emsLocaleDe();
    $walk = function ($node) use (&$walk, $de) {
        if (!is_array($node)) { return $node; }
        foreach ($node as $k => $v) {
            if ($k === 'values') { continue; }
            if (is_string($v) && in_array($k, ['caption', 'label', 'suffix', 'prefix'], true)) { $node[$k] = $de[$v] ?? $v; }
            elseif (is_array($v)) { $node[$k] = $walk($v); }
        }
        return $node;
    };
    $out = json_encode($walk(json_decode($json, true)), JSON_UNESCAPED_UNICODE);
    emsDump('Formular', $out);
    return $out;
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
    $r = $m->invokeArgs($obj, $args);
    emsDump('call ' . $method, $r);
    return $r;
}
function prop($n, $v) { $GLOBALS['PROP'][EMS_IID][$n] = $v; unset($GLOBALS['ATTR'][EMS_IID]['Live_' . $n], $GLOBALS['ATTR'][EMS_IID]['LiveSrc_' . $n]); }
/** Bedienwert (0.101.0): Attribut Live_<Property>, sonst die Property (wie liveStr im Modul). */
function live($n) { $a = $GLOBALS['ATTR'][EMS_IID]['Live_' . $n] ?? ''; return ($a === '' && ($GLOBALS['ATTR'][EMS_IID]['LiveSrc_' . $n] ?? '') === '') ? ($GLOBALS['PROP'][EMS_IID][$n] ?? '') : $a; }
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
    // 0.100.0: Die Bestandsfaelle rechnen mit dem abgeleiteten Pfad (Rundwirkungsgrade 0 = aus BAT_Conv_Eff_Pct 95 %), damit ihre
    // Zahlen (0,95 je Wandlung) gueltig bleiben. Die neuen Standards 91/82 prueft Abschnitt 8al ausdruecklich mit EMS_RAW_DEFAULTS.
    if (empty($GLOBALS['EMS_RAW_DEFAULTS'])) { prop('BAT_RT_PV_Pct', 0.0); prop('BAT_RT_Grid_Pct', 0.0); }
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

// Szenarien in Kapiteln (Umbau 18.11.2026, vorher eine Datei mit 4.500 Zeilen). Reihenfolge = Dateiname; im globalen
// Gueltigkeitsbereich geladen, weil spaetere Kapitel Werte frueherer nutzen.
define('EMS_TOOLS_DIR', __DIR__);
foreach (['10-steuerung', '20-formular-sprache', '30-konstellationen-preise', '40-solis', '50-wallbox', '60-preise-planung',
          '70-regressionen-netzdienlich', '80-anlage-abrechnung', '90-simulation-waermepumpe', '100-formularstatus-bilanz',
          '110-planung-verbund', '120-umbau-november', '130-phasenwaechter'] as $emsKapitel) {
    require __DIR__ . '/szenarien/' . $emsKapitel . '.php';
}

// ===========================================================================
if (getenv('EMS_DUMP')) { file_put_contents(getenv('EMS_DUMP'), implode("\n", $GLOBALS['DUMP'] ?? []) . "\n"); }
echo "\n" . ($fails === 0 ? "ALLE SZENARIEN BESTANDEN" : "$fails SZENARIO(S) VERLETZT") . "\n\n";
exit($fails === 0 ? 0 : 1);
