<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResolutionVote extends Model
{
    public $timestamps = false;

    protected $fillable = ['resolution_id', 'user_id', 'vote'];

    protected $casts = ['voted_at' => 'datetime'];

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
