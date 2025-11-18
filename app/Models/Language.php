<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'description', 'order_index', 'is_active'];

    // ✅ Untuk user-facing (hanya active sections)
    public function sections(): HasMany
    { 
        return $this->hasMany(Section::class)->where('is_active', true)->orderBy('order_index'); 
    }

    // ✅ Untuk admin (semua sections, termasuk yang inactive)
    public function allSections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order_index');
    }

    // ✅ Scope untuk active languages
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}