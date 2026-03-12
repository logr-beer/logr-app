<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {username?}';

    protected $description = 'Reset a user\'s password';

    public function handle(): int
    {
        $username = $this->argument('username') ?? $this->ask('Username');

        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->error("User '{$username}' not found.");

            return self::FAILURE;
        }

        $password = $this->secret('New password');
        $confirm = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password reset for user '{$username}'.");

        return self::SUCCESS;
    }
}
