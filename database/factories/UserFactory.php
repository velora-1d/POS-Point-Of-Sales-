<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        $outlet = \App\Models\Outlet::first() ?? \App\Models\Outlet::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Outlet',
        ]);

        $role = \App\Models\Role::first() ?? \App\Models\Role::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $outlet->id,
            'name' => 'Owner',
            'type' => 'owner',
        ]);

        return [
            'id' => (string) Str::uuid(),
            'outlet_id' => $outlet->id,
            'role_id' => $role->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '0812' . fake()->numerify('########'),
            'password_hash' => static::$password ??= Hash::make('password'),
            'approval_pin' => Hash::make('123456'),
            'is_active' => true,
            'join_date' => now(),
        ];
    }
}
