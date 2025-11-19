<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['language_id', 'name', 'description', 'order_index', 'exp_reward', 'is_active'];

    public function language(): BelongsTo 
    { 
        return $this->belongsTo(Language::class); 
    }
    
    // ✅ BENEDIN - PAKE 'is_active' BUKAN 'is_locked'
    public function parts(): HasMany
    { 
        return $this->hasMany(Part::class)->where('is_active', true)->orderBy('order_index'); 
    }

    // ✅ UNTUK ADMIN - SEMUA PARTS
    public function allParts(): HasMany
    {
        return $this->hasMany(Part::class)->orderBy('order_index');
    }

    // ✅ SCOPE ACTIVE
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}