<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    protected $fillable = ['ticket_number', 'requester_id', 'category_id', 'priority_id', 'title', 'description', 'status', 'solution', 'resolved_at', 'closed_at'];
    protected function casts(): array { return ['resolved_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function priority(): BelongsTo { return $this->belongsTo(Priority::class); }
    public function assignments(): HasMany { return $this->hasMany(TicketAssignment::class); }
    public function activeAssignment(): HasOne { return $this->hasOne(TicketAssignment::class)->whereNull('unassigned_at')->latestOfMany(); }
    public function comments(): HasMany { return $this->hasMany(TicketComment::class); }
    public function histories(): HasMany { return $this->hasMany(TicketHistory::class); }
    public function sla(): HasOne { return $this->hasOne(TicketSla::class); }
    public function rating(): HasOne { return $this->hasOne(TicketRating::class); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole('Administrator', 'Supervisor IT')) {
            return $query;
        }

        if ($user->hasRole('IT Support')) {
            return $query->whereHas('activeAssignment', fn ($assignment) => $assignment->where('assigned_to', $user->id));
        }

        if ($user->hasAnyRole('Manager Departemen', 'Kepala Departemen')) {
            return $query->whereHas('requester', fn ($requester) => $requester->where('department_id', $user->department_id));
        }

        return $query->where('requester_id', $user->id);
    }
}
