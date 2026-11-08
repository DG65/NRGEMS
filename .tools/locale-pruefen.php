<?php

declare(strict_types=1);

// ===========================================================================
// Statische Pruefung der Zweisprachigkeit (SUITE.md Konventionen Punkt 9, Store-Punkt 25).
//   php .tools/locale-pruefen.php [Vergleichsstand, Standard HEAD]
// 1. locale.json ist gueltiges JSON, ohne doppelte Schluessel.
// 2. Jeder Formulartext aus form.json (caption/label/suffix/prefix, auch Optionen, Spalten, status) und jedes
//    Literal in $this->t('...') / self::fest('...') / 'caption' => '...' (englisch) aus module.php und FormLayout.php
//    sowie jede Zeile von EMS_NEWS_VERSIONS und FORM_LAYOUT_TITLES steht in locale.json.
// 3. form.json enthaelt in Formulartexten kein Deutsch mehr (Heuristik).
// 4. Gegenprobe: form.json mit locale.json "auf Deutsch" gebracht ergibt exakt das form.json des Vergleichsstands
//    (bis auf die neu vergebenen name-Felder), und die NEWS-Zeilen ergeben exakt die alten deutschen Zeilen.
// Rueckgabe 1 bei einem Fehler.
// ===========================================================================

$root = dirname(__DIR__);
$ref = $argv[1] ?? 'HEAD';
$fehler = 0;
$fail = function (string $m) use (&$fehler) { echo "  FEHLER  $m\n"; $fehler++; };

// --- 1. locale.json ---
$raw = (string)file_get_contents($root . '/EMS/locale.json');
$loc = json_decode($raw, true);
if (!is_array($loc) || !isset($loc['translations']['de']) || !is_array($loc['translations']['de'])) {
    echo "  FEHLER  locale.json ungueltig\n";
    exit(1);
}
$de = $loc['translations']['de'];
preg_match_all('/^\s{12}("(?:[^"\\\\]|\\\\.)*")\s*:/m', $raw, $m);
$keys = array_map(function ($k) { return json_decode($k); }, $m[1]);
if (count($keys) !== count($de)) { $fail('Schluesselzahl roh ' . count($keys) . ' != dekodiert ' . count($de) . ' (doppelte Schluessel?)'); }
foreach (array_count_values($keys) as $k => $n) { if ($n > 1) { $fail('doppelter Schluessel: ' . $k); } }
echo 'locale.json: ' . count($de) . " Eintraege\n";

// --- 2./3. form.json ---
$deutsch = '/[äöüÄÖÜß]|\b(und|oder|nicht|nur|wenn|für|der|die|das|bei|mit|ohne|aus|Tagesplan|Akku|Batterie|Netz)\b/u';
$formTexte = [];
$walk = function ($n) use (&$walk, &$formTexte) {
    if (!is_array($n)) { return; }
    foreach ($n as $k => $v) {
        if ($k === 'values') { continue; }
        if (is_string($v) && in_array($k, ['caption', 'label', 'suffix', 'prefix'], true)) { $formTexte[] = $v; }
        elseif (is_array($v)) { $walk($v); }
    }
};
$form = json_decode((string)file_get_contents($root . '/EMS/form.json'), true);
$walk($form);
$formTexte = array_unique($formTexte);
foreach ($formTexte as $s) {
    if (!preg_match('/\p{L}/u', $s)) { continue; }
    if (!array_key_exists($s, $de)) { $fail('form.json-Text fehlt in locale.json: ' . $s); }
    // Objektname des Wochenplans bleibt bis Schritt 2 deutsch (wird zur Laufzeit so angelegt), im englischen Text daher woertlich
    if (preg_match($deutsch, str_replace('"EMS Tagesplan (automatisch)"', '', $s))) { $fail('form.json enthaelt noch Deutsch: ' . $s); }
}
echo 'form.json: ' . count($formTexte) . " Formulartexte\n";

// --- 2. PHP-Literale ---
$literale = [];
$nochDeutsch = 0;
foreach (['module.php', 'FormLayout.php'] as $datei) {
    $tok = token_get_all((string)file_get_contents($root . '/EMS/' . $datei));
    $n = count($tok);
    for ($i = 0; $i < $n; $i++) {
        $t = $tok[$i];
        if (!is_array($t)) { continue; }
        // t('...') / fest('...')
        if ($t[0] === T_STRING && in_array($t[1], ['t', 'fest'], true)) {
            $j = $i + 1;
            while ($j < $n && is_array($tok[$j]) && $tok[$j][0] === T_WHITESPACE) { $j++; }
            if (($tok[$j] ?? null) !== '(') { continue; }
            $j++;
            while ($j < $n && is_array($tok[$j]) && $tok[$j][0] === T_WHITESPACE) { $j++; }
            if (is_array($tok[$j] ?? null) && $tok[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                $nx = $j + 1;
                while ($nx < $n && is_array($tok[$nx]) && $tok[$nx][0] === T_WHITESPACE) { $nx++; }
                if (($tok[$nx] ?? null) === ')') { $literale[] = [$datei, $t[2], eval('return ' . $tok[$j][1] . ';'), true]; }
            }
        }
        // 'caption' => '...'
        if ($t[0] === T_CONSTANT_ENCAPSED_STRING && $t[1] === "'caption'") {
            $j = $i + 1;
            while ($j < $n && is_array($tok[$j]) && $tok[$j][0] === T_WHITESPACE) { $j++; }
            if (!is_array($tok[$j] ?? null) || $tok[$j][0] !== T_DOUBLE_ARROW) { continue; }
            $j++;
            while ($j < $n && is_array($tok[$j]) && $tok[$j][0] === T_WHITESPACE) { $j++; }
            if (!is_array($tok[$j] ?? null) || $tok[$j][0] !== T_CONSTANT_ENCAPSED_STRING) { continue; }
            $nx = $j + 1;
            while ($nx < $n && is_array($tok[$nx]) && $tok[$nx][0] === T_WHITESPACE) { $nx++; }
            if (in_array($tok[$nx] ?? null, [')', ','], true)) { $literale[] = [$datei, $tok[$j][2], eval('return ' . $tok[$j][1] . ';'), false]; }
        }
    }
}
foreach ($literale as [$datei, $zeile, $s, $pflicht]) {
    if (!preg_match('/\p{L}/u', $s)) { continue; }
    if (!$pflicht && preg_match('/[äöüÄÖÜß]/u', $s)) { $nochDeutsch++; continue; } // Laufzeittext, spaeterer Schritt
    if (!array_key_exists($s, $de)) { $fail("$datei:$zeile fehlt in locale.json: $s"); }
}
echo count($literale) . " PHP-Literale geprueft (davon $nochDeutsch deutsche Laufzeit-Captions, Schritt 2)\n";

// NEWS und FORM_LAYOUT_TITLES aus dem Quelltext
$mod = (string)file_get_contents($root . '/EMS/module.php');
$newsNeu = newsAus($mod);
foreach ($newsNeu as $ver => $zeilen) { foreach ($zeilen as $z) { if (!array_key_exists($z, $de)) { $fail("NEWS $ver fehlt: $z"); } } }
if (preg_match('/FORM_LAYOUT_TITLES = (array\(.*?\n    \));/s', $mod, $mm)) {
    foreach (eval('return ' . $mm[1] . ';') as $tt) { if (!array_key_exists($tt, $de)) { $fail('FORM_LAYOUT_TITLES fehlt: ' . $tt); } }
} else { $fail('FORM_LAYOUT_TITLES nicht gefunden'); }

// --- 4. Gegenprobe gegen den Vergleichsstand ---
$alt = json_decode((string)shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg($ref . ':EMS/form.json') . ' 2>/dev/null'), true);
if (!is_array($alt)) {
    $fail("form.json aus $ref nicht lesbar");
} else {
    $neueNamen = '/^(Panel[A-Za-z0-9]+|GridOverviewHint|InverterFallbackHint)$/';
    $toDe = function ($n) use (&$toDe, $de, $neueNamen) {
        if (!is_array($n)) { return $n; }
        $o = [];
        foreach ($n as $k => $v) {
            if ($k === 'name' && is_string($v) && preg_match($neueNamen, $v)) { continue; }
            if ($k !== 'values' && is_string($v) && in_array($k, ['caption', 'label', 'suffix', 'prefix'], true)) { $o[$k] = $de[$v] ?? $v; }
            else { $o[$k] = is_array($v) && $k !== 'values' ? $toDe($v) : $v; }
        }
        return $o;
    };
    $neuDe = $toDe($form);
    if ($neuDe !== $alt) {
        $fail("form.json auf Deutsch weicht von $ref ab");
        $a = json_encode($alt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); $b = json_encode($neuDe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $la = explode("\n", $a); $lb = explode("\n", $b);
        foreach ($la as $i => $l) { if (($lb[$i] ?? '') !== $l) { echo "    alt: $l\n    neu: " . ($lb[$i] ?? '') . "\n"; break; } }
    } else {
        echo "form.json auf Deutsch == $ref (alle " . count($formTexte) . " Texte identisch)\n";
    }
    $altMod = (string)shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg($ref . ':EMS/module.php') . ' 2>/dev/null');
    $newsAlt = newsAus($altMod);
    $newsDe = array_map(function ($z) use ($de) { return array_map(function ($l) use ($de) { return $de[$l] ?? $l; }, $z); }, $newsNeu);
    if ($newsDe !== $newsAlt) { $fail("NEWS auf Deutsch weicht von $ref ab"); } else { echo 'NEWS auf Deutsch == ' . $ref . ' (' . array_sum(array_map('count', $newsNeu)) . " Zeilen)\n"; }
}

function newsAus(string $src): array
{
    $s = strpos($src, "define('EMS_NEWS_VERSIONS', ");
    if ($s === false) { return []; }
    $e = strpos($src, "\n));\n", $s);
    return eval('return ' . substr($src, $s + strlen("define('EMS_NEWS_VERSIONS', "), $e + 2 - $s - strlen("define('EMS_NEWS_VERSIONS', ")) . ';');
}

echo $fehler === 0 ? "LOCALE-PRUEFUNG BESTANDEN\n" : "LOCALE-PRUEFUNG: $fehler Fehler\n";
exit($fehler === 0 ? 0 : 1);
