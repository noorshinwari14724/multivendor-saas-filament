<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'subscription_id',
        'user_id',
        'plan_id',
        'payment_number',
        'description',
        'amount',
        'currency',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_method_details',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'status',
        'paid_at',
        'refunded_at',
        'refunded_amount',
        'failure_reason',
        'billing_name',
        'billing_email',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_total',
        'status_badge',
        'is_paid',
        'is_refunded',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // Calculate total amount
            $payment->total_amount = $payment->amount + $payment->tax_amount - $payment->discount_amount;
        });
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForSubscription($query, $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopePaidBetween($query, $startDate, $endDate)
    {
        return $query->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return $this->formatCurrency($this->amount);
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->formatCurrency($this->total_amount);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'processing' => ['label' => 'Processing', 'color' => 'info'],
            'completed' => ['label' => 'Completed', 'color' => 'success'],
            'failed' => ['label' => 'Failed', 'color' => 'danger'],
            'refunded' => ['label' => 'Refunded', 'color' => 'secondary'],
            'partially_refunded' => ['label' => 'Partially Refunded', 'color' => 'secondary'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger'],
        ];

        return $badges[$this->status] ?? ['label' => ucfirst($this->status), 'color' => 'gray'];
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'completed' && $this->paid_at !== null;
    }

    public function getIsRefundedAttribute(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    // Helper Methods
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'payment',
            'description' => "Payment {$this->payment_number} was marked as paid",
            'event' => 'paid',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'payment',
            'description' => "Payment {$this->payment_number} failed",
            'event' => 'failed',
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'properties' => ['reason' => $reason],
        ]);
    }

    public function refund(float $amount = null): void
    {
        $refundAmount = $amount ?? $this->total_amount;
        $newRefundedAmount = $this->refunded_amount + $refundAmount;

        $status = $newRefundedAmount >= $this->total_amount ? 'refunded' : 'partially_refunded';

        $this->update([
            'status' => $status,
            'refunded_amount' => $newRefundedAmount,
            'refunded_at' => now(),
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'payment',
            'description' => "Payment {$this->payment_number} was refunded",
            'event' => 'refunded',
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'properties' => [
                'refund_amount' => $refundAmount,
                'total_refunded' => $newRefundedAmount,
            ],
        ]);
    }

    public function canBeRefunded(): bool
    {
        return $this->is_paid && $this->refunded_amount < $this->total_amount;
    }

    public function getRemainingRefundableAmount(): float
    {
        if (!$this->canBeRefunded()) {
            return 0;
        }

        return $this->total_amount - $this->refunded_amount;
    }

    private function formatCurrency(float $amount): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => $this->currency . ' ',
        };

        return $symbol . number_format($amount, 2);
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY-';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        
        return $prefix . $date . '-' . $random;
    }

    public static function getTotalRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        $query = self::completed();

        if ($startDate && $endDate) {
            $query->paidBetween($startDate, $endDate);
        }

        return $query->sum('total_amount');
    }

    public static function getMonthlyRevenue(int $months = 12): array
    {
        $results = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $revenue = self::completed()
                ->paidBetween($startOfMonth, $endOfMonth)
                ->sum('total_amount');
            
            $results[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue,
            ];
        }

        return $results;
    }
}
