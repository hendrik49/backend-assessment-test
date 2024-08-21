<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;

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
        $loan = new Loan();
        $loan->customer_id = $user->id;
        $loan->amount = $amount;
        $loan->terms = $terms;
        $loan->currency_code = $currencyCode; 
        $loan->processed_at = $processedAt;
        $loan->status = Loan::STATUS_DUE;
        $loan->save();
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
        $loan->amount -= $repaymentAmount;
        $loan->save();

        $repayment = new ReceivedRepayment();
        $repayment->loan_id = $loanId;
        $repayment->save();

        return $repayment;
    }
}
