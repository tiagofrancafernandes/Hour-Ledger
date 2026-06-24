<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Client;
use App\Models\Wallet;
use App\Models\LedgerEntry;

$user = User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ Usuário não encontrado\n";
    exit(1);
}

// Criar ou encontrar cliente
$client = Client::firstOrCreate(
    ['name' => 'Cliente Beta'],
    ['notes' => 'Cliente de teste para validação beta']
);

echo "✅ Cliente encontrado/criado: {$client->name}\n";

// Deletar carteira anterior se existir
Wallet::where('client_id', $client->id)->delete();

// Criar carteira
$wallet = Wallet::create([
    'client_id' => $client->id,
    'name' => 'Carteira Principal',
]);

echo "✅ Carteira criada!\n";
echo "ID: {$wallet->id}\n";
echo "Name: {$wallet->name}\n";

// Criar movimentações de teste (ledger_entries usa 'hours')
echo "\n=== Criando movimentações de teste ===\n";

$entries = [
    [
        'hours' => 10.00,
        'title' => 'Crédito',
        'description' => 'Crédito inicial',
    ],
    [
        'hours' => -2.50,
        'title' => 'Débito',
        'description' => 'Consumo de aula',
    ],
    [
        'hours' => 5.00,
        'title' => 'Bônus',
        'description' => 'Bonus adicional',
    ],
];

foreach ($entries as $entry) {
    LedgerEntry::create([
        'wallet_id' => $wallet->id,
        'hours' => $entry['hours'],
        'title' => $entry['title'],
        'description' => $entry['description'],
        'reference_date' => now()->toDateString(),
        'created_at' => now(),
    ]);

    $sign = $entry['hours'] >= 0 ? '+' : '';
    echo "✅ {$sign}{$entry['hours']}: {$entry['description']}\n";
}

// Calcular saldo
$balance = LedgerEntry::where('wallet_id', $wallet->id)->sum('hours');
echo "\n=== Saldo Final ===\n";
echo "Saldo: " . number_format($balance, 2, ',', '.') . " horas\n";
echo "Esperado: 12,50 horas\n";

if ($balance == 12.50) {
    echo "✅ Saldo correto!\n";
} else {
    echo "⚠️  Saldo incorreto (esperado 12.50, obtido $balance)\n";
}
