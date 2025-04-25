<?php

namespace App\Models;

use App\Repositories\Money\Money;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Integer amount
 */
class Payment extends Model
{
    use  SoftDeletes;

    protected $fillable = [
        'external_id',
        'description',
        'amount',
        'payment_source',
        'payment_date',
        'integration_payment_id',
        'integration_type',
        'invoice_id',
    ];

    protected $dates = ['payment_date'];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'external_id';
    }

    public function getPriceAttribute()
    {
        return app(Money::class, ['amount' => $this->amount]);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Check if payment amount is greater than remaining amount
     *
     * @return bool
     */
    public function isAmountGreaterThanRemaining()
    {
        $remainingAmount = $this->invoice->total - $this->invoice->payments->where('id', '!=', $this->id)->sum('amount');
        return $this->amount > $remainingAmount;
    }

    /**
     * Get the remaining amount after this payment
     *
     * @return float
     */
    public function getRemainingAmount()
    {
        return $this->invoice->total - $this->invoice->payments->sum('amount');
    }
}
