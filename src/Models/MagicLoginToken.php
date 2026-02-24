<?php

namespace SpykApp\PasswordlessLogin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class MagicLoginToken extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'max_uses' => 'integer',
        'use_count' => 'integer',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('passwordless-login.table', 'passwordless_login_tokens'));
    }

    /**
     * Get the authenticatable model.
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Determine if the token has been fully used.
     */
    public function isFullyUsed(): bool
    {
        if (is_null($this->max_uses)) {
            return false;
        }

        return $this->use_count >= $this->max_uses;
    }

    /**
     * Determine if the token is valid (not expired and not fully used).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isFullyUsed();
    }

    /**
     * Increment the use count.
     */
    public function incrementUseCount(): self
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);

        return $this;
    }

    /**
     * Get remaining uses (null if unlimited).
     */
    public function remainingUses(): ?int
    {
        if (is_null($this->max_uses)) {
            return null;
        }

        return max(0, $this->max_uses - $this->use_count);
    }

    /**
     * Scope: valid tokens only.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereColumn('use_count', '<', 'max_uses');
            });
    }

    /**
     * Scope: expired tokens.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: for a specific authenticatable.
     */
    public function scopeForAuthenticatable(Builder $query, Model $authenticatable): Builder
    {
        return $query->where('authenticatable_type', get_class($authenticatable))
            ->where('authenticatable_id', $authenticatable->getKey());
    }
}
