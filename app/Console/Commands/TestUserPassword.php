<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Helpers\PasswordHelper;

class TestUserPassword extends Command
{
    protected $signature = 'auth:test-password 
                            {email : User email address}
                            {password : Plain-text password to verify}
                            {--upgrade : Rehash the password automatically when the legacy hash matches}';

    protected $description = 'Verify a user password against the current hashing rules';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $upgrade = $this->option('upgrade');

        /** @var User|null $user */
        $user = User::where('user_email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return self::FAILURE;
        }

        $legacyUpgraded = false;
        $legacyUpgradeCallback = null;

        if ($upgrade) {
            $legacyUpgradeCallback = function () use (&$legacyUpgraded, $user, $password) {
                $user->user_pass = PasswordHelper::generatePassword($user->user_salt, $password);
                $user->save();
                $legacyUpgraded = true;
            };
        }

        $isValid = PasswordHelper::verifyPassword(
            $password,
            $user->user_salt,
            $user->user_pass,
            $legacyUpgradeCallback
        );

        $this->line('');
        $this->info('=== Password Verification Result ===');
        $this->line('Email: ' . $email);
        $this->line('Password length: ' . strlen($password));
        $this->line('Salt length: ' . strlen($user->user_salt));
        $this->line('Hash starts with: ' . substr($user->user_pass, 0, 20) . '...');
        $this->line('');

        if ($isValid) {
            $this->info('✓ Password is valid');

            if ($legacyUpgraded) {
                $this->warn('Legacy hash detected. User password has been rehashed using the new scheme.');
            } elseif (!$upgrade) {
                $this->comment('Hint: pass --upgrade to automatically rehash matching legacy passwords.');
            }
            return self::SUCCESS;
        }

        $this->error('✗ Password is invalid');
        return self::FAILURE;
    }
}

