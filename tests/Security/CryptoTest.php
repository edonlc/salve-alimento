<?php

declare(strict_types=1);

namespace SalveAlimento\Tests\Security;

use PHPUnit\Framework\TestCase;
use SalveAlimento\Services\CryptoService;

class CryptoTest extends TestCase
{
    private string $chaveAes;

    protected function setUp(): void
    {
        // Chave AES-256 aleatória (32 bytes = 256 bits)
        $this->chaveAes = random_bytes(32);
    }

    public function testCifrarDecifrarRoundTrip(): void
    {
        $original  = 'CPF: 123.456.789-00';
        $cifrado   = CryptoService::cifrarAes($original, $this->chaveAes);
        $decifrado = CryptoService::decifrarAes($cifrado, $this->chaveAes);

        $this->assertSame($original, $decifrado);
    }

    public function testBlobFormatoCorreto(): void
    {
        $cifrado = CryptoService::cifrarAes('teste', $this->chaveAes);
        $raw     = base64_decode($cifrado);

        // iv(12) + tag(16) = mínimo 28 bytes + pelo menos 1 byte de ciphertext
        $this->assertGreaterThan(28, strlen($raw), 'Blob deve ter pelo menos 29 bytes');
    }

    public function testIvAleatorioACadaCifragem(): void
    {
        $texto   = 'mesmo texto';
        $blob1   = CryptoService::cifrarAes($texto, $this->chaveAes);
        $blob2   = CryptoService::cifrarAes($texto, $this->chaveAes);

        // IVs diferentes garantem que o blob cifrado nunca é idêntico
        $this->assertNotSame($blob1, $blob2, 'Cada cifragem deve usar IV único');
    }

    public function testChaveErradaLancaExcecao(): void
    {
        $this->expectException(\RuntimeException::class);

        $cifrado      = CryptoService::cifrarAes('dado sensível', $this->chaveAes);
        $chaveErrada  = random_bytes(32);

        CryptoService::decifrarAes($cifrado, $chaveErrada);
    }

    public function testCifrarTextoVazio(): void
    {
        $cifrado   = CryptoService::cifrarAes('', $this->chaveAes);
        $decifrado = CryptoService::decifrarAes($cifrado, $this->chaveAes);

        $this->assertSame('', $decifrado);
    }

    public function testBlobCorrompidoLancaExcecao(): void
    {
        $this->expectException(\RuntimeException::class);

        // Blob inválido (não é base64 de iv+tag+ciphertext válido)
        CryptoService::decifrarAes(base64_encode(random_bytes(28)), $this->chaveAes);
    }
}
