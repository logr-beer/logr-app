<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create {username?} {--name=}';

    protected $description = 'Create a new user';

    public function handle(): int
    {
        $username = $this->argument('username') ?? $this->ask('Username');

        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' is already taken.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('Name', $username);

        $password = $this->secret('Password');
        $confirm = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($password),
        ]);

        $this->info("User '{$username}' created.");

        return self::SUCCESS;
    }
}
