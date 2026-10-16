<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TherapyFollowUpNotification;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TherapyStartDateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EvaluationPortalSeeder::class);
        $this->admin = User::query()->where("email", "admin@connectionscounseling.test")->firstOrFail();
        $this->participant = User::query()->where("email", "participant@connectionscounseling.test")->firstOrFail();
    }

    public function test_admin_can_set_and_clear_the_therapy_start_date(): void
    {
        $this->actingAs($this->admin)->get(route("admin.users.index"))
            ->assertOk()
            ->assertSee("Started therapy")
            ->assertSee("Case manager email")
            ->assertSee("name=\"therapy_started_on\"", false);

        $this->actingAs($this->admin)
            ->put(route("admin.users.therapy-start", $this->participant), [
                "therapy_started_on" => "2026-03-02",
                "case_manager_email" => "case.manager@example.com",
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route("admin.users.index"));

        $profile = $this->participant->participantProfile->fresh();
        $this->assertSame("2026-03-02", $profile->therapy_started_on->toDateString());
        $this->assertSame("case.manager@example.com", $profile->case_manager_email);

        $this->actingAs($this->admin)->get(route("admin.users.index"))
            ->assertSee("3-month alert Jun 2, 2026")
            ->assertSee("6-month alert Sep 2, 2026")
            ->assertSee("1-year alert Mar 2, 2027");

        $csv = $this->actingAs($this->admin)->get(route("admin.users.download"))->streamedContent();
        $this->assertStringContainsString("case_manager_email", $csv);
        $this->assertStringContainsString("2026-03-02", $csv);
        $this->assertStringContainsString("case.manager@example.com", $csv);

        $this->actingAs($this->admin)
            ->put(route("admin.users.therapy-start", $this->participant), [
                "therapy_started_on" => "",
                "case_manager_email" => "",
            ])
            ->assertSessionHasNoErrors();

        $profile = $this->participant->participantProfile->fresh();
        $this->assertNull($profile->therapy_started_on);
        $this->assertNull($profile->case_manager_email);
    }

    public function test_only_an_admin_can_set_the_date_and_only_for_a_participant(): void
    {
        $clinician = User::query()->where("email", "clinician@connectionscounseling.test")->firstOrFail();

        $this->actingAs($this->participant)
            ->put(route("admin.users.therapy-start", $this->participant), ["therapy_started_on" => "2026-03-02"])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route("admin.users.therapy-start", $clinician), ["therapy_started_on" => "2026-03-02"])
            ->assertNotFound();
    }

    public function test_follow_up_alerts_email_the_case_manager_once_per_milestone(): void
    {
        Notification::fake();

        $profile = $this->participant->participantProfile;
        $profile->update([
            "therapy_started_on" => now()->subMonths(3)->subDay()->toDateString(),
            "case_manager_email" => "case.manager@example.com",
        ]);

        $this->artisan("therapy:send-follow-up-alerts")->assertSuccessful();

        Notification::assertSentOnDemandTimes(TherapyFollowUpNotification::class, 1);
        Notification::assertSentOnDemand(TherapyFollowUpNotification::class, function (TherapyFollowUpNotification $notification): bool {
            return $notification->milestoneKey === "3_months";
        });

        $this->artisan("therapy:send-follow-up-alerts")->assertSuccessful();
        Notification::assertSentOnDemandTimes(TherapyFollowUpNotification::class, 1);

        $profile->update([
            "therapy_alerts_sent" => [
                "3_months" => now()->toIso8601String(),
                "6_months" => now()->toIso8601String(),
                "1_year" => now()->toIso8601String(),
            ],
        ]);
        $this->actingAs($this->admin)
            ->put(route("admin.users.therapy-start", $this->participant), [
                "therapy_started_on" => now()->subYear()->subDay()->toDateString(),
                "case_manager_email" => "case.manager@example.com",
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($profile->fresh()->therapy_alerts_sent);

        $this->artisan("therapy:send-follow-up-alerts")->assertSuccessful();
        Notification::assertSentOnDemandTimes(TherapyFollowUpNotification::class, 4);
    }
}
