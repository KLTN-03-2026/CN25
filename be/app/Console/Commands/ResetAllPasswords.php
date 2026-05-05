<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAllPasswords extends Command
{
    protected $signature = 'users:reset-password {password=111111}';

    protected $description = 'Reset password for all users';

    public function handle(): int
    {
        $password = $this->argument('password');
        $count = User::count();

        if ($count === 0) {
            $this->info('Khong co user nao trong he thong.');
            return 0;
        }

        User::query()->update(['password' => Hash::make($password)]);

        $this->info("Da doi mat khau thanh '$password' cho $count user.");

        $users = User::select('id', 'name', 'email', 'role')->get();
        $this->table(['ID', 'Name', 'Email', 'Role'], $users->toArray());

        return 0;
    }
}
