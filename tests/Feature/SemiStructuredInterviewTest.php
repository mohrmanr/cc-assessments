<?php

namespace Tests\Feature;

use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\User;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SemiStructuredInterviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $participant;

    private User $clinician;

    private Instrument $interview;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(EvaluationPortalSeeder::class);
        $this->admin = User::query()->where('email', 'admin@connectionscounseling.test')->firstOrFail();
        $this->participant = User::query()->where('email', 'participant@connectionscounseling.test')->firstOrFail();
        $this->clinician = User::query()->where('email', 'clinician@connectionscounseling.test')->firstOrFail();
        $this->interview = Instrument::query()->where('slug', 'interview')->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function validAnswers(): array
    {
        $answers = [];
        foreach ($this->interview->items as $item) {
            $id = $item['id'];
            $required = ! array_key_exists('required', $item) || $item['required'];
            if (! $required) {
                continue;
            }
            $type = $item['type'] ?? 'scale';
            if ($type === 'choice') {
                $answers[$id] = (string) array_key_first($item['options']);
            } elseif ($type === 'number') {
                $answers[$id] = $id === 'age' ? '34' : '2';
            } else {
                $answers[$id] = 'Interview note for '.$id;
            }
        }

        return $answers;
    }

    public function test_interview_is_staff_only_and_saves_without_scoring(): void
    {
        $this->assertTrue($this->interview->isStaffAdministered());
        $this->assertSame('none', $this->interview->scoring_config['method']);
        $this->assertSame('Interview', $this->interview->version);
        $this->assertGreaterThan(50, count($this->interview->items));

        $profile = $this->participant->participantProfile;

        $this->actingAs($this->participant)->post(route('participant.consent.store'), ['decision' => 'yes']);

        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertDontSee('Semi-structured Interview')
            ->assertDontSee(route('participant.assessments.show', $this->interview), false);
        $this->actingAs($this->participant)->get(route('participant.assessments.show', $this->interview))->assertNotFound();

        $this->actingAs($this->clinician)->get(route('staff.interviews.index'))
            ->assertOk()
            ->assertSee('Interview')
            ->assertSee(route('staff.interviews.show', [$this->interview, $profile]), false);

        $this->actingAs($this->clinician)->get(route('staff.interviews.show', [$this->interview, $profile]))
            ->assertOk()
            ->assertSee('Staff entry')
            ->assertSee('What is your age?')
            ->assertSee('C-SSRS');

        $this->actingAs($this->clinician)
            ->post(route('staff.interviews.store', [$this->interview, $profile]), $this->validAnswers())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('staff.interviews.index'));

        $result = AssessmentResult::query()->where('instrument_id', $this->interview->id)->sole();
        $this->assertSame($profile->id, $result->participant_id);
        $this->assertSame($this->clinician->id, $result->entered_by_user_id);
        $this->assertNull($result->total_score);
        $this->assertFalse($result->threshold_met);
        $this->assertSame(34, $result->item_responses['items']['age']);
        $this->assertSame(1, $result->item_responses['items']['living_ny_six_months']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Interview');
    }
}