<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(), // Creates a related Loan record if needed
            'due_date' => $this->faker->dateBetween('+1 month', '+6 months'),
            'amount' => $this->faker->numberBetween(50, 500), // Adjust the range as needed
            'currency_code' => $this->faker->currencyCode(),
            'status' => $this->faker->randomElement(['pending', 'paid']), // Adjust statuses as needed
      
        ];
    }
}
