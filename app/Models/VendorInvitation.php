<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VendorInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'invited_by',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
        'accepted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected $appends = [
        'is_expired',
        'is_accepted',
        'invitation_url',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast() && !$this->accepted_at;
    }

    public function getIsAcceptedAttribute(): bool
    {
        return $this->accepted_at !== null;
    }

    public function getInvitationUrlAttribute(): string
    {
        return url('/invitations/' . $this->token);
    }

    // Helper Methods
    public function accept(User $user): void
    {
        if ($this->is_expired) {
            throw new \Exception('Invitation has expired');
        }

        if ($this->is_accepted) {
            throw new \Exception('Invitation has already been accepted');
        }

        $this->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        // Create vendor user relationship
        VendorUser::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => $user->id,
            'role' => $this->role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => $user->id,
            'log_name' => 'invitation',
            'description' => "User {$user->email} accepted invitation to vendor",
            'event' => 'accepted',
        ]);
    }

    public function resend(): self
    {
        $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        return $this;
    }

    public function cancel(): void
    {
        $this->delete();

        ActivityLog::create([
            'vendor_id' => $this->vendor_id,
            'user_id' => auth()->id(),
            'log_name' => 'invitation',
            'description' => "Invitation to {$this->email} was cancelled",
            'event' => 'cancelled',
        ]);
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }

    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function createInvitation(
        Vendor $vendor,
        User $invitedBy,
        string $email,
        string $role = 'staff'
    ): self {
        // Cancel any existing pending invitations for this email
        self::forVendor($vendor->id)
            ->forEmail($email)
            ->pending()
            ->delete();

        $invitation = self::create([
            'vendor_id' => $vendor->id,
            'invited_by' => $invitedBy->id,
            'email' => $email,
            'role' => $role,
        ]);

        ActivityLog::create([
            'vendor_id' => $vendor->id,
            'user_id' => $invitedBy->id,
            'log_name' => 'invitation',
            'description' => "Invitation sent to {$email}",
            'event' => 'created',
        ]);

        return $invitation;
    }
}
