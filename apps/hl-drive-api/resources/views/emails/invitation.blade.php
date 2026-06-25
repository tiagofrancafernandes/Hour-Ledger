@component('mail::message')
# Convite para Vincular com Instrutor

Olá{{ $studentName ? ', ' . $studentName : '' }}!

Você foi convidado pelo instrutor **{{ $instructorName }}** para vincular sua conta na plataforma **{{ $tenantName }}**.

Ao aceitar este convite, você poderá acessar os recursos, agendamentos e dados compartilhados pelo seu instrutor.

## Como Aceitar o Convite

Clique no botão abaixo para aceitar o convite:

@component('mail::button', ['url' => $acceptLink, 'color' => 'primary'])
Aceitar Convite
@endcomponent

Ou copie e cole o seguinte código de verificação no aplicativo:

@component('mail::panel')
**Código: {{ substr($token, 0, 12) }}...**
@endcomponent

## Informações do Convite

- **Instrutor**: {{ $instructorName }}
- **Plataforma**: {{ $tenantName }}
- **Seu Email**: {{ $recipientEmail }}
- **Válido por**: 7 dias

Se você não solicitou este convite ou tem alguma dúvida, você pode simplesmente ignorar este email ou entrar em contato com o suporte.

---

O convite expirará em 7 dias. Após expirar, será necessário solicitar um novo convite.

Atenciosamente,<br>
**{{ config('application.mail.footer_name') }}**

@endcomponent
