<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Board\StoreBoardRequest;
use App\Http\Requests\Board\UpdateBoardRequest;
use App\Services\BoardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardController extends ApiController
{
    public function __construct(private BoardService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'user_id' => Auth::id() ?? null,
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexWithLists(Request $request)
    {
        try {
            $data = $this->service->listWithLists([
                'user_id' => Auth::id() ?? null,
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreBoardRequest $request)
    {
        try {
            $board = $this->service->create($request->validated());
            return $this->showAfterAction($board, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $board = $this->service->findForUserOrFail($id, Auth::id());
            return $this->showOne($board);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateBoardRequest $request, int $id)
    {
        try {
            $board = $this->service->findOwnedOrFail($id, Auth::id());
            $board = $this->service->update($board, $request->all());
            return $this->showAfterAction($board, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $board = $this->service->findOwnedOrFail($id, Auth::id());
            $this->service->delete($board);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function members(int $id)
    {
        try {
            $board = $this->service->findForUserOrFail($id, Auth::id());
            return response()->json(['data' => $this->service->members($board)]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function addMember(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
            ]);
            $board = $this->service->findOwnedOrFail($id, Auth::id());
            return response()->json(['data' => $this->service->addMember($board, $data['email'])], 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function removeMember(int $id, int $userId)
    {
        try {
            $board = $this->service->findOwnedOrFail($id, Auth::id());
            return response()->json(['data' => $this->service->removeMember($board, (int) $userId)]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
