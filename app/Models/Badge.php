<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // ✅ JANGAN LUPA INI!

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'icon_path', 'color', 'description', 
        'section_id', 'required_parts', 'order_index', 'is_active'
    ];

    // ✅ PASTIIN APPENDS UNTUK ACCESSOR
    protected $appends = ['icon_url'];

    // ✅ ACCESSOR YANG BENAR
    public function getIconUrlAttribute()
    {
        if (!$this->icon_path) {
            return null;
        }
        
        // Coba cara yang lebih simple
        return asset('storage/' . $this->icon_path);
    }


    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withPivot('earned_at')
                    ->withTimestamps();
    }
}