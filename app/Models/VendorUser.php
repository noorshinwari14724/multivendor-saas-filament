<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class VendorUser extends Model
{
    use HasFactory;

    protected $table = 'vendor_users';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'role',
        'permissions',
        'status',
        'invited_at',
        'joined_at',
        'department',
        'job_title',
    ];

    protected $casts = [
        'permissions' => 'json',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    protected $appends = [
        'role_label',
        'status_badge',
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

    public function scopeInvited($query)
    {
        return $query->where('status', 'invited');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getRoleLabelAttribute(): string
    {
        $labels = [
            'owner' => 'Owner',
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
        ];

        return $labels[$this->role] ?? ucfirst($this->role);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'active' => ['label' => 'Active', 'color' => 'success'],
            'inactive' => ['label' => 'Inactive', 'color' => 'gray'],
            'invited' => ['label' => 'Invited', 'color' => 'warning'],
            'suspended' => ['label' => 'Suspended', 'color' => 'danger'],
        ];

        return $badges[$this->status] ?? ['label' => ucfirst($this->status), 'color' => 'gray'];
    }

    // Helper Methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager']);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function assignPermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    public static function getAvailableRoles(): array
    {
        return [
            'owner' => 'Owner - Full access to everything',
            'admin' => 'Administrator - Can manage most settings',
            'manager' => 'Manager - Can manage team and content',
            'staff' => 'Staff - Limited access for daily operations',
            'viewer' => 'Viewer - Read-only access',
        ];
    }

    public static function getAvailablePermissions(): array
    {
        return [
            'vendors.view' => 'View Vendor Details',
            'vendors.edit' => 'Edit Vendor Settings',
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.edit' => 'Edit Users',
            'users.delete' => 'Delete Users',
            'products.view' => 'View Products',
            'products.create' => 'Create Products',
            'products.edit' => 'Edit Products',
            'products.delete' => 'Delete Products',
            'orders.view' => 'View Orders',
            'orders.manage' => 'Manage Orders',
            'reports.view' => 'View Reports',
            'settings.view' => 'View Settings',
            'settings.edit' => 'Edit Settings',
            'billing.view' => 'View Billing',
            'billing.manage' => 'Manage Billing',
        ];
    }
}
