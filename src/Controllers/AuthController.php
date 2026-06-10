<?php

declare(strict_types=1);

namespace SalveAlimento\Controllers;

use SalveAlimento\Models\Usuario;
use SalveAlimento\Services\CognitoService;
use SalveAlimento\Services\AuditService;
use SalveAlimento\Middleware\RateLimitMiddleware;

class AuthController
{
    // ── REGISTRO ──────────────────────────────────────────────────────────────

    public static function registrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include __DIR__ . '/../Views/auth/cadastro.php';
            return;
        }

        $nome   = trim($_POST['nome']   ?? '');
        $email  = trim($_POST['email']  ?? '');
        $senha  = $_POST['senha']        ?? '';
        $perfil = trim($_POST['perfil'] ?? '');

        $perfisValidos = ['doador', 'receptor_ong', 'receptor_familia'];

        if (!$nome || !$email || !$senha || !in_array($perfil, $perfisValidos, true)) {
            self::responderErro('Dados inválidos ou perfil não permitido.', '/cadastrar');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::responderErro('E-mail inválido.', '/cadastrar');
            return;
        }

        if (Usuario::existeEmail($email)) {
            self::responderErro('Este e-mail já está cadastrado.', '/cadastrar');
            return;
        }

        try {
            $resultado = CognitoService::registrar($email, $senha, $nome, $perfil);

            $sub = $resultado['UserSub'] ?? '';
            Usuario::criar([
                'cognito_sub' => $sub,
                'nome'        => $nome,
                'email'       => $email,
                'perfil'      => $perfil,
                'status'      => 'pendente',
            ]);

            AuditService::registrar('REGISTRO', 'usuarios', null, null, null);

            self::redirecionar('/entrar?cadastro=ok');
        } catch (\RuntimeException $e) {
            self::responderErro($e->getMessage(), '/cadastrar');
        }
    }

    // ── LOGIN — PASSO 1 (e-mail + senha) ─────────────────────────────────────

    public static function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include __DIR__ . '/../Views/auth/login.php';
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha']       ?? '';

        if (!$email || !$senha) {
            self::responderErro('Informe e-mail e senha.', '/entrar');
            return;
        }

        // Verifica bloqueio antes de chamar o Cognito
        RateLimitMiddleware::verificarLogin($email);

        try {
            self::iniciarSessao();
            $resultado = CognitoService::iniciarLogin($email, $senha);

            $desafio = $resultado['ChallengeName'] ?? null;

            if ($desafio === 'SOFTWARE_TOKEN_MFA') {
                $_SESSION['mfa_email']   = $email;
                $_SESSION['mfa_session'] = $resultado['Session'];

                RateLimitMiddleware::resetarTentativas($email);
                self::redirecionar('/verificar-2fa');
                return;
            }

            if ($desafio === 'MFA_SETUP') {
                // Chama AssociateSoftwareToken imediatamente — a session expira em ~3 min
                // e pode expirar antes do usuário abrir /configurar-2fa
                $totp    = CognitoService::associarTotp($resultado['Session']);
                $segredo = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $totp['SecretCode']));

                $_SESSION['mfa_setup_email']          = $email;
                $_SESSION['mfa_setup_segredo']        = $segredo;
                $_SESSION['mfa_verify_session']       = $totp['Session'];

                self::redirecionar('/configurar-2fa');
                return;
            }

            // Login sem desafio (não deveria ocorrer com MFA obrigatório)
            if (!empty($resultado['AuthenticationResult'])) {
                self::salvarTokens($resultado['AuthenticationResult'], $email);
                self::redirecionar('/painel');
            }
        } catch (\RuntimeException $e) {
            RateLimitMiddleware::registrarFalha($email);
            self::responderErro('E-mail ou senha incorretos.', '/entrar');
        }
    }

    // ── LOGIN — PASSO 2 (verificação 2FA) ────────────────────────────────────

    public static function verificar2fa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include __DIR__ . '/../Views/auth/verificar-2fa.php';
            return;
        }

        self::iniciarSessao();

        $email   = $_SESSION['mfa_email']   ?? '';
        $session = $_SESSION['mfa_session'] ?? '';
        $codigo  = trim($_POST['codigo']    ?? '');

        if (!$email || !$session || !preg_match('/^\d{6}$/', $codigo)) {
            self::responderErro('Sessão expirada ou código inválido.', '/entrar');
            return;
        }

        try {
            $resultado = CognitoService::responderDesafioMfa($email, $codigo, $session);

            if (!empty($resultado['AuthenticationResult'])) {
                unset($_SESSION['mfa_email'], $_SESSION['mfa_session']);
                self::salvarTokens($resultado['AuthenticationResult'], $email);
                AuditService::registrar('LOGIN', 'usuarios', null, null, self::idUsuarioLogado());
                self::redirecionar('/painel');
            } else {
                self::responderErro('Código 2FA inválido.', '/verificar-2fa');
            }
        } catch (\RuntimeException $e) {
            self::responderErro('Código 2FA inválido ou sessão expirada.', '/verificar-2fa');
        }
    }

    // ── CONFIGURAR 2FA (primeiro acesso) ─────────────────────────────────────

    public static function configurar2fa(): void
    {
        self::iniciarSessao();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $email   = $_SESSION['mfa_setup_email']   ?? '';
            $segredo = $_SESSION['mfa_setup_segredo'] ?? '';

            // AssociateSoftwareToken já foi chamado no login; se faltar, redireciona
            if (!$email || !$segredo) {
                self::redirecionar('/entrar');
                return;
            }

            $uriTotp = 'otpauth://totp/SalveAlimento:' . rawurlencode($email)
                . '?secret=' . $segredo . '&issuer=SalveAlimento';

            include __DIR__ . '/../Views/auth/configurar-2fa.php';
            return;
        }

        // POST — confirma o código TOTP digitado pelo usuário
        $codigo  = trim($_POST['codigo'] ?? '');
        $session = $_SESSION['mfa_verify_session'] ?? '';
        $email   = $_SESSION['mfa_setup_email']    ?? '';

        if (!preg_match('/^\d{6}$/', $codigo) || !$session || !$email) {
            self::responderErro('Código inválido ou sessão expirada. Faça login novamente.', '/entrar');
            return;
        }

        try {
            $sessionPos = CognitoService::verificarTotp($session, $codigo);
            $resultado  = CognitoService::responderDesafioMfaSetup($email, $sessionPos);

            if (!empty($resultado['AuthenticationResult'])) {
                unset(
                    $_SESSION['mfa_setup_email'],
                    $_SESSION['mfa_setup_segredo'],
                    $_SESSION['mfa_verify_session']
                );
                self::salvarTokens($resultado['AuthenticationResult'], $email);
                AuditService::registrar('LOGIN_PRIMEIRO_ACESSO', 'usuarios', null, null, self::idUsuarioLogado());
                self::redirecionar('/painel');
            }
        } catch (\RuntimeException $e) {
            self::responderErro('Código inválido. Verifique o aplicativo autenticador.', '/configurar-2fa');
        }
    }

    // ── LOGOUT ────────────────────────────────────────────────────────────────

    public static function logout(): void
    {
        self::iniciarSessao();

        $refreshToken = $_COOKIE['refresh_token'] ?? '';
        $idUsuario    = self::idUsuarioLogado();

        if ($refreshToken) {
            try {
                CognitoService::revogarToken($refreshToken);
            } catch (\RuntimeException) {
                // ignora — sessão será destruída de qualquer forma
            }
        }

        AuditService::registrar('LOGOUT', 'usuarios', null, null, $idUsuario);

        session_destroy();
        self::limparCookies();
        self::redirecionar('/entrar');
    }

    // ── RECUPERAÇÃO DE SENHA ─────────────────────────────────────────────────

    public static function recuperarSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include __DIR__ . '/../Views/auth/recuperar-senha.php';
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::responderErro('E-mail inválido.', '/recuperar-senha');
            return;
        }

        try {
            CognitoService::esqueceuSenha($email);
        } catch (\RuntimeException) {
            // Não revela se o e-mail existe ou não (enumeração de usuários)
        }

        // Responde sempre com sucesso para evitar enumeração
        self::redirecionar('/recuperar-senha?enviado=ok');
    }

    // ── REDEFINIÇÃO DE SENHA ─────────────────────────────────────────────────

    public static function redefinirSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include __DIR__ . '/../Views/auth/redefinir-senha.php';
            return;
        }

        $email     = trim($_POST['email']      ?? '');
        $codigo    = trim($_POST['codigo']     ?? '');
        $novaSenha = $_POST['nova_senha']       ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';

        if (!$email || !$codigo || !$novaSenha || $novaSenha !== $confirmar) {
            self::responderErro('Dados inválidos ou senhas não conferem.', '/recuperar-senha');
            return;
        }

        try {
            CognitoService::confirmarNovaSenha($email, $codigo, $novaSenha);
            AuditService::registrar('SENHA_REDEFINIDA', 'usuarios');
            self::redirecionar('/entrar?senha=redefinida');
        } catch (\RuntimeException $e) {
            self::responderErro('Código inválido ou expirado.', '/recuperar-senha');
        }
    }

    // ── HELPERS PRIVADOS ──────────────────────────────────────────────────────

    private static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure'   => !empty($_SERVER['HTTPS']),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
            ]);
        }
    }

    private static function salvarTokens(array $tokens, string $email): void
    {
        $seguro = !empty($_SERVER['HTTPS']);

        $expira = time() + ($tokens['ExpiresIn'] ?? 3600);

        setcookie('access_token', $tokens['AccessToken'], [
            'expires'  => $expira,
            'path'     => '/',
            'secure'   => $seguro,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // IdToken contém claims customizados (custom:perfil) — necessário para RBAC
        if (!empty($tokens['IdToken'])) {
            setcookie('id_token', $tokens['IdToken'], [
                'expires'  => $expira,
                'path'     => '/',
                'secure'   => $seguro,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        if (!empty($tokens['RefreshToken'])) {
            setcookie('refresh_token', $tokens['RefreshToken'], [
                'expires'  => time() + 30 * 24 * 3600,
                'path'     => '/',
                'secure'   => $seguro,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        // Armazena e-mail na sessão para renovação de token
        self::iniciarSessao();
        session_regenerate_id(true);
        $_SESSION['usuario_email'] = $email;

        // Sincroniza usuário local com o Cognito sub
        $usuario = Usuario::buscarPorEmail($email);
        if ($usuario) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
        }
    }

    private static function limparCookies(): void
    {
        foreach (['access_token', 'id_token', 'refresh_token'] as $cookie) {
            setcookie($cookie, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        }
    }

    private static function idUsuarioLogado(): ?int
    {
        self::iniciarSessao();
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    private static function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private static function responderErro(string $mensagem, string $redirecionarPara): void
    {
        self::iniciarSessao();
        $_SESSION['erro'] = $mensagem;
        self::redirecionar($redirecionarPara);
    }
}
