<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     * Accepts email (required), name (optional, defaults to "Admin User"), password (optional, defaults to "admin123").
     *
     * Example:
     *  php artisan user:create-admin admin@gymin.com "Admin User" admin123
     *
     * @var string
     */
    protected $signature = 'user:create-admin {email} {name=Admin User} {password=admin123}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create (or update) an admin user and print login credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->argument('name');
        $password = (string) $this->argument('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        // Create or update the user
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name ?: ($user->name ?: 'Admin User');
        // Always set password when explicitly provided via command
        $user->password = Hash::make($password ?: 'admin123');
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->is_admin = true;
        $user->save();

        $this->info('Admin user is ready.');
        $this->line("Email: {$user->email}");
        $this->line("Password: {$password}");
        $this->line('You can now log in and access the admin dashboard.');

        return 0;
    }
}
