<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\List\StoreListRequest;
use App\Http\Requests\List\UpdateListRequest;
use App\Services\BoardAccessService;
use App\Services\ListService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListController extends ApiController
{
    public function __construct(
        private ListService $service,
        private BoardAccessService $access,
    ) {
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'board_id' => $request->query('board_id'),
                'user_id' => Auth::id() ?? null,
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByBoard(int $boardId)
    {
        try {
            $this->access->assertBoard($boardId, Auth::id());
            $data = $this->service->list([
                'board_id' => $boardId,
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexWithTasks(Request $request)
    {
        try {
            $data = $this->service->listWithTasks([
                'board_id' => $request->query('board_id'),
                'user_id' => Auth::id() ?? null,
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreListRequest $request)
    {
        try {
            $data = $request->validated();
            $this->access->assertBoard((int) $data['board_id'], Auth::id());
            $list = $this->service->create($data);
            return $this->showAfterAction($list, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $this->access->assertList($id, Auth::id());
            $list = $this->service->findOrFail($id);
            return $this->showOne($list);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateListRequest $request, int $id)
    {
        try {
            $this->access->assertList($id, Auth::id());
            $data = $request->all();
            if (!empty($data['board_id'])) {
                $this->access->assertBoard((int) $data['board_id'], Auth::id());
            }
            $list = $this->service->findOrFail($id);
            $list = $this->service->update($list, $data);
            return $this->showAfterAction($list, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->access->assertList($id, Auth::id());
            $list = $this->service->findOrFail($id);
            $this->service->delete($list);
            return $this->showMessage('Registro eliminado correctamente');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
