<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
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

        $this->scheduleRepayments($loan);

        return @$loan;
    }

    protected function scheduleRepayments(Loan $loan)
    {
        $repaymentAmount = $loan->amount / ($loan->term);

        for ($i = 1; $i <= $loan->term; $i++) {
            $repayment = new ScheduledRepayment();
            $repayment->loan_id = $loan->id;
            $repayment->save();
        }
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

        $repayment = Loan::where('id', $loanId)
            ->where('amount', '>', 0)
            ->orderBy('created_at')
            ->first();

        if ($repayment) {
            $repayment->save();
        }

        $this->checkLoanStatus($loan);

        return $repayment;
    }

    protected function checkLoanStatus(Loan $loan)
    {
        $remainingRepayment = Loan::where('id', $loan->id)
            ->where('amount', '>', 0)
            ->count();

        if ($remainingRepayment == 0) {
            // Mark loan as fully repaid or closed
            $loan->status = 'repaid'; // or similar status
            $loan->save();
        }
    }


    public function getLoanDetails($loanId)
    {
        return Loan::with('repayments')->findOrFail($loanId);
    }
}
