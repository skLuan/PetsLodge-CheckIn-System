<?php

namespace Tests\Feature;

use App\Models\TermsAndConditions;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TermsAndConditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Plan 01 — Terms & Conditions stored in the DB and editable from the pet-staff dashboard.
 *
 * Covers: schema, versioning rules, the pet-staff auth matrix, the public JSON endpoint
 * and the check-in popup rendering the active version.
 */
class TermsAndConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** Seed version 1 (the legally reviewed text) before each test. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TermsAndConditionsSeeder::class);
    }

    // ---------------------------------------------------------------------
    // Schema & model
    // ---------------------------------------------------------------------

    /** @test */
    public function the_terms_table_exists_with_the_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('terms_and_conditions'));

        $this->assertTrue(Schema::hasColumns('terms_and_conditions', [
            'id', 'title', 'content', 'version', 'is_active', 'updated_by', 'created_at', 'updated_at',
        ]));
    }

    /** @test */
    public function the_seeder_creates_version_one_as_the_active_version()
    {
        $this->assertSame(1, TermsAndConditions::count());

        $terms = TermsAndConditions::active()->first();

        $this->assertNotNull($terms);
        $this->assertSame(1, $terms->version);
        $this->assertTrue($terms->is_active);
        // The seeded content must be the real legal text, not a placeholder.
        $this->assertStringContainsString('Pet Lodge & Spa', $terms->content);
        $this->assertStringContainsString('Health and Vaccination Requirements', $terms->content);
    }

    /** @test */
    public function publish_new_version_increments_the_version_and_deactivates_the_previous_one()
    {
        $staff = User::factory()->petStaff()->create();

        TermsAndConditions::publishNewVersion('<p>Second</p>', $staff);
        TermsAndConditions::publishNewVersion('<p>Third</p>', $staff);

        $this->assertSame([1, 2, 3], TermsAndConditions::orderBy('version')->pluck('version')->all());

        // Exactly one active row, and it is the newest.
        $active = TermsAndConditions::where('is_active', true)->get();
        $this->assertCount(1, $active);
        $this->assertSame(3, $active->first()->version);
        $this->assertSame($staff->id, $active->first()->updated_by);
    }

    /** @test */
    public function old_versions_are_retained_unchanged_after_publishing_a_new_one()
    {
        $original = TermsAndConditions::active()->first();
        $originalContent = $original->content;

        TermsAndConditions::publishNewVersion('<p>Replacement text</p>', User::factory()->petStaff()->create());

        $original->refresh();

        // Audit trail: the row is kept verbatim, only deactivated.
        $this->assertSame($originalContent, $original->content);
        $this->assertSame(1, $original->version);
        $this->assertFalse($original->is_active);
    }

    // ---------------------------------------------------------------------
    // Auth matrix on the editor routes
    // ---------------------------------------------------------------------

    /** @test */
    public function a_guest_cannot_open_the_terms_editor()
    {
        $this->get(route('pet-staff.terms.edit'))->assertRedirect(route('login'));
    }

    /** @test */
    public function a_non_staff_user_cannot_open_the_terms_editor()
    {
        // PetStaffOnly redirects to "/" with an error rather than aborting 403.
        $this->actingAs(User::factory()->create()) // default role: CLIENT
            ->get(route('pet-staff.terms.edit'))
            ->assertRedirect('/');
    }

    /** @test */
    public function pet_staff_can_open_the_terms_editor_and_see_the_active_version()
    {
        $this->actingAs(User::factory()->petStaff()->create())
            ->get(route('pet-staff.terms.edit'))
            ->assertOk()
            ->assertSee('Health and Vaccination Requirements', false)
            ->assertSee('Version 1');
    }

    /** @test */
    public function a_guest_cannot_update_the_terms()
    {
        $this->put(route('pet-staff.terms.update'), ['content' => '<p>Hacked</p>'])
            ->assertRedirect(route('login'));

        $this->assertSame(1, TermsAndConditions::count());
    }

    /** @test */
    public function a_non_staff_user_cannot_update_the_terms()
    {
        $this->actingAs(User::factory()->create())
            ->put(route('pet-staff.terms.update'), ['content' => '<p>Hacked</p>'])
            ->assertRedirect('/');

        $this->assertSame(1, TermsAndConditions::count());
    }

    // ---------------------------------------------------------------------
    // Updating
    // ---------------------------------------------------------------------

    /** @test */
    public function pet_staff_updating_the_terms_creates_a_new_version_and_records_the_author()
    {
        $staff = User::factory()->petStaff()->create();

        $this->actingAs($staff)
            ->put(route('pet-staff.terms.update'), [
                'title' => 'Terms & Conditions',
                'content' => '<p>Version two of the legal text.</p>',
            ])
            ->assertRedirect(route('pet-staff.terms.edit'))
            ->assertSessionHas('success');

        $this->assertSame(2, TermsAndConditions::count());

        $active = TermsAndConditions::active()->first();
        $this->assertSame(2, $active->version);
        $this->assertStringContainsString('Version two of the legal text.', $active->content);
        $this->assertSame($staff->id, $active->updated_by);

        $this->assertFalse(TermsAndConditions::where('version', 1)->first()->is_active);
    }

    /** @test */
    public function updating_requires_content()
    {
        $this->actingAs(User::factory()->petStaff()->create())
            ->put(route('pet-staff.terms.update'), ['content' => ''])
            ->assertSessionHasErrors('content');

        $this->assertSame(1, TermsAndConditions::count());
    }

    /** @test */
    public function dangerous_markup_is_stripped_before_the_new_version_is_stored()
    {
        $this->actingAs(User::factory()->petStaff()->create())
            ->put(route('pet-staff.terms.update'), [
                'content' => '<p>Safe text</p><script>alert("xss")</script>',
            ]);

        $active = TermsAndConditions::active()->first();

        $this->assertStringContainsString('Safe text', $active->content);
        $this->assertStringNotContainsString('<script', $active->content);
        $this->assertStringNotContainsString('alert(', $active->content);
    }

    // ---------------------------------------------------------------------
    // Public endpoint + check-in popup
    // ---------------------------------------------------------------------

    /** @test */
    public function the_public_endpoint_returns_the_active_version_as_json()
    {
        $this->getJson('/api/terms/active')
            ->assertOk()
            ->assertJsonStructure(['title', 'content', 'version'])
            ->assertJson(['version' => 1]);
    }

    /** @test */
    public function the_public_endpoint_follows_the_active_version_after_an_update()
    {
        TermsAndConditions::publishNewVersion('<p>Latest published text</p>', User::factory()->petStaff()->create());

        $this->getJson('/api/terms/active')
            ->assertOk()
            ->assertJson(['version' => 2])
            ->assertJsonFragment(['content' => '<p>Latest published text</p>']);
    }

    /**
     * The T&C popup lives in `Process.blade.php` (the multi-step check-in form served at
     * `/new-form`), not on the phone-entry page at `/check-in`.
     *
     * @test
     */
    public function the_check_in_form_popup_renders_the_active_terms()
    {
        $this->get('/new-form')
            ->assertOk()
            ->assertSee('Health and Vaccination Requirements', false)
            ->assertSee('Governing Law and Dispute Resolution', false);
    }

    /** @test */
    public function the_check_in_form_popup_renders_an_edited_version_immediately()
    {
        TermsAndConditions::publishNewVersion(
            '<p>Freshly edited terms for the popup.</p>',
            User::factory()->petStaff()->create()
        );

        $this->get('/new-form')
            ->assertOk()
            ->assertSee('Freshly edited terms for the popup.', false)
            ->assertDontSee('Health and Vaccination Requirements', false);
    }

    /** @test */
    public function the_check_in_form_does_not_crash_when_no_active_version_exists()
    {
        TermsAndConditions::query()->update(['is_active' => false]);

        $this->get('/new-form')->assertOk();
    }

    // ---------------------------------------------------------------------
    // Dashboard entry point
    // ---------------------------------------------------------------------

    /** @test */
    public function the_pet_staff_dashboard_links_to_the_terms_editor()
    {
        // PetStaffDashboardController looks up CHECKED_IN / DROPPED_IN statuses.
        $this->seed(StatusSeeder::class);

        $this->actingAs(User::factory()->petStaff()->create())
            ->get(route('pet-staff.dashboard'))
            ->assertOk()
            ->assertSee(route('pet-staff.terms.edit'), false)
            ->assertSee('Terms &amp; Conditions', false);
    }
}
