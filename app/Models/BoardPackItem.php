<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardPackItem extends Model
{
    protected $fillable = ['board_pack_id', 'meeting_document_id', 'order_column', 'page_number', 'page_count'];

    public function boardPack(): BelongsTo
    {
        return $this->belongsTo(BoardPack::class);
    }

    public function meetingDocument(): BelongsTo
    {
        return $this->belongsTo(MeetingDocument::class);
    }
}
