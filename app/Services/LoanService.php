<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        //
        // Validate the input data
        $validator = Validator::make([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'processed_at' => $processedAt,
            'status' => 'due', // Assuming 'due' is the initial status
        ], [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'terms' => 'required|integer|min:1',
            'processed_at' => 'nullable|date',
            'status' => 'required|string|in:due,repaid',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create and return the loan
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount, // Assuming outstanding amount equals the initial amount
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => 'due', // Setting initial status to 'due'
        ]);

        $this->scheduleRepayments($loan);

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        //
          // Validate repayment details
        $validator = Validator::make([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ], [
            'amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'received_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check if the loan matches the currency code
        if ($loan->currency_code !== $currencyCode) {
            throw new \Exception('Currency code mismatch');
        }

        // Check if the repayment amount is valid
        if ($amount <= 0) {
            throw new \Exception('Repayment amount must be greater than zero');
        }

        //Update ScheduledRepayment
        $repay = ScheduledRepayment::where('loan_id', $loan->id)->where('due_date', $receivedAt)->first();
        
        $repay->update([
            'outstanding_amount' => 0,
            'status' => 'repaid'
        ]);

        $amount2 = ScheduledRepayment::where('loan_id', $loan->id)->where('status','repaid')->sum('amount');

        // Calculate new outstanding amount
        $newOutstandingAmount = $loan->amount - $amount2;

        // Update loan status if fully repaid
        $status = $newOutstandingAmount <= 0 ? 'repaid' : 'due';

        // Update loan
        $loan->update([
            'outstanding_amount' => max($newOutstandingAmount, 0),
            'status' => $status
        ]);
        
        // Record the repayment
       return ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
            'status' => ScheduledRepayment::STATUS_REPAID
        ]);
    }

    public function scheduleRepayments(Loan $loan): void
    {
        $now = Carbon::createFromFormat('Y-m-d',$loan->processed_at);
        $repaymentDates = [];

        for ($i = 1; $i <= $loan->terms; $i++) {
            $repaymentDates[] = $now->copy()->addMonths($i)->toDateString();
        }

        foreach ($repaymentDates as $key => $date) {
            // Assuming you have a ScheduledRepayment model
            $amount = intval($loan->amount / $loan->terms);
            $sumamount = $amount + $amount;
            $key = $key + 1;

            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'due_date' => $date,
                'amount' => $key ==  $loan->terms? ($loan->amount - $sumamount) : $amount,
                'outstanding_amount' => $key ==  $loan->terms? ($loan->amount - $sumamount)  : $amount,
                'currency_code' => $loan->currency_code,
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
        }
    }
}