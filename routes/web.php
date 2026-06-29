<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Models\ApplicantCase;
use App\Services\DelayStageService;
use App\Services\SeverityService;     

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // API routes for dashboard data
    Route::get('/api/dashboard', [DashboardController::class, 'api']);
    Route::get('/api/dashboard/cases', [DashboardController::class, 'cases']);
    Route::get('/api/dashboard/filter-options', [DashboardController::class, 'filterOptions']);
    Route::get('/api/dashboard/search', [DashboardController::class, 'search']);
    Route::get('/api/dashboard/export-csv', [DashboardController::class, 'exportCSV'])->name('dashboard.export.csv');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    });

require __DIR__.'/auth.php';
