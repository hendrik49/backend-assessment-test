<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledRepayment extends Model
{
    use HasFactory;

    public const STATUS_DUE = 'due';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_REPAID = 'repaid';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'due_date',
        'amount',
        'outstanding_amount',
        'currency_code',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'outstanding_amount' => 'decimal:0'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * A Scheduled Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function getDueDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d'); // Format as YYYY-MM-DD
    }
}
