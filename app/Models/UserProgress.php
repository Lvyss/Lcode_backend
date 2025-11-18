<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'part_id', 
        'exercise_id', 
        'completed', 
        'exp_earned', 
        'completed_at',
        // ✅ TAMBAHIN INI BRO!
        'user_answer',    // UNTUK SIMPAN CODE/USER ANSWER
        'is_correct',     // UNTUK SIMPAN STATUS BENAR/SALAH
        'attempts'        // UNTUK TRACK ATTEMPTS
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'is_correct' => 'boolean',  // ✅ CAST BOOLEAN
        'completed' => 'boolean'
    ];

    public function user() { 
        return $this->belongsTo(User::class); 
    }
    
    public function part() { 
        return $this->belongsTo(Part::class); 
    }
    
    public function exercise() { 
        return $this->belongsTo(Exercise::class); 
    }
}