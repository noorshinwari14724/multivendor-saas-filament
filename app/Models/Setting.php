<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
        'description',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Cache key prefix
    private const CACHE_PREFIX = 'settings:';

    // Scopes
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Helper Methods
    public static function get(string $key, $default = null, ?string $group = null)
    {
        $cacheKey = self::CACHE_PREFIX . ($group ? $group . '.' : '') . $key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $group, $default) {
            $query = self::where('key', $key);
            
            if ($group) {
                $query->where('group', $group);
            }

            $setting = $query->first();

            if (!$setting) {
                return $default;
            }

            return $setting->castValue();
        });
    }

    public static function set(string $key, $value, ?string $group = null, ?string $type = null): self
    {
        $group = $group ?? 'general';
        
        // Determine type if not provided
        if (!$type) {
            $type = match (gettype($value)) {
                'boolean' => 'boolean',
                'integer' => 'integer',
                'double' => 'float',
                'array' => 'json',
                default => 'string',
            };
        }

        // Convert value to string for storage
        $storedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };

        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $group . '.' . $key);

        return $setting;
    }

    public static function forget(string $key, ?string $group = null): bool
    {
        $group = $group ?? 'general';
        
        $deleted = self::where('group', $group)
            ->where('key', $key)
            ->delete();

        if ($deleted) {
            Cache::forget(self::CACHE_PREFIX . $group . '.' . $key);
        }

        return $deleted;
    }

    public static function allByGroup(?string $group = null): array
    {
        $query = self::query();

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();
        $result = [];

        foreach ($settings as $setting) {
            $key = $setting->group . '.' . $setting->key;
            $result[$key] = $setting->castValue();
        }

        return $result;
    }

    public static function getGroup(string $group): array
    {
        $settings = self::byGroup($group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->castValue();
        }

        return $result;
    }

    public function castValue()
    {
        return match ($this->type) {
            'boolean' => $this->value === '1' || $this->value === 'true',
            'integer' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'json', 'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public static function clearCache(): void
    {
        // Clear all setting caches
        Cache::flush();
    }

    // Predefined Settings Groups
    public static function getGroups(): array
    {
        return [
            'general' => 'General Settings',
            'branding' => 'Branding & Appearance',
            'email' => 'Email Configuration',
            'payment' => 'Payment Settings',
            'security' => 'Security Settings',
            'notifications' => 'Notification Settings',
            'integrations' => 'Third-party Integrations',
            'features' => 'Feature Toggles',
        ];
    }

    // Default Settings
    public static function getDefaultSettings(): array
    {
        return [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'SaaS Platform', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Multi-Vendor SaaS Platform', 'type' => 'string'],
            ['group' => 'general', 'key' => 'default_timezone', 'value' => 'UTC', 'type' => 'string'],
            ['group' => 'general', 'key' => 'default_currency', 'value' => 'USD', 'type' => 'string'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string'],
            ['group' => 'general', 'key' => 'time_format', 'value' => 'H:i', 'type' => 'string'],
            
            // Branding
            ['group' => 'branding', 'key' => 'logo', 'value' => '', 'type' => 'string'],
            ['group' => 'branding', 'key' => 'favicon', 'value' => '', 'type' => 'string'],
            ['group' => 'branding', 'key' => 'primary_color', 'value' => '#3B82F6', 'type' => 'string'],
            ['group' => 'branding', 'key' => 'secondary_color', 'value' => '#10B981', 'type' => 'string'],
            
            // Email
            ['group' => 'email', 'key' => 'from_name', 'value' => 'SaaS Platform', 'type' => 'string'],
            ['group' => 'email', 'key' => 'from_address', 'value' => 'noreply@example.com', 'type' => 'string'],
            ['group' => 'email', 'key' => 'enable_notifications', 'value' => '1', 'type' => 'boolean'],
            
            // Payment
            ['group' => 'payment', 'key' => 'default_currency', 'value' => 'USD', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'tax_rate', 'value' => '0', 'type' => 'float'],
            ['group' => 'payment', 'key' => 'enable_invoicing', 'value' => '1', 'type' => 'boolean'],
            
            // Security
            ['group' => 'security', 'key' => 'require_email_verification', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'two_factor_auth', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => '8', 'type' => 'integer'],
            ['group' => 'security', 'key' => 'session_lifetime', 'value' => '120', 'type' => 'integer'],
            
            // Features
            ['group' => 'features', 'key' => 'enable_registration', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'enable_vendor_registration', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'require_vendor_approval', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'enable_trial', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'trial_days', 'value' => '14', 'type' => 'integer'],
        ];
    }

    public static function installDefaults(): void
    {
        foreach (self::getDefaultSettings() as $setting) {
            self::set($setting['key'], $setting['value'], $setting['group'], $setting['type']);
        }
    }
}
