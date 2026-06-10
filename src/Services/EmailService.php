<?php

declare(strict_types=1);

namespace SalveAlimento\Services;

use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    public static function enviarVerificacao(string $destinatario, string $nome, string $token): bool
    {
        $link = ($_ENV['APP_URL'] ?? '') . '/verificar-email?token=' . urlencode($token);

        return self::enviar(
            $destinatario,
            $nome,
            'Confirme seu e-mail — Salve Alimento',
            self::corpoVerificacao($nome, $link)
        );
    }

    public static function enviarRecuperacaoSenha(string $destinatario, string $nome, string $token): bool
    {
        $link = ($_ENV['APP_URL'] ?? '') . '/recuperar-senha?token=' . urlencode($token);

        return self::enviar(
            $destinatario,
            $nome,
            'Redefinição de senha — Salve Alimento',
            self::corpoRecuperacao($nome, $link)
        );
    }

    public static function enviarAlertaBloqueio(string $destinatario, string $nome): bool
    {
        return self::enviar(
            $destinatario,
            $nome,
            'Alerta de segurança: conta bloqueada temporariamente',
            self::corpoBloqueio($nome)
        );
    }

    private static function enviar(string $para, string $nomePara, string $assunto, string $corpo): bool
    {
        if (empty($_ENV['MAIL_HOST'])) {
            error_log("[EmailService] SMTP não configurado — e-mail não enviado para {$para}");
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USER'] ?? '';
            $mail->Password   = $_ENV['MAIL_PASS'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $_ENV['MAIL_FROM']      ?? 'noreply@salvealimento.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Salve Alimento'
            );
            $mail->addAddress($para, $nomePara);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $corpo;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('[EmailService] Falha ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    private static function corpoVerificacao(string $nome, string $link): string
    {
        return "<p>Olá, <strong>{$nome}</strong>!</p>
<p>Clique no botão abaixo para confirmar seu endereço de e-mail:</p>
<p><a href='{$link}' style='background:#2d6a4f;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none'>Confirmar e-mail</a></p>
<p>O link expira em 24 horas. Se não foi você, ignore este e-mail.</p>
<p>Equipe Salve Alimento</p>";
    }

    private static function corpoRecuperacao(string $nome, string $link): string
    {
        return "<p>Olá, <strong>{$nome}</strong>!</p>
<p>Recebemos uma solicitação para redefinir sua senha. Clique no botão abaixo:</p>
<p><a href='{$link}' style='background:#2d6a4f;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none'>Redefinir senha</a></p>
<p>O link expira em <strong>1 hora</strong>. Se não foi você, ignore este e-mail com segurança.</p>
<p>Equipe Salve Alimento</p>";
    }

    private static function corpoBloqueio(string $nome): string
    {
        return "<p>Olá, <strong>{$nome}</strong>!</p>
<p>Detectamos várias tentativas de login malsucedidas na sua conta.</p>
<p>Como medida de segurança, sua conta foi <strong>bloqueada temporariamente por 15 minutos</strong>.</p>
<p>Após esse período, você poderá tentar novamente normalmente.</p>
<p>Se não foi você, recomendamos alterar sua senha assim que possível.</p>
<p>Equipe Salve Alimento</p>";
    }
}
