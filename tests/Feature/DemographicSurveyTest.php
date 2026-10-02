<?php

namespace Tests\Feature;

use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\User;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemographicSurveyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(EvaluationPortalSeeder::class);
        $this->admin = User::query()->where('email', 'admin@connectionscounseling.test')->firstOrFail();
        $this->participant = User::query()->where('email', 'participant@connectionscounseling.test')->firstOrFail();
    }

    private function uploadDefinition(array|string $definition): \Illuminate\Testing\TestResponse
    {
        $json = is_string($definition) ? $definition : json_encode($definition, JSON_THROW_ON_ERROR);

        return $this->actingAs($this->admin)->post(route('admin.assessments.upload'), [
            'assessment_file' => UploadedFile::fake()->createWithContent('assessment.json', $json),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validAnswers(): array
    {
        return [
            'gender' => '2',
            'english_first_language' => '0',
            'first_language' => 'Spanish',
            'parents_divorced_before_18' => '1',
            'moves_before_18' => '3',
            'siblings_by_18' => '2',
            'siblings_comment' => '',
            'marital_status' => '3',
            'marital_status_comment' => '',
            'ethnicity' => '2',
            'education' => '3',
            'other_education' => '',
            'household_income' => '6',
            'people_supported_by_income' => '1',
        ];
    }

    public function test_demographics_upload_take_and_export(): void
    {
        $this->uploadDefinition(file_get_contents(base_path('database/instruments/demographics.json')))
            ->assertSessionHasNoErrors();

        $instrument = Instrument::query()->where('slug', 'demographics')->firstOrFail();
        $this->assertSame('none', $instrument->scoring_config['method']);
        $this->assertSame('choice', $instrument->items[0]['type']);
        $this->assertSame(['1' => 'Male', '2' => 'Female', '3' => 'Other'], $instrument->items[0]['options']);
        $this->assertFalse($instrument->items[2]['required']);

        $this->actingAs($this->participant)->get(route('participant.dashboard'))
            ->assertOk()
            ->assertSee('Demographics');

        $this->actingAs($this->participant)->get(route('participant.assessments.show', $instrument))
            ->assertOk()
            ->assertSee('What is your gender?')
            ->assertSee('(optional)')
            ->assertSee('I prefer not to answer');

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.store', $instrument), ['gender' => '9'])
            ->assertSessionHasErrors(['gender', 'moves_before_18', 'household_income'])
            ->assertSessionDoesntHaveErrors(['first_language', 'siblings_comment']);

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.store', $instrument), $this->validAnswers())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('participant.dashboard'));

        $result = AssessmentResult::query()->where('instrument_id', $instrument->id)->sole();
        $this->assertNull($result->total_score);
        $this->assertFalse($result->threshold_met);
        $this->assertSame(2, $result->item_responses['items']['gender']);
        $this->assertSame(3, $result->item_responses['items']['moves_before_18']);
        $this->assertSame('Spanish', $result->item_responses['items']['first_language']);
        $this->assertNull($result->item_responses['items']['siblings_comment']);

        $csv = $this->actingAs($this->admin)->get(route('admin.assessments.completed.download'))->streamedContent();
        $row = collect(explode("\n", $csv))->first(fn (string $line): bool => str_contains($line, ',demographics,'));
        $this->assertNotNull($row);
        $this->assertStringContainsString('""gender"":2', $row);
        $this->assertStringContainsString('""first_language"":""Spanish""', $row);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Female (2)');
    }

    public function test_admin_editor_round_trips_item_types(): void
    {
        $this->uploadDefinition(file_get_contents(base_path('database/instruments/demographics.json')))
            ->assertSessionHasNoErrors();
        $instrument = Instrument::query()->where('slug', 'demographics')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.instruments.edit', $instrument))
            ->assertOk()
            ->assertSee('Own choices')
            ->assertSee("1 = Male\n2 = Female\n3 = Other", false);

        $items = collect($instrument->items)->map(fn (array $item): array => [
            'id' => $item['id'],
            'text' => $item['text'],
            'type' => $item['type'] ?? 'scale',
            'required' => ($item['required'] ?? true) ? '1' : '0',
            'options' => isset($item['options'])
                ? collect($item['options'])->map(fn ($label, $code) => "{$code} = {$label}")->implode("\n")
                : '',
        ])->all();
        $items[0]['options'] = "1 = Man\n2 = Woman\n3 = Other";

        $this->actingAs($this->admin)->put(route('admin.instruments.update', $instrument), [
            'instructions' => 'Answer each question.',
            'answer_type' => 'custom',
            'response_labels' => [['value' => 0, 'label' => 'No'], ['value' => 1, 'label' => 'Yes']],
            'is_active' => '1',
            'items' => $items,
        ])->assertSessionHasNoErrors();

        $instrument->refresh();
        $this->assertSame(['1' => 'Man', '2' => 'Woman', '3' => 'Other'], $instrument->items[0]['options']);
        $this->assertSame('number', $instrument->items[4]['type']);
        $this->assertFalse($instrument->items[2]['required']);
        $this->assertArrayNotHasKey('required', $instrument->items[0]);

        $items[0]['options'] = 'Man';
        $this->actingAs($this->admin)->put(route('admin.instruments.update', $instrument), [
            'instructions' => 'Answer each question.',
            'answer_type' => 'custom',
            'response_labels' => [['value' => 0, 'label' => 'No']],
            'items' => $items,
        ])->assertSessionHasErrors('items');
    }

    public function test_mixed_survey_scores_only_shared_scale_questions(): void
    {
        $this->uploadDefinition([
            'slug' => 'mixed-check',
            'name' => 'Mixed Check',
            'version' => 'MIX',
            'domain' => 'test',
            'response_labels' => ['0' => 'Never', '1' => 'Sometimes', '2' => 'Often'],
            'scoring_config' => ['method' => 'sum', 'threshold' => 3],
            'items' => [
                ['id' => 'q1', 'text' => 'Scale one'],
                ['id' => 'q2', 'text' => 'Scale two'],
                ['id' => 'age', 'text' => 'Your age', 'type' => 'number'],
                ['id' => 'role', 'text' => 'Your role', 'type' => 'choice', 'options' => ['1' => 'A', '2' => 'B']],
            ],
        ])->assertSessionHasNoErrors();
        $instrument = Instrument::query()->where('slug', 'mixed-check')->firstOrFail();

        $this->actingAs($this->participant)
            ->post(route('participant.assessments.store', $instrument), ['q1' => '2', 'q2' => '1', 'age' => '40', 'role' => '2'])
            ->assertSessionHasNoErrors();

        $result = AssessmentResult::query()->where('instrument_id', $instrument->id)->sole();
        $this->assertEquals(3, $result->total_score);
        $this->assertTrue($result->threshold_met);
    }

    public function test_upload_rejects_bad_item_definitions(): void
    {
        $this->uploadDefinition([
            'slug' => 'bad',
            'name' => 'Bad',
            'version' => 'BAD',
            'domain' => 'test',
            'scoring_config' => ['method' => 'none'],
            'items' => [
                ['id' => 'q1', 'text' => 'Uses shared scale but none given'],
                ['id' => 'q2', 'text' => 'Choice without options', 'type' => 'choice'],
                ['id' => 'q3', 'text' => 'Bad type', 'type' => 'slider'],
            ],
        ])->assertSessionHasErrors('assessment_file');

        $this->assertDatabaseMissing('instruments', ['slug' => 'bad']);
    }
}
