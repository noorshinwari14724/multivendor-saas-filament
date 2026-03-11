<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'plan_id',
        'user_id',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'amount',
        'currency',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'cancel_at',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancel_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'metadata' => 'json',
    ];

    protected $appends = [
        'is_active',
        'is_trial',
        'is_canceled',
        'is_past_due',
        'days_until_renewal',
        'formatted_amount',
        'status_badge',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereIn('status', ['incomplete', 'incomplete_expired']);
    }

    public function scopeValid($query)
    {
        return $query->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereIn('status', ['active', 'trialing'])
            ->where('current_period_end', '<=', now()->addDays($days))
            ->where('current_period_end', '>', now());
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['active', 'trialing']) &&
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function getIsTrialAttribute(): bool
    {
        return $this->status === 'trialing' || 
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function getIsCanceledAttribute(): bool
    {
        return $this->canceled_at !== null || $this->status === 'canceled';
    }

    public function getIsPastDueAttribute(): bool
    {
        return $this->status === 'past_due';
    }

    public function getDaysUntilRenewalAttribute(): ?int
    {
        if (!$this->current_period_end) {
            return null;
        }
        return now()->diffInDays($this->current_period_end, false);
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $this->currency . ' ',
        };

        return $currency . number_format($this->amount, 2);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'active' => ['label' => 'Active', 'color' => 'success'],
            'trialing' => ['label' => 'Trial', 'color' => 'info'],
            'past_due' => ['label' => 'Past Due', 'color' => 'warning'],
            'canceled' => ['label' => 'Canceled', 'color' => 'danger'],
            'unpaid' => ['label' => 'Unpaid', 'color' => 'danger'],
            'incomplete' => ['label' => 'Incomplete', 'color' => 'warning'],
            'incomplete_expired' => ['label' => 'Expired', 'color' => 'danger'],
        ];

        return $badges[$this->status] ?? ['label' => ucfirst($this->status), 'color' => 'gray'];
    }

    // Helper Methods
    public function cancel(bool $atPeriodEnd = true): void
    {
        if ($atPeriodEnd) {
            $this->update([
                'cancel_at_period_end' => true,
                'cancel_at' => $this->current_period_end,
            ]);
        } else {
            $this->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'ends_at' => now(),
            ]);
        }

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription was canceled",
            'event' => 'canceled',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'cancel_at_period_end' => false,
            'cancel_at' => null,
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription was resumed",
            'event' => 'resumed',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function changePlan(Plan $newPlan): void
    {
        $oldPlan = $this->plan;

        $this->update([
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->price,
            'billing_cycle' => $newPlan->billing_cycle,
            'stripe_price' => $newPlan->stripe_price_id,
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription plan changed from '{$oldPlan->name}' to '{$newPlan->name}'",
            'event' => 'plan_changed',
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'properties' => [
                'old_plan_id' => $oldPlan->id,
                'new_plan_id' => $newPlan->id,
            ],
        ]);
    }

    public function renew(): void
    {
        $periodLength = match ($this->billing_cycle) {
            'monthly' => 1,
            'yearly' => 12,
            default => 1,
        };

        $this->update([
            'current_period_start' => now(),
            'current_period_end' => now()->addMonths($periodLength),
            'status' => 'active',
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription was renewed",
            'event' => 'renewed',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function markAsPastDue(): void
    {
        $this->update(['status' => 'past_due']);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription marked as past due",
            'event' => 'past_due',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'active']);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'subscription',
            'description' => "Subscription marked as paid",
            'event' => 'paid',
            'subject_type' => self::class,
            'subject_id' => $this->id,
        ]);
    }

    public function onTrial(): bool
    {
        return $this->is_trial;
    }

    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getRemainingTrialDays(): ?int
    {
        if (!$this->onTrial() || !$this->trial_ends_at) {
            return null;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }

    public function scopeForStripeId($query, string $stripeId)
    {
        return $query->where('stripe_id', $stripeId);
    }

    public static function findByStripeId(string $stripeId): ?self
    {
        return static::where('stripe_id', $stripeId)->first();
    }
}
