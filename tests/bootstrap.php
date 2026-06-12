<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Sincroniza variáveis de processo (GitHub Actions / Docker) com $_ENV
// O phpdotenv só é chamado em public/index.php, não nos testes
foreach ([
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'JWT_SECRET', 'JWT_ISSUER', 'JWT_EXPIRY', 'APP_ENV',
    'RSA_PUBLIC_KEY_PATH', 'RSA_PRIVATE_KEY_PATH',
] as $var) {
    $valor = getenv($var);
    if ($valor !== false && !isset($_ENV[$var])) {
        $_ENV[$var] = $valor;
    }
}
