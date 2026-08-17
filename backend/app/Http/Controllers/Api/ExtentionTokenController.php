<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ExtentionToken\StoreExtentionTokenRequest;
use App\Http\Requests\ExtentionToken\UpdateExtentionTokenRequest;
use App\Services\ExtentionTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExtentionTokenController extends ApiController
{
    public function __construct(private ExtentionTokenService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'user_id' => Auth::id() ?? null,
                'revoked' => $request->query('revoked'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreExtentionTokenRequest $request)
    {
        try {
            $token = $this->service->create($request->validated());
            return $this->showAfterAction($token, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $token = $this->service->findOrFail($id);
            return $this->showOne($token);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateExtentionTokenRequest $request, int $id)
    {
        try {
            $token = $this->service->findOrFail($id);
            $token = $this->service->update($token, $request->all());
            return $this->showAfterAction($token, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $token = $this->service->findOrFail($id);
            $this->service->delete($token);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function issue(Request $request)
    {
        try {
            $data = $request->validate([
                'label' => ['nullable', 'string', 'max:100'],
            ]);
            $token = $this->service->issue($request->user(), $data['label'] ?? null);
            return $this->showAfterAction($token, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function revoke(int $id)
    {
        try {
            $token = $this->service->findOrFail($id);
            $token = $this->service->revoke($token);
            return $this->showAfterAction($token, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function touchLastUsed(int $id)
    {
        try {
            $token = $this->service->findOrFail($id);
            $token = $this->service->touchLastUsed($token);
            return $this->showAfterAction($token, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function resolve(Request $request)
    {
        try {
            $data = $request->validate([
                'raw' => ['required', 'string'],
            ]);
            $token = $this->service->resolveFromRawToken($data['raw']);
            if (!$token) {
                return $this->errorResponse('Token inválido o revocado', 404);
            }
            return $this->showOne($token);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}