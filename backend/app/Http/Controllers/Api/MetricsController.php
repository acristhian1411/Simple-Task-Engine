<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\MetricsService;
use Illuminate\Http\Request;

class MetricsController extends ApiController
{
    public function __construct(private MetricsService $service) {}

    public function tasksByComponent()
    {
        try {
            $data = $this->service->tasksByComponent();
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function bugsBySeverity()
    {
        try {
            $data = $this->service->bugsBySeverity();
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function blockedTasks()
    {
        try {
            $data = $this->service->blockedTasks();
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function componentsWithUnresolvedCriticalDeps()
    {
        try {
            $data = $this->service->componentsWithUnresolvedCriticalDeps();
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function activityByUser(Request $request)
    {
        try {
            $data = $this->service->activityByUser([
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ]);
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}