<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'google_id', 'avatar', 
        'total_exp', 'current_streak', 'last_activity_date', 'role', 'username', 'bio'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

        // ✅ ACCESSOR UNTUK LEVEL (Consistency)
    public function getLevelAttribute()
    {
        return floor($this->total_exp / 100) + 1;
    }

    // ✅ ACCESSOR UNTUK EXP (Backward compatibility)
    public function getExpAttribute()
    {
        return $this->total_exp;
    }

    // RELATIONSHIPS
    public function progress() { return $this->hasMany(UserProgress::class); }
   public function badges()
{
    return $this->belongsToMany(Badge::class, 'user_badges')
                ->withPivot('earned_at')
                ->withTimestamps();
}
public function userBadges()
{
    return $this->hasMany(UserBadge::class);
}
    public function tree() { return $this->hasOne(Tree::class); }
    
    // METHODS
    public function isAdmin() { return $this->role === 'admin'; }
    public function addExp($exp) { 
        $this->total_exp += $exp;
        $this->save();
        if ($this->tree) $this->tree->updateGrowth();
    }
}