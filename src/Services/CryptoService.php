<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class CryptoService
{
    public static function obterChavePublicaPem(): string
    {
        $caminho  = $_ENV['RSA_PUBLIC_KEY_PATH'] ?? '/var/www/html/keys/public.pem';
        $conteudo = file_get_contents($caminho);

        if ($conteudo === false) {
            throw new \RuntimeException('Chave pública não encontrada');
        }

        return $conteudo;
    }

    /**
     * Decifra a chave AES enviada pelo frontend (cifrada com RSA-OAEP SHA-256)
     */
    public static function decifrarChaveAes(string $chaveAesCifradaBase64): string
    {
        $caminho  = $_ENV['RSA_PRIVATE_KEY_PATH'] ?? '/var/www/html/keys/private.pem';
        $conteudo = file_get_contents($caminho);

        if ($conteudo === false) {
            throw new \RuntimeException('Chave privada não encontrada');
        }

        $chave = PublicKeyLoader::load($conteudo)
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        $resultado = $chave->decrypt(base64_decode($chaveAesCifradaBase64));

        if ($resultado === false) {
            throw new \RuntimeException('Falha ao decifrar chave AES');
        }

        return $resultado;
    }

    /**
     * Decifra dados AES-256-GCM recebidos do frontend.
     * Formato do blob: base64( iv[12] + tag[16] + cifrado )
     */
    public static function decifrarAes(string $blobBase64, string $chaveAes): string
    {
        $raw   = base64_decode($blobBase64);
        $iv    = substr($raw, 0, 12);
        $tag   = substr($raw, 12, 16);
        $dados = substr($raw, 28);

        $texto = openssl_decrypt($dados, 'aes-256-gcm', $chaveAes, OPENSSL_RAW_DATA, $iv, $tag);

        if ($texto === false) {
            throw new \RuntimeException('Falha ao decifrar dados AES');
        }

        return $texto;
    }

    /**
     * Cifra dados com AES-256-GCM.
     * Retorna base64( iv[12] + tag[16] + cifrado )
     */
    public static function cifrarAes(string $dados, string $chaveAes): string
    {
        $iv  = random_bytes(12);
        $tag = '';

        $cifrado = openssl_encrypt($dados, 'aes-256-gcm', $chaveAes, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($cifrado === false) {
            throw new \RuntimeException('Falha ao cifrar dados');
        }

        return base64_encode($iv . $tag . $cifrado);
    }

}
