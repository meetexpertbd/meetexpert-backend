<?php

use App\Http\Controllers\Admin\V1\AdminsWebController;
use App\Http\Controllers\Admin\V1\BookingsWebController;
use App\Http\Controllers\Admin\V1\CategoryController;
use App\Http\Controllers\Admin\V1\ContactMessagesWebController;
use App\Http\Controllers\Admin\V1\ExpertApplicationsWebController;
use App\Http\Controllers\Admin\V1\ExpertsWebController;
use App\Http\Controllers\Admin\V1\SkillController;
use App\Http\Controllers\Admin\V1\SubcategoryController;
use App\Http\Controllers\Admin\V1\TaxonomyBulkUploadController;
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

    Route::delete('taxonomy/categories/bulk-destroy', [CategoryController::class, 'bulkDestroy'])
        ->name('taxonomy.categories.bulk-destroy');
    Route::resource('taxonomy/categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.categories.index',
        'store' => 'taxonomy.categories.store',
        'update' => 'taxonomy.categories.update',
        'destroy' => 'taxonomy.categories.destroy',
    ]);

    Route::delete('taxonomy/subcategories/bulk-destroy', [SubcategoryController::class, 'bulkDestroy'])
        ->name('taxonomy.subcategories.bulk-destroy');
    Route::resource('taxonomy/subcategories', SubcategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.subcategories.index',
        'store' => 'taxonomy.subcategories.store',
        'update' => 'taxonomy.subcategories.update',
        'destroy' => 'taxonomy.subcategories.destroy',
    ]);

    Route::delete('taxonomy/skills/bulk-destroy', [SkillController::class, 'bulkDestroy'])
        ->name('taxonomy.skills.bulk-destroy');
    Route::resource('taxonomy/skills', SkillController::class)->only(['index', 'store', 'update', 'destroy'])->names([
        'index' => 'taxonomy.skills.index',
        'store' => 'taxonomy.skills.store',
        'update' => 'taxonomy.skills.update',
        'destroy' => 'taxonomy.skills.destroy',
    ]);

    Route::get('taxonomy/bulk-upload', [TaxonomyBulkUploadController::class, 'create'])
        ->name('taxonomy.bulk-upload.create');
    Route::post('taxonomy/bulk-upload', [TaxonomyBulkUploadController::class, 'store'])
        ->name('taxonomy.bulk-upload.store');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('expert-applications', [ExpertApplicationsWebController::class, 'index'])
            ->name('admin.expert-applications.index');
        Route::delete('expert-applications/bulk-destroy', [ExpertApplicationsWebController::class, 'bulkDestroy'])
            ->name('admin.expert-applications.bulk-destroy');
        Route::get('expert-applications/{expert_application}', [ExpertApplicationsWebController::class, 'show'])
            ->name('admin.expert-applications.show');
        Route::post('expert-applications/{expert_application}/approve', [ExpertApplicationsWebController::class, 'approve'])
            ->name('admin.expert-applications.approve');
        Route::post('expert-applications/{expert_application}/reject', [ExpertApplicationsWebController::class, 'reject'])
            ->name('admin.expert-applications.reject');
        Route::delete('expert-applications/{expert_application}', [ExpertApplicationsWebController::class, 'destroy'])
            ->name('admin.expert-applications.destroy');

        Route::get('experts', [ExpertsWebController::class, 'index'])
            ->name('admin.experts.index');
        Route::delete('experts/bulk-destroy', [ExpertsWebController::class, 'bulkDestroy'])
            ->name('admin.experts.bulk-destroy');
        Route::get('experts/{user}/edit', [ExpertsWebController::class, 'edit'])
            ->name('admin.experts.edit');
        Route::put('experts/{user}', [ExpertsWebController::class, 'update'])
            ->name('admin.experts.update');
        Route::get('experts/{user}', [ExpertsWebController::class, 'show'])
            ->name('admin.experts.show');
        Route::delete('experts/{user}', [ExpertsWebController::class, 'destroy'])
            ->name('admin.experts.destroy');

        Route::get('bookings', [BookingsWebController::class, 'index'])
            ->name('admin.bookings.index');
        Route::delete('bookings/bulk-destroy', [BookingsWebController::class, 'bulkDestroy'])
            ->name('admin.bookings.bulk-destroy');
        Route::get('bookings/{booking}', [BookingsWebController::class, 'show'])
            ->name('admin.bookings.show');

        Route::get('contact-messages', [ContactMessagesWebController::class, 'index'])
            ->name('admin.contact-messages.index');
        Route::get('contact-messages/{contact_message}', [ContactMessagesWebController::class, 'show'])
            ->name('admin.contact-messages.show');
        Route::post('contact-messages/{contact_message}/mark-replied', [ContactMessagesWebController::class, 'markReplied'])
            ->name('admin.contact-messages.mark-replied');
        Route::post('contact-messages/{contact_message}/mark-unread', [ContactMessagesWebController::class, 'markUnread'])
            ->name('admin.contact-messages.mark-unread');
        Route::delete('contact-messages/{contact_message}', [ContactMessagesWebController::class, 'destroy'])
            ->name('admin.contact-messages.destroy');

        Route::get('users', [UsersWebController::class, 'index'])
            ->name('admin.users.index');
        Route::post('users', [UsersWebController::class, 'store'])
            ->name('admin.users.store');
        Route::delete('users/bulk-destroy', [UsersWebController::class, 'bulkDestroy'])
            ->name('admin.users.bulk-destroy');
        Route::delete('users/{user}', [UsersWebController::class, 'destroy'])
            ->name('admin.users.destroy');
        Route::get('users/{user}/apply-for-expert', [UsersWebController::class, 'applyForExpert'])
            ->name('admin.users.apply-for-expert');
        Route::post('users/{user}/apply-for-expert', [UsersWebController::class, 'storeExpertApplication'])
            ->name('admin.users.apply-for-expert.store');

        Route::get('admins', [AdminsWebController::class, 'index'])
            ->name('admin.admins.index');
        Route::post('admins', [AdminsWebController::class, 'store'])
            ->name('admin.admins.store');
        Route::delete('admins/bulk-destroy', [AdminsWebController::class, 'bulkDestroy'])
            ->name('admin.admins.bulk-destroy');
        Route::delete('admins/{user}', [AdminsWebController::class, 'destroy'])
            ->name('admin.admins.destroy');
    });
});
