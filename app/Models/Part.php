<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'title', 'description', 'order_index', 'exp_reward', 'is_active'];

    public function section(): BelongsTo 
    { 
        return $this->belongsTo(Section::class); 
    }
    
    public function contentBlocks(): HasMany 
    { 
        return $this->hasMany(ContentBlock::class)->orderBy('order_index'); 
    }
    
    public function exercises(): HasMany 
    { 
        return $this->hasMany(Exercise::class)->orderBy('order_index'); 
    }

    // ✅ SCOPE ACTIVE
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}