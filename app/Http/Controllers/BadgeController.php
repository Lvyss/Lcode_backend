<?php
// App/Http/Controllers/BadgeController.php - UPDATE
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\BadgeService;

class BadgeController extends Controller
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    public function getUserBadges(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $badges = $this->badgeService->getUserBadgesWithProgress($user);
            
        return response()->json($badges);
    }

    // ✅ NEW: GET BADGES FOR SPECIFIC SECTION
    public function getSectionBadges(Request $request, $sectionId): JsonResponse
    {
        $user = $request->user();
        
        $badges = $this->badgeService->getUserBadgesWithProgress($user, $sectionId);
            
        return response()->json($badges);
    }
}