<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRecordIdAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        return (string) ($this->new_values['id'] ?? $this->old_values['id'] ?? '');
    }
}
