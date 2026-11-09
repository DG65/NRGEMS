<?php

declare(strict_types=1);

// ===========================================================================
// Gegenprobe Zweisprachigkeit: Bleibt die deutsche Ausgabe unveraendert?
//   php .tools/deutsch-vergleich.php [Vergleichsstand, Standard HEAD]
// Laesst den Pruefstand (.tools/test-scenarios.php) zweimal laufen -- einmal mit dem Modul des Vergleichsstands, einmal mit
// dem aktuellen -- und schreibt dabei jeden sichtbaren Text mit (EMS_DUMP: Variablenwerte, Formular wie die deutsche Konsole
// es zeigt, Rueckgaben, UpdateFormField, Push). Danach muessen beide Mitschriften gleich sein. Zeitstempel/Uhrzeiten werden
// vorher vereinheitlicht; bekannte, gewollte Strukturaenderungen (neue name-Felder, eingesetzte Wochentage) sind ausgenommen.
// Englische Abschnitte (EMS_LANG_EN) schreibt der Pruefstand nicht mit. Rueckgabe 1 bei Abweichung.
// ===========================================================================

$root = dirname(__DIR__);
$ref = $argv[1] ?? 'HEAD';
$tmp = sys_get_temp_dir() . '/ems-deutsch-vergleich-' . getmypid();
@mkdir($tmp . '/EMS', 0777, true);
foreach (['module.php', 'FormLayout.php', 'form.json', 'eeg-pv-verguetung.json'] as $f) {
    file_put_contents($tmp . '/EMS/' . $f, (string)shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg($ref . ':EMS/' . $f) . ' 2>/dev/null'));
}
$run = function (string $module, string $out) use ($root) {
    $env = 'EMS_DUMP=' . escapeshellarg($out) . ($module !== '' ? ' EMS_TEST_MODULE=' . escapeshellarg($module) : '');
    shell_exec('cd ' . escapeshellarg($root) . ' && ' . $env . ' php .tools/test-scenarios.php >/dev/null 2>&1');
    $s = (string)@file_get_contents($out);
    $s = preg_replace('/[0-9]{9,10}/', 'N', $s);
    $s = preg_replace('/[0-9]{2}:[0-9]{2}(:[0-9]{2})?/', 'T', $s);
    $s = preg_replace('/,"loadValuesFromConfiguration":false,"values":\[\{"Day":1.*?"Day":7[^\]]*\]/', '', $s);
    $s = str_replace(['"name":"ForceApplyButton",', 'echo \"✅ ApplyChanges() ausgeführt.\";'], ['', "echo '✅ ApplyChanges() ausgeführt.';"], $s);
    return explode("\n", $s);
};
$alt = $run($tmp . '/EMS/module.php', $tmp . '/alt.txt');
$neu = $run('', $tmp . '/neu.txt');
$nurAlt = array_diff($alt, $neu);
$nurNeu = array_diff($neu, $alt);
echo 'Mitschrift: ' . count($alt) . ' Zeilen (' . $ref . '), ' . count($neu) . " Zeilen (aktuell)\n";
foreach (array_slice($nurAlt, 0, 10) as $l) { echo '  nur ' . $ref . ': ' . mb_substr($l, 0, 300) . "\n"; }
foreach (array_slice($nurNeu, 0, 10) as $l) { echo '  nur aktuell: ' . mb_substr($l, 0, 300) . "\n"; }
array_map('unlink', glob($tmp . '/EMS/*') ?: []); @rmdir($tmp . '/EMS'); @unlink($tmp . '/alt.txt'); @unlink($tmp . '/neu.txt'); @rmdir($tmp);
$ok = (count($nurAlt) === 0 && count($nurNeu) === 0);
echo $ok ? "DEUTSCH-VERGLEICH BESTANDEN\n" : 'DEUTSCH-VERGLEICH: ' . (count($nurAlt) + count($nurNeu)) . " abweichende Zeilen\n";
exit($ok ? 0 : 1);
