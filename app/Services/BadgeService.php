<?php
// app/Services/BadgeService.php - UPDATE METHODS
namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\Section;
use Illuminate\Support\Facades\Log;

class BadgeService
{
    /**
     * ✅ CHECK AND AWARD BADGES WHEN USER COMPLETES A PART
     */
    public function checkAndAwardBadges(User $user, $sectionId)
    {
        try {
            Log::info("🔍 CHECKING BADGES FOR USER: {$user->id}, SECTION: {$sectionId}");
            
            $section = Section::find($sectionId);
            if (!$section) {
                Log::warning("❌ Section not found: {$sectionId}");
                return [];
            }

            // ✅ GET ALL BADGES FOR THIS SECTION
            $badges = Badge::where('section_id', $sectionId)
                ->where('is_active', true)
                ->orderBy('required_parts')
                ->get();

            Log::info("📛 Found {$badges->count()} badges for section {$sectionId}");

            $awardedBadges = [];

            foreach ($badges as $badge) {
                // ✅ CHECK IF USER ALREADY HAS THIS BADGE
                if ($user->badges()->where('badge_id', $badge->id)->exists()) {
                    Log::info("✅ User already has badge: {$badge->name}");
                    continue;
                }

                // ✅ CHECK IF USER MEETS REQUIREMENTS
                if ($this->meetsBadgeRequirements($user, $badge, $sectionId)) {
                    // ✅ AWARD THE BADGE!
                    $this->awardBadge($user, $badge);
                    $awardedBadges[] = [
                        'id' => $badge->id,
                        'name' => $badge->name,
                        'icon_path' => $badge->icon_path,
                        'icon_url' => $badge->icon_url,
                        'color' => $badge->color,
                        'description' => $badge->description
                    ];
                    
                    Log::info("🎉 BADGE AWARDED: User {$user->id} earned '{$badge->name}'");
                }
            }

            return $awardedBadges;

        } catch (\Exception $e) {
            Log::error('BadgeService Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ CHECK IF USER MEETS BADGE REQUIREMENTS - FIXED METHOD NAME
     */
    private function meetsBadgeRequirements(User $user, Badge $badge, $sectionId): bool
    {
        // ✅ COUNT COMPLETED PARTS IN THIS SECTION - PAKE progress() BUKAN userProgress()
        $completedParts = $user->progress() // ✅ METHOD NAME YANG BARU
            ->where('part_id', '!=', null)
            ->whereHas('part', function($query) use ($sectionId) {
                $query->where('section_id', $sectionId);
            })
            ->where('completed', true)
            ->where('is_correct', true)
            ->distinct('part_id')
            ->count('part_id');

        Log::info("📊 User {$user->id} completed {$completedParts}/{$badge->required_parts} parts for badge '{$badge->name}'");

        return $completedParts >= $badge->required_parts;
    }

    /**
     * ✅ AWARD BADGE TO USER - TETAP SAMA
     */
    private function awardBadge(User $user, Badge $badge)
    {
        UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'earned_at' => now()
        ]);

        // ✅ LOG THE AWARD
        Log::info("🏆 BADGE AWARDED - User: {$user->id}, Badge: {$badge->name} ({$badge->id})");
    }

    /**
     * ✅ GET USER'S BADGES WITH PROGRESS - FIXED METHOD NAME
     */
    public function getUserBadgesWithProgress(User $user, $sectionId = null)
    {
        $query = Badge::with('section')
            ->where('is_active', true);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $badges = $query->orderBy('order_index')->get();

        return $badges->map(function($badge) use ($user) {
            $userBadge = $user->badges()->where('badge_id', $badge->id)->first();
            $hasBadge = !is_null($userBadge);
            
            // ✅ CALCULATE PROGRESS - FIXED METHOD NAME
            $progress = $this->calculateBadgeProgress($user, $badge);

            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'icon_path' => $badge->icon_path,
                'icon_url' => $badge->icon_url,
                'color' => $badge->color,
                'description' => $badge->description,
                'section_id' => $badge->section_id,
                'section_name' => $badge->section->name ?? null,
                'required_parts' => $badge->required_parts,
                'earned' => $hasBadge,
                'earned_at' => $hasBadge ? $userBadge->pivot->earned_at : null,
                'progress' => $progress,
                'progress_percentage' => $badge->required_parts > 0 
                    ? min(round(($progress['completed_parts'] / $badge->required_parts) * 100), 100)
                    : 0
            ];
        });
    }

    /**
     * ✅ CALCULATE BADGE PROGRESS - FIXED METHOD NAME
     */
    private function calculateBadgeProgress(User $user, Badge $badge): array
    {
        if (!$badge->section_id) {
            return ['completed_parts' => 0, 'required_parts' => 0, 'remaining_parts' => 0];
        }

        $completedParts = $user->progress() // ✅ METHOD NAME YANG BARU
            ->where('part_id', '!=', null)
            ->whereHas('part', function($query) use ($badge) {
                $query->where('section_id', $badge->section_id);
            })
            ->where('completed', true)
            ->where('is_correct', true)
            ->distinct('part_id')
            ->count('part_id');

        return [
            'completed_parts' => $completedParts,
            'required_parts' => $badge->required_parts,
            'remaining_parts' => max($badge->required_parts - $completedParts, 0)
        ];
    }
}