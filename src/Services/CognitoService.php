<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CognitoService
{
    private static function cliente(): Client
    {
        return new Client([
            'base_uri' => 'https://cognito-idp.' . $_ENV['COGNITO_REGION'] . '.amazonaws.com/',
            'timeout'  => 10,
            'headers'  => ['Content-Type' => 'application/x-amz-json-1.1'],
        ]);
    }

    private static function chamar(string $alvo, array $corpo): array
    {
        try {
            $resposta = self::cliente()->post('', [
                'headers' => ['X-Amz-Target' => 'AWSCognitoIdentityProviderService.' . $alvo],
                'json'    => $corpo,
            ]);
            return json_decode($resposta->getBody()->getContents(), true) ?? [];
        } catch (ClientException $e) {
            $dados = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new \RuntimeException($dados['message'] ?? 'Erro no Cognito');
        }
    }

    public static function calcularSecretHash(string $username): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $username . $_ENV['COGNITO_CLIENT_ID'],
            $_ENV['COGNITO_CLIENT_SECRET'],
            true
        ));
    }

    public static function registrar(string $email, string $senha, string $nome, string $perfil): array
    {
        return self::chamar('SignUp', [
            'ClientId'       => $_ENV['COGNITO_CLIENT_ID'],
            'SecretHash'     => self::calcularSecretHash($email),
            'Username'       => $email,
            'Password'       => $senha,
            'UserAttributes' => [
                ['Name' => 'email',         'Value' => $email],
                ['Name' => 'name',          'Value' => $nome],
                ['Name' => 'custom:perfil', 'Value' => $perfil],
            ],
        ]);
    }

    /**
     * Passo 1 do login — retorna desafio MFA ou tokens (se MFA já configurado)
     */
    public static function iniciarLogin(string $email, string $senha): array
    {
        return self::chamar('InitiateAuth', [
            'AuthFlow' => 'USER_PASSWORD_AUTH',
            'ClientId' => $_ENV['COGNITO_CLIENT_ID'],
            'AuthParameters' => [
                'USERNAME'    => $email,
                'PASSWORD'    => $senha,
                'SECRET_HASH' => self::calcularSecretHash($email),
            ],
        ]);
    }

    /**
     * Passo 2 do login — responde ao desafio SOFTWARE_TOKEN_MFA com código TOTP
     */
    public static function responderDesafioMfa(string $email, string $codigo, string $session): array
    {
        return self::chamar('RespondToAuthChallenge', [
            'ClientId'      => $_ENV['COGNITO_CLIENT_ID'],
            'ChallengeName' => 'SOFTWARE_TOKEN_MFA',
            'Session'       => $session,
            'ChallengeResponses' => [
                'USERNAME'                => $email,
                'SOFTWARE_TOKEN_MFA_CODE' => $codigo,
                'SECRET_HASH'             => self::calcularSecretHash($email),
            ],
        ]);
    }

    /**
     * Primeiro acesso — associa um autenticador TOTP à conta
     * Retorna o SecretCode para gerar o QR code
     */
    public static function associarTotp(string $accessToken): string
    {
        $resposta = self::chamar('AssociateSoftwareToken', [
            'AccessToken' => $accessToken,
        ]);
        return $resposta['SecretCode'];
    }

    /**
     * Confirma o código TOTP digitado pelo usuário após escanear o QR code
     */
    public static function verificarTotp(string $accessToken, string $codigo): void
    {
        self::chamar('VerifySoftwareToken', [
            'AccessToken'        => $accessToken,
            'UserCode'           => $codigo,
            'FriendlyDeviceName' => 'Salve Alimento',
        ]);
    }

    /**
     * Responde ao desafio MFA_SETUP após verificar o TOTP
     */
    public static function responderDesafioMfaSetup(string $email, string $session): array
    {
        return self::chamar('RespondToAuthChallenge', [
            'ClientId'      => $_ENV['COGNITO_CLIENT_ID'],
            'ChallengeName' => 'MFA_SETUP',
            'Session'       => $session,
            'ChallengeResponses' => [
                'USERNAME'    => $email,
                'SECRET_HASH' => self::calcularSecretHash($email),
            ],
        ]);
    }

    public static function revogarToken(string $refreshToken): void
    {
        self::chamar('RevokeToken', [
            'ClientId'     => $_ENV['COGNITO_CLIENT_ID'],
            'ClientSecret' => $_ENV['COGNITO_CLIENT_SECRET'],
            'Token'        => $refreshToken,
        ]);
    }

    public static function renovarToken(string $refreshToken, string $email): array
    {
        return self::chamar('InitiateAuth', [
            'AuthFlow' => 'REFRESH_TOKEN_AUTH',
            'ClientId' => $_ENV['COGNITO_CLIENT_ID'],
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
                'SECRET_HASH'   => self::calcularSecretHash($email),
            ],
        ]);
    }

    /**
     * Dispara o fluxo de recuperação de senha — Cognito envia código por e-mail
     */
    public static function esqueceuSenha(string $email): void
    {
        self::chamar('ForgotPassword', [
            'ClientId'   => $_ENV['COGNITO_CLIENT_ID'],
            'SecretHash' => self::calcularSecretHash($email),
            'Username'   => $email,
        ]);
    }

    /**
     * Confirma a nova senha com o código enviado por e-mail pelo Cognito
     */
    public static function confirmarNovaSenha(string $email, string $codigo, string $novaSenha): void
    {
        self::chamar('ConfirmForgotPassword', [
            'ClientId'         => $_ENV['COGNITO_CLIENT_ID'],
            'SecretHash'       => self::calcularSecretHash($email),
            'Username'         => $email,
            'ConfirmationCode' => $codigo,
            'Password'         => $novaSenha,
        ]);
    }
}
