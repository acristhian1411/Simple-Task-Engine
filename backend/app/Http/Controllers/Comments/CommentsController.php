<?php

namespace App\Http\Controllers\Comments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\BoardAccessService;
use App\Services\CommentService;
use App\Models\Bugs;
use App\Models\Components;
use App\Models\Task;
use App\Models\TestCases;

class CommentsController extends Controller
{
    public function __construct(
        private CommentService $service,
        private BoardAccessService $access,
    ) {
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'user_id' => Auth::id() ?? null,
                'search' => $request->query('search'),
            ]);
            return response()->json($data);
        } catch (\Throwable $e) {
            $code = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'content' => 'required|string',
                'commentable_type' => 'required|string',
                'commentable_id' => 'required|integer',
            ]);
            $this->access->assertCommentable($data['commentable_type'], (int) $data['commentable_id'], Auth::id());
            $comment = $this->service->create($data);
            return response()->json($comment, 201);
        } catch (\Throwable $e) {
            $code = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    public function show(int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            $this->access->assertCommentable($comment->commentable_type, (int) $comment->commentable_id, Auth::id());
            return response()->json($comment);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            $this->access->assertCommentable($comment->commentable_type, (int) $comment->commentable_id, Auth::id());
            if ($comment->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            $data = $request->validate([
                'content' => 'sometimes|required|string',
            ]);
            $updatedComment = $this->service->update($comment, $data);
            return response()->json($updatedComment);
        } catch (\Throwable $e) {
            $code = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    public function destroy(int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            $this->access->assertCommentable($comment->commentable_type, (int) $comment->commentable_id, Auth::id());
            if ($comment->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            $this->service->delete($comment);
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            $code = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    public function forModel(string $type, int $id)
    {
        try {
            $model = match ($type) {
                'task', 'tasks' => Task::findOrFail($id),
                'bug', 'bugs' => Bugs::findOrFail($id),
                'test-case', 'test-cases', 'test_case' => TestCases::findOrFail($id),
                'component', 'components' => Components::findOrFail($id),
                default => throw new \InvalidArgumentException("Tipo no soportado: {$type}"),
            };
            if ($model instanceof Task) {
                $this->access->assertTask($model->id, Auth::id());
            }
            $data = $this->service->forModel($model);
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
