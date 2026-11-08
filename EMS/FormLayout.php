<?php

declare(strict_types=1);

/**
 * "🪟 Formular: Aufbau" (SUITE.md "Einheitliche Formular-Optik" Punkt 3a, Dietmar 29.10.2026, Vorbild Batteriewächter): je Fachpanel
 * waehlt der Nutzer Aufgeklappt / Zugeklappt / Ausgeblendet und die Position. Gespeichert in der Property "FormLayout"
 * (JSON-Liste {Key, Panel, Opened, Closed, Hidden, Pos}); kein Haken = Standard; bei Widerspruch gilt ausgeblendet vor zugeklappt vor aufgeklappt.
 * Ausgeblendet heisst visible:false, nie weglassen: die Werte bleiben erhalten. Unbekannte Schluessel fallen auf Standard zurueck.
 *
 * Ein "Bundle" ist ein waehlbares Fachpanel samt den frei stehenden Elementen, die direkt dahinter stehen (z. B. ein Such-Knopf mit Ergebniszeile
 * oder "Stil zuruecksetzen"); sie wandern und verschwinden mit dem Panel. Alles VOR dem ersten Fachpanel bleibt fest oben.
 * Uebernommen aus NRGDashboard libs/FormLayout.php (31.10.2026), Trait-Name mit Modul-Praefix. EMS setzt sein Formular zur Laufzeit
 * zusammen (Bilanz, KI, Benachrichtigungen kommen nicht aus form.json), deshalb setzt formLayoutReset() die Standardreihenfolge
 * aus der Panel-Liste statt aus form.json.
 */
trait EMSFormLayout
{
    /**
     * @param array $elements Elemente des Formulars (oberste Ebene)
     * @param array $panels   Panel-name => stabiler Schluessel (nie die Caption: die ist uebersetzt)
     * @return array [head, bundles] bundles: key => ['panel' => ..., 'rest' => [...]], in Standardreihenfolge
     */
    private function formLayoutSplit(array $elements, array $panels): array
    {
        $head = [];
        $bundles = [];
        $current = null;
        foreach ($elements as $el) {
            $key = null;
            if (is_array($el) && ($el['type'] ?? '') === 'ExpansionPanel') {
                $name = (string) ($el['name'] ?? '');
                if ($name !== '' && isset($panels[$name])) {
                    $key = $panels[$name];
                }
            }
            if ($key !== null) {
                $bundles[$key] = ['panel' => $el, 'rest' => []];
                $current = $key;
            } elseif ($current === null) {
                $head[] = $el;
            } else {
                $bundles[$current]['rest'][] = $el;
            }
        }
        return [$head, $bundles];
    }

    /** Gespeicherte Auswahl: key => ['open' => std|open|closed|hidden, 'pos' => 1..99, 'idx' => Ursprungsindex], in der anzuwendenden Reihenfolge. */
    private function formLayoutRead(array $keys, string $json): array
    {
        $saved = json_decode($json, true);
        $by = [];
        foreach (is_array($saved) ? $saved : [] as $row) {
            if (is_array($row) && isset($row['Key'])) {
                $by[(string) $row['Key']] = $row;
            }
        }
        $out = [];
        foreach (array_values($keys) as $i => $k) {
            $r = $by[$k] ?? [];
            $open = !empty($r['Hidden']) ? 'hidden' : (!empty($r['Closed']) ? 'closed' : (!empty($r['Opened']) ? 'open' : 'std'));
            $out[$k] = ['open' => $open, 'pos' => isset($r['Pos']) ? max(1, min(99, (int) $r['Pos'])) : $i + 1, 'idx' => $i];
        }
        uasort($out, function ($a, $b) { return [$a['pos'], $a['idx']] <=> [$b['pos'], $b['idx']]; });
        return $out;
    }

    /** Setzt Reihenfolge, Aufklapp-Zustand und Sichtbarkeit der Fachpanels; haengt das Panel "Formular: Aufbau" hinten an. Rueckgabe: neue Elementliste. */
    private function formLayoutApply(array $elements, array $panels): array
    {
        [$head, $bundles] = $this->formLayoutSplit($elements, $panels);
        if (count($bundles) === 0) {
            return $elements;
        }
        $layout = $this->formLayoutRead(array_keys($bundles), (string) $this->ReadPropertyString('FormLayout'));
        $middle = [];
        foreach ($layout as $key => $l) {
            $panel = $bundles[$key]['panel'];
            $rest = $bundles[$key]['rest'];
            if ($l['open'] === 'open') {
                $panel['expanded'] = true;
            } elseif ($l['open'] === 'closed') {
                $panel['expanded'] = false;
            } elseif ($l['open'] === 'hidden') {
                // nicht weglassen: die Felder bleiben im Formular, gespeicherte Werte bleiben beim "Uebernehmen" erhalten
                $panel['visible'] = false;
                foreach ($rest as &$r) {
                    if (is_array($r)) {
                        $r['visible'] = false;
                    }
                }
                unset($r);
            }
            $middle[] = $panel;
            foreach ($rest as $r) {
                $middle[] = $r;
            }
        }
        return array_merge($head, $middle, [$this->formLayoutPanel($layout, $bundles)]);
    }

    private function formLayoutPanelName(array $panel): string
    {
        return trim((string) preg_replace('/\s*\(.*$/u', '', (string) ($panel['caption'] ?? '')));
    }

    private function formLayoutPanel(array $layout, array $bundles): array
    {
        $rows = [];
        $hidden = [];
        foreach ($layout as $key => $l) {
            // Listenzelle: die Konsole uebersetzt sie nicht (SUITE.md Store-Punkt 25), deshalb selbst
            $name = $this->t($this->formLayoutPanelName($bundles[$key]['panel']));
            $rows[] = ['Key' => $key, 'Panel' => $name, 'Opened' => $l['open'] === 'open', 'Closed' => $l['open'] === 'closed',
                'Hidden' => $l['open'] === 'hidden', 'Pos' => $l['pos']];
            if ($l['open'] === 'hidden') {
                $hidden[] = trim((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $name));
            }
        }
        $hiddenLine = $hidden
            ? [['type' => 'Label', 'caption' => sprintf($this->t('🙈 Hidden: %s — to show it again, untick "Hidden". Hidden sections keep working, only the display is missing.'), implode(', ', $hidden))]]
            : [];
        return [
            'type' => 'ExpansionPanel', 'name' => 'PanelFormLayout', 'expanded' => false,
            'caption' => $hidden ? sprintf($this->t('🪟  Form: layout (%d hidden)'), count($hidden)) : '🪟  Form: layout',
            'items' => array_merge($hiddenLine, [
                ['type' => 'Label', 'caption' => 'Here you define which sections are expanded when the form opens and in which order they appear. Symcon does not remember expanding and collapsing itself, so the setting here applies every time the form opens. It takes effect after "Apply" the next time the form is opened.'],
                ['type' => 'Label', 'caption' => 'No tick means "Default": as the module intends. If you choose "Collapsed", you will not see new items there immediately. The position is a number; with equal numbers the default order is kept. "Hidden" removes a section from the display without it losing anything: its settings are kept and remain in effect (intended for sections you rarely need). To show it again, untick "Hidden" or click "Reset to default". If several ticks are set, "Hidden" takes precedence over "Collapsed" over "Expanded". The sections at the top (purpose, new, documentation) and at the bottom (feedback, about this module) stay where they are and cannot be hidden.'],
                [
                    'type' => 'List', 'name' => 'FormLayout', 'caption' => 'Sections', 'rowCount' => count($rows), 'add' => false, 'delete' => false,
                    'loadValuesFromConfiguration' => false,
                    'values' => $rows,
                    'columns' => [
                        ['caption' => 'Section', 'name' => 'Panel', 'width' => 'auto', 'add' => ''],
                        ['caption' => 'Key', 'name' => 'Key', 'width' => '0px', 'visible' => false, 'add' => '', 'save' => true],
                        ['caption' => 'Expanded', 'name' => 'Opened', 'width' => '110px', 'add' => false, 'edit' => ['type' => 'CheckBox']],
                        ['caption' => 'Collapsed', 'name' => 'Closed', 'width' => '110px', 'add' => false, 'edit' => ['type' => 'CheckBox']],
                        ['caption' => '🙈 Hidden', 'name' => 'Hidden', 'width' => '130px', 'add' => false, 'edit' => ['type' => 'CheckBox']],
                        ['caption' => 'Position', 'name' => 'Pos', 'width' => '100px', 'add' => 1, 'edit' => ['type' => 'NumberSpinner', 'minimum' => 1, 'maximum' => 99]],
                    ],
                ],
                ['type' => 'Button', 'caption' => '↩️ Reset to default', 'onClick' => 'echo ' . 'EMS_ResetFormLayout($id);'],
            ]),
        ];
    }

    /** Setzt Aufklapp-Zustand und Reihenfolge im offenen Formular auf den Standard (gilt nach "Uebernehmen"). */
    private function formLayoutReset(array $panels, array $titles): string
    {
        $rows = [];
        $i = 0;
        foreach ($panels as $key) {
            // Listenzelle: selbst uebersetzen (die Konsole uebersetzt nur Beschriftungen, keine Zellinhalte)
            $rows[] = ['Key' => $key, 'Panel' => $this->t((string) ($titles[$key] ?? $key)), 'Opened' => false, 'Closed' => false, 'Hidden' => false, 'Pos' => ++$i];
        }
        $this->UpdateFormField('FormLayout', 'values', json_encode($rows, JSON_UNESCAPED_UNICODE));
        return 'ℹ️ Auf Standard gesetzt. Zum Speichern unten „Übernehmen“ klicken.';
    }
}
