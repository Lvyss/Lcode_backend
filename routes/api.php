
    <?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\LanguageController;
    use App\Http\Controllers\SectionController;
    use App\Http\Controllers\PartController;
    use App\Http\Controllers\ProgressController;
    use App\Http\Controllers\UserController;
    use App\Http\Controllers\BadgeController;
    use App\Http\Controllers\TreeController;
    use App\Http\Controllers\ExerciseController;

    // ADMIN CONTROLLERS
    use App\Http\Controllers\Admin\AdminLanguageController;
    use App\Http\Controllers\Admin\AdminSectionController;
    use App\Http\Controllers\Admin\AdminPartController;
    use App\Http\Controllers\Admin\AdminExerciseController;
    use App\Http\Controllers\Admin\AdminBadgeController;
    use App\Http\Controllers\Admin\AdminDashboardController;

    // PUBLIC LEARNING ROUTES
    Route::get('/languages', [LanguageController::class, 'index']);
    Route::get('/languages/{id}/sections', [LanguageController::class, 'getSections']);

    // PROTECTED ROUTES
    Route::middleware('auth:sanctum')->group(function () {
        // AUTH
        Route::post('/logout', [AuthController::class, 'logoutApi']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // ✅ DASHBOARD & PROGRESS
            // ✅ PROFILE ROUTES
    Route::get('/user/profile-stats', [UserController::class, 'getProfileStats']);
    Route::get('/user/tree-progress', [TreeController::class, 'getUserTreeProgress']);
    Route::get('/user/leaderboard', [UserController::class, 'getLeaderboard']);
    
    // ✅ UPDATE PROFILE
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    
        Route::get('/user/progress', [ProgressController::class, 'getUserProgress']);
    Route::get('/user/badges', [BadgeController::class, 'getUserBadges']);
    Route::get('/sections/{sectionId}/badges', [BadgeController::class, 'getSectionBadges']);
    
        // ✅ LANGUAGE FLOW
        Route::get('/languages/{id}', [LanguageController::class, 'show']); 
        Route::get('/languages/{id}/sections', [SectionController::class, 'getByLanguage']);

        // ✅ SECTION FLOW  
        Route::get('/sections/{id}', [SectionController::class, 'show']); 
        Route::get('/sections/{id}/parts', [PartController::class, 'getBySection']);

        // ✅ PART LEARNING FLOW
        Route::get('/parts/{id}', [PartController::class, 'show']); 
        Route::get('/parts/{id}/content', [PartController::class, 'getWithContent']);
        Route::get('/parts/{id}/exercises', [PartController::class, 'getByPart']);

        // ✅ PROGRESS TRACKING
        Route::post('/progress/complete-exercise', [ProgressController::class, 'completeExercise']);
            Route::get('/progress/part/{partId}', [ProgressController::class, 'getPartProgress']); 
                 Route::get('/progress/section/{sectionId}', [ProgressController::class, 'getSectionProgress']);
            Route::get('/progress/exercise-status/{exerciseId}', [ProgressController::class, 'getExerciseStatus']);



        // ADMIN ROUTES
        Route::middleware('admin')->prefix('admin')->group(function () {
            // Dashboard
            Route::get('/dashboard', [AdminDashboardController::class, 'getStats']);
            
            // Languages
            Route::get('/languages', [AdminLanguageController::class, 'index']);
            Route::post('/languages', [AdminLanguageController::class, 'store']);
            Route::put('/languages/{id}', [AdminLanguageController::class, 'update']);
            Route::delete('/languages/{id}', [AdminLanguageController::class, 'destroy']);
            
            // Sections
        Route::get('/languages/{languageId}/sections', [AdminSectionController::class, 'index']);
        Route::post('/sections', [AdminSectionController::class, 'store']);
        Route::put('/sections/{id}', [AdminSectionController::class, 'update']);
        Route::delete('/sections/{id}', [AdminSectionController::class, 'destroy']);
        Route::post('/sections/reorder', [AdminSectionController::class, 'reorder']);
            
        // Parts routes - VERIFY THESE EXIST
        Route::get('/sections/{sectionId}/parts', [AdminPartController::class, 'index']);
        Route::post('/parts', [AdminPartController::class, 'store']);
        Route::put('/parts/{id}', [AdminPartController::class, 'update']);
        Route::delete('/parts/{id}', [AdminPartController::class, 'destroy']); // ✅ METHOD destroy
        
        // Content blocks routes - VERIFY THESE EXIST  
        Route::get('/parts/{partId}/content-blocks', [AdminPartController::class, 'getContentBlocks']);
        Route::post('/parts/{partId}/content-blocks', [AdminPartController::class, 'storeContentBlock']);
        Route::put('/parts/{partId}/content-blocks/{blockId}', [AdminPartController::class, 'updateContentBlock']);
        Route::delete('/parts/{partId}/content-blocks/{blockId}', [AdminPartController::class, 'destroyContentBlock']);
        Route::post('/parts/{partId}/content-blocks/reorder', [AdminPartController::class, 'updateContentBlocksOrder']);
    
        // Exercises routes
        Route::get('/parts/{partId}/exercises', [AdminExerciseController::class, 'index']);
        Route::post('/exercises', [AdminExerciseController::class, 'store']);
        Route::put('/exercises/{id}', [AdminExerciseController::class, 'update']);
        Route::delete('/exercises/{id}', [AdminExerciseController::class, 'destroy']);
        Route::post('/exercises/reorder', [AdminExerciseController::class, 'reorder']);
        
        // Badges
    Route::get('/badges', [AdminBadgeController::class, 'index']);
    Route::post('/badges', [AdminBadgeController::class, 'store']);
    Route::put('/badges/{id}', [AdminBadgeController::class, 'update']);
    Route::delete('/badges/{id}', [AdminBadgeController::class, 'destroy']);
    Route::get('/badges/sections', [AdminBadgeController::class, 'getSections']);
    Route::post('/badges/reorder', [AdminBadgeController::class, 'reorder']); // ✅ BONUS
        });
    });