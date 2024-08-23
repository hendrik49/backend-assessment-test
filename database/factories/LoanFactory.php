<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Creates a new user or use an existing user ID
            'amount' => $this->faker->numberBetween(1000, 10000),
            'terms' => json_encode([
                'monthly' => $this->faker->numberBetween(100, 500)
            ]),
            'outstanding_amount' => $this->faker->numberBetween(1000, 10000),
            'currency_code' => $this->faker->currencyCode,
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'status' => $this->faker->randomElement(['due', 'repaid']),
        ];
    }
}
