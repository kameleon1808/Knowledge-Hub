<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\Moderator\DashboardController as ModeratorDashboardController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('questions', QuestionController::class);
    Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');
    Route::get('/answers/{answer}/edit', [AnswerController::class, 'edit'])->name('answers.edit');
    Route::put('/answers/{answer}', [AnswerController::class, 'update'])->name('answers.update');
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('/categories', function () {
            return Inertia::render('Admin/Categories/Index');
        })->name('categories.index');

        Route::get('/tags', function () {
            return Inertia::render('Admin/Tags/Index');
        })->name('tags.index');
    });

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderator')
    ->name('moderator.')
    ->group(function () {
        Route::get('/', [ModeratorDashboardController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/auth.php';
