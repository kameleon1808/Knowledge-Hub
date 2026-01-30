<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\AcceptanceController;
use App\Http\Controllers\AiAnswerController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Moderator\DashboardController as ModeratorDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\ProjectKnowledgeController;
use App\Http\Controllers\ProjectRagController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/vote', function () {
    return redirect()->route('questions.index');
})->name('vote.redirect');

Route::get('/votes', function () {
    return redirect()->route('questions.index');
})->name('votes.redirect');

Route::get('/questions/{question}/accept/{answer}', function ($question) {
    return redirect()->route('questions.show', $question);
})->name('questions.accept.redirect');

Route::get('/questions/{question}/accept', function ($question) {
    return redirect()->route('questions.show', $question);
})->name('questions.accept.index.redirect');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/knowledge-items', [ProjectKnowledgeController::class, 'storeDocument'])
        ->name('projects.knowledge-items.store');
    Route::post('projects/{project}/knowledge-emails', [ProjectKnowledgeController::class, 'storeEmail'])
        ->name('projects.knowledge-emails.store');
    Route::post('projects/{project}/rag-ask', [ProjectRagController::class, 'ask'])->name('projects.rag-ask');
    Route::get('projects/{project}/export/markdown', [ProjectExportController::class, 'markdown'])->name('projects.export.markdown');
    Route::get('projects/{project}/export/pdf', [ProjectExportController::class, 'pdf'])->name('projects.export.pdf');
    Route::get('projects/{project}/members/search', [ProjectController::class, 'searchMembers'])->name('projects.members.search');
    Route::post('projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');
    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy');
    Route::resource('questions', QuestionController::class);
    Route::post('/votes', [VoteController::class, 'store'])->name('votes.store');
    Route::delete('/votes', [VoteController::class, 'destroy'])->name('votes.destroy');
    Route::post('/questions/{question}/bookmark', [BookmarkController::class, 'store'])->name('questions.bookmark');
    Route::delete('/questions/{question}/bookmark', [BookmarkController::class, 'destroy'])->name('questions.bookmark.destroy');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/questions/{question}/accept/{answer}', [AcceptanceController::class, 'store'])->name('questions.accept');
    Route::delete('/questions/{question}/accept', [AcceptanceController::class, 'destroy'])->name('questions.accept.destroy');
    Route::post('/questions/{question}/ai-answer', [AiAnswerController::class, 'store'])->name('questions.ai-answer');
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
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('tags', AdminTagController::class)->except(['show']);
    });

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderator')
    ->name('moderator.')
    ->group(function () {
        Route::get('/', [ModeratorDashboardController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/auth.php';
