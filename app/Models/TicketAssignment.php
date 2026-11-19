<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TicketAssignment extends Model
{
    protected $fillable = ['ticket_id', 'assigned_to', 'assigned_by', 'assigned_at', 'unassigned_at'];
    protected function casts(): array { return ['assigned_at' => 'datetime', 'unassigned_at' => 'datetime']; }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
