<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Helpers\PasswordHelper;

class RehashUserPassword extends Command
{
    protected $signature = 'auth:rehash-password 
                            {email : User email address}
                            {password : Plain-text password that should remain valid}';

    protected $description = 'Rehash an existing user password using the latest hashing scheme';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        /** @var User|null $user */
        $user = User::where('user_email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return self::FAILURE;
        }

        $user->user_pass = PasswordHelper::generatePassword($user->user_salt, $password);
        $user->save();

        $this->info("Password for {$email} has been rehashed successfully.");
        $this->comment('Any appended characters will now be respected by bcrypt because the pre-hash input is fixed at 64 bytes.');

        return self::SUCCESS;
    }
}

