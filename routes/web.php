<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // notifications
    Route::get('/notifications',              [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // people — draft & duplicate harus didaftarkan sebelum resource agar tidak bertabrakan dengan {person}
    Route::post('/people/draft/save',       [PersonController::class, 'saveDraft'])->name('people.draft.save');
    Route::delete('/people/draft/clear',    [PersonController::class, 'clearDraft'])->name('people.draft.clear');
    Route::post('/people/check-duplicates', [PersonController::class, 'checkDuplicates'])->name('people.check-duplicates');
    Route::delete('/people/bulk-destroy',   [PersonController::class, 'bulkDestroy'])->name('people.bulk-destroy');

    Route::resource('people', PersonController::class);

    // relationships
    Route::post('/relationships',                        [RelationshipController::class, 'store'])->name('relationships.store');
    Route::delete('/relationships/{relationship}',       [RelationshipController::class, 'destroy'])->name('relationships.destroy');
    Route::post('/relationships/{relationship}/approve', [RelationshipController::class, 'approve'])->name('relationships.approve');

    // tree
    Route::get('/tree',              [TreeController::class, 'index'])->name('tree');
    Route::get('/api/tree/data',     [TreeController::class, 'data'])->name('tree.data');
    Route::get('/api/tree/search',   [TreeController::class, 'searchPeople'])->name('tree.search');
});

Route::middleware(['auth', 'verified', 'role:admin|moderator'])->group(function () {
    Route::get('/approvals',                          [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{approval}',               [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('/approvals/{approval}/approve',      [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approval}/reject',       [ApprovalController::class, 'reject'])->name('approvals.reject');
});

// admin only
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',                              [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/{user}',                       [UserManagementController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/role',                [UserManagementController::class, 'updateRole'])->name('users.update-role');
    Route::post('/users/{user}/reset-password',       [UserManagementController::class, 'sendPasswordReset'])->name('users.reset-password');
});

// export routes (admin only)
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('export')->name('export.')->group(function () {
    Route::get('/',                    [ExportController::class, 'index'])->name('index');
    Route::get('/people/csv',          [ExportController::class, 'exportPeopleCSV'])->name('people.csv');
    Route::get('/relations/csv',       [ExportController::class, 'exportRelationsCSV'])->name('relations.csv');
    Route::get('/statistics/csv',      [ExportController::class, 'exportStatisticsCSV'])->name('statistics.csv');
});

// person pdf — accessible to all logged-in users (for their own profile)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/export/people/{person}/pdf', [ExportController::class, 'exportPersonPDF'])->name('export.person.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
