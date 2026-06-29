<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BmsNotification extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'bms_notifications';

    protected $fillable = ['tenant_id', 'user_id', 'type', 'title', 'body', 'data', 'link', 'read_at'];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public static function notify(int $tenantId, int $userId, string $type, string $title, ?string $body = null, array $data = [], ?string $link = null): self
    {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'data'      => $data,
            'link'      => $link,
        ]);
    }
}
