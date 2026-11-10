# EMS — Energy management for Symcon (NRG-Stack)

![Symcon](https://img.shields.io/badge/Symcon-PHP--Module-blue)
![Module Version](https://img.shields.io/badge/Module_Version-0.101.0-blue)
![Symcon Version](https://img.shields.io/badge/Symcon_Version-9.0%2B-blue)
![License](https://img.shields.io/badge/License-PolyForm_Noncommercial_1.0.0-lightgrey)
[![Check Style](https://github.com/DG65/NRGEMS/actions/workflows/check-style.yml/badge.svg)](https://github.com/DG65/NRGEMS/actions/workflows/check-style.yml)
[![PayPal](https://img.shields.io/badge/PayPal-Me-blue?logo=paypal)](https://paypal.me/DietmarGureth)

Deutsche Fassung: [README.md](README.md)

## Overview

EMS plans and controls the home battery, wallboxes and (optionally) the heat pump's extra run based on
quarter-hourly electricity prices and PV and load forecasts. The goal is the cheapest grid import, taking the
real battery losses into account. It is the coordinating instance of the NRG-Stack: it finds the other
NRG-Stack modules by itself and reads their data through fixed interfaces (contracts). Every building block is
optional; whatever is missing is skipped.

**An honest assessment:** How much EMS saves depends heavily on the price spread. With small day/night
differences (typical in autumn), little is left after charging and discharging losses, and EMS then mostly
leaves the inverter in its own automatic mode. The panel **"📊 EMS balance"** recalculates every day whether
EMS saved or lost money compared with the inverter's plain automatic mode.

## Features

- **Day plan per quarter hour:** prices and PV and load forecasts result in a plan (automatic, grid charging,
  hold battery). It is visible in the form, as the Symcon schedule "EMS Tagesplan (automatisch)" and via
  `EMS_GetDayPlan()`; every quarter hour has a reason.
- **Grid charging only when it pays off:** EMS charges only if the price, after losses (round-trip
  efficiency grid → battery → house, default 82 %), safety factor (default 90 %), cycle costs and minimum
  spread, is below the expected later price.
- **Hold battery:** if electricity is cheaper now than later, the grid supplies the house and the battery is
  kept for the expensive hours.
- **Wallboxes:** charging modes price-controlled, manual, PV only, PV share, hybrid and off; charging target
  with deadline, weekly deadlines, "charge now". Tibber Smart Charging / Grid Rewards: Tibber controls the car,
  the home battery keeps following its plan.
- **Heat pump (optional, off by default):** extra run only on PV surplus that would otherwise be fed into the
  grid; the battery has priority.
- **§14a EnWG (Germany):** grid operator requirements (via SteuerboxHub) always take precedence, including over
  all price decisions.
- **EMS balance, invoice check, battery cost price, special events** (days with feed-in limitation, negative
  prices or §14a are marked so that learning modules do not treat them as normal days).

Priority: **law/grid operator > aggregator (Grid Rewards) > EMS optimization > comfort.**

## Supported hardware

| Area | Scope |
|---|---|
| Inverter with battery (via NRG-Stack **InverterHub**) | **GoodWe ET/EH**: controlled, tested live. **Solis hybrid**: minimal control (forced charging, hold battery via discharge lock, automatic). **Other manufacturers** (e.g. SMA, Fronius, Sungrow, Huawei, RCT): EMS reads and plans but does not control — the plan is then for display only. |
| Wallboxes | NRG-Stack **ChargerHub** (e.g. go-e) and **OCPPHub**: controlled. **ChargerEEBUSHub**: measured, not yet switched. |
| Heat pump | **HeishaMon** and **WPEEBUSHub** (control pattern `optionalRunID`); other heat pump modules are measured only. |
| Prices | NRG-Stack **TibberGridRewards**, **Börsenpreis**, Symcon electricity price or a price variable of your own. |
| Forecasts | NRG-Stack **Prognose** (PV and load forecast); without forecasts EMS uses fixed assumptions. |
| §14a | NRG-Stack **SteuerboxHub**. |

## Requirements

- Symcon 9.0 or newer
- At least one inverter via NRG-Stack InverterHub (for control: GoodWe or Solis)
- Recommended: a price source with quarter-hourly prices and the NRG-Stack forecast
- For the EMS balance: logging (archive) of the measured values. If it is missing, the panel
  "📈 Logging missing" lists the variables and enables logging on click.

## Installation

1. Add the URL `https://github.com/DG65/NRGEMS` in Module Control (or install via the Symcon Store)
2. Create the instance "EMS"
3. Go through the form from top to bottom. Partner modules are found automatically; the
   **configuration check** shows what is missing.

## Safe start

- EMS is **off** by default. A new instance writes only after the risk confirmation has been set,
  "EMS active" has been switched on and then **"Go live now"** has been chosen in the configuration check.
  Until then EMS runs in **dry run**: it plans and explains but writes nothing.
- The day plan is calculated even while EMS is off, so it can be checked before switching on.

### What EMS writes even while switched off

Disclosed because it affects devices:

1. **§14a requirements of the grid operator** (only with SteuerboxHub): feed-in limitation at the inverter and
   switching off the wallboxes during a load limitation, then releasing them again. Legal requirements must not
   depend on whether price optimization is on.
2. **Handover to the inverter's automatic mode** when switching off: once automatic mode and release of the EMS
   control (GoodWe: `ctl_ems_enable=false`, mode automatic, power 0; Solis: forced mode off), so that no command
   sent last remains active.
3. **Dead-man fallback:** if the connection to the inverter fails repeatedly, EMS switches to the configured
   fallback mode (default: inverter automatic).

EMS writes to devices only through their actions or the control channels of the NRG-Stack modules, and only
where the device is not claimed by another actor (`managedBy`).

## Operation

- **Form (console):** settings, day plan, EMS balance, configuration check.
- **WebFront:** variables below the instance (status, decision, wallbox operation). Charging mode, charging
  target, deadline and weekly deadlines can be changed there immediately; these operating values are stored
  separately from the settings. A value changed and applied in the settings replaces them.
- **Other modules** (e.g. Dashboard) use the public functions, including `EMS_GetDayPlan`,
  `EMS_GetCurrentDecision`, `EMS_GetSelfBalance`, `EMS_GetManualPlan`/`EMS_SetManualPlan`,
  `EMS_GetWeekTargets`/`EMS_SetWeekTargets`, `EMS_GetBatteryCost`, `EMS_GetSpecialEvents`,
  `EMS_GetGridImportOutlook`.

## Key settings

| Setting | Default | Effect |
|---|---|---|
| Round-trip efficiency PV → battery → house | 91 % | Loss when the battery is charged from PV |
| Round-trip efficiency grid → battery → house | 82 % | Loss when charging from the grid; decides when grid charging pays off |
| Grid charging safety factor | 90 % | Margin against forecast errors (100 = off) |
| Night window / evening minimum SOC | depends on the system | Frame for grid charging and reserve |

## Safety notes

- EMS sends control commands to inverters and wallboxes. A wrong configuration can lead to unwanted behavior
  or higher costs. Observe in dry run first.
- Software limits do not replace electrical protection.

## License

PolyForm Noncommercial 1.0.0 — private/non-commercial use is free, commercial use requires a separate license
from the rights holder (DG65). Older versions already published under MIT remain MIT.
License text: [LICENSE](LICENSE). Donations: [paypal.me/DietmarGureth](https://paypal.me/DietmarGureth).
