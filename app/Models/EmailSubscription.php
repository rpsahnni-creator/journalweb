<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailSubscription extends Model
{
    protected $fillable = [
        'email',
        'confirm_token',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<EmailSubscription>  $query
     * @return Builder<EmailSubscription>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public static function start(string $email): self
    {
        $subscription = static::query()->firstOrNew(['email' => Str::lower(trim($email))]);
        $subscription->confirm_token = Str::random(48);
        $subscription->unsubscribed_at = null;
        if (! $subscription->exists) {
            $subscription->confirmed_at = null;
        }
        $subscription->save();

        return $subscription;
    }
}
