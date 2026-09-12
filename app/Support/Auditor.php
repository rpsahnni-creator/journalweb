<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Auditor
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function log(string $action, Model $auditable, ?array $old = null, ?array $new = null, ?int $journalId = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'journal_id' => $journalId ?? CurrentJournal::get()?->id,
            'auditable_type' => $auditable::class,
            'auditable_id' => (int) $auditable->getKey(),
            'action' => $action,
            'old_values' => self::safe($old),
            'new_values' => self::safe($new),
            'ip_address' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 255, ''),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private static function safe(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        unset(
            $values['password'],
            $values['remember_token'],
            $values['token'],
            $values['secret'],
            $values['api_key'],
            $values['authorization'],
            $values['password_confirmation'],
            $values['current_password'],
        );

        foreach (array_keys($values) as $key) {
            if (is_string($key) && preg_match('/password|token|secret|authorization/i', $key) === 1) {
                unset($values[$key]);
            }
        }

        return $values;
    }
}
