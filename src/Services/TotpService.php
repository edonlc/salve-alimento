<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use OTPHP\TOTP;

class TotpService
{
    public static function gerarSegredo(): string
    {
        return TOTP::generate()->getSecret();
    }

    public static function gerarUriQrCode(string $segredo, string $email): string
    {
        $totp = TOTP::createFromSecret($segredo);
        $totp->setLabel($email);
        $totp->setIssuer('Salve Alimento');

        return $totp->getProvisioningUri();
    }

    /**
     * Verifica um código TOTP com janela de ±1 período (30 s) para tolerância de clock
     */
    public static function verificar(string $segredo, string $codigo): bool
    {
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }

        $totp = TOTP::createFromSecret($segredo);

        return $totp->verify($codigo, null, 1);
    }
}
