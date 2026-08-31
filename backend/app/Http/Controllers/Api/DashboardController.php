<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\DashboardService;

class DashboardController extends ApiController
{
    public function __construct(private DashboardService $service) {}

    public function index()
    {
        try {
            return response()->json(['data' => $this->service->dashboard()]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
