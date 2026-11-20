<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TicketSla extends Model
{
    protected $fillable = ['ticket_id', 'response_target_minutes', 'resolution_target_minutes', 'response_due_at', 'resolution_due_at', 'first_response_at', 'resolved_at'];
    protected function casts(): array { return ['response_due_at' => 'datetime', 'resolution_due_at' => 'datetime', 'first_response_at' => 'datetime', 'resolved_at' => 'datetime']; }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
}
