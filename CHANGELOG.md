# Changelog

Ältere Versionen: [CHANGELOG-Archiv.md](CHANGELOG-Archiv.md)

## 0.71.0 (2026-10-04)
- **Grid Rewards bestellt keinen Netzeinkauf-Sollwert mehr (Modus 4), sondern „Laden aus PV“ mit Xmax=0 (Modus 2):** Befund aus dem archivierten 6-s-Bild vom 03.10.2026 (Fenster 12:44–13:00, 13:34–14:02, 15:52–16:17, Quellen: PV, Netz, Batterie, Wallbox, `ctl_ems_mode`/`ctl_ems_power`). Bis 0.70.0 setzte EMS bei Grid Rewards Modus 4 mit Xset = aktueller Wallbox-Leistung, in der Annahme, der Wechselrichter kaufe damit die Leistung für das Auto zu. Tatsächlich bestellt Xset **Batterieladung** (`PBattery = Xset + PV`, so steht es in der Register-Tabelle): Bei Xset 7,2 kW lud die Batterie mit 10–11 kW, und das Auto wurde zusätzlich aus dem Netz versorgt — Netzbezug 14–20 kW (Spitze 19,8 kW um 13:50) statt rund 8 kW. Bei Xset 0 W (Auto pausiert) lud die Batterie 3–5 kW aus PV, unauffällig.
  Jetzt dasselbe Stellglied wie bei Smart Charging (0.70.0): Die Batterie lädt nur aus PV und entlädt nicht, Auto und Haus kommen aus dem Netz. Das hält auch die Grid-Reward-Prämie (Auto aus dem Netz, nicht aus der Batterie). `op_mode` bleibt `EMS_OP_GRIDREWARDS`, Quelle `tibber`, die Wallbox-Freigabe bleibt unangetastet (0.67.1). Der Grund nennt die gemessene Auto-Leistung.
  **SUITE.md korrigiert:** Das „Muster: gezielter Stromeinkauf neben laufender WR-Automatik“ (10.09.2026) ist widerrufen; Xset-Modi sind ausschließlich Batterie-Ladeziele. Das war der eigentliche Fehler hinter 0.27.x–0.70.0.
  Zwei bestehende Prüfungen auf den neuen Sollwert umgestellt, eine neue für den Grund; Mutationstest bestätigt (mit altem Modus 4 schlagen drei Prüfungen an). Noch nicht live beobachtet: Der nächste Grid Reward bei ladendem Auto muss Netzbezug ≈ Auto + Haus und Batterie ≈ 0 W oder nur PV-Ladung zeigen.

## 0.70.0 (2026-10-03)
- **Smart Charging: Auto aus dem Netz laden, Hausbatterie schonen:** Anlass war der Fund vom 27.09.2026 (ein per Smart Charging ladendes Auto zog 7,4 kW aus der Batterie) und Dietmars Entscheidung vom 02.10., das Auto dann wie bei Grid Rewards aus dem Netz zu versorgen. Lädt eine Wallbox im Modus Smart Charging (Tibber hat die Hoheit) mit mindestens 500 W und fährt EMS sonst nur die WR-Automatik, geht der Wechselrichter in „Laden aus PV“ mit Xmax=0 (GoodWe-Modus 2): Die Batterie lädt nur aus PV und entlädt nicht, Haus und Auto kommen aus PV bzw. Netz. Quelle der Entscheidung: `smartcharging` (neuer Wert im Feld `source` von `EMS_GetCurrentDecision`, additiv).
  **Bewusst nicht wie bei Grid Rewards gebaut:** Dort ist ein Netzeinkauf-Sollwert (Modus 4, AC-Import) in Höhe der Wallbox-Leistung im Einsatz. Laut Live-Test 19.09. und Vorfall 20.09.2026 (SUITE.md, GoodWe-Modi) drosselt Modus 4 die PV und nahm bei einem großen Sollwert 3 h 43 min lang nichts auf. Modus 2 mit Xmax=0 ist dasselbe Stellglied wie die Fehlbetrag-Regel (0.66.0) und der Tagesplan, es drosselt die PV nicht und braucht keine Schutzbedingung gegen gleichzeitigen Netzkauf bei PV-Überschuss, weil gar nichts bestellt wird. Beim heutigen Grid Reward (Sollwert 0 W) blieb die PV unauffällig bei 5,3 bis 5,8 kW; ein Sollwert ungleich 0 W ist bei Grid Rewards live weiter ungeprüft.
  Nur auf reinen Automatik-Entscheidungen (Quelle `ems`/`tagesplan`), wie B1: Netzbetreiber, Grid Rewards, Batterie-Boost, Tibber steuert die Batterie und Plan-Sollwerte haben Vorrang. Die Quelle `smartcharging` ist für den Plausibilitätswächter geschützt, sonst hätte er „Batterie ~0 W bei Netzbezug“ nach 5 Minuten als Fehler gewertet und die Batterie wieder ins Auto entladen lassen. Neuer Schalter `WB_Smart_GridBuy` im Panel „Wallboxen“ (Standard an).
  17 neue Prüfungen im Prüfstand (Block 4d), zwei Mutationstests bestätigt (ohne Wächter-Schutz fällt EMS zurück, ohne Mindestleistung greift die Regel auch ohne ladendes Auto).

## 0.69.0 (2026-10-02)
- **Wallbox-Modus automatisch aus Tibber erkennen:** Das Tibber-Modul liefert seit 2.9.2 `TIBBERGR_GetFlexDevices()` (Vertrag 1.0, alle Fahrzeuge und Batterien unabhängig vom Lieferzustand, mit `isPluggedIn`/`isSmartChargingEnabled`). EMS bekommt je Wallbox die Auswahl „Tibber-Fahrzeug“ (`WB1_TibberVehicle`/`WB2_TibberVehicle`, Anzeige „Name (Hersteller)“, Wert Tibbers `deviceId`). Die Zuordnung bestätigt der Nutzer einmal selbst; ein Namensabgleich findet bewusst nicht statt (kein VIN, Namen werden auf beiden Seiten unabhängig vergeben, bei MigrationsHub zweimal schiefgegangen). Gibt es genau ein Fahrzeug und genau eine Wallbox, zeigt die Statuszeile nur einen Vorschlag.
  Im Modus „Automatisch“ gilt: meldet Tibber für das zugeordnete Fahrzeug Smart Charging, ist die Wallbox im Modus Smart Charging (Tibber hat die Hoheit); sonst wie bisher (schaltbar: EMS steuert, nicht schaltbar: beobachten). Meldet Tibber das Fahrzeug kurz nicht oder ist nicht erreichbar, gilt der zuletzt gesehene Stand (Attribut `WB{n}SmartSeen`), damit ein Tibber-Aussetzer nicht zwischen den Modi umschlagen lässt; nie gesehen heißt Rückfall auf das bisherige Verhalten. Fremde Vertrags-Major oder fehlender Aufruf: Kopplung aus, alles andere läuft normal.
  Neue Warnungen in der Statuszeile: „EMS steuert“ gewählt, obwohl Tibber Smart Charging für das Fahrzeug meldet (zwei Regler), und „Smart Charging“ gewählt, obwohl es bei Tibber aus ist (Grid Rewards kann nicht greifen). Die Statuszeile folgt beiden Auswahlfeldern live per `onChange`; `EMS_WallboxModeChanged` hat dafür den Parameter `vehicleId` bekommen (0.68.0 war noch nicht ausgerollt).
  Noch offen: Netzeinkauf-Sollwert bei normalem Smart Charging (Auto aus dem Netz statt aus der Batterie) braucht noch die Schutzbedingung gegen gleichzeitigen Netzkauf bei PV-Überschuss. 18 neue Prüfungen im Prüfstand (Block 4c), gegen den Stub, nicht gegen einen echten Tibber-Account.

## 0.68.0 (2026-10-02)
- **Wallbox-Modus je Wallbox (manuell wählbar):** Auslöser war Dietmars Hinweis, dass Grid Rewards nur funktioniert, wenn Wallbox bzw. Auto auf Smart Charging stehen, und dass EMS dann nicht dazwischenregeln darf. Neue Auswahl je Wallbox im Panel „Wallboxen“ (`WB1_Mode`/`WB2_Mode`): *Automatisch* (schaltbar: EMS steuert, sonst nur beobachten — bisheriges Verhalten), *Smart Charging* (Tibber hat die Hoheit), *EMS steuert*, *Nur beobachten*.
  Smart Charging: EMS sperrt und erzwingt nichts an dieser Wallbox. Hat EMS sie früher selbst gesperrt (neues Merkmal `WB{n}EmsLocked`, nur eigene Sperren zählen), gibt es sie einmal wieder frei — auch während Grid Rewards, weil Tibber die Freigabe braucht. Eine vom Nutzer gesperrte Wallbox bleibt gesperrt. Nur beobachten: EMS schreibt nichts, die Leistung zählt zum Hausverbrauch. Das Netzanschluss-Budget drosselt nur noch Wallboxen im Modus „EMS steuert“. `GetSituation` zeigt je Wallbox den Modus (`mode`); Smart Charging gilt als Situation B (Quelle `tibber`, nicht schaltbar).
  Statuszeile je Wallbox im Formular (🔗 automatisch erkannt, ✏️ eigene Wahl, ⚠️ „EMS steuert“ an einer nicht schaltbaren Wallbox, ℹ️ keine Wallbox gefunden), folgt per `onChange` der Auswahl im offenen Formular.
  **Noch nicht enthalten, bewusst:** die automatische Modus-Erkennung aus Tibber (wartet auf `TIBBERGR_GetFlexDevices()` und die einmalige Fahrzeug-Zuordnung je Wallbox) und der Netzeinkauf-Sollwert bei normalem Smart Charging (Auto aus dem Netz statt aus der Batterie). Letzterer braucht noch eine Schutzbedingung, damit bei echtem PV-Überschuss nicht gleichzeitig Netzstrom gekauft wird.
  25 neue Prüfungen im Prüfstand (Block 4b), Mutationstests bestätigt (ohne Moduswahl sperrt EMS die Smart-Charging-Wallbox, ohne Freigabe-Logik bleibt eine eigene Sperre bestehen).

## 0.67.1 (2026-10-02)
- **Grid Rewards sperrt die Wallbox nicht mehr:** Im Grid-Rewards-Zweig stand `wb1_enable=false` mit dem Kommentar „Tibber steuert die Wallbox direkt", `applyDecision()` rief danach aber trotzdem `controlWallbox(n, false)` auf — und das ist ein echtes Sperren (`ctl_enable=false`), sobald die Freigabe gerade an war. Tibbers Smart Charging lädt das Fahrzeug über die Tesla-API (außerhalb von EMS), braucht dafür aber eine freigegebene Wallbox; EMS hätte ihm die Freigabe genommen. Aufgefallen beim Durcharbeiten der Frage „Was passiert bei einem oder zwei angesteckten Autos?" (Dietmar, 02.10.2026). Grid Rewards war live bisher nie aktiv, deshalb unbemerkt.
  Neu: Die Grid-Rewards-Entscheidung trägt das Flag `wb_hands_off`; `applyDecision()` (auch im Netzdienlich-Pfad) schreibt dann weder Freigabe noch Strombegrenzung an die Wallboxen und lässt die Lastverteilung aus. Der Grund-Text nennt es („Wallbox-Freigabe bleibt unangetastet"), der Trockenlauf zeigt „unverändert".
  Bewusst nicht Teil dieser Änderung: Hat EMS die Wallbox VOR Grid Rewards aus Preisgründen gesperrt, gibt es sie auch jetzt nicht von sich aus frei — das gehört zum geplanten Konzept „Smart Charging / EMS steuert / fremd gesteuert" je Wallbox.
  Drei neue Prüfungen im Prüfstand (Block 4), inklusive Gegenprobe; Mutationstest bestätigt: ohne den Fix sendet `applyDecision` tatsächlich `ctl_enable=false`.

## 0.67.0 (2026-09-30)
- **Netzdienlicher Baustein B1 ("Mittagsspitze aufnehmen") lädt jetzt gezielt im PV-Spitzenfenster, statt das Laden möglichst lange hinauszuschieben:** Auslöser war ein Live-Fund (30.09.2026, 12:28 Uhr) — die Batterie war morgens von 73 % auf 90 % geladen, dann sperrte B1 exakt zur Mittagsspitze das weitere Laden und schickte den Überschuss ins Netz. Das widersprach dem eigenen Konzept ("zur Spitze lädt die Batterie aus PV", `EMS-Netzdienlich-Konzept.md` §5) und Dietmars Punkt: Die Mittagsspitze ist genau der Moment, in dem alle PV-Anlagen im Netz gleichzeitig am stärksten einspeisen (deshalb gibt es §14a-Dimmung) — dort sollte die Batterie aufnehmen, nicht zusätzlich einspeisen.
  Ursache: Die alte Reserve-Rechnung verglich den Platzbedarf gegen den PV-Überschuss des GESAMTEN Tagesrests (bis Mitternacht), nicht nur bis zur Spitze — bei reichlich Prognose blieb die Sperre dadurch auch während und nach der eigentlichen Spitze aktiv.
  Neu: `peakWindowSlots()` bestimmt die Spitze datengetrieben aus der PV-Prognose (Slots ≥ `NETZ_B1_Peak_Fraction_Pct` % des Tagesmaximums, neues Property, Standard 80 %). Vor der Spitze sperrt B1 nur noch, wenn der Überschuss bis zum FENSTERBEGINN (nicht mehr bis Tagesende) für den Platzbedarf reicht. Ab Fensterbeginn (und danach) gibt B1 immer frei, unabhängig vom Rest-Tages-Überschuss. Die manuelle "Spätestens freigeben um"-Uhrzeit bleibt als unabhängige Notbremse erhalten.
  Zusätzlich, zeitunabhängig: Ist der Einkaufspreis — inklusive Wandlungsverlust (`convEff()`) und Zykluskosten (`cycleCostCt()`), dieselbe Formel wie das PV_SELFUSE-Live-Sicherheitsnetz — günstiger als die Einspeisevergütung, sperrt B1 immer (egal ob vor, in oder nach der Spitze): Zwischenspeichern lohnt sich dann nicht, ein späterer Bedarf wird einfach billig nachgekauft (Dietmar: "kaufe alles im Netz ein").
  16 neue/überarbeitete Prüfungen in Block 15/16/19 des Prüfstands (Spitzenfenster-Erkennung isoliert, Reserve nur bis Fensterbeginn, Übergang ins/aus dem Fenster, Preis-Override mit/ohne Zykluskosten), alle bestehenden weiterhin grün.

## 0.66.0 (2026-09-27)
- **Fehlbetrag zwischen PV und Hausverbrauch bevorzugt aus dem Netz statt aus der Batterie:** Auslöser war ein Live-Fund (27.09.2026, 11:24 Uhr): Eine Wallbox-Ladung zog bei 18 ct Bezugspreis 7,4 kW aus der Batterie, obwohl der Fehlbetrag zwischen PV (5 kW) und Wallbox-Bedarf (10,9 kW) genauso gut aus dem Netz hätte kommen können. Physikalischer Hintergrund (Dietmar): Netz→Haus/WB ist reines AC/AC ohne Wandlung, PV→Akku läuft über den DC/DC-Steller ebenfalls ohne die 5 %-AC-Wandlungsverluste — würde die Batterie stattdessen den Fehlbetrag decken (Akku→Haus), fiele genau dieser Verlust an, zusätzlich zur unnötigen Zyklisierung.
  Neue Regel in `simulateAutomatikSlot()`/`simulateDaySlot()`: Sobald der aktuelle Strompreis auch mit 5 % Sicherheitsabstand noch unter den eigenen Zykluskosten (Panel „Batterie“) liegt, lädt die Batterie ausschließlich aus der PV (GoodWe-Modus 2, Xmax=0) — der Fehlbetrag zwischen PV und Last deckt der Netzanschluss automatisch, ohne EMS-Entscheidung (Kirchhoff). Gilt unabhängig vom sonstigen Preis-Schwellwert `thDischarge`. Ohne eingetragene oder berechenbare Zykluskosten (Batteriepreis/Zyklenzahl im Panel „Batterie“) greift die Regel nicht, das bisherige Verhalten bleibt unverändert. Live-Sicherheitsnetz in `applyPlanSlot()` entsprechend erweitert (Xmax=0 auch ohne realen PV-Überschuss zulässig, wenn die Regel greift).
  **Wichtig, ehrlich gemessen:** Bei Dietmars eigenen Zykluskosten (3,12 ct/kWh aus 9.998 € / 8.000 Zyklen / 40 kWh) greift die Regel erst unterhalb von ca. 2,98 ct Bezugspreis — der auslösende 18-ct-Fall von heute Morgen wird von dieser Änderung **nicht** anders entschieden, dort bleibt die Batterie-Entladung wirtschaftlich richtig (14,88 ct Ersparnis gegen 5-%-Wandlungsverlust von 0,9 ct). Die Regel wirkt vor allem bei sehr günstigen oder negativen Preisfenstern.
  Sechs neue Prüfungen im Prüfstand (8ad), alle bestehenden weiterhin grün.

## 0.65.0 (2026-09-24)
- **Verbund-Gesundheit erkennt schlafende Tesla-Fahrzeuge:** `EMS_FederationHealth` zählte ein über Tessie eingebundenes Fahrzeug bislang als auffällig, sobald dessen Telemetrie 900 s alt war (Status 203) — bei einem schlafenden Auto der Normalfall, kein Fehler. Ab `TESSIE_GetVehicleState` contractVersion 1.6 liefert Tessie den rohen Schlaf-/Wachzustand (`vehicleStatus`: `asleep`/`waiting_for_sleep`/`awake`/`null`) mit. Bei Status 203 gilt eine Instanz mit `asleep`/`waiting_for_sleep` jetzt als gesund und wird in der Zusammenfassung gesondert als „schläft“ genannt statt unter „auffällig“; `awake` oder `null` (noch nie erfolgreich abgefragt) bleibt weiterhin ein echter Verdacht. Andere Module sind von der Sonderregel nicht betroffen. Auslöser: Tagesauswertung 24.09.2026, mit Tessie geklärt.

## 0.64.2 (2026-09-21)
- **🔗-Zeilen (automatisch übernommen) erscheinen grün** (Label-Farbe 0x2E8B3D), auch wenn sie im offenen Formular per `onChange` aktualisiert werden. Andere Statuszeilen bleiben unverändert.

## 0.64.1 (2026-09-21)
- **Formular zeigt die Verbindung zum Börsenpreis-Modul:** Unter „Netzdienliche Bausteine aktiv“ steht jetzt eine Statuszeile für die Kurve der Negativpreis-Pflicht (§ 51 EEG):
  ✅ Instanz, Name, Umfang (Viertelstunden, bis wann) und aktueller Börsenpreis, ⚠️ bei mehreren Börsenpreis-Instanzen (das EMS nutzt die erste), ℹ️ ohne Modul mit dem
  Tibber-Ersatz oder dem Hinweis, dass die Pflicht nicht geprüft werden kann. Bisher stand dazu nichts im Formular (Hinweis der Börsenpreis-Sitzung).

## 0.64.0 (2026-09-21)
- **Automatisch gelieferte Werte ersetzen das Eingabefeld (Formular):** Liefert eine automatische Verbindung den Wert und ist das Feld leer, wird das Eingabefeld ausgeblendet und die
  Statuszeile zeigt „🔗 Automatisch übernommen: …“ statt „Felder unten werden ignoriert“. Betrifft Batterie-SOC, Netz-Gesamtleistung, PV- und WR-Gesamtleistung, EMS-Modus/-Leistung des
  Wechselrichters und die 15-Minuten-Preise (heute/morgen). Eine eigene Angabe im Feld bleibt sichtbar und hat Vorrang. Der automatische Wert wird nie in das Feld geschrieben, damit „Übernehmen“
  ihn nicht als eigene Angabe speichert. Ohne Verbindung bleibt das Feld sichtbar.

## 0.63.0 (2026-09-21)
- **`EMS_GetSpecialEvents()` 1.1 (additiv):** Neben Tibber Grid Rewards führt das EMS jetzt auch Batterie-Boost (`boost`), negative Börsenpreise mit 0-W-Einspeisung
  (`negativpreis`), Einspeisereduktion des Netzbetreibers (`einspeisung_netzbetreiber`) und § 14a-Lastbegrenzung (`lastbegrenzung_14a`) als Ereignisse.
  Neues Feld `affects` je Ereignis: `pv` (PV-Erzeugung war abgeregelt: Einspeisereduktion, Negativpreis) oder `load` (Last durch Wallbox, Batterie oder Lastbegrenzung
  verfälscht: Grid Rewards, Boost, § 14a). Ältere Einträge ohne Angabe gelten als beides. Lernende Module können damit gezielt ausschließen: die PV-Kalibrierung
  überspringt `pv`-Ereignisse, die Lastprognose `load`-Ereignisse.
- Die Ereignisse werden auch bei ausgeschaltetem EMS mitgeschrieben (Aufruf ohne Zustand lässt offene Grid-Rewards-Ereignisse unberührt).
  Bekannte Grenze: Das Protokoll ist ein Instanz-Attribut, hält höchstens 500 Einträge (keine Zeitgrenze) und geht bei einem vollständigen Modul-Neuladen verloren.

## 0.62.3 (2026-09-21)
- **Statuszeilen folgen der Auswahl im offenen Formular:** Die Zeile zur Preisquelle (Symcon-Strompreis-Instanz) und die Zeile zu den Verschleißkosten (Preis, Zyklen, ct/kWh)
  zeigten bisher den zuletzt gespeicherten Stand, obwohl im Formular schon etwas anderes gewählt oder eingetragen war (Hinweis MeterHub, Symcon lässt die Zeile nicht live
  umschalten). Jetzt aktualisieren sich beide per `onChange`. Prüfstand: Eingabe im Formular ohne Speichern erscheint sofort in der Zeile.

## 0.62.2 (2026-09-21)
- **Formular zeigt, was gelernt wurde:** Unter „Reale max. Ladeleistung“ steht jetzt eine Statuszeile mit der gelernten Ladeleistung je Ladestand
  (z. B. „90–95 %: 21,5 kW“), oder der Grund, warum nichts gelernt wird (Wechselrichter nicht über den InverterHub angebunden, noch keine Ladung).
  Prüfstand: die Zeile erscheint tatsächlich im Formular (auch die Zeilen zu Verschleißkosten).

## 0.62.1 (2026-09-21)
- Formular: Die Beschreibung der realen max. Ladeleistung sagt jetzt, dass das EMS die Ladeleistung je Ladestand nur bei Anbindung über den InverterHub
  selbst lernt; mit von Hand eingetragenen Batterie-Variablen gilt nur der eingetragene Wert.

## 0.62.0 (2026-09-21)
- **Ladekurve aus beobachteter Leistung:** Das Batteriemanagement meldete in der Nacht 20./21.09.2026 bei 88 bis 95 % SOC 8 bis 10 kW, tatsächlich nahm die Batterie
  20 bis 22 kW auf; der Plan hatte deshalb zu viele Ladeslots angenommen. Das EMS lernt jetzt zusätzlich die real beobachtete Ladeleistung je 5-%-Stufe
  (Spitzenwert, nach 30 Tagen ohne Bestätigung verfällt er) und nimmt je Stufe das Maximum aus Meldung und Beobachtung (weiter begrenzt durch
  `BAT_Charge_Max_kW` und Anschluss). Zur Laufzeit gilt unverändert die aktuelle BMS-Grenze; die Beobachtung kann die Kurve nur verbessern.
- **Kein Neustart des Nachtladens bei kleinem Rückgang:** Ist das Nachtziel im Nachtfenster einmal erreicht, plant das EMS bei einem Rückgang von höchstens
  2 Prozentpunkten (Selbstverbrauch, Rauschen) kein neues Netzladen mehr (Nacht 21.09.: zweimal 2 bis 3 Minuten Netzladen bei 99 % ohne Wirkung, der Wächter brach ab).
  Fällt der SOC deutlich, wird wieder geladen. Das Nachtfenster von morgen beginnt frisch.
- Hinweis im Formular: Der Symcon-Strompreis enthält keine zeitvariablen Netzentgelte (§ 14a Modul 3).

## 0.61.0 (2026-09-20)
- **Verschleißkosten im Formular erfragt:** Neue optionale Felder „Anschaffungspreis des Speichers (EUR)“ und „Ladezyklen laut Hersteller“ (`BAT_Price_EUR`,
  `BAT_Cycles`). Steht bei „Verschleißkosten je kWh“ 0, berechnet das EMS Preis / (Zyklen × Kapazität) selbst; ein direkt eingetragener Wert gilt
  weiter vorrangig. Das Formular zeigt den geltenden Wert an. Ohne Angaben werden wie bisher keine Verschleißkosten berücksichtigt (kein Vorgabewert).
- Prüfstand: die beiden B1-Fälle mit Restüberschuss des Tages überspringen jetzt ab 22:00 statt ab 23:45 (waren abends nach 23 Uhr zeitabhängig rot).

## 0.60.2 (2026-09-20)
- **„Neu in Version“ im Formular auf den Stand gebracht:** Das Panel nannte noch Neuerungen von 0.6.0. Jetzt stehen dort Restwert, Symcon-Strompreis,
  Ladeleistung nach Ladestand, zusammenhängende Ladefenster, Trockenlauf, einstellbarer Wirkungsgrad, Wallbox-Mindestleistung und der
  Fallback nach Ausfallzeit (wieder wegklickbar, erscheint einmal je Version).
- **Restwert robuster:** Ohne nutzbare Energie (unter Mindest- und Reservegrenze) oder mit unbekannter Kapazität wird nie „gehalten“.
  Neue Prüfstandsfälle für fehlende Prognose- und Preisdaten, Preislücken, PV-Überschuss, nur heute veröffentlichte Preise im
  Symcon-Strompreis und defekte Marktdaten.
- Prüfstand setzt die Zeitzone selbst (Europe/Berlin) und ist damit auf jeder Maschine grün.

## 0.60.1 (2026-09-20)
- **Restwert rechnet mit vorsichtiger PV (p10):** Der Restwert darf sich nicht darauf verlassen, dass die PV die Batterie wieder füllt.
  Fällt die PV aus (Schnee, Nebel), müssen die teuren Zeiten morgens und abends aus günstig gekauftem Strom überbrückt werden können.
  Das Wiederauffüllen durch PV wird deshalb mit der vorsichtigen Prognose (p10) angenommen, nicht mit p50. Fehlt p10 (ältere Prognose),
  gilt p50. Nur wirksam, wenn der Schalter `PLAN_Restwert_Aktiv` an ist.

## 0.60.0 (2026-09-20)
- **Restwert der Batterieenergie im Plan (Beta, Schalter `PLAN_Restwert_Aktiv`, Standard aus):** Das EMS bewertet die gespeicherte Energie mit
  einem Grenzwert statt mit mehreren Reserve-Sonderregeln. Über die nächsten 24 Stunden werden die Viertelstunden mit Bedarf (Hauslast
  minus PV) nach Preis absteigend mit der nutzbaren Energie belegt; die erste, die nicht mehr gedeckt wäre, setzt den Restwert. Die
  Betrachtung endet, sobald sich die Batterie wieder auffüllt (PV-Überschuss oder geplantes Netzladen). Reicht die Energie für alles,
  gilt der Wiederbeschaffungspreis (günstigster Preis / Wirkungsgrad). Liegt der Netzbezug jetzt um mindestens die Mindestspanne unter dem
  Restwert, plant das EMS „Akku halten, Haus aus dem Netz“ mit Begründung. Bei ausgeschaltetem Schalter ändert sich nichts.
  Der Plan enthält je gehaltenem Slot das Feld `rw` (Restwert in ct/kWh).

## 0.59.0 (2026-09-20)
- **Wallbox-Mindestleistung aus der gemeldeten Phasenzahl:** Meldet die Wallbox ihre aktuelle Phasenzahl (`phases`, ChargerHub-Vertrag
  ab 1.6, derzeit Peblar und go-e im erzwungenen Modus), rechnet das EMS die kleinste sinnvolle Ladeleistung selbst
  (Phasen × 230 V × Mindeststrom, `stationMinCurrentA` aus OCPP, sonst 6 A). Gilt nur, solange die Einstellung noch auf dem
  vorsichtigen Standardwert 4140 W steht; eine ausdrücklich eingestellte Mindestleistung geht immer vor. Die Hardware-Stromgrenze
  `stationMaxCurrentA` (OCPPHub ab 1.7) begrenzt zusätzlich den freigegebenen Ladestrom.
- **Fallback nach Ausfallzeit statt Fehlerzahl:** Der Rückfall in die Wechselrichter-Automatik bei wiederholten Steuerfehlern löst jetzt
  aus, wenn die Fehlerserie so lange dauert wie `EMS_Fallback_Timeout` (Sekunden seit dem ersten Fehler), nicht nach einer aus
  Timeout und Intervall errechneten Anzahl. Übersprungene oder verzögerte Takte verfälschen das nicht mehr. Bei den Standardwerten
  (60 s, Takt 30 s) greift der Rückfall nach 60 s statt nach dem zweiten Fehler.

## 0.58.0 (2026-09-20)
- **Symcon-Strompreis als Preisquelle** (Symcon-Bibliothek „Strompreis“, Modul PowerPrice, Anbieter aWATTar, EPEX Spot oder Tibber): Wer weder Tibber Grid
  Rewards noch das Börsenpreis-Modul hat, aber den Symcon-Strompreis, bekommt jetzt den Tagesplan aus dessen Preisen. Das EMS liest
  die Variable `MarketData` (Liste aus `start`, `end`, `price`, ct/kWh) automatisch, wenn genau eine Instanz vorhanden ist; bei
  mehreren wählt man die Instanz im Formular. Reihenfolge: Tibber Grid Rewards, dann ein ausdrücklich verknüpftes Preisfeld,
  dann Symcon-Strompreis. Der Preis enthält die dort eingestellten Aufschläge (Grundpreis, Aufschlag, MwSt) und gilt als
  Endkundenpreis; die Einstellungen müssen dort stimmen.
- **Stundenpreise:** Preiseinträge mit `end` gelten für alle Viertelstunden ihres Zeitraums (bisher nur für die erste). Ohne
  `end` unverändert eine Viertelstunde. Die Preisquellen-Statuszeile im Formular nennt die gefundene Quelle.
- Der Schalter „Tibber & Tarif aktiv“ (Panel „Tibber & Tarif“) muss weiter eingeschaltet sein, damit der Tagesplan rechnet.

## 0.57.0 (2026-09-20)
- **Zusammenhängende Ladefenster:** Die Nachtladung wählte die günstigsten Viertelstunden einzeln und zerstückelte das Fenster
  (Live-Plan 20.09.: zwei Blöcke, getrennt durch drei Halte-Slots zum praktisch gleichen Preis). Jetzt kostet jeder zusätzliche
  Ladeblock einen kleinen Aufschlag (0,5 ct/kWh); eine Lücke wird geschlossen, wenn die Mehrkosten darunter liegen. Anzahl der Slots
  und Wirtschaftlichkeitsgrenze bleiben unverändert, weniger Moduswechsel und Schreibzugriffe.

## 0.56.0 (2026-09-20)
- **Vertragsfelder fürs Dashboard (additiv, Wunsch NRGDashboard):** `EMS_GetCurrentDecision()` 1.1 liefert `dryRun` (Trockenlauf),
  `observeOnly` und `observeReason` (EMS kann mangels Stellglied/Steuerhoheit nichts schreiben, mit Grund), damit kein Konsument
  den Statustext auswerten muss. `EMS_GetDayPlan()` 1.2 liefert `savingsEur` (Tagessumme) und `windows[]` je Netz-Ladefenster
  (`start`, `end`, `kWh`, `avgPriceCt`, `referenceCt`, `savingsEur`). Ersparnis = (Ø-Preis des Planzeitraums − Ø-Preis des Fensters)
  × geladene Energie; nur Anzeige, ändert nichts an der Steuerung.

## 0.55.0 (2026-09-20)
- **Ladeleistung nach SOC im Plan** (gelernt, bei jedem Nutzer neu und laufend): Viele Batterien drosseln die Ladeleistung nahe voll
  stark; der Plan rechnete die ganze Nacht mit der BMS-Grenze des aktuellen SOC (bei der Entwicklungsanlage 2,1 kW bei 100 %
  gegen 24 kW bei 50 %) und plante dadurch zu viele Ladeslots. Das EMS lernt jetzt die vom BMS gemeldete Ladegrenze je 5-%-SOC-Stufe
  aus dem laufenden Betrieb (gleitender Mittelwert, höchstens 30 Tage alt, begrenzt durch `BAT_Charge_Max_kW` und Anschluss) und
  nutzt sie für Slotzahl und SOC-Verlauf im Plan. Keine Abhängigkeit vom Archiv oder von bestimmter Hardware: Meldet das BMS
  nichts (oder fehlen Stufen), rechnet der Plan wie bisher mit dem festen Wert; fehlende Stufen nehmen die nächste bekannte.
  Zur Laufzeit ändert sich nichts (der Sollwert richtet sich weiter nach der aktuellen BMS-Grenze), so kann die Kurve nicht auf
  einem zu niedrigen Wert einrasten.

## 0.54.0 (2026-09-20)
- **Wirkungsgrad einstellbar** (`BAT_Conv_Eff_Pct`, Standard 95 %): Der Wandlungsverlust stand an zehn Stellen fest als 5 % im Code
  (Preisgrenzen fürs Netzladen, Halten, Vorentladen, Ersatzpreis, Batterie-Einstandspreis). Jetzt eine Einstellung (70–100 %).
- **Ehrliche Statuszeile:** Kann das EMS mangels Stellglied nichts schreiben (fremder Wechselrichter ohne `ctl_ems_*`, Steuerhoheit
  nicht beim EMS, kein Wechselrichter gefunden), zeigt `EMS_Status` „Nur beobachtend (Grund): …“ statt „OK: …“.
