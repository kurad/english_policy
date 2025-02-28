<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EssayController;
use App\Http\Controllers\EssayReviewController;
use App\Http\Controllers\OffenseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/staff/register', [AuthController::class, 'staffRegister']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.jwt');
Route::get('/user', [AuthController::class, 'getUser'])->middleware('auth.jwt');
Route::post('/complete-profile', [AuthController::class, 'completeProfile'])->middleware('auth.jwt');
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);


Route::post('/assign-essay', [OffenseController::class, 'assignEssay'])->middleware('auth.jwt');

Route::post('/ofenses/{offense_id}/submit-essay',[EssayController::class,'submitEssay'])->middleware('auth.jwt');
Route::post('/essays/{essay_id}/review', [EssayReviewController::class, 'reviewEssay'])->middleware('auth.jwt');
Route::get('/essays/{id}', [EssayController::class, 'showEssayToModify']);
Route::get('/students', [AuthController::class, 'fetchStudents']);
Route::get('/teacher/get-offenses',[OffenseController::class, 'index'])->middleware('auth.jwt');
Route::get('/teacher/get-all-offenses',[OffenseController::class, 'index1'])->middleware('auth.jwt');

Route::put('/offense/{id}',[OffenseController::class, 'updateAssignment'])->middleware('auth.jwt');

Route::get('/student/essays', [EssayController::class, 'getStudentEssay'])->middleware('auth.jwt');
Route::get('/student/essays/{essayId}', [EssayController::class, 'getEssay'])->middleware('auth.jwt');
Route::get('/essay/{id}/review', [EssayController::class, 'getEssayForReview']);
Route::post('/essay/comment', [EssayController::class, 'addComment'])->middleware('auth.jwt');
Route::post('/student/essays/{id}/save', [EssayController::class, 'saveDraft'])->middleware('auth.jwt');
Route::post('/student/essays/{id}/submit', [EssayController::class, 'submitEssay'])->middleware('auth.jwt');
Route::post('/essays/{id}/review', [EssayController::class, 'markAsReviewed']);

Route::get('/essay/get-reviews/{id}', [EssayReviewController::class, 'getEssaysWithReviews']);
Route::delete('/reviews/{id}', [EssayReviewController::class, 'deleteReview']);
