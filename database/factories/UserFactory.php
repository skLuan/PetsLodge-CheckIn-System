<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            // The users table requires phone/address/role (see create_users_table);
            // phone is unique, so keep it generated per-user.
            'phone' => fake()->unique()->numerify('##########'),
            'address' => fake()->streetAddress(),
            'role' => 'CLIENT',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A user who can reach the pet-staff area (`pet.staff.only`).
     */
    public function petStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'PET_STAFF',
        ]);
    }

    /**
     * A super admin — also allowed through `pet.staff.only`.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'SUPER_ADMIN',
        ]);
    }
}
