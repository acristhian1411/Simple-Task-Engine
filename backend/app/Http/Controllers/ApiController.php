<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponser;

class ApiController extends Controller
{
    //
    use ApiResponser;
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Standardized exception responder for controllers.
     *
     * @param \Throwable $e
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondException(\Throwable $e, int $code = 500)
    {
        $message = $e->getMessage();

        // If it's a model not found exception, return 404
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $code = 404;
            $message = 'Recurso no encontrado.';
        }

        return $this->errorResponse($message, $code);
    }
}
