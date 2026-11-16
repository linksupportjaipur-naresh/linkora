<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first administrator from ADMIN_EMAIL / ADMIN_PASSWORD (.env → config/linkora.php).
     * Locally the password falls back to a development default; in production a real
     * ADMIN_PASSWORD is required so the site never ships with a known password.
     */
    public function run()
    {
        $email = config('linkora.admin.email');
        $password = config('linkora.admin.password');

        if (! $password) {
            if (app()->environment('production')) {
                throw new RuntimeException('Set ADMIN_PASSWORD in .env before seeding in production.');
            }
            $password = 'Admin@12345'; // local development only
        }

        $admin = User::firstOrNew(['email' => $email]);

        if (! $admin->exists) {
            $admin->fill([
                'name'     => 'Linkora Admin',
                'username' => 'linkora-admin',
                'password' => Hash::make($password),
            ]);
        }

        $admin->forceFill([
            'role'              => User::ROLE_ADMIN,
            'password_set_at'   => $admin->password_set_at ?? now(),
            'status'            => User::STATUS_ACTIVE,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();
    }
}
