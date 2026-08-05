<?php

namespace App\Models;

use Database\Factories\BugLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BugLog extends Model
{
    /** @use HasFactory<BugLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exception_class',
        'message',
        'fingerprint',
        'url',
        'method',
        'file',
        'line',
        'ip_address',
        'user_agent',
        'trace',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public static function record(Throwable $exception): void
    {
        try {
            static::create([
                'user_id' => Auth::id(),
                'exception_class' => $exception::class,
                'message' => mb_strimwidth($exception->getMessage() ?: 'Erreur sans message', 0, 1000),
                'fingerprint' => static::fingerprint($exception),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'trace' => mb_strimwidth($exception->getTraceAsString(), 0, 60000),
            ]);
        } catch (Throwable) {
            //
        }
    }

    protected static function fingerprint(Throwable $exception): string
    {
        return hash('sha256', implode('|', [
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        ]));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function markResolved(User $user, ?string $note = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_note' => $note,
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_note' => null,
        ]);
    }
}
