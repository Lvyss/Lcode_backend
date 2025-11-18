<?php
// app/Http/Controllers/AdminDashboardController.php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Language;
use App\Models\Section;
use App\Models\Part;
use App\Models\Exercise;
use App\Models\Badge;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function getStats()
    {
         $stats = [
        'total_users' => User::count(),
        'total_languages' => Language::count(),
        'total_sections' => Section::count(),
        'total_parts' => Part::count(),
        'total_exercises' => Exercise::count(),
        'total_badges' => Badge::count(),
        'recent_users' => User::latest()->take(5)->get(),
        
        // ✅ SIMPLE DULU, NANTI BISA DIPERBAIKI
        'popular_languages' => Language::active()->orderBy('order_index')->take(3)->get()
    ];

        return response()->json($stats);
    }
}