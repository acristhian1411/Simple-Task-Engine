<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\List\StoreListRequest;
use App\Http\Requests\List\UpdateListRequest;
use App\Services\ListService;
use Illuminate\Http\Request;

class ListController extends ApiController
{
    public function __construct(private ListService $service)
    {
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'board_id' => $request->query('board_id'),
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
            $list = $this->service->create($request->validated());
            return $this->showAfterAction($list, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $list = $this->service->findOrFail($id);
            return $this->showOne($list);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateListRequest $request, int $id)
    {
        try {
            $list = $this->service->findOrFail($id);
            $list = $this->service->update($list, $request->all());
            return $this->showAfterAction($list, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $list = $this->service->findOrFail($id);
            $this->service->delete($list);
            return $this->showMessage('Registro eliminado correctamente');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}