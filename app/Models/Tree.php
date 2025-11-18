<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tree extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'current_stage', 'growth_percentage', 'total_exp'];

    const STAGES = [
        'seed' => ['min' => 0, 'max' => 100, 'icon' => '🌱'],
        'sprout' => ['min' => 100, 'max' => 500, 'icon' => '🌿'],
        'small_tree' => ['min' => 500, 'max' => 1000, 'icon' => '🌳'],
        'big_tree' => ['min' => 1000, 'max' => 5000, 'icon' => '🏞️'],
        'legendary' => ['min' => 5000, 'max' => PHP_INT_MAX, 'icon' => '🌋']
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function updateGrowth()
    {
        $totalExp = $this->total_exp;
        foreach (self::STAGES as $stage => $range) {
            if ($totalExp >= $range['min'] && $totalExp < $range['max']) {
                $this->current_stage = $stage;
                $stageRange = $range['max'] - $range['min'];
                $progressInStage = $totalExp - $range['min'];
                $this->growth_percentage = ($progressInStage / $stageRange) * 100;
                break;
            }
        }
        $this->save();
    }
}