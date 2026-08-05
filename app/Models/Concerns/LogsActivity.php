<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsActivity
{
    protected static array $activityHiddenFields = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            static::recordActivity($model, 'created', null, static::activityValues($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changes = static::activityValues($model->getChanges());

            if (empty($changes)) {
                return;
            }

            $oldValues = [];

            foreach (array_keys($changes) as $field) {
                $oldValues[$field] = $model->getOriginal($field);
            }

            static::recordActivity($model, 'updated', static::activityValues($oldValues), $changes);
        });

        static::deleted(function (Model $model): void {
            static::recordActivity($model, 'deleted', static::activityValues($model->getOriginal()), null);
        });
    }

    protected static function recordActivity(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        if (! Auth::check()) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'description' => static::activityDescription($model, $action),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    protected static function activityValues(array $values): array
    {
        return collect($values)
            ->reject(fn ($value, string $key) => in_array($key, static::$activityHiddenFields, true))
            ->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value)
            ->all();
    }

    protected static function activityDescription(Model $model, string $action): string
    {
        $subject = static::activitySubjectName($model);

        return match ($action) {
            'created' => "{$subject} créé(e)",
            'updated' => "{$subject} modifié(e)",
            'deleted' => "{$subject} supprimé(e)",
            default => "{$subject} {$action}",
        };
    }

    protected static function activitySubjectName(Model $model): string
    {
        $label = method_exists($model, 'activityLabel')
            ? $model->activityLabel()
            : class_basename($model);

        return Str::of($label)->replace('_', ' ')->toString();
    }
}
