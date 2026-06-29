<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardPack extends Model
{
    protected $fillable = ['meeting_id', 'version', 'status', 'generated_by', 'generated_at', 'published_at', 'file_path'];

    protected $casts = [
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BoardPackItem::class)->orderBy('order_column');
    }
}
