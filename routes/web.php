<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Librarian\AnnouncementController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\Faculty\ClassController as FacultyClassController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\RecommendationController;
use App\Http\Controllers\Librarian\ClassificationController;
use App\Http\Controllers\Librarian\ReportController;
use App\Http\Controllers\Librarian\ResourceController;
use App\Http\Controllers\Librarian\ReviewController;
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
     Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
    Route::post('/notifications/{announcement}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::post('/notifications/notice/{id}/read', [NotificationController::class, 'readNotice'])
        ->name('notifications.notice.read');
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
        
    // My Classes (faculty share a class link; students save the class)
    Route::get('/my-classes', [ClassController::class, 'index'])->name('classes.index');
    Route::post('/my-classes/join', [ClassController::class, 'joinByCode'])->name('classes.join-code');
    Route::get('/my-classes/{courseClass}', [ClassController::class, 'show'])->name('classes.show');
    Route::delete('/my-classes/{courseClass}/leave', [ClassController::class, 'leave'])->name('classes.leave');
    Route::get('/join/{code}', [ClassController::class, 'join'])->name('classes.join');
    Route::post('/join/{code}', [ClassController::class, 'save'])->name('classes.save');
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
    Route::delete('/recommendations/{recommendation}', [RecommendationController::class, 'destroy'])->name('recommendations.destroy');
    
    // My Classes - manage classes and their resources
    Route::get('/classes/create', [FacultyClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [FacultyClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{courseClass}/edit', [FacultyClassController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{courseClass}', [FacultyClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{courseClass}', [FacultyClassController::class, 'destroy'])->name('classes.destroy');
    Route::patch('/classes/{courseClass}/archive', [FacultyClassController::class, 'archive'])->name('classes.archive');
Route::patch('/classes/{courseClass}/restore', [FacultyClassController::class, 'restore'])->name('classes.restore');
    Route::post('/classes/{courseClass}/resources', [FacultyClassController::class, 'addResource'])->name('classes.resources.add');
    Route::delete('/classes/{courseClass}/resources/{resource}', [FacultyClassController::class, 'removeResource'])->name('classes.resources.remove');
           Route::delete('/classes/{courseClass}/students/{student}', [FacultyClassController::class, 'removeStudent'])->name('classes.students.remove');
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
    Route::post('/classifications/college/{college}/logo', [ClassificationController::class, 'logo'])->name('classifications.logo');
    Route::put('/classifications/{type}/{id}', [ClassificationController::class, 'update'])
        ->whereIn('type', ['college', 'program', 'subject'])->whereNumber('id')->name('classifications.update');
    Route::delete('/classifications/{type}/{id}', [ClassificationController::class, 'destroy'])
        ->whereIn('type', ['college', 'program', 'subject'])->name('classifications.destroy');

    // User accounts (no self-registration - librarian creates accounts)
  //  Route::get('/users', [UserController::class, 'index'])->name('users.index');
    //Route::post('/users', [UserController::class, 'store'])->name('users.store');
    //Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        // Announcements (shown on faculty and student dashboards)
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Monitoring & reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
   // Route::get('/access-logs', [ReportController::class, 'accessLogs'])->name('access-logs.index');
});
 
/*
|--------------------------------------------------------------------------
| Admin only - manage user accounts
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});