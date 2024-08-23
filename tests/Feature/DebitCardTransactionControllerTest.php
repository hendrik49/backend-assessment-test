<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        // Create some transactions for the debit card
        $transaction1 = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => 'IDR',
        ]);

        $transaction2 = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 200,
            'currency_code' => 'THB',
        ]);

        // Make a GET request to retrieve the list of debit card transactions
        $response = $this->json('GET', '/api/debit-card-transactions/', [
            'debit_card_id' => $this->debitCard->id
        ]);

        // Assert that the response status is OK
        $response->assertStatus(200);
        
        // Assert that the response contains the transactions
        $response->assertJsonFragment([
            'amount' => $transaction1->amount,
            'currency_code' => $transaction1->currency_code,
        ]);

        $response->assertJsonFragment([
            'amount' => $transaction2->amount,
            'currency_code' => $transaction2->currency_code,
        ]);

        // Optionally assert the count of transactions if needed
        //$response->assertJsonCount(2, 'data'); // Adjust the path if your response format is different
    
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
