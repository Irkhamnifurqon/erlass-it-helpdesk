<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueType extends Model
{
    protected $fillable = ['category_id', 'subcategory_id', 'name', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function subcategory(): BelongsTo { return $this->belongsTo(Subcategory::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
}
