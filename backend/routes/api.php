<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Comments\CommentsController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::apiResource('boards', BoardController::class);
    Route::apiResource('lists', ListController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::get('lists/{listId}/tasks', [TaskController::class, 'indexByList']);
    Route::apiResource('subtasks', SubtaskController::class);
    Route::get('tasks/{taskId}/subtasks', [SubtaskController::class, 'indexByTask']);
    Route::apiResource('task-dependencies', TaskDependencyController::class);
    Route::get('tasks/{taskId}/dependencies', [TaskDependencyController::class, 'indexByTask']);
    Route::apiResource('comments', CommentsController::class);
});
// Extra endpoints
Route::middleware('auth:sanctum')->get('lists-with-tasks', [ListController::class, 'indexWithTasks']);
Route::middleware('auth:sanctum')->get('boards-with-lists', [BoardController::class, 'indexWithLists']);
Route::middleware('auth:sanctum')->get('boards/{boardId}/lists', [ListController::class, 'indexByBoard']);
