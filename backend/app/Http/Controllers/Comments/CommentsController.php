<?php

namespace App\Http\Controllers\Comments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CommentService;

class CommentsController extends Controller
{
    public function __construct(private CommentService $service)
    {
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
            return response()->json(['error' => $e->getMessage()], 500);
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
            $comment = $this->service->create($data);
            return response()->json($comment, 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            return response()->json($comment);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            $data = $request->validate([
                'content' => 'sometimes|required|string',
            ]);
            $updatedComment = $this->service->update($comment, $data);
            return response()->json($updatedComment);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $comment = $this->service->findOrFail($id);
            $this->service->delete($comment);
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
