<?php
namespace App\Http\Services\Audits;
use App\Models\Audit;
use Illuminate\Database\Eloquent\Collection;
class AuditService
{
    public function getAll(): Collection
    {
        return Audit::all();
    }

    public function getById($id): ?Audit
    {
        return Audit::find($id);
    }

    public function getAuditByAuditableId($auditableId, $auditableType)
    {
        $eventLabels = [
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
        ];

        return Audit::where('auditable_id', $auditableId)
            ->where('auditable_type', 'ilike', "%$auditableType%")
            ->join('users', 'audits.user_id', '=', 'users.id')
            ->select('audits.*', 'users.name as username')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) use ($eventLabels) {
                $oldValues = is_string($audit->old_values)
                    ? json_decode($audit->old_values, true) ?? []
                    : ($audit->old_values ?? []);
                $newValues = is_string($audit->new_values)
                    ? json_decode($audit->new_values, true) ?? []
                    : ($audit->new_values ?? []);

                $cambios = [];
                foreach ($newValues as $campo => $nuevoValor) {
                    $viejoValor = $oldValues[$campo] ?? null;
                    if ($viejoValor !== null) {
                        $cambios[] = "El campo '{$campo}' cambió de '{$viejoValor}' a '{$nuevoValor}'";
                    } else {
                        $cambios[] = "El campo '{$campo}' fue establecido en '{$nuevoValor}'";
                    }
                }

                return [
                    'usuario' => $audit->username,
                    'id' => $audit->id,
                    'fecha' => $audit->created_at?->format('d/m/Y H:i:s'),
                    'evento' => $eventLabels[$audit->event] ?? ucfirst($audit->event),
                    'usuario_id' => $audit->user_id,
                    'cambios' => $cambios ?: ['Sin cambios registrados'],
                    'ip' => $audit->ip_address,
                ];
            })
        ;
    }

}