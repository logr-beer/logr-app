<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {username}';

    protected $description = 'Give an existing user admin access';

    public function handle(): int
    {
        $username = $this->argument('username');
        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->error("User '{$username}' not found.");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("User '{$username}' is now an admin.");

        return self::SUCCESS;
    }
}
