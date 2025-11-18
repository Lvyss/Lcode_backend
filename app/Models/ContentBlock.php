<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id', 'type', 'content', 'text_content', 
        'language', 'order_index', 'metadata'
    ];

    // ✅ CAST JSON FIELDS
    protected $casts = [
        'content' => AsArrayObject::class,
        'metadata' => AsArrayObject::class,
    ];

    public function part(): BelongsTo 
    { 
        return $this->belongsTo(Part::class); 
    }

    // ✅ SCOPE UNTUK FILTER TYPE
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}