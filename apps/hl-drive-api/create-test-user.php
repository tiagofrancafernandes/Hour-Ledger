<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Deletar usuário de teste se existir
User::where('email', 'test@example.com')->delete();

// Criar novo usuário
$user = User::create([
    'name' => 'Beta Tester',
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
    'email_verified_at' => now()
]);

echo "✅ Usuário criado com sucesso!\n";
echo "Email: {$user->email}\n";
echo "Senha: password123\n";
echo "ID: {$user->id}\n";
