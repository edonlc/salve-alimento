<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;

class JwtService
{
    private static ?array $jwks = null;

    public static function validar(string $token): array
    {
        $jwks = self::obterJwks();

        try {
            $chaves  = JWK::parseKeySet($jwks);
            $payload = (array) JWT::decode($token, $chaves);

            $issuerEsperado = sprintf(
                'https://cognito-idp.%s.amazonaws.com/%s',
                $_ENV['COGNITO_REGION'],
                $_ENV['COGNITO_USER_POOL_ID']
            );

            if (($payload['iss'] ?? '') !== $issuerEsperado) {
                throw new \RuntimeException('Issuer inválido');
            }

            return $payload;
        } catch (\Exception $e) {
            throw new \RuntimeException('Token inválido: ' . $e->getMessage());
        }
    }

    public static function extrairPerfil(array $payload): string
    {
        return $payload['custom:perfil'] ?? '';
    }

    public static function extrairSub(array $payload): string
    {
        return $payload['sub'] ?? '';
    }

    private static function obterJwks(): array
    {
        if (self::$jwks !== null) {
            return self::$jwks;
        }

        $cliente      = new Client(['timeout' => 5]);
        $resposta     = $cliente->get($_ENV['COGNITO_JWKS_URL']);
        self::$jwks   = json_decode($resposta->getBody()->getContents(), true);

        return self::$jwks;
    }
}
