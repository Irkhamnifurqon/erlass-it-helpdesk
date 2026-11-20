<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
    public function slaPolicy(): HasOne { return $this->hasOne(SlaPolicy::class); }
    public function subcategories(): HasMany { return $this->hasMany(Subcategory::class); }
    public function issueTypes(): HasMany { return $this->hasMany(IssueType::class); }
}
