<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed admin users
        $this->call(AdminUserSeeder::class);

        // Seed tenants
        $this->call(TenantSeeder::class);

        // Seed instructor and student users
        $this->call(InstructorStudentUserSeeder::class);

        // Seed invitations and links
        $this->call(InvitationAndLinkSeeder::class);

        $this->call(DevDummyDataSeeder::class);
    }
}
