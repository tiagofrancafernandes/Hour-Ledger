<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Deletar usuário admin de teste se existir
User::where('email', 'admin@example.com')->delete();

// Criar novo usuário admin
$admin = User::create([
    'name' => 'Admin Tester',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
    'email_verified_at' => now()
]);

echo "✅ Usuário admin criado com sucesso!\n";
echo "Email: {$admin->email}\n";
echo "Senha: password123\n";
echo "ID: {$admin->id}\n";

// Tentar criar/dar role admin
try {
    // Criar role admin se não existir
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

    // Atribuir role ao usuário
    $admin->assignRole('admin');

    echo "\n✅ Role 'admin' atribuído ao usuário!\n";
    echo "Roles: " . implode(', ', $admin->getRoleNames()->toArray()) . "\n";
} catch (\Exception $e) {
    echo "\n⚠️  Erro ao atribuir role: " . $e->getMessage() . "\n";
    echo "   Isso pode ser esperado se as policies não usam roles\n";
}

// Dar permissões comuns
try {
    $permissions = [
        'view-wallet',
        'view-wallets',
        'view-entries',
        'view-clients',
        'view-reports'
    ];

    foreach ($permissions as $permission) {
        $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        $admin->givePermissionTo($perm);
    }

    echo "\n✅ Permissões atribuídas:\n";
    echo "   - " . implode("\n   - ", $permissions) . "\n";
} catch (\Exception $e) {
    echo "\n⚠️  Erro ao atribuir permissões: " . $e->getMessage() . "\n";
}

echo "\n=== Resumo ===\n";
echo "Usuario: admin@example.com\n";
echo "Senha: password123\n";
echo "Tipo: Admin (com permissões)\n";
echo "\nUse este usuário para testes com acesso a wallet e relatórios.\n";
