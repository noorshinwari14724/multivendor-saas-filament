<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'trial_days',
        'is_active',
        'is_featured',
        'is_default',
        'sort_order',
        'max_vendors',
        'max_users_per_vendor',
        'max_products',
        'max_storage_mb',
        'max_api_calls_per_day',
        'has_custom_domain',
        'has_priority_support',
        'has_advanced_analytics',
        'has_white_label',
        'features',
        'metadata',
        'stripe_price_id',
        'stripe_product_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_default' => 'boolean',
        'trial_days' => 'integer',
        'max_vendors' => 'integer',
        'max_users_per_vendor' => 'integer',
        'max_products' => 'integer',
        'max_storage_mb' => 'integer',
        'max_api_calls_per_day' => 'integer',
        'has_custom_domain' => 'boolean',
        'has_priority_support' => 'boolean',
        'has_advanced_analytics' => 'boolean',
        'has_white_label' => 'boolean',
        'features' => 'json',
        'metadata' => 'json',
    ];

    protected $appends = [
        'formatted_price',
        'price_per_month',
        'is_free',
        'display_features',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });

        static::saving(function ($plan) {
            // Ensure only one default plan
            if ($plan->is_default) {
                static::where('id', '!=', $plan->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->whereIn('status', ['active', 'trialing']);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return 'Free';
        }

        $currency = match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $this->currency . ' ',
        };

        $cycle = match ($this->billing_cycle) {
            'monthly' => '/month',
            'yearly' => '/year',
            'lifetime' => ' (lifetime)',
            default => '',
        };

        return $currency . number_format($this->price, 2) . $cycle;
    }

    public function getPricePerMonthAttribute(): float
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->price;
        }

        if ($this->billing_cycle === 'yearly') {
            return $this->price / 12;
        }

        return 0;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->price == 0;
    }

    public function getDisplayFeaturesAttribute(): array
    {
        $features = $this->features ?? [];
        
        // Add system-generated features
        $systemFeatures = [
            [
                'icon' => 'heroicon-o-users',
                'label' => 'Team Members',
                'value' => $this->max_users_per_vendor === -1 ? 'Unlimited' : $this->max_users_per_vendor,
            ],
            [
                'icon' => 'heroicon-o-cube',
                'label' => 'Products',
                'value' => $this->max_products === -1 ? 'Unlimited' : $this->max_products,
            ],
            [
                'icon' => 'heroicon-o-server',
                'label' => 'Storage',
                'value' => $this->max_storage_mb === -1 ? 'Unlimited' : $this->max_storage_mb . ' MB',
            ],
            [
                'icon' => 'heroicon-o-bolt',
                'label' => 'API Calls',
                'value' => $this->max_api_calls_per_day === -1 ? 'Unlimited' : number_format($this->max_api_calls_per_day) . '/day',
            ],
        ];

        if ($this->has_custom_domain) {
            $systemFeatures[] = [
                'icon' => 'heroicon-o-globe-alt',
                'label' => 'Custom Domain',
                'value' => 'Included',
            ];
        }

        if ($this->has_priority_support) {
            $systemFeatures[] = [
                'icon' => 'heroicon-o-headphones',
                'label' => 'Priority Support',
                'value' => 'Included',
            ];
        }

        if ($this->has_advanced_analytics) {
            $systemFeatures[] = [
                'icon' => 'heroicon-o-chart-bar',
                'label' => 'Advanced Analytics',
                'value' => 'Included',
            ];
        }

        if ($this->has_white_label) {
            $systemFeatures[] = [
                'icon' => 'heroicon-o-paint-brush',
                'label' => 'White Label',
                'value' => 'Included',
            ];
        }

        return array_merge($systemFeatures, $features);
    }

    // Helper Methods
    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function getTrialDays(): int
    {
        return $this->trial_days;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripe_price_id;
    }

    public function setAsDefault(): void
    {
        // Remove default from other plans
        static::where('is_default', true)->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getFeatureList(): array
    {
        return [
            'max_vendors' => $this->max_vendors,
            'max_users_per_vendor' => $this->max_users_per_vendor,
            'max_products' => $this->max_products,
            'max_storage_mb' => $this->max_storage_mb,
            'max_api_calls_per_day' => $this->max_api_calls_per_day,
            'has_custom_domain' => $this->has_custom_domain,
            'has_priority_support' => $this->has_priority_support,
            'has_advanced_analytics' => $this->has_advanced_analytics,
            'has_white_label' => $this->has_white_label,
        ];
    }

    public function exceedsLimits(Plan $otherPlan): bool
    {
        return $this->max_users_per_vendor > $otherPlan->max_users_per_vendor ||
               $this->max_products > $otherPlan->max_products ||
               $this->max_storage_mb > $otherPlan->max_storage_mb;
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function getActivePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->ordered()->get();
    }
}
