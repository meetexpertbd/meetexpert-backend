<?php

use App\Http\Controllers\Admin\V1\BookingsWebController;
use App\Http\Controllers\Admin\V1\CategoryController;
use App\Http\Controllers\Admin\V1\ExpertApplicationsWebController;
use App\Http\Controllers\Admin\V1\ExpertsWebController;
use App\Http\Controllers\Admin\V1\SkillController;
use App\Http\Controllers\Admin\V1\SubcategoryController;
use App\Http\Controllers\Admin\V1\UsersWebController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/signin', [AuthenticatedSessionController::class, 'create'])->name('signin');
    Route::post('/signin', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::get('/signup', [RegisteredUserController::class, 'create'])->name('signup');
    Route::post('/signup', [RegisteredUserController::class, 'store'])->name('register');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
    })->name('dashboard');

    Route::resource('taxonomy/categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.categories.index',
        'store' => 'taxonomy.categories.store',
        'update' => 'taxonomy.categories.update',
        'destroy' => 'taxonomy.categories.destroy',
    ]);

    Route::resource('taxonomy/subcategories', SubcategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.subcategories.index',
        'store' => 'taxonomy.subcategories.store',
        'update' => 'taxonomy.subcategories.update',
        'destroy' => 'taxonomy.subcategories.destroy',
    ]);

    Route::resource('taxonomy/skills', SkillController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.skills.index',
        'store' => 'taxonomy.skills.store',
        'update' => 'taxonomy.skills.update',
        'destroy' => 'taxonomy.skills.destroy',
    ]);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('expert-applications', [ExpertApplicationsWebController::class, 'index'])
            ->name('admin.expert-applications.index');
        Route::get('expert-applications/{expert_application}', [ExpertApplicationsWebController::class, 'show'])
            ->name('admin.expert-applications.show');
        Route::post('expert-applications/{expert_application}/approve', [ExpertApplicationsWebController::class, 'approve'])
            ->name('admin.expert-applications.approve');
        Route::post('expert-applications/{expert_application}/reject', [ExpertApplicationsWebController::class, 'reject'])
            ->name('admin.expert-applications.reject');

        Route::get('experts', [ExpertsWebController::class, 'index'])
            ->name('admin.experts.index');
        Route::get('experts/{user}', [ExpertsWebController::class, 'show'])
            ->name('admin.experts.show');

        Route::get('bookings', [BookingsWebController::class, 'index'])
            ->name('admin.bookings.index');

        Route::get('users', [UsersWebController::class, 'index'])
            ->name('admin.users.index');
    });
});
