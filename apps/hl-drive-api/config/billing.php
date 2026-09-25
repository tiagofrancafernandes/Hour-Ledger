<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Grace Period Days
    |--------------------------------------------------------------------------
    |
    | Number of days allowed for an instructor to continue using the system
    | after their subscription renewal date has passed, before the account
    | drops into read-only mode.
    |
    */
    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    |
    | Default plan assigned to new instructors when registering.
    |
    */
    'default_plan_slug' => env('BILLING_DEFAULT_PLAN_SLUG', 'instrutor-autonomo-mensal'),

    /*
    |--------------------------------------------------------------------------
    | Payment Receiver Details (PIX & PicPay)
    |--------------------------------------------------------------------------
    |
    | Details shown to instructors for direct online or offline payments.
    |
    */
    'pix' => [
        'key' => env('BILLING_PIX_KEY', 'financeiro@hourledger.com'),
        'key_type' => env('BILLING_PIX_KEY_TYPE', 'email'),
        'receiver_name' => env('BILLING_PIX_RECEIVER_NAME', 'Hour Ledger Tecnologia Ltda'),
        'city' => env('BILLING_PIX_CITY', 'SAO PAULO'),
    ],

    'picpay' => [
        'username' => env('BILLING_PICPAY_USER', '@hourledger'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification and Banner Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'grace_period' => 'Identificamos uma pendência no pagamento do seu plano. Regularize seus dados de pagamento até :deadline para evitar a suspensão dos serviços.',
        'extended' => 'O prazo do seu plano foi prorrogado pelo administrador até :deadline. Regularize seu pagamento.',
        'suspended' => 'Seu plano está suspenso por falta de pagamento. A conta está em modo somente leitura até a regularização.',
    ],
];
