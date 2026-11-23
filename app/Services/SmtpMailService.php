<?php

namespace App\Services;

use App\Models\SmtpConfig;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class SmtpMailService
{
    /**
     * Envia um e-mail usando a configuração SMTP salva pelo Administrador na tabela `config_smtp`.
     *
     * @return array{success: bool, error: ?string}
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        try {
            $smtp = SmtpConfig::first();

            if ($smtp === null) {
                return [
                    'success' => false,
                    'error'   => 'O servidor SMTP ainda não foi configurado pelo administrador em Configurações > Servidor SMTP.',
                ];
            }

            if ((int) ($smtp->status ?? 0) !== 1) {
                return [
                    'success' => false,
                    'error'   => 'O envio de e-mails está desativado nas configurações de SMTP do sistema.',
                ];
            }

            $host       = trim((string) $smtp->smtp_host);
            $port       = (int) ($smtp->smtp_port ?: 587);
            $encryption = strtoupper(trim((string) ($smtp->smtp_encryption ?: 'STARTTLS')));
            $username   = trim((string) $smtp->smtp_username);
            $password   = (string) $smtp->smtp_password;
            $fromEmail  = trim((string) $smtp->email_address);
            $fromName   = trim((string) ($smtp->email_name ?: 'Sistema Patrimonial'));
            $replyTo    = trim((string) ($smtp->reply_to_email ?? ''));
            $useAuth    = ((int) ($smtp->smtp_auth ?? 1) === 1);
            $verifySsl  = ((int) ($smtp->smtp_verify_ssl ?? 1) === 1);
            $timeout    = max(5, (int) ($smtp->smtp_timeout ?: 30));

            if ($host === '' || $fromEmail === '') {
                return [
                    'success' => false,
                    'error'   => 'Configuração SMTP incompleta (verifique Host e E-mail do Remetente em Configurações).',
                ];
            }

            // SSL implícito (porta 465 / SSL) usa tls=true; STARTTLS usa tls=false (negociado automaticamente pelo EsmtpTransport)
            $useImplicitTls = ($encryption === 'SSL' || $port === 465);

            $transport = new EsmtpTransport($host, $port, $useImplicitTls);

            if ($encryption === 'NONE') {
                $transport->setAutoTls(false);
            }

            $stream = $transport->getStream();
            if (method_exists($stream, 'setTimeout')) {
                $stream->setTimeout($timeout);
            }
            if (! $verifySsl && method_exists($stream, 'setStreamOptions')) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ]);
            }

            // Se houver usuário e senha cadastrados, envia autenticação automaticamente (evita erro 530 no Gmail)
            if (($useAuth || ($username !== '' && $password !== '')) && $username !== '') {
                $transport->setUsername($username);
                $transport->setPassword($password);
            }

            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from(new Address($fromEmail, $fromName))
                ->to(new Address($toEmail, $toName !== '' ? $toName : $toEmail))
                ->subject($subject)
                ->html($htmlBody)
                ->text($textBody ?? strip_tags($htmlBody));

            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $email->replyTo(new Address($replyTo));
            }

            $mailer->send($email);

            return [
                'success' => true,
                'error'   => null,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error'   => 'Falha ao enviar e-mail via SMTP: ' . $e->getMessage(),
            ];
        }
    }
}
