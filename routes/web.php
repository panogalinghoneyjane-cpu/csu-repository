<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\RecommendationController;
use App\Http\Controllers\Librarian\ClassificationController;
use App\Http\Controllers\Librarian\ReportController;
use App\Http\Controllers\Librarian\ResourceController;
use App\Http\Controllers\Librarian\ReviewController;
use App\Http\Controllers\Librarian\UserController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guests (not logged in) - only the login page. No guest access to resources.
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| All logged-in users (librarian, faculty, student)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Browse by classification: College -> Program -> Subject
    Route::get('/colleges', [BrowseController::class, 'colleges'])->name('browse.colleges');
    Route::get('/colleges/{college}', [BrowseController::class, 'college'])->name('browse.college');
    Route::get('/programs/{program}', [BrowseController::class, 'program'])->name('browse.program');
    Route::get('/subjects/{subject}', [BrowseController::class, 'subject'])->name('browse.subject');

    // Keyword search + recommended (related) resources
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Controlled, view-only reader
    Route::get('/resources/{resource}/read', [ReaderController::class, 'show'])->name('reader.show');
    Route::get('/resources/{resource}/stream', [ReaderController::class, 'stream'])
        ->middleware('signed')->name('reader.stream');
});

/*
|--------------------------------------------------------------------------
| Faculty only - recommend resources (no uploading)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:faculty'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::get('/recommendations/create', [RecommendationController::class, 'create'])->name('recommendations.create');
    Route::post('/recommendations', [RecommendationController::class, 'store'])->name('recommendations.store');
    Route::get('/recommendations/{recommendation}/edit', [RecommendationController::class, 'edit'])->name('recommendations.edit');
    Route::put('/recommendations/{recommendation}', [RecommendationController::class, 'update'])->name('recommendations.update');
});

/*
|--------------------------------------------------------------------------
| Librarian only - review, upload, classify, monitor, report
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:librarian'])->prefix('librarian')->name('librarian.')->group(function () {
    // Review faculty recommendations
    Route::get('/recommendations', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/recommendations/{recommendation}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/recommendations/{recommendation}/decide', [ReviewController::class, 'decide'])->name('reviews.decide');

    // Manage repository resources (upload / edit / archive)
    Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/create', [ResourceController::class, 'create'])->name('resources.create');
    Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::get('/resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::put('/resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Classification: colleges, programs, subjects
    Route::get('/classifications', [ClassificationController::class, 'index'])->name('classifications.index');
    Route::post('/classifications/{type}', [ClassificationController::class, 'store'])
        ->whereIn('type', ['college', 'program', 'subject'])->name('classifications.store');
    Route::delete('/classifications/{type}/{id}', [ClassificationController::class, 'destroy'])
        ->whereIn('type', ['college', 'program', 'subject'])->name('classifications.destroy');

    // User accounts (no self-registration - librarian creates accounts)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

    // Monitoring & reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/access-logs', [ReportController::class, 'accessLogs'])->name('access-logs.index');
});
