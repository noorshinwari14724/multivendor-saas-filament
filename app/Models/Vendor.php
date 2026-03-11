<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'email',
        'phone',
        'website',
        'logo',
        'favicon',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'business_type',
        'tax_id',
        'registration_number',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'custom_domain',
        'custom_domain_verified',
        'primary_color',
        'secondary_color',
        'settings',
        'total_users',
        'total_products',
        'total_orders',
        'total_revenue',
        'trial_ends_at',
        'is_trial',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'custom_domain_verified' => 'boolean',
        'is_trial' => 'boolean',
        'settings' => 'json',
        'total_revenue' => 'decimal:2',
    ];

    protected $appends = [
        'logo_url',
        'favicon_url',
        'full_address',
        'is_on_trial',
        'trial_days_left',
        'status_badge',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->name);
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeWithTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeByCustomDomain($query, string $domain)
    {
        return $query->where('custom_domain', $domain)
            ->where('custom_domain_verified', true);
    }

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_users')
            ->withPivot('role', 'permissions', 'status', 'invited_at', 'joined_at', 'department', 'job_title')
            ->withTimestamps();
    }

    public function vendorUsers(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(VendorInvitation::class);
    }

    // Accessors
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? asset('storage/' . $this->favicon) : null;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    public function getIsOnTrialAttribute(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getTrialDaysLeftAttribute(): ?int
    {
        if (!$this->is_on_trial) {
            return null;
        }
        return now()->diffInDays($this->trial_ends_at, false);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'approved' => ['label' => 'Approved', 'color' => 'success'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
            'suspended' => ['label' => 'Suspended', 'color' => 'danger'],
            'inactive' => ['label' => 'Inactive', 'color' => 'gray'],
        ];

        return $badges[$this->status] ?? ['label' => ucfirst($this->status), 'color' => 'gray'];
    }

    // Helper Methods
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function approve(User $approvedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);

        // Create activity log
        ActivityLog::create([
            'vendor_id' => $this->id,
            'user_id' => $approvedBy->id,
            'log_name' => 'vendor',
            'description' => "Vendor '{$this->name}' was approved",
            'event' => 'approved',
            'properties' => ['notes' => $notes],
        ]);
    }

    public function reject(User $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        ActivityLog::create([
            'vendor_id' => $this->id,
            'user_id' => $rejectedBy->id,
            'log_name' => 'vendor',
            'description' => "Vendor '{$this->name}' was rejected",
            'event' => 'rejected',
            'properties' => ['reason' => $reason],
        ]);
    }

    public function suspend(User $suspendedBy, ?string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
        ]);

        ActivityLog::create([
            'vendor_id' => $this->id,
            'user_id' => $suspendedBy->id,
            'log_name' => 'vendor',
            'description' => "Vendor '{$this->name}' was suspended",
            'event' => 'suspended',
            'properties' => ['reason' => $reason],
        ]);
    }

    public function activate(User $activatedBy): void
    {
        $this->update([
            'status' => 'approved',
        ]);

        ActivityLog::create([
            'vendor_id' => $this->id,
            'user_id' => $activatedBy->id,
            'log_name' => 'vendor',
            'description' => "Vendor '{$this->name}' was activated",
            'event' => 'activated',
        ]);
    }

    public function startTrial(int $days = 14): void
    {
        $this->update([
            'is_trial' => true,
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function endTrial(): void
    {
        $this->update([
            'is_trial' => false,
            'trial_ends_at' => null,
        ]);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function canAccessFeature(string $feature): bool
    {
        $subscription = $this->activeSubscription();
        
        if (!$subscription) {
            return false;
        }

        $plan = $subscription->plan;
        
        return match ($feature) {
            'custom_domain' => $plan->has_custom_domain,
            'priority_support' => $plan->has_priority_support,
            'advanced_analytics' => $plan->has_advanced_analytics,
            'white_label' => $plan->has_white_label,
            default => false,
        };
    }

    public function getPlanLimit(string $limit): int
    {
        $subscription = $this->activeSubscription();
        
        if (!$subscription) {
            return 0;
        }

        return match ($limit) {
            'max_users' => $subscription->plan->max_users_per_vendor,
            'max_products' => $subscription->plan->max_products,
            'max_storage' => $subscription->plan->max_storage_mb,
            'max_api_calls' => $subscription->plan->max_api_calls_per_day,
            default => 0,
        };
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_users' => $this->vendorUsers()->where('status', 'active')->count(),
        ]);
    }
}
