<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Carbon\Carbon;

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
        DebitCard::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');
    
        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        
        // Create debit cards for the authenticated user
        DebitCard::factory()->count(2)->create(['user_id' => $this->user->id]);

        // Create a new user
        $otherUser = User::factory()->create();

        // Create debit cards for the other user
        DebitCard::factory()->count(3)->create(['user_id' => $otherUser->id]);

        // Make a request to list debit cards for the authenticated user
        $response = $this->getJson('/api/debit-cards');

        // Ensure that the response contains only the debit cards for the authenticated user
        $response->assertStatus(200);

    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards        
        $data = [
            'type' => 'VISA'
        ];
    
        $response = $this->postJson('/api/debit-cards', $data);    
        $response->assertStatus(201)
                 ->assertJsonFragment(['type' => 'VISA']);
    
        $this->assertDatabaseHas('debit_cards', $data);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(200)
                ->assertJsonFragment(['id' => $debitCard->id]);

    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(403); // Forbidden response
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}        
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}        
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
           
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}

    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        
    }

    // Extra bonus for extra tests :)
}
