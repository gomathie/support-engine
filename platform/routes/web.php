<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ResourceDownloadController;
use App\Http\Controllers\SupportPanelController;
use Illuminate\Support\Facades\Route;

/*
| Employee portal. The administration side is not here — Filament owns /admin
| and registers its own routes from AdminPanelProvider.
*/

Route::redirect('/', '/dashboard');

// ------------------------------------------------------------------- guest
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1');
});

/*
| Certificate verification is deliberately public and unauthenticated: the
| point of a certificate number is that somebody outside the company can check
| it. The token is random and 48 characters, and the page shows only name,
| course and date — never a score or an email address.
*/
Route::get('verify/{token}', [CertificateController::class, 'verify'])
    ->name('certificates.verify')
    ->middleware('throttle:30,1');

// ------------------------------------------------------------------- auth
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // ------------------------------------------------------------- courses
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('courses/{course}/enroll', [CourseController::class, 'enroll'])
        ->name('courses.enroll');

    // ------------------------------------------------------------- lessons
    Route::get('courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])
        ->name('lessons.show');
    Route::post('courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'complete'])
        ->name('lessons.complete');
    Route::delete('courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'uncomplete'])
        ->name('lessons.uncomplete');

    // -------------------------------------------------------------- quizzes
    Route::get('courses/{course}/quiz/{quiz}', [QuizController::class, 'show'])
        ->name('quizzes.show');
    Route::post('courses/{course}/quiz/{quiz}/start', [QuizController::class, 'start'])
        ->name('quizzes.start');
    Route::post('courses/{course}/quiz/{quiz}/submit', [QuizController::class, 'submit'])
        ->name('quizzes.submit');
    Route::get('attempts/{attempt}', [QuizController::class, 'result'])
        ->name('attempts.show');

    // ------------------------------------------------------------- progress
    Route::get('my-progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::delete('my-progress/courses/{course}', [ProgressController::class, 'reset'])
        ->name('progress.reset');

    // --------------------------------------------------------- certificates
    Route::get('certificates', [CertificateController::class, 'index'])
        ->name('certificates.index');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');

    // ------------------------------------------------------------ resources
    Route::get('resources/{resource}/download', [ResourceDownloadController::class, 'download'])
        ->name('resources.download');
    Route::get('resources/{resource}/view', [ResourceDownloadController::class, 'stream'])
        ->name('resources.stream');

    // -------------------------------------------------------- support panel
    Route::get('support-panel', [SupportPanelController::class, 'index'])
        ->name('support-panel.index');
    Route::post('support-panel/cases', [SupportPanelController::class, 'store'])
        ->name('support-cases.store');
    Route::put('support-panel/cases/{case}', [SupportPanelController::class, 'update'])
        ->name('support-cases.update');

    // -------------------------------------------------------- notifications
    Route::post('notifications/read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    // -------------------------------------------------------------- profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');
    Route::put('profile/theme', [ProfileController::class, 'updateTheme'])
        ->name('profile.theme');
});
