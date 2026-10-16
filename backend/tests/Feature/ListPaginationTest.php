<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->string('status')->default('untested');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('component_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('depends_on_id');
            $table->string('criticality');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('status');
            $table->string('severity');
            $table->unsignedBigInteger('test_case_id')->nullable();
            $table->unsignedBigInteger('test_step_id')->nullable();
            $table->unsignedBigInteger('reported_by_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('components')->insert([
            ['id' => 1, 'name' => 'Billing', 'type' => 'service', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'name' => 'Catalog', 'type' => 'module', 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00'],
            ['id' => 3, 'name' => 'Accounts', 'type' => 'service', 'created_at' => '2026-01-03 00:00:00', 'updated_at' => '2026-01-03 00:00:00'],
        ]);

        DB::table('test_cases')->insert([
            ['id' => 1, 'title' => 'Billing check one', 'component_id' => 1, 'status' => 'passed', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'title' => 'Catalog check', 'component_id' => 2, 'status' => 'passed', 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00'],
            ['id' => 3, 'title' => 'Billing check two', 'component_id' => 1, 'status' => 'passed', 'created_at' => '2026-01-03 00:00:00', 'updated_at' => '2026-01-03 00:00:00'],
            ['id' => 4, 'title' => 'Billing check three', 'component_id' => 1, 'status' => 'passed', 'created_at' => '2026-01-04 00:00:00', 'updated_at' => '2026-01-04 00:00:00'],
        ]);

        DB::table('bugs')->insert([
            ['id' => 1, 'title' => 'Billing bug one', 'description' => '', 'status' => 'open', 'severity' => 'low', 'test_case_id' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'title' => 'Catalog bug', 'description' => '', 'status' => 'open', 'severity' => 'low', 'test_case_id' => 2, 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00'],
            ['id' => 3, 'title' => 'Billing bug two', 'description' => '', 'status' => 'open', 'severity' => 'low', 'test_case_id' => 3, 'created_at' => '2026-01-03 00:00:00', 'updated_at' => '2026-01-03 00:00:00'],
            ['id' => 4, 'title' => 'Billing bug three', 'description' => '', 'status' => 'open', 'severity' => 'low', 'test_case_id' => 4, 'created_at' => '2026-01-04 00:00:00', 'updated_at' => '2026-01-04 00:00:00'],
        ]);

        DB::table('component_dependencies')->insert([
            ['id' => 1, 'component_id' => 1, 'depends_on_id' => 2, 'criticality' => 'critical', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'component_id' => 3, 'depends_on_id' => 2, 'criticality' => 'critical', 'deleted_at' => '2026-01-02 00:00:00', 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00'],
        ]);

        Sanctum::actingAs(new User());
    }

    public function test_bugs_are_filtered_by_component_before_pagination(): void
    {
        $this->getJson('/api/bugs?component_id=1&per_page=2&page=1')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('data.0.id', 4);

        $this->getJson('/api/bugs?component_id=1&per_page=2&page=2')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_components_are_filtered_by_live_critical_dependencies_before_pagination(): void
    {
        $this->getJson('/api/components?criticality=critical&per_page=2&page=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', 1);

        $this->getJson('/api/components?criticality=optional&per_page=2&page=1')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_test_cases_keep_component_and_status_filters_before_pagination(): void
    {
        $this->getJson('/api/test-cases?component_id=1&status=passed&per_page=2&page=1')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('data.0.id', 4);
    }

    public function test_empty_list_response_does_not_require_pagination_metadata(): void
    {
        $this->getJson('/api/test-cases?component_id=999')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}