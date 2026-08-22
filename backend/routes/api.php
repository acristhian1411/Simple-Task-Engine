<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Audits\AuditController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\ComponentController;
use App\Http\Controllers\Api\ComponentDependencyController;
use App\Http\Controllers\Api\BugController;
use App\Http\Controllers\Api\ExtentionTokenController;
use App\Http\Controllers\Api\TestCaseController;
use App\Http\Controllers\Api\TestStepController;
use App\Http\Controllers\Api\TestCaseActorController;
use App\Http\Controllers\Api\RecordingController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Comments\CommentsController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::group(['prefix' => 'audits'], function (): void {
        Route::get('/auditable/{auditableId}', [AuditController::class, 'getAuditByAuditableId']);
    });
    Route::apiResource('boards', BoardController::class);
    Route::apiResource('lists', ListController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::get('lists/{listId}/tasks', [TaskController::class, 'indexByList']);
    Route::apiResource('subtasks', SubtaskController::class);
    Route::get('tasks/{taskId}/subtasks', [SubtaskController::class, 'indexByTask']);
    Route::apiResource('task-dependencies', TaskDependencyController::class);
    Route::get('tasks/{taskId}/dependencies', [TaskDependencyController::class, 'indexByTask']);
    Route::apiResource('comments', CommentsController::class);
    Route::get('comments/for/{type}/{id}', [CommentsController::class, 'forModel']);

    // Tasks: vínculos cruzados
    Route::post('tasks/{id}/components', [TaskController::class, 'attachComponent']);
    Route::delete('tasks/{id}/components/{componentId}', [TaskController::class, 'detachComponent']);
    Route::put('tasks/{id}/components', [TaskController::class, 'syncComponents']);
    Route::post('tasks/{id}/bugs', [TaskController::class, 'attachBug']);
    Route::delete('tasks/{id}/bugs/{bugId}', [TaskController::class, 'detachBug']);
    Route::put('tasks/{id}/bugs', [TaskController::class, 'syncBugs']);
    Route::get('tasks/{id}/blocked-by', [TaskController::class, 'blockedBy']);

    // Components (las rutas literales van antes del apiResource para ganarle a {component})
    Route::get('components/tree', [ComponentController::class, 'tree']);
    Route::apiResource('components', ComponentController::class);
    Route::get('components/{componentId}/children', [ComponentController::class, 'indexByParent']);
    Route::get('components/{componentId}/dependencies', [ComponentController::class, 'dependencies']);
    Route::get('components/{componentId}/dependents', [ComponentController::class, 'dependents']);
    Route::get('components/{componentId}/critical-dependents', [ComponentController::class, 'criticalDependents']);
    Route::get('components/{componentId}/impact', [ComponentController::class, 'impact']);
    Route::get('components/{componentId}/tasks', [ComponentController::class, 'tasks']);
    Route::get('components/{componentId}/test-cases', [ComponentController::class, 'testCases']);
    Route::post('components/{componentId}/dependencies', [ComponentController::class, 'attachDependency']);
    Route::delete('components/{componentId}/dependencies/{dependsOnId}', [ComponentController::class, 'detachDependency']);

    Route::apiResource('component-dependencies', ComponentDependencyController::class);

    // Bugs (rutas literales antes del apiResource)
    Route::get('bugs/status/{status}', [BugController::class, 'listByStatus']);
    Route::get('bugs/severity/{severity}', [BugController::class, 'listBySeverity']);
    Route::apiResource('bugs', BugController::class);
    Route::patch('bugs/{id}/status', [BugController::class, 'changeStatus']);
    Route::post('bugs/{id}/tasks', [BugController::class, 'linkTask']);
    Route::delete('bugs/{id}/tasks/{taskId}', [BugController::class, 'unlinkTask']);
    Route::get('bugs/{id}/tasks', [BugController::class, 'tasks']);
    Route::post('bugs/{id}/recordings', [BugController::class, 'attachRecording']);

    // Extension tokens
    Route::post('extension-tokens/issue', [ExtentionTokenController::class, 'issue']);
    Route::post('extension-tokens/resolve', [ExtentionTokenController::class, 'resolve']);
    Route::apiResource('extension-tokens', ExtentionTokenController::class);
    Route::post('extension-tokens/{id}/revoke', [ExtentionTokenController::class, 'revoke']);
    Route::post('extension-tokens/{id}/touch', [ExtentionTokenController::class, 'touchLastUsed']);

    // Recordings
    Route::apiResource('recordings', RecordingController::class);
    Route::post('recordings/{id}/attach', [RecordingController::class, 'attachTo']);
    Route::post('recordings/{id}/complete', [RecordingController::class, 'markCompleted']);
    Route::post('recordings/{id}/failed', [RecordingController::class, 'markFailed']);

    // Test cases (rutas literales antes del apiResource)
    Route::get('test-cases/with-steps', [TestCaseController::class, 'indexWithSteps']);
    Route::apiResource('test-cases', TestCaseController::class);
    Route::get('test-cases/{testCaseId}/test-steps', [TestStepController::class, 'indexByTestCase']);
    Route::get('test-cases/{testCaseId}/actors', [TestCaseActorController::class, 'indexByTestCase']);
    Route::get('test-cases/{testCaseId}/bugs', [TestCaseController::class, 'bugs']);
    Route::post('test-cases/{testCaseId}/steps', [TestCaseController::class, 'addStep']);
    Route::put('test-cases/{testCaseId}/steps/reorder', [TestCaseController::class, 'reorderSteps']);
    Route::post('test-cases/{testCaseId}/actors', [TestCaseController::class, 'addActor']);
    Route::post('test-cases/{testCaseId}/duplicate', [TestCaseController::class, 'duplicate']);
    Route::patch('test-cases/{testCaseId}/status', [TestCaseController::class, 'markStatus']);

    Route::apiResource('test-steps', TestStepController::class);
    Route::apiResource('test-case-actors', TestCaseActorController::class);
});

// Extra endpoints
Route::middleware('auth:sanctum')->get('lists-with-tasks', [ListController::class, 'indexWithTasks']);
Route::middleware('auth:sanctum')->get('boards-with-lists', [BoardController::class, 'indexWithLists']);
Route::middleware('auth:sanctum')->get('boards/{boardId}/lists', [ListController::class, 'indexByBoard']);

// Metrics
Route::middleware('auth:sanctum')->get('metrics/tasks-by-component', [MetricsController::class, 'tasksByComponent']);
Route::middleware('auth:sanctum')->get('metrics/bugs-by-severity', [MetricsController::class, 'bugsBySeverity']);
Route::middleware('auth:sanctum')->get('metrics/blocked-tasks', [MetricsController::class, 'blockedTasks']);
Route::middleware('auth:sanctum')->get('metrics/components-with-unresolved-critical-deps', [MetricsController::class, 'componentsWithUnresolvedCriticalDeps']);
Route::middleware('auth:sanctum')->get('metrics/activity-by-user', [MetricsController::class, 'activityByUser']);