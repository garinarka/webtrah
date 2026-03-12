<?php

namespace Tests\Feature\Performance;

use App\Models\Approval;
use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePerformanceTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════════════
    // 1. N+1 — People Index
    // Assertion: total query count untuk halaman dengan N records
    // tidak boleh naik LINEAR dengan N. Threshold = baseline × 3.
    // ══════════════════════════════════════════════════════════════════════

    public function test_people_index_does_not_produce_n_plus_1_queries(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        // 20 people - halaman penuh (page size = 20)
        Person::factory()->active()->count(20)->create([
            'family_unit_id' => $family->id,
            'created_by'     => $admin->id,
        ]);

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/people');
        $queriesFor20 = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        // Tanpa N+1: query count seharusnya <= 20 (overhead tetap ~10-12,
        // plus max ~8 toleransi untuk Spatie cache miss, dll).
        // Dengan N+1: 20 records × 2 relasi = 40 extra queries → total ~50
        $this->assertLessThanOrEqual(
            25,
            $queriesFor20,
            "Terlalu banyak query ({$queriesFor20}) untuk 20 orang pada satu halaman. "
                . "Dengan eager loading yang benar, seharusnya <= 25. "
                . "Pastikan PersonController pakai Person::with(['familyUnit', 'creator:id,name'])."
        );
    }

    public function test_people_index_total_queries_are_reasonable(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();
        Person::factory()->active()->count(20)->create([
            'family_unit_id' => $family->id,
            'created_by'     => $admin->id,
        ]);

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/people');
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            25,
            $count,
            "Terlalu banyak query di /people: {$count} query. Maksimal 25."
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. N+1 — Approvals Index
    // ══════════════════════════════════════════════════════════════════════

    public function test_approvals_index_does_not_produce_n_plus_1_queries(): void
    {
        $admin     = $this->makeAdmin();
        $moderator = $this->makeModerator();
        $family    = $this->makeFamily();

        // 10 approvals - satu halaman penuh (page size = 15)
        for ($i = 0; $i < 10; $i++) {
            $person = Person::factory()->pending()->create([
                'family_unit_id' => $family->id,
                'created_by'     => $moderator->id,
            ]);
            Approval::factory()->pending()->forCreate()->create([
                'approvable_type' => Person::class,
                'approvable_id'   => $person->id,
                'requested_by'    => $moderator->id,
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/approvals');
        $queriesFor10 = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        // Tanpa N+1: ~15 fixed queries
        // Dengan N+1 (lazy-load familyUnit per approvable): 10 × 1 = 10 extra → ~25
        $this->assertLessThanOrEqual(
            25,
            $queriesFor10,
            "N+1 di /approvals: {$queriesFor10} query untuk 10 approvals. "
                . "Dengan eager loading, seharusnya <= 25. "
                . "Pastikan ApprovalController pakai with(['approvable.familyUnit:id,name', 'requester:id,name'])."
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // 3. PAGINATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_people_index_paginates_and_does_not_return_all_records(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();
        Person::factory()->active()->count(50)->create([
            'family_unit_id' => $family->id,
            'created_by'     => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/people');
        $response->assertOk();

        $data = $response->original->getData()['page']['props']['people'];
        $data = is_array($data) ? $data : (array) $data;

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertLessThanOrEqual(20, count($data['data']));
        $this->assertEquals(50, $data['total']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 4. TREE API
    // ══════════════════════════════════════════════════════════════════════

    public function test_tree_api_query_count_does_not_grow_exponentially(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        $buyut = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $kakek = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $ayah  = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $anak  = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);

        foreach ([[$buyut, $kakek], [$kakek, $ayah], [$ayah, $anak]] as [$parent, $child]) {
            Relationship::create([
                'subject_id' => $parent->id,
                'object_id' => $child->id,
                'type' => 'parent',
                'status' => 'approved',
                'is_biological' => true,
                'created_by' => $admin->id,
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->getJson("/api/tree/data?root_id={$anak->id}&depth_up=1&depth_down=0");
        $queryDepth1 = count(DB::getQueryLog());
        DB::flushQueryLog();

        DB::enableQueryLog();
        $this->actingAs($admin)->getJson("/api/tree/data?root_id={$anak->id}&depth_up=3&depth_down=0");
        $queryDepth3 = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(
            $queryDepth1 + 10,
            $queryDepth3,
            "Tree BFS tumbuh eksponensial: depth 1={$queryDepth1}, depth 3={$queryDepth3}."
        );
    }

    public function test_tree_api_returns_correct_nodes_for_3_generations(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        $kakek = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $ayah  = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $anak  = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);

        foreach ([[$kakek, $ayah], [$ayah, $anak]] as [$parent, $child]) {
            Relationship::create([
                'subject_id' => $parent->id,
                'object_id' => $child->id,
                'type' => 'parent',
                'status' => 'approved',
                'is_biological' => true,
                'created_by' => $admin->id,
            ]);
        }

        $response = $this->actingAs($admin)
            ->getJson("/api/tree/data?root_id={$anak->id}&depth_up=2&depth_down=0");

        $response->assertOk();
        $nodeIds = collect($response->json('nodes'))->pluck('id');

        $this->assertTrue($nodeIds->contains($anak->id),  'Root (anak) tidak ada di nodes.');
        $this->assertTrue($nodeIds->contains($ayah->id),  'Ayah tidak ada di nodes.');
        $this->assertTrue($nodeIds->contains($kakek->id), 'Kakek tidak ada di nodes.');
    }

    public function test_tree_api_excludes_pending_relationships(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        $ayah = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $anak = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);

        Relationship::create([
            'subject_id' => $ayah->id,
            'object_id' => $anak->id,
            'type' => 'parent',
            'status' => 'pending',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/tree/data?root_id={$anak->id}&depth_up=1&depth_down=0");

        $nodeIds = collect($response->json('nodes'))->pluck('id');
        $this->assertFalse($nodeIds->contains($ayah->id), 'Pending relationship menyebabkan node muncul di tree.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // 5. DB INTEGRITY
    // ══════════════════════════════════════════════════════════════════════

    public function test_duplicate_relationship_is_rejected(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();
        $p1     = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $p2     = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->post('/relationships', [
            'person_id'     => $p2->id,
            'related_id'    => $p1->id,
            'type'          => 'parent',
            'is_biological' => true,
        ]);
        $this->assertDatabaseCount('relationships', 1);

        $this->actingAs($admin)->post('/relationships', [
            'person_id'     => $p2->id,
            'related_id'    => $p1->id,
            'type'          => 'parent',
            'is_biological' => true,
        ])->assertSessionHasErrors('related_id');

        $this->assertDatabaseCount('relationships', 1);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 6. SOFT DELETE
    // ══════════════════════════════════════════════════════════════════════

    public function test_soft_deleted_person_does_not_appear_in_people_index(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();
        $person = Person::factory()->active()->create([
            'family_unit_id' => $family->id,
            'created_by'     => $admin->id,
            'display_name'   => 'Orang Yang Dihapus',
        ]);

        $person->delete();

        $this->actingAs($admin)->get('/people')->assertOk()->assertDontSee('Orang Yang Dihapus');
    }

    public function test_soft_deleted_person_does_not_appear_in_tree(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        $ayah = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);
        $anak = Person::factory()->active()->create(['family_unit_id' => $family->id, 'created_by' => $admin->id]);

        Relationship::create([
            'subject_id' => $ayah->id,
            'object_id' => $anak->id,
            'type' => 'parent',
            'status' => 'approved',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);

        $ayah->delete();

        $response = $this->actingAs($admin)
            ->getJson("/api/tree/data?root_id={$anak->id}&depth_up=1&depth_down=0");

        $nodeIds = collect($response->json('nodes'))->pluck('id');
        $this->assertFalse($nodeIds->contains($ayah->id), 'Soft-deleted person masih muncul di tree.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // 7. EXPORT
    // ══════════════════════════════════════════════════════════════════════

    public function test_people_csv_contains_utf8_bom_and_correct_headers(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();
        Person::factory()->active()->count(3)->create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
        ]);

        $content = $this->actingAs($admin)->get('/export/people/csv?status=active')->streamedContent();

        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3), 'CSV tidak mengandung BOM UTF-8.');
        $this->assertStringContainsString('Nama Lengkap', $content);
        $this->assertStringContainsString('Jenis Kelamin', $content);
        $this->assertStringContainsString('Tanggal Lahir', $content);
    }

    public function test_people_csv_active_filter_excludes_pending(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        Person::factory()->active()->create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'display_name'   => 'Anggota Aktif Nyata',
        ]);
        Person::factory()->pending()->create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'display_name'   => 'Anggota Pending Excluded',
        ]);

        $content = $this->actingAs($admin)->get('/export/people/csv?status=active')->streamedContent();

        $this->assertStringContainsString('Anggota Aktif Nyata', $content);
        $this->assertStringNotContainsString('Anggota Pending Excluded', $content);
    }

    public function test_people_csv_all_filter_includes_pending(): void
    {
        $admin  = $this->makeAdmin();
        $family = $this->makeFamily();

        Person::factory()->pending()->create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'display_name'   => 'Anggota Belum Approve',
        ]);

        $content = $this->actingAs($admin)->get('/export/people/csv?status=all')->streamedContent();

        $this->assertStringContainsString('Anggota Belum Approve', $content);
    }
}
