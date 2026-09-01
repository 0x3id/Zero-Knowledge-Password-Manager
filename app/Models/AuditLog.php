<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The model does not manage timestamps.
     *
     * The table only carries a `created_at` column per the architecture.
     */
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * Restricted to known metadata fields; an action_type outside the
     * fixed enum would be rejected by the database.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action_type',
        'ip_address',
        'device_info',
        'created_at',
    ];

    /**
     * Get the user this audit entry belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
