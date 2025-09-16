<?php

namespace Database\Seeders\Auth;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = App::environment('local') ? $this->getDevUsers() : [];

        $this->createUsers($users);
    }

    protected function getDevUsers(): array
    {
        return [
            [
                'name' => 'root.dev',
                'email' => 'root.dev@example.com',
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(60),
                'is_password_set' => true,
                'role' => __('Superuser'),
            ],
            [
                'name' => 'admin.dev',
                'email' => 'admin.dev@example.com',
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(60),
                'is_password_set' => true,
                'role' => __('Systems Administrator'),
            ],
        ];
    }

    protected function createUsers(array $users): void
    {
        foreach ($users as $user)
        {
            $createdUser = User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => $user['password'],
                'remember_token' => $user['remember_token'],
                'is_password_set' => $user['is_password_set'],
            ]);

            $createdUser->assignRole($user['role']);
        }
    }
}
