<?php

namespace Tests\Unit;

use App\Models\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use PHPUnit\Framework\TestCase;

class AuditModelTest extends TestCase
{
    public function test_audit_records_are_not_auditable(): void
    {
        $this->assertNotInstanceOf(Auditable::class, new Audit());
    }
}
