<?php

namespace Tests\Feature;

use App\Enums\AdministrationType;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\User;
use App\Services\AssessmentFollowUp;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AssessmentFollowUpRetakeTest extends TestCase
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

        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'yes']);
    }

    /**
     * @return array<string, string>
     */
    private function pclAnswers(): array
    {
        $answers = [];
        foreach ($this->pcl5->items as $item) {
            $answers[$item['id']] = '1';
        }

        return $answers;
    }

    public function test_follow_up_appends_a_result_and_keeps_the_baseline(): void
    {
        $profile = $this->participant->participantProfile;

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.pcl5.store'), $this->pclAnswers())
            ->assertRedirect(route('participant.dashboard'));

        $baseline = AssessmentResult::query()
            ->where('participant_id', $profile->id)
            ->where('instrument_id', $this->pcl5->id)
            ->where('administration_type', AdministrationType::Baseline)
            ->sole();

        $profile->update([
            'therapy_started_on' => now()->subMonths(3)->subDay()->toDateString(),
            'case_manager_email' => null,
        ]);

        $followUp = app(AssessmentFollowUp::class);
        $this->assertSame('3_months', $followUp->nextOpenMilestone($profile->fresh(), $this->pcl5));

        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertSee('Follow-up due')
            ->assertSee('Start 3-month follow-up');

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.pcl5.store'), $this->pclAnswers())
            ->assertRedirect(route('participant.dashboard'));

        $results = AssessmentResult::query()
            ->where('participant_id', $profile->id)
            ->where('instrument_id', $this->pcl5->id)
            ->orderBy('id')
            ->get();

        $this->assertTrue($results->contains('id', $baseline->id));
        $this->assertCount(2, $results);
        $followUpResult = $results->firstWhere('milestone_key', '3_months');
        $this->assertNotNull($followUpResult);
        $this->assertSame(AdministrationType::Reassessment, $followUpResult->administration_type);
        $this->assertNull($followUp->nextOpenMilestone($profile->fresh(), $this->pcl5));

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.pcl5.store'), $this->pclAnswers())
            ->assertRedirect(route('participant.dashboard'));

        $this->assertSame(2, AssessmentResult::query()
            ->where('participant_id', $profile->id)
            ->where('instrument_id', $this->pcl5->id)
            ->count());
    }

    public function test_without_therapy_start_date_follow_up_stays_closed(): void
    {
        $profile = $this->participant->participantProfile;

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.pcl5.store'), $this->pclAnswers())
            ->assertRedirect(route('participant.dashboard'));

        $profile->update(['therapy_started_on' => null]);

        $this->assertNull(app(AssessmentFollowUp::class)->nextOpenMilestone($profile->fresh(), $this->pcl5));

        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertDontSee('Start 3-month follow-up');
    }
}
