<?php

namespace Tests\Feature;

use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\ParticipantConsent;
use App\Models\User;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InformedConsentTest extends TestCase
{
    use RefreshDatabase;

    private User $participant;

    private Instrument $pcl5;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(EvaluationPortalSeeder::class);
        $this->participant = User::query()->where('email', 'participant@connectionscounseling.test')->firstOrFail();
        $this->pcl5 = Instrument::query()->where('slug', 'pcl-5')->firstOrFail();
    }

    private function resultCount(): int
    {
        return AssessmentResult::query()
            ->where('participant_id', $this->participant->participantProfile->id)
            ->where('instrument_id', $this->pcl5->id)
            ->count();
    }

    public function test_surveys_are_locked_until_consent(): void
    {
        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertSee('Review consent form')
            ->assertSee('Locked')
            ->assertDontSee(route('participant.assessments.pcl5'), false);

        $this->actingAs($this->participant)->get(route('participant.assessments.pcl5'))
            ->assertRedirect(route('participant.consent.show'));
        $this->actingAs($this->participant)->post(route('participant.assessments.pcl5.store'), [])
            ->assertRedirect(route('participant.consent.show'));
        $this->actingAs($this->participant)->get(route('participant.assessments.show', $this->pcl5))
            ->assertRedirect(route('participant.consent.show'));
        $this->assertSame(0, $this->resultCount());

        $this->actingAs($this->participant)->get(route('participant.consent.show'))
            ->assertOk()
            ->assertSee('INFORMED CONSENT FOR PARTICIPATION IN RESEARCH')
            ->assertSee('Andrew Schaeffer, LMHC')
            ->assertSee('I voluntarily agree to participate in this research study.')
            ->assertSee('No, I do not want to participate in this research study.');
    }

    public function test_decline_is_recorded_and_can_be_changed_to_yes(): void
    {
        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'no'])
            ->assertRedirect(route('participant.dashboard'));

        $this->assertSame(ParticipantConsent::DECLINED, $this->participant->participantProfile->latestConsent->decision);
        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertSee('you chose not to participate')
            ->assertSee('Review consent form again')
            ->assertSee('Locked');
        $this->actingAs($this->participant)->get(route('participant.assessments.pcl5'))
            ->assertRedirect(route('participant.consent.show'));

        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'yes'])
            ->assertRedirect(route('participant.dashboard'));

        $profile = $this->participant->participantProfile->fresh();
        $this->assertTrue($profile->hasConsented());
        $this->assertSame([ParticipantConsent::DECLINED, ParticipantConsent::AGREED], $profile->consents()->orderBy('id')->pluck('decision')->all());

        $consent = $profile->latestConsent;
        $this->assertSame($this->participant->name, $consent->signed_name);
        $this->assertSame(config('portal.consent_version'), $consent->consent_version);

        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertSee('View / print your copy')
            ->assertDontSee('Locked')
            ->assertSee(route('participant.assessments.pcl5'), false);
        $this->actingAs($this->participant)->get(route('participant.assessments.pcl5'))->assertOk();

        $this->actingAs($this->participant)->get(route('participant.consent.show'))
            ->assertOk()
            ->assertSee('Signed electronically by')
            ->assertSee('Print / save as PDF')
            ->assertDontSee('name="decision"', false);
    }

    public function test_consent_cannot_be_changed_after_agreeing(): void
    {
        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'yes']);
        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'no'])
            ->assertRedirect(route('participant.consent.show'));

        $profile = $this->participant->participantProfile->fresh();
        $this->assertSame(1, $profile->consents()->count());
        $this->assertTrue($profile->hasConsented());
    }

    public function test_decision_is_required(): void
    {
        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'maybe'])
            ->assertSessionHasErrors('decision');
        $this->assertSame(0, $this->participant->participantProfile->consents()->count());
    }

    public function test_only_participants_see_the_consent_page(): void
    {
        $clinician = User::query()->where('email', 'clinician@connectionscounseling.test')->firstOrFail();

        $this->actingAs($clinician)->get(route('participant.consent.show'))->assertForbidden();
        $this->actingAs($clinician)->post(route('participant.consent.store'), ['decision' => 'yes'])->assertForbidden();
    }
}
