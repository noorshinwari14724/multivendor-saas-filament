<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'log_name',
        'description',
        'event',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'properties' => 'json',
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByLogName($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->latest()->limit($limit);
    }

    public function scopeForSubject($query, string $type, $id)
    {
        return $query->where('subject_type', $type)
            ->where('subject_id', $id);
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }

    // Helper Methods
    public static function log(
        string $description,
        string $logName = 'default',
        ?string $event = null,
        $subject = null,
        $causer = null,
        array $properties = [],
        ?int $vendorId = null
    ): self {
        $data = [
            'log_name' => $logName,
            'description' => $description,
            'event' => $event,
            'properties' => $properties,
            'vendor_id' => $vendorId,
        ];

        if ($subject) {
            $data['subject_type'] = get_class($subject);
            $data['subject_id'] = $subject->id;
        }

        if ($causer) {
            $data['causer_type'] = get_class($causer);
            $data['causer_id'] = $causer->id;
            $data['user_id'] = $causer->id;
        }

        if (request()) {
            $data['ip_address'] = request()->ip();
            $data['user_agent'] = request()->userAgent();
            $data['url'] = request()->url();
            $data['method'] = request()->method();
        }

        return self::create($data);
    }

    public static function logModelCreated($model, $causer = null, ?int $vendorId = null): self
    {
        return self::log(
            "Created " . class_basename($model) . " #{$model->id}",
            strtolower(class_basename($model)),
            'created',
            $model,
            $causer,
            $model->toArray(),
            $vendorId
        );
    }

    public static function logModelUpdated($model, array $oldValues, $causer = null, ?int $vendorId = null): self
    {
        return self::log(
            "Updated " . class_basename($model) . " #{$model->id}",
            strtolower(class_basename($model)),
            'updated',
            $model,
            $causer,
            $model->toArray(),
            $vendorId
        );
    }

    public static function logModelDeleted($model, $causer = null, ?int $vendorId = null): self
    {
        return self::log(
            "Deleted " . class_basename($model) . " #{$model->id}",
            strtolower(class_basename($model)),
            'deleted',
            $model,
            $causer,
            $model->toArray(),
            $vendorId
        );
    }

    public function getChangesAttribute(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $changes = [];
        foreach ($new as $key => $value) {
            if (isset($old[$key]) && $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key],
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
