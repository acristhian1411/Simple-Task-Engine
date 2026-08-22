<?php
namespace App\Http\Controllers\Audits;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Services\Audits\AuditService;
class AuditController extends ApiController
{
    protected AuditService $auditService;
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function getAuditByAuditableId(Request $request, $auditableId)
    {
        try {
            $auditableType = $request->query('auditable_type');
            $data = $this->auditService->getAuditByAuditableId($auditableId, $auditableType);
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}