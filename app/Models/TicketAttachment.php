<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_id', 'comment_id', 'user_id', 'original_name', 'stored_path', 'mime_type', 'size'];
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function comment(): BelongsTo { return $this->belongsTo(TicketComment::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
