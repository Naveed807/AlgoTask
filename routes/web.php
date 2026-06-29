<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Models\ApplicantCase;
use App\Services\DelayStageService;
use App\Services\SeverityService;     

Route::get('/test-stage', function (DelayStageService $service) {

    $case = ApplicantCase::with([
        'financialRelease',
        'inspection',
    ])->first();

    return $service->determineStage($case);
});

Route::get('/test-severity', function (
    DelayStageService $delayStageService,
    SeverityService $severityService
) {

    $case = ApplicantCase::with([
        'financialRelease',
        'inspection',
    ])->first();

    $stage = $delayStageService->determineStage($case);

    if (!$stage) {
        return response()->json([
            'message' => 'Case is completed.'
        ]);
    }

    $severity = $severityService->calculate(
        $stage['stage_type'],
        $stage['stage_start_date']
    );

    return response()->json([
        'case_uuid' => $case->case_uuid,
        'stage' => $stage,
        'severity' => $severity,
    ]);
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
