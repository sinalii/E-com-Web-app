<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create', function () {
    $name = trim($this->ask('Administrator name'));
    $email = Str::lower(trim($this->ask('Administrator email')));

    if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('A name and valid email address are required.');
        return 1;
    }

    if (User::where('email', $email)->exists()) {
        $this->error('That email address already belongs to an account.');
        return 1;
    }

    $password = $this->secret('Password (at least 8 characters, including letters and numbers)');
    $confirmation = $this->secret('Confirm password');
    if (strlen($password) < 8 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password) || ! hash_equals($password, $confirmation)) {
        $this->error('The password confirmation failed or the password does not meet the requirements.');
        return 1;
    }

    User::forceCreate([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => User::ROLE_ADMIN,
        'is_admin' => true,
    ]);

    $this->info('Administrator account created.');
    return 0;
})->purpose('Create an administrator account from the command line');
