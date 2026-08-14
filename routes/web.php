<?php

use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\Admin\AssessmentUploadController;
use App\Http\Controllers\Admin\InstrumentController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Learner\CourseController;
use App\Http\Controllers\Clinician\CaseloadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Participant\AssessmentController;
use App\Http\Controllers\Participant\ParticipantDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\Supervisor\ScreeningReviewController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'portal.home')->name('home');
Route::view('/demo', 'portal.demo-script')->name('demo.script');

Route::get('/screening', [ScreeningController::class, 'create'])->name('screening.start');
Route::post('/screening', [ScreeningController::class, 'store'])->name('screening.store');
Route::get('/screening/result/{submission}', [ScreeningController::class, 'result'])->name('screening.result');

Route::get('/activate/{token}', [AccountActivationController::class, 'show'])->name('activate.show');
Route::post('/activate/{token}', [AccountActivationController::class, 'store'])->name('activate.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:learner,admin'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course:slug}/pay', [CourseController::class, 'pay'])->name('courses.pay');
    Route::get('/courses/{course:slug}/video', [CourseController::class, 'showVideo'])->name('courses.video');
    Route::post('/courses/{course:slug}/video', [CourseController::class, 'completeVideo'])->name('courses.video.complete');
    Route::get('/courses/{course:slug}/certificate', [CourseController::class, 'certificate'])->name('courses.certificate');
    Route::get('/courses/{course:slug}/{kind}', [CourseController::class, 'showQuiz'])
        ->whereIn('kind', ['pretest', 'posttest'])
        ->name('courses.quiz');
    Route::post('/courses/{course:slug}/{kind}', [CourseController::class, 'storeQuiz'])
        ->whereIn('kind', ['pretest', 'posttest'])
        ->name('courses.quiz.store');
});

Route::middleware(['auth', 'verified', 'role:participant'])->group(function () {
    Route::get('/participant', [ParticipantDashboardController::class, 'index'])->name('participant.dashboard');
    Route::get('/participant/assessments/pcl5', [AssessmentController::class, 'showPcl5'])->name('participant.assessments.pcl5');
    Route::post('/participant/assessments/pcl5', [AssessmentController::class, 'storePcl5'])->name('participant.assessments.pcl5.store');
    Route::get('/participant/assessments/ace', [AssessmentController::class, 'showAce'])->name('participant.assessments.ace');
    Route::post('/participant/assessments/ace', [AssessmentController::class, 'storeAce'])->name('participant.assessments.ace.store');
    Route::get('/participant/assessments/des-ii', [AssessmentController::class, 'showDesIi'])->name('participant.assessments.des-ii');
    Route::post('/participant/assessments/des-ii', [AssessmentController::class, 'storeDesIi'])->name('participant.assessments.des-ii.store');
    Route::get('/participant/assessments/{instrument:slug}', [AssessmentController::class, 'show'])->name('participant.assessments.show');
    Route::post('/participant/assessments/{instrument:slug}', [AssessmentController::class, 'store'])->name('participant.assessments.store');
});

Route::middleware(['auth', 'verified', 'role:clinician'])->group(function () {
    Route::get('/clinician', [CaseloadController::class, 'index'])->name('clinician.dashboard');
    Route::post('/clinician/recommendations/{recommendation}/confirm', [CaseloadController::class, 'confirmRecommendation'])
        ->name('clinician.recommendations.confirm');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin', [AssessmentUploadController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/assessments/upload', [AssessmentUploadController::class, 'store'])->name('admin.assessments.upload');
    Route::get('/admin/assessments/completed/download', [AssessmentUploadController::class, 'downloadCompleted'])
        ->name('admin.assessments.completed.download');
    Route::get('/admin/participants/{participant}/results', [AssessmentUploadController::class, 'participantResults'])
        ->name('admin.participants.results');
    Route::post('/admin/assessment-results/{result}/reset', [AssessmentUploadController::class, 'resetResult'])
        ->name('admin.assessment-results.reset');
    Route::get('/admin/instruments/{instrument}/edit', [InstrumentController::class, 'edit'])
        ->name('admin.instruments.edit');
    Route::put('/admin/instruments/{instrument}', [InstrumentController::class, 'update'])
        ->name('admin.instruments.update');
    Route::post('/admin/instruments/{instrument}/questions/import', [InstrumentController::class, 'importQuestions'])
        ->name('admin.instruments.questions.import');
    Route::get('/admin/instruments/{instrument}/questions/template.csv', [InstrumentController::class, 'downloadQuestionsTemplate'])
        ->name('admin.instruments.questions.template');
    Route::get('/admin/users', [UserAdminController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserAdminController::class, 'store'])->name('admin.users.store');
    Route::post('/admin/users/{user}/roles', [UserAdminController::class, 'updateRoles'])->name('admin.users.roles');
    Route::post('/admin/users/{user}/courses', [UserAdminController::class, 'grantCourse'])->name('admin.users.courses');
    Route::post('/admin/users/{user}/posttest-reset', [UserAdminController::class, 'resetPosttest'])->name('admin.users.posttest-reset');
});

Route::middleware(['auth', 'verified', 'role:clinical_supervisor'])->group(function () {
    Route::get('/supervisor', [ScreeningReviewController::class, 'index'])->name('supervisor.dashboard');
    Route::post('/supervisor/screenings/{submission}/approve', [ScreeningReviewController::class, 'approve'])
        ->name('supervisor.screenings.approve');
    Route::post('/supervisor/screenings/{submission}/decline', [ScreeningReviewController::class, 'decline'])
        ->name('supervisor.screenings.decline');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
