<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * `user_id` and `category_id` are assignable only from server-side
     * authorization logic; a client can never mass-assign an item into
     * another user's vault or category.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'username',
        'encrypted_password',
        'encrypted_notes',
        'iv',
        'url',
    ];

    /**
     * The attributes that should be hidden from serialization.
     *
     * Security note: `encrypted_password`, `encrypted_notes`, and `iv`
     * are AES-256-GCM ciphertext meant exclusively for client-side
     * decryption. They are hidden by default to prevent accidental
     * exposure (logs, cache, lists). Endpoints serving the authenticated
     * client must explicitly opt in with `->makeVisible([...])`.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'encrypted_password',
        'encrypted_notes',
        'iv',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the user that owns this vault item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category this vault item belongs to, if any.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
