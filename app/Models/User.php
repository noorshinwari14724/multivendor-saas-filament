<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'bio',
        'status',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
        'is_super_admin',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    // Relationships
    public function ownedVendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'owner_id');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_users')
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
        return $this->hasMany(VendorInvitation::class, 'invited_by');
    }

    // Accessors
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('super_admin');
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function belongsToVendor(Vendor $vendor): bool
    {
        return $this->vendors()->where('vendor_id', $vendor->id)->exists();
    }

    public function getVendorRole(Vendor $vendor): ?string
    {
        $vendorUser = $this->vendorUsers()->where('vendor_id', $vendor->id)->first();
        return $vendorUser?->role;
    }

    public function hasVendorPermission(Vendor $vendor, string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $vendorUser = $this->vendorUsers()->where('vendor_id', $vendor->id)->first();
        
        if (!$vendorUser || $vendorUser->status !== 'active') {
            return false;
        }

        if ($vendorUser->role === 'owner') {
            return true;
        }

        $permissions = $vendorUser->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
