#!/usr/bin/env python3
"""Prüft eeg-pv-verguetung.json: Lücken/Überlappungen je Kategorie, Pflichtfelder, vier Gegenproben."""
import json, sys, datetime as dt
from pathlib import Path

fn = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).with_name("eeg-pv-verguetung.json")
data = json.loads(fn.read_text())
Z = data["zeitraeume"]; D = dt.date.fromisoformat
fehler = 0

def err(msg):
    global fehler; fehler += 1; print("FEHLER:", msg)

# 1) Lücken / Überlappungen
for kat in sorted({z["kategorie"] for z in Z}):
    zs = sorted((z for z in Z if z["kategorie"] == kat), key=lambda z: z["von"])
    for z in zs:
        if D(z["von"]) > D(z["bis"]): err(f"{kat} {z['von']}: von > bis")
        if not z.get("klassen"): err(f"{kat} {z['von']}: keine Klassen")
    for a, b in zip(zs, zs[1:]):
        delta = (D(b["von"]) - D(a["bis"])).days
        if delta > 1: err(f"{kat}: Lücke {a['bis']} → {b['von']}")
        if delta < 1: err(f"{kat}: Überlappung {a['von']}–{a['bis']} / {b['von']}–{b['bis']}")
    print(f"{kat}: {len(zs)} Zeiträume, {zs[0]['von']} bis {zs[-1]['bis']}, lückenlos/überlappungsfrei geprüft")

def satz(datum, kwp, feld="teil", kat="gebaeude"):
    t = D(datum)
    for z in Z:
        if z["kategorie"] == kat and D(z["von"]) <= t <= D(z["bis"]):
            for k in z["klassen"]:  # Klassen aufsteigend, null = offen
                if k["bis_kwp"] is None or kwp <= k["bis_kwp"]:
                    return k[feld], z
    return None, None

proben = [
 ("1: IBN 24.10.2012, 9,18 kWp", "2012-10-24", 9.18, "teil", 18.36),
 ("2: IBN 11/2012, bis 10 kWp", "2012-11-15", 10, "teil", 17.90),
 ("3a: IBN 08/2026-01/2027 ≤10 kWp Teil", "2026-09-13", 10, "teil", 7.70),
 ("3b: dto. ≤10 kWp Voll", "2027-01-31", 10, "voll", 12.22),
 ("3c: dto. 10-40 kWp Teil", "2026-08-01", 30, "teil", 6.66),
 ("3d: dto. 10-40 kWp Voll", "2026-12-01", 30, "voll", 10.24),
 ("4: EEG 2012 01.01.-31.03.2012 Dach ≤30 kW", "2012-02-01", 30, "teil", 24.43),
]
print("\nGegenproben:")
for name, datum, kwp, feld, soll in proben:
    ist, z = satz(datum, kwp, feld)
    ok = ist is not None and abs(ist - soll) < 0.005
    if not ok: err(f"Gegenprobe {name}: soll {soll}, ist {ist}")
    print(f"  [{'OK ' if ok else 'XX '}] {name}: soll {soll:.2f}, ist {ist} ({z['fassung'] if z else '-'}, geprüft={z['geprueft'] if z else '-'})")

g = sum(1 for z in Z if z["geprueft"])
print(f"\n{len(Z)} Zeiträume, davon {g} geprüft ({100*g/len(Z):.0f} %), {len(Z)-g} ungeprüft:")
for z in Z:
    if not z["geprueft"]: print(f"  {z['kategorie']} {z['von']}–{z['bis']} {z['fassung']}")
print("\nERGEBNIS:", "alles in Ordnung" if fehler == 0 else f"{fehler} Fehler")
sys.exit(1 if fehler else 0)
