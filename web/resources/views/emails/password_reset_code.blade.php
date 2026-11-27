<div style="font-family: Arial, Helvetica, sans-serif; max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
    <div style="padding: 20px 24px;">
        <h2 style="margin: 0; font-size: 18px; color: #072c5e;">{{ $appName }}</h2>
        @if (! empty($companyName))
            <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">{{ $companyName }}</p>
        @endif
    </div>
    <div style="padding: 24px; color: #1e293b;">
        <p style="margin: 0 0 12px;">Olá, <strong>{{ $userName }}</strong>!</p>
        <p style="margin: 0 0 18px; line-height: 1.5; font-size: 14px; color: #475569;">
            Recebemos uma solicitação para redefinir a senha da sua conta. Utilize o código de 6 dígitos abaixo:
        </p>
        <div style="text-align: center; margin: 22px 0;">
            <span style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #dd4814; background: #fff7ed; border: 1px solid #fed7aa; padding: 14px 26px; border-radius: 8px;">{{ $code }}</span>
        </div>
        <p style="margin: 16px 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
            Este código é válido por <strong>1 minuto e 30 segundos</strong>. Caso você não tenha solicitado a alteração de senha, ignore este e-mail.
        </p>
    </div>
</div>
