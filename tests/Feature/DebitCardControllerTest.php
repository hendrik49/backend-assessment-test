<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards

        $user = User::factory()->create();

        // Create debit cards for the authenticated user
        DebitCard::factory()->count(3)->create(['user_id' => $user->id]);

        // Create debit cards for another user (should not be visible to the first user)
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Authenticate as the first user
        $response = $this->actingAs($user)->getJson('/debit-cards');

        // Assert that the response is successful
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data') // Ensure only 3 debit cards are returned
                ->assertJson([
                    'data' => [
                        '*' => [
                            'user_id' => $user->id,
                        ],
                    ],
                ]); // Ensure all returned debit cards belong to the authenticated user

    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        // Create a user
        $user = User::factory()->create();

        // Create debit cards for the authenticated user
        DebitCard::factory()->count(3)->create(['user_id' => $user->id]);

        // Create debit cards for another user
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Authenticate as the first user
        $response = $this->actingAs($user)->getJson('/debit-cards');

        // Assert that the response is successful
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data') // Ensure only 3 debit cards are returned
                ->assertJsonMissing([
                    'data' => [
                        '*' => [
                            'user_id' => $otherUser->id,
                        ],
                    ],
                ]); 
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $user = User::factory()->create();
        $data = [
            'type' => 'VISA',
            'number' => '1234567890123456',
            'expiration_date' => Carbon::now()->addYear()
        ];

        $response = $this->actingAs($user)->postJson('/debit-cards', $data);

        $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'type' => '',
                        'number' => '1234567890123456',
                        'expiration_date' => Carbon::now()->addYear()
                    ],
                ]);

        $this->assertDatabaseHas('debit_cards', [
            'type' => 'VISA',
            'number' => '1234567890123456',
            'expiration_date' => Carbon::now()->addYear(),
            'user_id' => $user->id,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
             ->assertJson([
                 'data' => [
                     'type' => $debitCard->type,
                     'number' => $debitCard->number,
                     'expiration_date' => $debitCard->expiration_date,
                 ],
             ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson("/debit-cards/{$debitCard->id}");

        $response->assertStatus(403); // Assuming the policy returns a 403 Forbidden response

    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $response = $this->actingAs($user)->putJson("/debit-cards/{$debitCard->id}", [
            'disabled_at' =>  null,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'disabled_at' => null,
                    ],
                ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' =>  null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $response = $this->actingAs($user)->putJson("/debit-cards/{$debitCard->id}", [
            'disabled_at' =>  null,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'disabled_at' => null,
                    ],
                ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' =>  null,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/debit-cards/{$debitCard->id}", [
            'number' => 'invalid-card-number', // Invalid card number
        ]);

        $response->assertStatus(422) // Expect validation error
                ->assertJsonValidationErrors('number');
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);
    
        $response = $this->actingAs($user)->deleteJson("/debit-cards/{$debitCard->id}");
    
        $response->assertStatus(204); // No content on successful deletion
    
        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);
        // Add a transaction to the debit card
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->actingAs($user)->deleteJson("/debit-cards/{$debitCard->id}");

        $response->assertStatus(422) // Expect a validation error or custom error for constraints
                ->assertJson([
                    'message' => 'Cannot delete debit card with existing transactions',
                ]);
    }

    // Extra bonus for extra tests :)
}
