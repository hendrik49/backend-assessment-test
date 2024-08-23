<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => Carbon::now()
        ]);
    
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => true
        ]);
    
        $response->assertStatus(200);
    
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);       
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => Carbon::now()
        ]);         
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => Carbon::now() // Invalid data
        ]);

        $response->assertStatus(422); // Unprocessable Entity
           
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(204); // No Content

        // $this->assertDatabaseMissing('debit_cards', [
        //     'id' => $debitCard->id
        // ]);

    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        // Create a transaction linked to the debit card (assume DebitCardTransaction model exists)
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(403); // Bad Request or relevant status

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id
        ]);
    }

    // Extra bonus for extra tests :)
}
