<?php

declare(strict_types=1);

// EEG-Verguetungstabelle (vormals eeg-pv-verguetung.json, seit 0.101.0 als PHP-Datei: kein Dateilesen zur Laufzeit, Store-Regel).
return array (
  'stand' => '2026-09-13',
  'einheit' => 'ct/kWh',
  'hinweise' => 
  array (
    0 => 'Maßgeblich ist das Inbetriebnahmedatum (IBN); Vergütung gilt 20 Jahre zzgl. IBN-Jahr.',
    1 => 'Ab EEG 2009 (für EEG 2004 umstritten/anders) wird bei Anlagen über einer Klassengrenze anteilig nach Leistung vergütet (Mischsatz), nicht der Satz der höchsten Klasse. \'bis_kwp\': null = offene oberste Klasse.',
    2 => 'kategorie \'gebaeude\' = Anlagen auf/an Gebäuden und Lärmschutzwänden (ab 2015: Wohngebäude/Lärmschutzwände bzw. Gebäude nach § 48 Abs. 2/3). kategorie \'sonstige\' (Freiflächen etc.) erst ab 01.04.2012 erfasst; 2004–03/2012 weggelassen (07/2010 und 10/2010 ohne Primärquelle, Flächenkategorien Konversion/Acker/sonstige zu zersplittert).',
    3 => '2000–2003 (EEG 2000) gab es keine Gebäudeunterscheidung; der Einheitssatz ist unter \'gebaeude\' mit bis_kwp null eingetragen.',
    4 => 'Ab 04/2012 bis 07/2014 (EEG 2012) und ab 08/2014 enthalten die BNetzA-Tabellen ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (Gesetz: Rundung auf zwei Stellen; Folgedegression rechnet mit ungerundetem Wert).',
    5 => 'Ab 08/2014 ist \'teil\' die feste Einspeisevergütung (anzulegender Wert − 0,4 ct/kWh nach § 37 Abs. 3 EEG 2014 / § 53 EEG 2017/2021/2023). Darüber hinaus Direktvermarktung (Marktprämie), hier nicht abgebildet.',
    6 => 'Eigenverbrauchsbonus (§ 33 Abs. 2 EEG 2009/2012 a.F.): \'eigenverbrauch\' = Satz bis 30 % Eigenverbrauchsanteil (bzw. einstufig 2009–06/2010), \'eigenverbrauch_ueber30\' = Satz für den Anteil über 30 %. Nur für IBN 01.01.2009–31.03.2012.',
    7 => 'Marktintegrationsmodell (PV-Novelle 2012): für Anlagen 10–1000 kWp mit IBN 01.04.2012–31.07.2014 nur 90 % der Jahresstrommenge vergütungsfähig (ab 2014); als Feld \'marktintegration\' in den betroffenen Klassen vermerkt, nicht im Satz eingerechnet.',
    8 => 'Übergangsregel PV-Novelle 2012: Dachanlagen mit Netzanschlussbegehren vor 24.02.2012 und IBN bis 30.06.2012 erhalten noch die Sätze 01.01.–31.03.2012.',
    9 => 'Ab 30.07.2022: \'voll\' = Volleinspeisung (§ 100 Abs. 14 S. 2 EEG 2021 bzw. § 48 Abs. 2a EEG 2023), \'teil\' = Überschusseinspeisung.',
    10 => 'Fassadenbonus (EEG 2004: +5 ct) und Sonderregeln (Konversionsflächen, Mieterstrom, Solarspitzengesetz § 51 Abs. 1 bei negativen Preisen ab 25.02.2025) sind nicht abgebildet.',
  ),
  'zeitraeume' => 
  array (
    0 => 
    array (
      'von' => '2000-04-01',
      'bis' => '2001-12-31',
      'fassung' => 'EEG 2000 (§ 8)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 50.62,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'http://www.iwr.de/re/wf/e_preis.html (99 Pf/kWh, 1 EUR = 1,95583 DM)',
      'geprueft' => false,
      'anmerkung' => 'Einheitssatz ohne Größenklassen/Gebäudeunterscheidung (Anlagen bis 5 MW auf Gebäuden bzw. 100 kW sonst). 99 Pf = 50,62 ct umgerechnet. Nur Sekundärquelle IWR, Gesetzestext (BGBl. I 2000 S. 305) nicht abgerufen.',
    ),
    1 => 
    array (
      'von' => '2002-01-01',
      'bis' => '2002-12-31',
      'fassung' => 'EEG 2000 (§ 8, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 48.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'http://www.iwr.de/re/wf/e_preis.html',
      'geprueft' => false,
      'anmerkung' => 'Degression 5 % p.a. ab 2002 (§ 8 Abs. 2 EEG 2000); nur Sekundärquelle IWR.',
    ),
    2 => 
    array (
      'von' => '2003-01-01',
      'bis' => '2003-12-31',
      'fassung' => 'EEG 2000 (§ 8, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 45.7,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'http://www.iwr.de/re/wf/e_preis.html',
      'geprueft' => false,
      'anmerkung' => 'nur Sekundärquelle IWR.',
    ),
    3 => 
    array (
      'von' => '2004-01-01',
      'bis' => '2004-12-31',
      'fassung' => 'PV-Vorschaltgesetz (ab 01.01.2004) / EEG 2004 § 11 (ab 01.08.2004)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 57.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 54.6,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 54.0,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.iwr.de/re/wf/eeg%20photovoltaik.htm',
      'geprueft' => false,
      'anmerkung' => 'Sätze Vorschaltgesetz und EEG 2004 identisch; Fassadenbonus +5 ct (hier nicht enthalten); Klasse \'bis_kwp: null\' = über 100 kW. Nur Sekundärquelle IWR.',
    ),
    4 => 
    array (
      'von' => '2005-01-01',
      'bis' => '2005-12-31',
      'fassung' => 'EEG 2004 (§ 11, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 54.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 51.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 51.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.iwr.de/re/wf/eeg%20photovoltaik.htm',
      'geprueft' => false,
      'anmerkung' => 'nur Sekundärquelle IWR; Fassadenbonus +5 ct nicht enthalten.',
    ),
    5 => 
    array (
      'von' => '2006-01-01',
      'bis' => '2006-12-31',
      'fassung' => 'EEG 2004 (§ 11, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 51.8,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 49.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 48.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.iwr.de/re/wf/eeg%20photovoltaik.htm',
      'geprueft' => false,
      'anmerkung' => 'nur Sekundärquelle IWR.',
    ),
    6 => 
    array (
      'von' => '2007-01-01',
      'bis' => '2007-12-31',
      'fassung' => 'EEG 2004 (§ 11, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 49.21,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 46.82,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 46.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.iwr.de/re/wf/eeg%20photovoltaik.htm',
      'geprueft' => false,
      'anmerkung' => 'nur Sekundärquelle IWR.',
    ),
    7 => 
    array (
      'von' => '2008-01-01',
      'bis' => '2008-12-31',
      'fassung' => 'EEG 2004 (§ 11, Degression 5 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 46.75,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 44.48,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 43.99,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.iwr.de/re/wf/eeg%20photovoltaik.htm',
      'geprueft' => false,
      'anmerkung' => 'nur Sekundärquelle IWR.',
    ),
    8 => 
    array (
      'von' => '2009-01-01',
      'bis' => '2009-12-31',
      'fassung' => 'EEG 2009 (§ 33)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 43.01,
          'voll' => NULL,
          'eigenverbrauch' => 25.01,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 40.91,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 39.58,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 33.0,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.sfv.de/artikel/eeg_2009_inkl_aenderungen_eeg_2012_bis_3132012_einspeiseverguetung ; Eigenverbrauch: https://www.sfv.de/artikel/2008/foerderung_des_eigenverbrauchs_von_solarstrom',
      'geprueft' => false,
      'anmerkung' => 'Eigenverbrauch § 33 Abs. 2 EEG 2009 nur bis 30 kW, ohne 30-%-Stufe (Einspeisesatz − 18 ct). Nur Sekundärquelle SFV.',
    ),
    9 => 
    array (
      'von' => '2010-01-01',
      'bis' => '2010-06-30',
      'fassung' => 'EEG 2009 (§ 33, Degression 2010)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 39.14,
          'voll' => NULL,
          'eigenverbrauch' => 22.76,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 37.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 35.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 29.37,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze2010_pdf.pdf (Tabelle S. 1)',
      'geprueft' => true,
      'anmerkung' => 'Eigenverbrauch nur bis 30 kW, ohne 30-%-Stufe.',
    ),
    10 => 
    array (
      'von' => '2010-07-01',
      'bis' => '2010-09-30',
      'fassung' => 'EEG 2009 i.d.F. 1. Änderungsgesetz v. 11.08.2010 (außerordentliche Absenkung 07/2010)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 34.05,
          'voll' => NULL,
          'eigenverbrauch' => 17.67,
          'eigenverbrauch_ueber30' => 22.05,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 32.39,
          'voll' => NULL,
          'eigenverbrauch' => 16.01,
          'eigenverbrauch_ueber30' => 20.39,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 30.65,
          'voll' => NULL,
          'eigenverbrauch' => 14.27,
          'eigenverbrauch_ueber30' => 18.65,
          'eigenverbrauch_nur_bis_kwp' => 500,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 25.55,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.sfv.de/artikel/eeg_2009_inkl_aenderungen_eeg_2012_bis_3132012_einspeiseverguetung ; https://www.photovoltaik-web.de/photovoltaik/eigenverbrauch-pv/verguetungssaetze-eigenverbrauch',
      'geprueft' => false,
      'anmerkung' => 'Außerordentliche Absenkung −16 % Gebäude. Eigenverbrauch ab 07/2010 bis 500 kW, zweistufig (≤30 % / >30 % Eigenverbrauchsanteil). Nur Sekundärquellen (SFV, photovoltaik-web.de), BGBl. I 2010 S. 1170 nicht abgerufen; bis-30-kW-Werte auch in https://www.sfv.de/artikel/regelungen_zur_eigenverbrauchsfoerderung_ab_1_juli_2010 bestätigt.',
    ),
    11 => 
    array (
      'von' => '2010-10-01',
      'bis' => '2010-12-31',
      'fassung' => 'EEG 2009 i.d.F. 1. Änderungsgesetz v. 11.08.2010 (außerordentliche Absenkung 10/2010)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 33.03,
          'voll' => NULL,
          'eigenverbrauch' => 16.65,
          'eigenverbrauch_ueber30' => 21.03,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 31.42,
          'voll' => NULL,
          'eigenverbrauch' => 15.04,
          'eigenverbrauch_ueber30' => 19.42,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 29.73,
          'voll' => NULL,
          'eigenverbrauch' => 13.35,
          'eigenverbrauch_ueber30' => 17.73,
          'eigenverbrauch_nur_bis_kwp' => 500,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 24.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.sfv.de/artikel/eeg_2009_inkl_aenderungen_eeg_2012_bis_3132012_einspeiseverguetung ; https://www.photovoltaik-web.de/photovoltaik/eigenverbrauch-pv/verguetungssaetze-eigenverbrauch',
      'geprueft' => false,
      'anmerkung' => 'Weitere Absenkung −3 %. Nur Sekundärquellen (SFV, photovoltaik-web.de).',
    ),
    12 => 
    array (
      'von' => '2011-01-01',
      'bis' => '2011-12-31',
      'fassung' => 'EEG 2009 (§ 33, Degression 13 %)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 28.74,
          'voll' => NULL,
          'eigenverbrauch' => 12.36,
          'eigenverbrauch_ueber30' => 16.74,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 27.33,
          'voll' => NULL,
          'eigenverbrauch' => 10.96,
          'eigenverbrauch_ueber30' => 15.34,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 25.86,
          'voll' => NULL,
          'eigenverbrauch' => 9.49,
          'eigenverbrauch_ueber30' => 13.87,
          'eigenverbrauch_nur_bis_kwp' => 500,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 21.56,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze2011_pdf.pdf (Tabelle S. 1) und DegressionsVergSaetzeJuliSept2011_pdf.pdf (0 % zum 01.07.2011)',
      'geprueft' => false,
      'anmerkung' => 'Einspeisesätze BNetzA-bestätigt (inkl. 0-%-Anpassung 01.07.2011 → Satz gilt ganzjährig). Eigenverbrauchssätze nur Sekundärquelle photovoltaik-web.de (https://www.photovoltaik-web.de/photovoltaik/eigenverbrauch-pv/verguetungssaetze-eigenverbrauch), BNetzA nennt sie nicht (abgeleitet aus § 33 Abs. 2).',
    ),
    13 => 
    array (
      'von' => '2012-01-01',
      'bis' => '2012-03-31',
      'fassung' => 'EEG 2012 (a.F., vor PV-Novelle)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 30,
          'teil' => 24.43,
          'voll' => NULL,
          'eigenverbrauch' => 8.05,
          'eigenverbrauch_ueber30' => 12.43,
        ),
        1 => 
        array (
          'bis_kwp' => 100,
          'teil' => 23.23,
          'voll' => NULL,
          'eigenverbrauch' => 6.85,
          'eigenverbrauch_ueber30' => 11.23,
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 21.98,
          'voll' => NULL,
          'eigenverbrauch' => 5.6,
          'eigenverbrauch_ueber30' => 9.98,
          'eigenverbrauch_nur_bis_kwp' => 500,
        ),
        3 => 
        array (
          'bis_kwp' => NULL,
          'teil' => 18.33,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetzeAbJan2012_pdf.pdf (Tabelle S. 1)',
      'geprueft' => false,
      'anmerkung' => 'Einspeisesätze BNetzA-bestätigt (Degression 15 %). Eigenverbrauchssätze nur Sekundärquelle photovoltaik-web.de. Übergang: Dachanlagen mit Netzanschlussbegehren vor 24.02.2012 und IBN bis 30.06.2012 behalten diese Sätze (§ 66 Abs. 18 EEG 2012 n.F.).',
    ),
    14 => 
    array (
      'von' => '2012-04-01',
      'bis' => '2012-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 19.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 18.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 16.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    15 => 
    array (
      'von' => '2012-05-01',
      'bis' => '2012-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 19.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 18.32,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 16.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.37,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    16 => 
    array (
      'von' => '2012-06-01',
      'bis' => '2012-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 19.11,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 18.13,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 16.17,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    17 => 
    array (
      'von' => '2012-07-01',
      'bis' => '2012-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 18.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 17.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 16.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    18 => 
    array (
      'von' => '2012-08-01',
      'bis' => '2012-08-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 18.73,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 17.77,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 15.85,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.08.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    19 => 
    array (
      'von' => '2012-09-01',
      'bis' => '2012-09-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 18.54,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 17.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 15.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.84,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.09.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    20 => 
    array (
      'von' => '2012-10-01',
      'bis' => '2012-10-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 18.36,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 17.42,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 15.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.10.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    21 => 
    array (
      'von' => '2012-11-01',
      'bis' => '2012-11-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 17.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 16.98,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 15.15,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.39,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.11.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    22 => 
    array (
      'von' => '2012-12-01',
      'bis' => '2012-12-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 17.45,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 16.56,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 14.77,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.12.2012\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    23 => 
    array (
      'von' => '2013-01-01',
      'bis' => '2013-01-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 17.02,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 16.14,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 14.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.78,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.01.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    24 => 
    array (
      'von' => '2013-02-01',
      'bis' => '2013-02-28',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 16.64,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 15.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 14.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.52,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.02.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    25 => 
    array (
      'von' => '2013-03-01',
      'bis' => '2013-03-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 16.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 15.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 13.77,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.03.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    26 => 
    array (
      'von' => '2013-04-01',
      'bis' => '2013-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 15.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 15.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 13.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.02,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    27 => 
    array (
      'von' => '2013-05-01',
      'bis' => '2013-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 15.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 14.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 13.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.82,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    28 => 
    array (
      'von' => '2013-06-01',
      'bis' => '2013-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 15.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 14.56,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 12.99,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    29 => 
    array (
      'von' => '2013-07-01',
      'bis' => '2013-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 15.07,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 14.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 12.75,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    30 => 
    array (
      'von' => '2013-08-01',
      'bis' => '2013-08-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 14.8,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 14.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 12.52,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.08.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    31 => 
    array (
      'von' => '2013-09-01',
      'bis' => '2013-09-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 14.54,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 13.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 12.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.06,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.09.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    32 => 
    array (
      'von' => '2013-10-01',
      'bis' => '2013-10-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 14.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 13.54,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 12.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.10.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    33 => 
    array (
      'von' => '2013-11-01',
      'bis' => '2013-11-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 14.07,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 13.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.91,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.11.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    34 => 
    array (
      'von' => '2013-12-01',
      'bis' => '2013-12-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 13.17,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.12.2013\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    35 => 
    array (
      'von' => '2014-01-01',
      'bis' => '2014-01-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.68,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.98,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.58,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.01.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    36 => 
    array (
      'von' => '2014-02-01',
      'bis' => '2014-02-28',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.55,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.85,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.46,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.38,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.02.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    37 => 
    array (
      'von' => '2014-03-01',
      'bis' => '2014-03-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.41,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.72,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.03.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    38 => 
    array (
      'von' => '2014-04-01',
      'bis' => '2014-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.6,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.19,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    39 => 
    array (
      'von' => '2014-05-01',
      'bis' => '2014-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.14,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.12,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    40 => 
    array (
      'von' => '2014-06-01',
      'bis' => '2014-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 13.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 11.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    41 => 
    array (
      'von' => '2014-07-01',
      'bis' => '2014-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.22,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        2 => 
        array (
          'bis_kwp' => 1000,
          'teil' => 10.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
          'marktintegration' => 'Marktintegrationsmodell (§ 33 Abs. 1 EEG 2012 i.d.F. PV-Novelle): für 10-1000 kWp nur 90 % der jährlich erzeugten Strommenge vergütungsfähig (ab 2014)',
        ),
        3 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 8.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2014\'',
      'geprueft' => true,
      'anmerkung' => 'BNetzA-Tabelle führt ungerundete Kettenwerte; hier kaufmännisch auf 2 Nachkommastellen gerundet (§ 20b Abs. 9a EEG 2012 n.F.). Keine Eigenverbrauchsvergütung für IBN ab 01.04.2012.',
    ),
    42 => 
    array (
      'von' => '2014-08-01',
      'bis' => '2014-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.75,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 11.09,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    43 => 
    array (
      'von' => '2014-09-01',
      'bis' => '2014-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 11.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    44 => 
    array (
      'von' => '2014-10-01',
      'bis' => '2014-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.65,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 11.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    45 => 
    array (
      'von' => '2014-11-01',
      'bis' => '2014-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.62,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.98,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    46 => 
    array (
      'von' => '2014-12-01',
      'bis' => '2014-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    47 => 
    array (
      'von' => '2015-01-01',
      'bis' => '2015-01-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.56,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.22,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    48 => 
    array (
      'von' => '2015-02-01',
      'bis' => '2015-02-28',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.18,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    49 => 
    array (
      'von' => '2015-03-01',
      'bis' => '2015-03-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.15,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    50 => 
    array (
      'von' => '2015-04-01',
      'bis' => '2015-04-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.12,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.84,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    51 => 
    array (
      'von' => '2015-05-01',
      'bis' => '2015-05-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.43,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.09,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.82,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    52 => 
    array (
      'von' => '2015-06-01',
      'bis' => '2015-06-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.06,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    53 => 
    array (
      'von' => '2015-07-01',
      'bis' => '2015-07-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.37,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.76,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    54 => 
    array (
      'von' => '2015-08-01',
      'bis' => '2015-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 12.0,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.73,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    55 => 
    array (
      'von' => '2015-09-01',
      'bis' => '2015-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    56 => 
    array (
      'von' => '2015-10-01',
      'bis' => '2015-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    57 => 
    array (
      'von' => '2015-11-01',
      'bis' => '2015-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    58 => 
    array (
      'von' => '2015-12-01',
      'bis' => '2015-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 500,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    59 => 
    array (
      'von' => '2016-01-01',
      'bis' => '2016-01-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    60 => 
    array (
      'von' => '2016-02-01',
      'bis' => '2016-02-29',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    61 => 
    array (
      'von' => '2016-03-01',
      'bis' => '2016-03-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    62 => 
    array (
      'von' => '2016-04-01',
      'bis' => '2016-04-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    63 => 
    array (
      'von' => '2016-05-01',
      'bis' => '2016-05-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    64 => 
    array (
      'von' => '2016-06-01',
      'bis' => '2016-06-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    65 => 
    array (
      'von' => '2016-07-01',
      'bis' => '2016-07-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    66 => 
    array (
      'von' => '2016-08-01',
      'bis' => '2016-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    67 => 
    array (
      'von' => '2016-09-01',
      'bis' => '2016-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    68 => 
    array (
      'von' => '2016-10-01',
      'bis' => '2016-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    69 => 
    array (
      'von' => '2016-11-01',
      'bis' => '2016-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    70 => 
    array (
      'von' => '2016-12-01',
      'bis' => '2016-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    71 => 
    array (
      'von' => '2017-01-01',
      'bis' => '2017-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/AnzulegendeWertePV_Jan17.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    72 => 
    array (
      'von' => '2017-02-01',
      'bis' => '2017-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    73 => 
    array (
      'von' => '2017-03-01',
      'bis' => '2017-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Feb-Apr2017.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    74 => 
    array (
      'von' => '2017-04-01',
      'bis' => '2017-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Feb-Apr2017.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    75 => 
    array (
      'von' => '2017-05-01',
      'bis' => '2017-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.93,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.66,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    76 => 
    array (
      'von' => '2017-06-01',
      'bis' => '2017-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.24,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    77 => 
    array (
      'von' => '2017-07-01',
      'bis' => '2017-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    78 => 
    array (
      'von' => '2017-08-01',
      'bis' => '2017-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    79 => 
    array (
      'von' => '2017-09-01',
      'bis' => '2017-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    80 => 
    array (
      'von' => '2017-10-01',
      'bis' => '2017-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    81 => 
    array (
      'von' => '2017-11-01',
      'bis' => '2017-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    82 => 
    array (
      'von' => '2017-12-01',
      'bis' => '2017-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    83 => 
    array (
      'von' => '2018-01-01',
      'bis' => '2018-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    84 => 
    array (
      'von' => '2018-02-01',
      'bis' => '2018-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    85 => 
    array (
      'von' => '2018-03-01',
      'bis' => '2018-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    86 => 
    array (
      'von' => '2018-04-01',
      'bis' => '2018-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    87 => 
    array (
      'von' => '2018-05-01',
      'bis' => '2018-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    88 => 
    array (
      'von' => '2018-06-01',
      'bis' => '2018-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    89 => 
    array (
      'von' => '2018-07-01',
      'bis' => '2018-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    90 => 
    array (
      'von' => '2018-08-01',
      'bis' => '2018-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 12.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    91 => 
    array (
      'von' => '2018-09-01',
      'bis' => '2018-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.62,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.39,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    92 => 
    array (
      'von' => '2018-10-01',
      'bis' => '2018-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    93 => 
    array (
      'von' => '2018-11-01',
      'bis' => '2018-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.38,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.17,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    94 => 
    array (
      'von' => '2018-12-01',
      'bis' => '2018-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 10.07,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    95 => 
    array (
      'von' => '2019-01-01',
      'bis' => '2019-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.15,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 9.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    96 => 
    array (
      'von' => '2019-02-01',
      'bis' => '2019-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 11.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 9.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    97 => 
    array (
      'von' => '2019-03-01',
      'bis' => '2019-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.99,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    98 => 
    array (
      'von' => '2019-04-01',
      'bis' => '2019-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 11.11,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.81,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    99 => 
    array (
      'von' => '2019-05-01',
      'bis' => '2019-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.65,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.38,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    100 => 
    array (
      'von' => '2019-06-01',
      'bis' => '2019-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    101 => 
    array (
      'von' => '2019-07-01',
      'bis' => '2019-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.64,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.13,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    102 => 
    array (
      'von' => '2019-08-01',
      'bis' => '2019-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.48,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.19,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    103 => 
    array (
      'von' => '2019-09-01',
      'bis' => '2019-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.33,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 10.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.89,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    104 => 
    array (
      'von' => '2019-10-01',
      'bis' => '2019-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.18,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.78,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    105 => 
    array (
      'von' => '2019-11-01',
      'bis' => '2019-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 10.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.7,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    106 => 
    array (
      'von' => '2019-12-01',
      'bis' => '2019-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.62,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    107 => 
    array (
      'von' => '2020-01-01',
      'bis' => '2020-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.87,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.54,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    108 => 
    array (
      'von' => '2020-02-01',
      'bis' => '2020-02-29',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.72,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.45,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.42,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    109 => 
    array (
      'von' => '2020-03-01',
      'bis' => '2020-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.58,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    110 => 
    array (
      'von' => '2020-04-01',
      'bis' => '2020-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.18,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.21,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    111 => 
    array (
      'von' => '2020-05-01',
      'bis' => '2020-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.3,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 9.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    112 => 
    array (
      'von' => '2020-06-01',
      'bis' => '2020-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.17,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.91,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.0,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    113 => 
    array (
      'von' => '2020-07-01',
      'bis' => '2020-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 9.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.78,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.89,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    114 => 
    array (
      'von' => '2020-08-01',
      'bis' => '2020-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.9,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.65,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    115 => 
    array (
      'von' => '2020-09-01',
      'bis' => '2020-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.77,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    116 => 
    array (
      'von' => '2020-10-01',
      'bis' => '2020-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.64,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    117 => 
    array (
      'von' => '2020-11-01',
      'bis' => '2020-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.48,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.24,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.46,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    118 => 
    array (
      'von' => '2020-12-01',
      'bis' => '2020-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.32,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 8.09,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    119 => 
    array (
      'von' => '2021-01-01',
      'bis' => '2021-01-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.16,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.93,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.22,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    120 => 
    array (
      'von' => '2021-02-01',
      'bis' => '2021-02-28',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.81,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.13,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    121 => 
    array (
      'von' => '2021-03-01',
      'bis' => '2021-03-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.7,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    122 => 
    array (
      'von' => '2021-04-01',
      'bis' => '2021-04-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.81,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    123 => 
    array (
      'von' => '2021-05-01',
      'bis' => '2021-05-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.69,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.86,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    124 => 
    array (
      'von' => '2021-06-01',
      'bis' => '2021-06-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.58,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.36,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.77,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    125 => 
    array (
      'von' => '2021-07-01',
      'bis' => '2021-07-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.68,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    126 => 
    array (
      'von' => '2021-08-01',
      'bis' => '2021-08-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.36,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.15,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.6,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    127 => 
    array (
      'von' => '2021-09-01',
      'bis' => '2021-09-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.04,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.51,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    128 => 
    array (
      'von' => '2021-10-01',
      'bis' => '2021-10-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.14,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.94,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.43,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    129 => 
    array (
      'von' => '2021-11-01',
      'bis' => '2021-11-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    130 => 
    array (
      'von' => '2021-12-01',
      'bis' => '2021-12-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.93,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.73,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    131 => 
    array (
      'von' => '2022-01-01',
      'bis' => '2022-01-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.19,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    132 => 
    array (
      'von' => '2022-02-01',
      'bis' => '2022-02-28',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.73,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.11,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    133 => 
    array (
      'von' => '2022-03-01',
      'bis' => '2022-03-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    134 => 
    array (
      'von' => '2022-04-01',
      'bis' => '2022-04-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.96,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    135 => 
    array (
      'von' => '2022-05-01',
      'bis' => '2022-05-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.43,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    136 => 
    array (
      'von' => '2022-06-01',
      'bis' => '2022-06-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.15,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.81,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    137 => 
    array (
      'von' => '2022-07-01',
      'bis' => '2022-07-29',
      'fassung' => 'EEG 2021',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 6.24,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.06,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Feste Einspeisevergütung (anzulegender Wert − 0,4 ct); Direktvermarktungspflicht darüber (bis 31.12.2015 ab 500 kWp, ab 2016 ab 100 kWp). Ungerundete BNetzA-Werte kaufmännisch gerundet.',
    ),
    138 => 
    array (
      'von' => '2022-07-30',
      'bis' => '2022-07-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 30.07.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    139 => 
    array (
      'von' => '2022-08-01',
      'bis' => '2022-08-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    140 => 
    array (
      'von' => '2022-09-01',
      'bis' => '2022-09-30',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    141 => 
    array (
      'von' => '2022-10-01',
      'bis' => '2022-10-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    142 => 
    array (
      'von' => '2022-11-01',
      'bis' => '2022-11-30',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    143 => 
    array (
      'von' => '2022-12-01',
      'bis' => '2022-12-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022 (§ 100 Abs. 14)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Degression für Gebäudeanlagen ausgesetzt. Voll = Volleinspeisung nach § 100 Abs. 14 S. 2 EEG 2021 (jährliche Anmeldung beim Netzbetreiber).',
    ),
    144 => 
    array (
      'von' => '2023-01-01',
      'bis' => '2024-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.2,
          'voll' => 13.0,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.1,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.8,
          'voll' => 10.9,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.01.2023\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    145 => 
    array (
      'von' => '2024-02-01',
      'bis' => '2024-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.11,
          'voll' => 12.87,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 7.03,
          'voll' => 10.79,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.74,
          'voll' => 10.79,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2024\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    146 => 
    array (
      'von' => '2024-08-01',
      'bis' => '2025-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 8.03,
          'voll' => 12.73,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.95,
          'voll' => 10.68,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.68,
          'voll' => 10.68,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2024\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    147 => 
    array (
      'von' => '2025-02-01',
      'bis' => '2025-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.94,
          'voll' => 12.6,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.88,
          'voll' => 10.56,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.62,
          'voll' => 10.56,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2025\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    148 => 
    array (
      'von' => '2025-08-01',
      'bis' => '2026-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.86,
          'voll' => 12.47,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.8,
          'voll' => 10.45,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.56,
          'voll' => 10.45,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2025\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    149 => 
    array (
      'von' => '2026-02-01',
      'bis' => '2026-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.78,
          'voll' => 12.34,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.73,
          'voll' => 10.35,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.5,
          'voll' => 10.35,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2026\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet.',
    ),
    150 => 
    array (
      'von' => '2026-08-01',
      'bis' => '2027-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 2/2a, § 49, § 53)',
      'kategorie' => 'gebaeude',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10,
          'teil' => 7.7,
          'voll' => 12.22,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        1 => 
        array (
          'bis_kwp' => 40,
          'teil' => 6.66,
          'voll' => 10.24,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
        2 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.44,
          'voll' => 10.24,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2026\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Halbjährliche Degression 1 % ab 01.02.2024 (§ 49 EEG 2023). Werte = anzulegender Wert − 0,4 ct; BNetzA-Kettenwerte kaufmännisch gerundet. Zeitraum 2027-01 liegt nach Stand; von BNetzA bereits veröffentlicht.',
    ),
    151 => 
    array (
      'von' => '2012-04-01',
      'bis' => '2012-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    152 => 
    array (
      'von' => '2012-05-01',
      'bis' => '2012-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.37,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    153 => 
    array (
      'von' => '2012-06-01',
      'bis' => '2012-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.23,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    154 => 
    array (
      'von' => '2012-07-01',
      'bis' => '2012-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 13.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    155 => 
    array (
      'von' => '2012-08-01',
      'bis' => '2012-08-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.08.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    156 => 
    array (
      'von' => '2012-09-01',
      'bis' => '2012-09-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.84,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.09.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    157 => 
    array (
      'von' => '2012-10-01',
      'bis' => '2012-10-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.71,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.10.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    158 => 
    array (
      'von' => '2012-11-01',
      'bis' => '2012-11-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.39,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.11.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    159 => 
    array (
      'von' => '2012-12-01',
      'bis' => '2012-12-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 12.08,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.12.2012\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    160 => 
    array (
      'von' => '2013-01-01',
      'bis' => '2013-01-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.78,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.01.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    161 => 
    array (
      'von' => '2013-02-01',
      'bis' => '2013-02-28',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.52,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.02.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    162 => 
    array (
      'von' => '2013-03-01',
      'bis' => '2013-03-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.03.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    163 => 
    array (
      'von' => '2013-04-01',
      'bis' => '2013-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 11.02,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    164 => 
    array (
      'von' => '2013-05-01',
      'bis' => '2013-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.82,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    165 => 
    array (
      'von' => '2013-06-01',
      'bis' => '2013-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    166 => 
    array (
      'von' => '2013-07-01',
      'bis' => '2013-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    167 => 
    array (
      'von' => '2013-08-01',
      'bis' => '2013-08-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.25,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.08.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    168 => 
    array (
      'von' => '2013-09-01',
      'bis' => '2013-09-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 10.06,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.09.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    169 => 
    array (
      'von' => '2013-10-01',
      'bis' => '2013-10-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.10.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    170 => 
    array (
      'von' => '2013-11-01',
      'bis' => '2013-11-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.11.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    171 => 
    array (
      'von' => '2013-12-01',
      'bis' => '2013-12-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.12.2013\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    172 => 
    array (
      'von' => '2014-01-01',
      'bis' => '2014-01-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.01.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    173 => 
    array (
      'von' => '2014-02-01',
      'bis' => '2014-02-28',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.38,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.02.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    174 => 
    array (
      'von' => '2014-03-01',
      'bis' => '2014-03-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.03.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    175 => 
    array (
      'von' => '2014-04-01',
      'bis' => '2014-04-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.19,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.04.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    176 => 
    array (
      'von' => '2014-05-01',
      'bis' => '2014-05-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.1,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.05.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    177 => 
    array (
      'von' => '2014-06-01',
      'bis' => '2014-06-30',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 9.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.06.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    178 => 
    array (
      'von' => '2014-07-01',
      'bis' => '2014-07-31',
      'fassung' => 'EEG 2012 / PV-Novelle',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 10000,
          'teil' => 8.92,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Degressions-%20und%20Verg%C3%BCtungss%C3%A4tze%20April%202012%20bis%20Juli%202014.xls (via web.archive.org, Snapshot 20140916091357), Tabelle1, Zeile \'ab 01.07.2014\', Spalte G (§ 32 Abs. 1)',
      'geprueft' => true,
      'anmerkung' => 'Freiflächen/sonstige Anlagen bis 10 MWp.',
    ),
    179 => 
    array (
      'von' => '2014-08-01',
      'bis' => '2014-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    180 => 
    array (
      'von' => '2014-09-01',
      'bis' => '2014-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.79,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    181 => 
    array (
      'von' => '2014-10-01',
      'bis' => '2014-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.76,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    182 => 
    array (
      'von' => '2014-11-01',
      'bis' => '2014-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.74,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    183 => 
    array (
      'von' => '2014-12-01',
      'bis' => '2014-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.72,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2014\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    184 => 
    array (
      'von' => '2015-01-01',
      'bis' => '2015-01-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.7,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    185 => 
    array (
      'von' => '2015-02-01',
      'bis' => '2015-02-28',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.68,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    186 => 
    array (
      'von' => '2015-03-01',
      'bis' => '2015-03-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.65,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    187 => 
    array (
      'von' => '2015-04-01',
      'bis' => '2015-04-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.63,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    188 => 
    array (
      'von' => '2015-05-01',
      'bis' => '2015-05-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    189 => 
    array (
      'von' => '2015-06-01',
      'bis' => '2015-06-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.59,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    190 => 
    array (
      'von' => '2015-07-01',
      'bis' => '2015-07-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.57,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    191 => 
    array (
      'von' => '2015-08-01',
      'bis' => '2015-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.55,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    192 => 
    array (
      'von' => '2015-09-01',
      'bis' => '2015-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    193 => 
    array (
      'von' => '2015-10-01',
      'bis' => '2015-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    194 => 
    array (
      'von' => '2015-11-01',
      'bis' => '2015-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    195 => 
    array (
      'von' => '2015-12-01',
      'bis' => '2015-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 500,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/Okt-Dez_2015.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2015\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    196 => 
    array (
      'von' => '2016-01-01',
      'bis' => '2016-01-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    197 => 
    array (
      'von' => '2016-02-01',
      'bis' => '2016-02-29',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    198 => 
    array (
      'von' => '2016-03-01',
      'bis' => '2016-03-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    199 => 
    array (
      'von' => '2016-04-01',
      'bis' => '2016-04-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    200 => 
    array (
      'von' => '2016-05-01',
      'bis' => '2016-05-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    201 => 
    array (
      'von' => '2016-06-01',
      'bis' => '2016-06-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    202 => 
    array (
      'von' => '2016-07-01',
      'bis' => '2016-07-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    203 => 
    array (
      'von' => '2016-08-01',
      'bis' => '2016-08-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    204 => 
    array (
      'von' => '2016-09-01',
      'bis' => '2016-09-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    205 => 
    array (
      'von' => '2016-10-01',
      'bis' => '2016-10-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    206 => 
    array (
      'von' => '2016-11-01',
      'bis' => '2016-11-30',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    207 => 
    array (
      'von' => '2016-12-01',
      'bis' => '2016-12-31',
      'fassung' => 'EEG 2014',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Okt-Dez2016.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2016\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    208 => 
    array (
      'von' => '2017-01-01',
      'bis' => '2017-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.51,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/AnzulegendeWertePV_Jan17.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    209 => 
    array (
      'von' => '2017-02-01',
      'bis' => '2017-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.51,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    210 => 
    array (
      'von' => '2017-03-01',
      'bis' => '2017-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.51,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Feb-Apr2017.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    211 => 
    array (
      'von' => '2017-04-01',
      'bis' => '2017-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.51,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/SharedDocs/Downloads/DE/Sachgebiete/Energie/Unternehmen_Institutionen/ErneuerbareEnergien/Photovoltaik/ArchivDatenMeldgn/DegressionsVergSaetze_Feb-Apr2017.xls (via web.archive.org), Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    212 => 
    array (
      'von' => '2017-05-01',
      'bis' => '2017-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.49,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    213 => 
    array (
      'von' => '2017-06-01',
      'bis' => '2017-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.47,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    214 => 
    array (
      'von' => '2017-07-01',
      'bis' => '2017-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    215 => 
    array (
      'von' => '2017-08-01',
      'bis' => '2017-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    216 => 
    array (
      'von' => '2017-09-01',
      'bis' => '2017-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    217 => 
    array (
      'von' => '2017-10-01',
      'bis' => '2017-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    218 => 
    array (
      'von' => '2017-11-01',
      'bis' => '2017-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    219 => 
    array (
      'von' => '2017-12-01',
      'bis' => '2017-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2017\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    220 => 
    array (
      'von' => '2018-01-01',
      'bis' => '2018-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Nov17-Jan18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    221 => 
    array (
      'von' => '2018-02-01',
      'bis' => '2018-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    222 => 
    array (
      'von' => '2018-03-01',
      'bis' => '2018-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    223 => 
    array (
      'von' => '2018-04-01',
      'bis' => '2018-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    224 => 
    array (
      'von' => '2018-05-01',
      'bis' => '2018-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    225 => 
    array (
      'von' => '2018-06-01',
      'bis' => '2018-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    226 => 
    array (
      'von' => '2018-07-01',
      'bis' => '2018-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_Mai-Juli18.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    227 => 
    array (
      'von' => '2018-08-01',
      'bis' => '2018-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.35,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    228 => 
    array (
      'von' => '2018-09-01',
      'bis' => '2018-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.27,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    229 => 
    array (
      'von' => '2018-10-01',
      'bis' => '2018-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.18,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    230 => 
    array (
      'von' => '2018-11-01',
      'bis' => '2018-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.09,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    231 => 
    array (
      'von' => '2018-12-01',
      'bis' => '2018-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 8.01,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_19.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2018\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    232 => 
    array (
      'von' => '2019-01-01',
      'bis' => '2019-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.93,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    233 => 
    array (
      'von' => '2019-02-01',
      'bis' => '2019-02-28',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.84,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    234 => 
    array (
      'von' => '2019-03-01',
      'bis' => '2019-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.76,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    235 => 
    array (
      'von' => '2019-04-01',
      'bis' => '2019-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.68,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    236 => 
    array (
      'von' => '2019-05-01',
      'bis' => '2019-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.57,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    237 => 
    array (
      'von' => '2019-06-01',
      'bis' => '2019-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.45,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    238 => 
    array (
      'von' => '2019-07-01',
      'bis' => '2019-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.34,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    239 => 
    array (
      'von' => '2019-08-01',
      'bis' => '2019-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.24,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    240 => 
    array (
      'von' => '2019-09-01',
      'bis' => '2019-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.13,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    241 => 
    array (
      'von' => '2019-10-01',
      'bis' => '2019-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 7.02,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    242 => 
    array (
      'von' => '2019-11-01',
      'bis' => '2019-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.95,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    243 => 
    array (
      'von' => '2019-12-01',
      'bis' => '2019-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.88,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2019\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    244 => 
    array (
      'von' => '2020-01-01',
      'bis' => '2020-01-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.8,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    245 => 
    array (
      'von' => '2020-02-01',
      'bis' => '2020-02-29',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.7,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    246 => 
    array (
      'von' => '2020-03-01',
      'bis' => '2020-03-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.6,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    247 => 
    array (
      'von' => '2020-04-01',
      'bis' => '2020-04-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.5,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    248 => 
    array (
      'von' => '2020-05-01',
      'bis' => '2020-05-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.41,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    249 => 
    array (
      'von' => '2020-06-01',
      'bis' => '2020-06-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.31,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    250 => 
    array (
      'von' => '2020-07-01',
      'bis' => '2020-07-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.22,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    251 => 
    array (
      'von' => '2020-08-01',
      'bis' => '2020-08-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.13,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    252 => 
    array (
      'von' => '2020-09-01',
      'bis' => '2020-09-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.03,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    253 => 
    array (
      'von' => '2020-10-01',
      'bis' => '2020-10-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.94,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    254 => 
    array (
      'von' => '2020-11-01',
      'bis' => '2020-11-30',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.83,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    255 => 
    array (
      'von' => '2020-12-01',
      'bis' => '2020-12-31',
      'fassung' => 'EEG 2017',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.72,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2020\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    256 => 
    array (
      'von' => '2021-01-01',
      'bis' => '2021-01-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.61,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_11-01_21.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    257 => 
    array (
      'von' => '2021-02-01',
      'bis' => '2021-02-28',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    258 => 
    array (
      'von' => '2021-03-01',
      'bis' => '2021-03-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.44,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    259 => 
    array (
      'von' => '2021-04-01',
      'bis' => '2021-04-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.36,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    260 => 
    array (
      'von' => '2021-05-01',
      'bis' => '2021-05-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.28,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    261 => 
    array (
      'von' => '2021-06-01',
      'bis' => '2021-06-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.2,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    262 => 
    array (
      'von' => '2021-07-01',
      'bis' => '2021-07-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.12,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    263 => 
    array (
      'von' => '2021-08-01',
      'bis' => '2021-08-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 5.05,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    264 => 
    array (
      'von' => '2021-09-01',
      'bis' => '2021-09-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.97,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    265 => 
    array (
      'von' => '2021-10-01',
      'bis' => '2021-10-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.89,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    266 => 
    array (
      'von' => '2021-11-01',
      'bis' => '2021-11-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.82,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    267 => 
    array (
      'von' => '2021-12-01',
      'bis' => '2021-12-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.75,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2021\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    268 => 
    array (
      'von' => '2022-01-01',
      'bis' => '2022-01-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.67,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.01.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    269 => 
    array (
      'von' => '2022-02-01',
      'bis' => '2022-02-28',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.6,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.02.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    270 => 
    array (
      'von' => '2022-03-01',
      'bis' => '2022-03-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.53,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.03.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    271 => 
    array (
      'von' => '2022-04-01',
      'bis' => '2022-04-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.46,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.04.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    272 => 
    array (
      'von' => '2022-05-01',
      'bis' => '2022-05-31',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.4,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.05.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    273 => 
    array (
      'von' => '2022-06-01',
      'bis' => '2022-06-30',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.33,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.06.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    274 => 
    array (
      'von' => '2022-07-01',
      'bis' => '2022-07-29',
      'fassung' => 'EEG 2021',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.26,
          'voll' => NULL,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze_08bis1022.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.07.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen (Freifläche, Nichtwohngebäude Außenbereich).',
    ),
    275 => 
    array (
      'von' => '2022-07-30',
      'bis' => '2022-07-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.26,
          'voll' => 4.26,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 30.07.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    276 => 
    array (
      'von' => '2022-08-01',
      'bis' => '2022-08-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.2,
          'voll' => 4.2,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.08.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    277 => 
    array (
      'von' => '2022-09-01',
      'bis' => '2022-09-30',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.13,
          'voll' => 4.13,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.09.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    278 => 
    array (
      'von' => '2022-10-01',
      'bis' => '2022-10-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 4.07,
          'voll' => 4.07,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.10.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    279 => 
    array (
      'von' => '2022-11-01',
      'bis' => '2022-11-30',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 3.99,
          'voll' => 3.99,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.11.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    280 => 
    array (
      'von' => '2022-12-01',
      'bis' => '2022-12-31',
      'fassung' => 'EEG 2021 i.d.F. EEG-Novelle 28.07.2022',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 3.91,
          'voll' => 3.91,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/Archiv/DegressionsVergSaetze1122bis0124.xlsx, Block \'Feste Einspeisevergütung\', Zeile \'ab 01.12.2022\'',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen: Degression 1,4 % bzw. 1,8 % monatlich lief weiter; kein Volleinspeiser-Zuschlag.',
    ),
    281 => 
    array (
      'von' => '2023-01-01',
      'bis' => '2024-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.6,
          'voll' => 6.6,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.01.2023\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    282 => 
    array (
      'von' => '2024-02-01',
      'bis' => '2024-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.53,
          'voll' => 6.53,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2024\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    283 => 
    array (
      'von' => '2024-08-01',
      'bis' => '2025-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.46,
          'voll' => 6.46,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2024\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    284 => 
    array (
      'von' => '2025-02-01',
      'bis' => '2025-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.39,
          'voll' => 6.39,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2025\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    285 => 
    array (
      'von' => '2025-08-01',
      'bis' => '2026-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.32,
          'voll' => 6.32,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2025\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    286 => 
    array (
      'von' => '2026-02-01',
      'bis' => '2026-07-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.26,
          'voll' => 6.26,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.02.2026\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
    287 => 
    array (
      'von' => '2026-08-01',
      'bis' => '2027-01-31',
      'fassung' => 'EEG 2023 (§ 48 Abs. 1)',
      'kategorie' => 'sonstige',
      'klassen' => 
      array (
        0 => 
        array (
          'bis_kwp' => 100,
          'teil' => 6.19,
          'voll' => 6.19,
          'eigenverbrauch' => NULL,
          'eigenverbrauch_ueber30' => NULL,
        ),
      ),
      'quelle' => 'https://www.bundesnetzagentur.de/DE/Fachthemen/ElektrizitaetundGas/ErneuerbareEnergien/EEG_Foerderung/Archiv_VergSaetze/_DL/VergSaetzeAug26bisJan27.xlsx, Block \'Feste Einspeisevergütung\', Zeilen \'ab 01.08.2026\' Teil-/Volleinspeisung (gerundet)',
      'geprueft' => true,
      'anmerkung' => 'Sonstige Anlagen bis 100 kW.',
    ),
  ),
);
