<?php

namespace Tests\Feature\Security;

use App\Models\Approval;
use App\Models\Person;
use App\Models\Relationship;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════════════
    // 1. UNAUTHENTICATED
    // ══════════════════════════════════════════════════════════════════════

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_people_index(): void
    {
        $this->get('/people')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_people_create(): void
    {
        $this->get('/people/create')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_approvals(): void
    {
        $this->get('/approvals')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_admin_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_export(): void
    {
        $this->get('/export')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_tree(): void
    {
        $this->get('/tree')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_tree_api(): void
    {
        $this->get('/api/tree/data')->assertRedirect('/login');
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. ROLE-BASED ACCESS
    // ══════════════════════════════════════════════════════════════════════

    public function test_regular_user_cannot_access_approvals_page(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/approvals')
            ->assertRedirect('/dashboard');
    }

    public function test_regular_user_cannot_access_admin_users_page(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin/users')
            ->assertRedirect();
    }

    public function test_moderator_cannot_access_admin_users_page(): void
    {
        $this->actingAs($this->makeModerator())
            ->get('/admin/users')
            ->assertRedirect();
    }

    public function test_regular_user_cannot_access_export_page(): void
    {
        // ExportController pakai middleware can:export_data (Gate),
        // beda dari role:admin yang redirect — Gate middleware selalu 403.
        $this->actingAs($this->makeUser())
            ->get('/export')
            ->assertForbidden();
    }

    public function test_moderator_cannot_access_export_page(): void
    {
        $this->actingAs($this->makeModerator())
            ->get('/export')
            ->assertForbidden();
    }

    public function test_regular_user_cannot_download_people_csv(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/export/people/csv')
            ->assertForbidden();
    }

    public function test_moderator_cannot_download_people_csv(): void
    {
        $this->actingAs($this->makeModerator())
            ->get('/export/people/csv')
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 3. IDOR
    // ══════════════════════════════════════════════════════════════════════

    public function test_regular_user_cannot_approve_approval(): void
    {
        $user = $this->makeUser();
        $family = $this->makeFamily();
        $person = Person::factory()->pending()->create([
            'family_unit_id' => $family->id,
            'created_by' => $user->id,
        ]);
        $approval = Approval::factory()->pending()->create([
            'approvable_type' => Person::class,
            'approvable_id' => $person->id,
            'requested_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/approvals/{$approval->id}/approve", ['confirmation' => 'SETUJU'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'pending']);
    }

    public function test_regular_user_cannot_reject_approval(): void
    {
        $user = $this->makeUser();
        $family = $this->makeFamily();
        $person = Person::factory()->pending()->create([
            'family_unit_id' => $family->id,
            'created_by' => $user->id,
        ]);
        $approval = Approval::factory()->pending()->create([
            'approvable_type' => Person::class,
            'approvable_id' => $person->id,
            'requested_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/approvals/{$approval->id}/reject", ['reason' => 'coba-coba'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'pending']);
    }

    public function test_regular_user_cannot_change_any_user_role(): void
    {
        $user = $this->makeUser();
        $target = $this->makeModerator();

        $this->actingAs($user)
            ->patch("/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertRedirect();

        $this->assertTrue($target->fresh()->hasRole('moderator'));
    }

    public function test_regular_user_cannot_delete_person_created_by_others(): void
    {
        $user = $this->makeUser();
        $family = $this->makeFamily();
        $creator = $this->makeUser();
        $person = Person::factory()->active()->create([
            'family_unit_id' => $family->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($user)
            ->delete("/people/{$person->id}")
            ->assertForbidden();
    }

    public function test_moderator_cannot_approve_relationship_directly(): void
    {
        $moderator = $this->makeModerator();
        $family = $this->makeFamily();
        $p1 = Person::factory()->active()->create(['family_unit_id' => $family->id]);
        $p2 = Person::factory()->active()->create(['family_unit_id' => $family->id]);

        $relation = Relationship::create([
            'subject_id' => $p1->id,
            'object_id' => $p2->id,
            'type' => 'parent',
            'status' => 'pending',
            'is_biological' => true,
            'created_by' => $moderator->id,
        ]);

        $this->actingAs($moderator)
            ->post("/relationships/{$relation->id}/approve")
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 4. MASS ASSIGNMENT
    // ══════════════════════════════════════════════════════════════════════

    public function test_moderator_cannot_inject_status_active_when_creating_person(): void
    {
        $moderator = $this->makeModerator();
        $family = $this->makeFamily();

        $this->actingAs($moderator)->post('/people', [
            'display_name' => 'Injeksi Test',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'birth_accuracy' => 'exact',
            'death_date' => null,
            'death_accuracy' => 'unknown',
            'family_unit_id' => $family->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('people', ['display_name' => 'Injeksi Test', 'status' => 'pending']);
        $this->assertDatabaseMissing('people', ['display_name' => 'Injeksi Test', 'status' => 'active']);
    }

    public function test_moderator_update_creates_pending_approval_not_direct_change(): void
    {
        // withoutExceptionHandling() untuk surface exception tersembunyi
        $this->withoutExceptionHandling();

        $moderator = $this->makeModerator();
        $family = $this->makeFamily();

        // Moderator harus terdaftar sebagai pengelola family unit ini
        // supaya PersonPolicy::update() mengizinkan (lihat User::managesUnit()).
        \Illuminate\Support\Facades\DB::table('moderator_family_units')->insert([
            'user_id' => $moderator->id,
            'family_unit_id' => $family->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $person = Person::factory()->active()->create([
            'family_unit_id' => $family->id,
            'created_by' => $moderator->id,
            'display_name' => 'Nama Original',
            'gender' => 'male',
            'birth_date' => '1985-06-15',
            'birth_accuracy' => 'exact',
            'death_date' => null,
            'death_accuracy' => 'unknown',
        ]);

        // Kirim update dengan display_name BERBEDA - pasti ada perubahan
        $response = $this->actingAs($moderator)->patch("/people/{$person->id}", [
            'display_name' => 'Nama Setelah Edit',  // berbeda dari 'Nama Original'
            'gender' => 'male',
            'birth_date' => '1985-06-15',         // SAMA - tidak berubah
            'birth_accuracy' => 'exact',
            'death_date' => null,
            'death_accuracy' => 'unknown',
            'family_unit_id' => $family->id,
            'edit_reason' => 'Koreksi nama yang salah tulis',
        ]);

        // Response harus redirect (bukan 422 validation error atau 403)
        $response->assertRedirect();

        // Person asli tidak berubah langsung - approval workflow berlaku
        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'display_name' => 'Nama Original',
        ]);

        // Approval pending harus terbuat
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Person::class,
            'approvable_id' => $person->id,
            'action' => 'update',
            'status' => 'pending',
            'requested_by' => $moderator->id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 5. SELF-MODIFICATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}/role", ['role' => 'user'])
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertFalse($admin->fresh()->hasRole('user'));
    }

    public function test_admin_cannot_send_password_reset_to_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/reset-password")
            ->assertRedirect()
            ->assertSessionHasErrors('reset');
    }

    // ══════════════════════════════════════════════════════════════════════
    // 6. EXPORT
    // ══════════════════════════════════════════════════════════════════════

    public function test_admin_can_access_all_export_endpoints(): void
    {
        $admin = $this->makeAdmin();
        $family = $this->makeFamily();
        Person::factory()->active()->count(3)->create(['family_unit_id' => $family->id]);

        $this->actingAs($admin)->get('/export/people/csv')->assertOk();
        $this->actingAs($admin)->get('/export/relations/csv')->assertOk();
        $this->actingAs($admin)->get('/export/statistics/csv')->assertOk();
    }

    public function test_person_pdf_export_requires_authentication(): void
    {
        $family = $this->makeFamily();
        $person = Person::factory()->active()->create(['family_unit_id' => $family->id]);

        $this->get("/export/people/{$person->id}/pdf")->assertRedirect('/login');
    }

    public function test_person_pdf_accessible_to_any_logged_in_user(): void
    {
        $user = $this->makeUser();
        $family = $this->makeFamily();
        $person = Person::factory()->active()->create(['family_unit_id' => $family->id]);

        $this->actingAs($user)->get("/export/people/{$person->id}/pdf")->assertOk();
    }
}
