<?php

declare(strict_types=1);

/**
 * EMS, Teil der Klasse EMS (Umbau Schritt 3, 17.11.2026): Erkennung der Wechselrichter-Art und Beschreibung, was ein Befehl dort bewirkt.
 * Wörtlich aus module.php verschoben, Verhalten unverändert. Eingebunden per "use EMSInverterKind;" in der Klasse EMS.
 */
trait EMSInverterKind
{
    // ----------------------------------------------------------------
    //  Wechselrichter-Wissen (Umbau 2b, 17.11.2026): welche Art von Wechselrichter, und was ein Befehl dort bewirkt.
    //  Vorher fragten sechs Stellen selbst "ctl_ems_mode fehlt und Solis-Steuerpunkte da?". Neue Hersteller kommen hier dazu,
    //  nicht als weitere Weiche irgendwo im Code.
    // ----------------------------------------------------------------

    /**
     * Art des Wechselrichters nach seinen InverterHub-Steuerpunkten: 'goodwe' (ctl_ems_mode/-power/-enable), 'solis'
     * (ctl_force_mode/-w ohne ctl_ems_*), 'readonly' (keine EMS-Stellglieder), 'none' (keine Instanz).
     */
    private function inverterKind(int $iid): string
    {
        if ($iid <= 0) { return 'none'; }
        if ($this->isSolisDriver($iid)) { return 'solis'; }
        $has = function (string $ident) use ($iid): bool { return $this->findChildVariableIdByIdent($iid, $ident) > 0; };
        if ($has('ctl_ems_mode') && $has('ctl_ems_power') && $has('ctl_ems_enable')) { return 'goodwe'; }
        // 0.109.0: andere Hersteller ueber die gemeinsame Fernsteuerung (InverterHub-Vertrag 1.9, ctl_rc_*)
        return $has('ctl_rc_mode') ? 'remote' : 'readonly';
    }

    private function batteryInverterKind(): string
    {
        $inv = $this->getInverterEntry();
        return $this->inverterKind((int)($inv['instanceID'] ?? 0));
    }

    /**
     * Statussatz fuer "Auto aus dem Netz" (Modus 9) je Wechselrichter: GoodWe regelt den Netzbezug auf das Auto, das Haus laeuft
     * aus PV und Akku; Solis kennt das nicht, setSolisMode() haelt dort den Akku, das Haus laeuft ebenfalls aus dem Netz
     * (Live-Befund Paul 17.11.2026). Ersetzt die Solis-Weiche in carFromGridSetting() aus 0.105.5.
     */
    private function carFromGridText(float $carW, int $xsetW): string
    {
        $inv = $this->getInverterEntry();
        $iid = (int)($inv['instanceID'] ?? 0);
        $kind = $this->batteryInverterKind();
        if (($kind === 'solis' || $kind === 'remote') && $this->remoteUsable($iid)) {
            $ch = $this->remoteChannel($iid);
            if (!in_array(3, $this->remoteModes($iid, $ch), true)) {
                return sprintf($this->t('car charging at %.1f kW from the grid, battery supplies only the house (inverter has no grid import control)'), $carW / 1000.0);
            }
        } elseif ($kind === 'solis') {
            return sprintf($this->t('car charging at %.1f kW from the grid, battery is held (Solis has no grid import control), house also from the grid'), $carW / 1000.0);
        }
        return sprintf($this->t('car charging at %.1f kW, grid import controlled to %.1f kW (car from the grid), house from PV and battery'), $carW / 1000.0, $xsetW / 1000.0);
    }
}
