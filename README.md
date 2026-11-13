# EMS — Energiemanagement für Symcon (NRG-Stack)

![Symcon](https://img.shields.io/badge/Symcon-PHP--Modul-blue)
![Modul Version](https://img.shields.io/badge/Modul_Version-0.102.1-blue)
![Symcon Version](https://img.shields.io/badge/Symcon_Version-9.0%2B-blue)
![License](https://img.shields.io/badge/License-PolyForm_Noncommercial_1.0.0-lightgrey)
[![Check Style](https://github.com/DG65/NRGEMS/actions/workflows/check-style.yml/badge.svg)](https://github.com/DG65/NRGEMS/actions/workflows/check-style.yml)
[![PayPal](https://img.shields.io/badge/PayPal-Me-blue?logo=paypal)](https://paypal.me/DietmarGureth)

English version: [README.en.md](README.en.md)

## Übersicht

Das EMS plant und steuert Hausakku, Wallboxen und (optional) den Zusatzlauf der Wärmepumpe anhand von
Viertelstunden-Strompreisen, PV- und Verbrauchsprognose. Ziel ist der günstigste Strombezug — unter
Berücksichtigung der echten Akkuverluste. Es ist die koordinierende Instanz des NRG-Stack: Es findet die
anderen NRG-Stack-Module selbst und liest deren Daten über feste Schnittstellen (Verträge). Jeder Baustein ist
optional; was nicht vorhanden ist, wird übersprungen.

**Ehrliche Einordnung:** Wie viel das EMS spart, hängt stark von der Preisspanne ab. Bei kleinen
Tag-Nacht-Unterschieden (typisch Herbst) bleibt nach den Lade- und Entladeverlusten wenig übrig, dann lässt
das EMS den Wechselrichter meist in seiner eigenen Automatik. Der Bereich **„📊 EMS-Bilanz“** rechnet täglich
nach, ob das EMS gegenüber der reinen Wechselrichter-Automatik gespart oder verloren hat.

## Funktionen

- **Tagesplan je Viertelstunde:** Aus Preisen, PV- und Lastprognose entsteht ein Plan (Automatik, Netzladen,
  Akku halten). Er ist im Formular, als Symcon-Wochenplan „EMS Tagesplan (automatisch)“ und über
  `EMS_GetDayPlan()` sichtbar; jede Viertelstunde hat eine Begründung.
- **Netzladen nur, wenn es sich lohnt:** Geladen wird nur, wenn der Preis nach Verlusten
  (Rundwirkungsgrad Netz → Akku → Haus, Standard 82 %), Sicherheitsfaktor (Standard 90 %), Zykluskosten und
  Mindestspanne unter dem erwarteten späteren Preis liegt.
- **Akku halten:** Ist Strom jetzt billiger als später, deckt das Netz das Haus und der Akku bleibt für die
  teure Zeit.
- **Wallboxen:** Ladearten preisgesteuert, manuell, nur PV, PV-Anteil, Hybrid und aus; Ladeziel mit Frist,
  Wochen-Fristen, „Jetzt laden“. Tibber Smart Charging / Grid Rewards: Tibber steuert das Auto, der Hausakku
  folgt weiter seinem Plan.
- **Wärmepumpe (optional, ab Werk aus):** Zusatzlauf nur bei PV-Überschuss, der sonst eingespeist würde; der
  Akku hat Vorrang.
- **§14a EnWG:** Vorgaben des Netzbetreibers (über SteuerboxHub) haben immer Vorrang, auch vor allen
  Preis-Entscheidungen.
- **EMS-Bilanz, Rechnungsprüfung, Akku-Einstandspreis, Sondereffekte** (Tage mit Einspeisereduktion, Negativpreis,
  §14a werden markiert, damit lernende Module sie nicht als Normaltag lernen).

Priorität: **Gesetz/Netzbetreiber > Vermarkter (Grid Rewards) > EMS-Optimierung > Komfort.**

## Unterstützte Hardware

| Bereich | Umfang |
|---|---|
| Wechselrichter mit Akku (über NRG-Stack **InverterHub**) | **GoodWe ET/EH**: gesteuert, live erprobt. **Solis Hybrid**: minimale Steuerung (Zwangsladen, Akku halten über Entladesperre, Automatik). **Andere Hersteller** (u. a. SMA, Fronius, Sungrow, Huawei, RCT): das EMS liest und plant, steuert aber nicht — der Plan ist dann eine Anzeige. |
| Wallboxen | NRG-Stack **ChargerHub** (u. a. go-e) und **OCPPHub**: gesteuert. **ChargerEEBUSHub**: gemessen, noch nicht geschaltet. |
| Wärmepumpe | **HeishaMon** und **WPEEBUSHub** (Steuermuster `optionalRunID`); andere WP-Module nur Messung. |
| Preise | NRG-Stack **TibberGridRewards**, **Börsenpreis**, Symcon-Strompreis oder eine eigene Preisvariable. |
| Prognosen | NRG-Stack **Prognose** (PV- und Lastprognose); ohne Prognose rechnet das EMS mit festen Annahmen. |
| §14a | NRG-Stack **SteuerboxHub**. |

## Voraussetzungen

- Symcon ab Version 9.0
- Mindestens ein Wechselrichter über den NRG-Stack InverterHub (für die Steuerung: GoodWe oder Solis)
- Empfohlen: eine Preisquelle mit Viertelstunden-Preisen und die NRG-Stack-Prognose
- Für die EMS-Bilanz: Logging (Archiv) der Messwerte. Fehlt es, zeigt der Bereich „📈 Logging fehlt“ die
  Variablen und schaltet es auf Klick ein.

## Installation

1. Im Module Control folgende URL hinzufügen: `https://github.com/DG65/NRGEMS` (oder über den Symcon-Store)
2. Instanz „EMS“ anlegen
3. Formular von oben nach unten durchgehen. Partner-Module werden automatisch gefunden; die
   **Konfigurationsprüfung** zeigt, was fehlt.

## Sicherer Start

- Ab Werk ist das EMS **aus**. Eine neue Instanz schreibt erst, wenn die Risikobestätigung gesetzt,
  „EMS aktiv“ eingeschaltet und danach in der Konfigurationsprüfung **„Jetzt live schalten“** gewählt wurde.
  Bis dahin läuft das EMS im **Trockenlauf**: Es plant und begründet, schreibt aber nichts.
- Der Tagesplan wird auch bei ausgeschaltetem EMS berechnet, damit er vor dem Einschalten geprüft werden kann.

### Was das EMS auch im ausgeschalteten Zustand schreibt

Offengelegt, weil es Geräte betrifft:

1. **§14a-Vorgaben des Netzbetreibers** (nur mit SteuerboxHub): Einspeisereduktion am Wechselrichter und
   Abschalten der Wallboxen während einer Lastbegrenzung, danach Wiederfreigabe. Gesetzliche Vorgaben dürfen
   nicht davon abhängen, ob die Preisoptimierung an ist.
2. **Übergabe an die Wechselrichter-Automatik** beim Ausschalten: einmalig Automatik-Modus und Freigabe der
   EMS-Steuerung (GoodWe: `ctl_ems_enable=false`, Modus Automatik, Leistung 0; Solis: Zwangsmodus aus), damit
   kein zuletzt gesendeter Befehl hängen bleibt.
3. **Totmann-Rückfall:** Fällt die Verbindung zum Wechselrichter wiederholt aus, wechselt das EMS in den
   eingestellten Rückfallmodus (Standard: Wechselrichter-Automatik).

Das EMS schreibt nur in Geräte über deren Aktionen bzw. die Steuerkanäle der NRG-Stack-Module und nur dort,
wo das Gerät dem EMS nicht von einem anderen Akteur entzogen ist (`managedBy`).

## Bedienung

- **Formular (Konsole):** Einstellungen, Tagesplan, EMS-Bilanz, Konfigurationsprüfung.
- **WebFront:** Variablen unter der Instanz (Status, Entscheidung, Wallbox-Bedienung). Ladeart, Ladeziel,
  Frist und Wochen-Fristen lassen sich dort sofort ändern; diese Bedienwerte werden getrennt von den
  Einstellungen gespeichert. Ein in den Einstellungen geänderter und übernommener Wert ersetzt sie.
- **Andere Module** (z. B. Dashboard) nutzen die öffentlichen Funktionen, u. a. `EMS_GetDayPlan`,
  `EMS_GetCurrentDecision`, `EMS_GetSelfBalance`, `EMS_GetManualPlan`/`EMS_SetManualPlan`,
  `EMS_GetWeekTargets`/`EMS_SetWeekTargets`, `EMS_GetBatteryCost`, `EMS_GetSpecialEvents`,
  `EMS_GetGridImportOutlook`.

## Wichtige Einstellungen

| Einstellung | Standard | Wirkung |
|---|---|---|
| Rundwirkungsgrad PV → Akku → Haus | 91 % | Verlust, wenn der Akku aus PV geladen wird |
| Rundwirkungsgrad Netz → Akku → Haus | 82 % | Verlust beim Netzladen; bestimmt, ab wann Netzladen lohnt |
| Sicherheitsfaktor Netzladen | 90 % | Puffer gegen Prognosefehler (100 = aus) |
| Nachtfenster / Abend-Mindest-SOC | anlagenabhängig | Rahmen für Netzladen und Reserve |

## Sicherheitshinweise

- Das EMS sendet Steuerbefehle an Wechselrichter und Wallboxen. Eine falsche Konfiguration kann zu
  unerwünschtem Verhalten oder höheren Kosten führen. Zuerst im Trockenlauf beobachten.
- Softwaregrenzen ersetzen keine elektrische Absicherung.

## Lizenz

PolyForm Noncommercial 1.0.0 — private/nicht-kommerzielle Nutzung ist frei, gewerbliche Nutzung erfordert
eine gesonderte Lizenz vom Rechteinhaber (DG65). Bereits unter MIT veröffentlichte Altversionen bleiben MIT.
Lizenztext: [LICENSE](LICENSE). Spenden: [paypal.me/DietmarGureth](https://paypal.me/DietmarGureth).
