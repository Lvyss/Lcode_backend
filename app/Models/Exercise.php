<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id', 
        'type', 
        'question', 
        'solution', 
        'code_template', 
        'hint',
        'difficulty',
        'exp_reward', 
        'order_index',
        'is_active'
    ];

    protected $casts = [
        'solution' => 'array'
    ];

    public function part(): BelongsTo 
    { 
        return $this->belongsTo(Part::class); 
    }

    // ✅ SCOPE ACTIVE
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ✅ SCOPE BY DIFFICULTY
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}