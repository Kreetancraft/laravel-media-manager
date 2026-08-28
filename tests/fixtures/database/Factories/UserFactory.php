<?php

namespace Kreetancraft\Media\Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kreetancraft\Media\Tests\Fixtures\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => 'Test User '.Str::random(6),
            'email' => Str::random(10).'@example.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];
    }
}
