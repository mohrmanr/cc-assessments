<?php

namespace Database\Seeders;

use App\Enums\AdministrationType;
use App\Enums\UserRole;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\Participant;
use App\Models\TreatmentTrack;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EvaluationPortalSeeder extends Seeder
{
    public function run(): void
    {
        $ptsdTrack = TreatmentTrack::query()->updateOrCreate(
            ['slug' => 'ptsd'],
            [
                'name' => 'PTSD Track',
                'description' => 'Specialized treatment for elevated trauma symptoms.',
                'reassessment_schedule' => [
                    ['instrument_slug' => 'pcl-5', 'interval_weeks' => 4],
                    ['instrument_slug' => 'des-ii', 'interval_weeks' => 8],
                    ['instrument_slug' => 'gse-10', 'interval_weeks' => 8],
                ],
            ]
        );

        $generalTrack = TreatmentTrack::query()->updateOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General Trauma-Informed Track',
                'description' => 'Broader trauma-informed coordination when multiple domains are elevated.',
                'reassessment_schedule' => [
                    ['instrument_slug' => 'pcl-5', 'interval_weeks' => 8],
                    ['instrument_slug' => 'gse-10', 'interval_weeks' => 8],
                ],
            ]
        );

        $instruments = [
            [
                'slug' => 'pcl-5',
                'name' => 'PTSD Checklist for DSM-5',
                'version' => 'PCL-5',
                'domain' => 'ptsd',
                'items' => config('portal.pcl5_demo_items'),
                'scoring_config' => ['threshold' => 33, 'method' => 'sum'],
            ],
            [
                'slug' => 'des-ii',
                'name' => 'Dissociative Experiences Scale-II',
                'version' => 'DES-II',
                'domain' => 'dissociation',
                'items' => config('portal.des_ii_items'),
                'scoring_config' => ['threshold' => 30, 'method' => 'mean_x100'],
            ],
            [
                'slug' => 'ace',
                'name' => 'Adverse Childhood Experiences Questionnaire',
                'version' => 'ACE-10',
                'domain' => 'ace',
                'items' => config('portal.ace_items'),
                'scoring_config' => ['threshold' => 4, 'method' => 'sum'],
            ],
            [
                'slug' => 'gse-10',
                'name' => 'General Self-Efficacy Scale',
                'version' => 'GSE-10',
                'domain' => 'self_efficacy',
                'items' => $this->demoItems('gse', 10, 'I can handle this challenge even when things are difficult.'),
                'scoring_config' => [
                    'threshold' => 20,
                    'method' => 'sum',
                    'direction' => 'below',
                    'response_labels' => [
                        1 => 'Not at all true',
                        2 => 'Hardly true',
                        3 => 'Moderately true',
                        4 => 'Exactly true',
                    ],
                ],
            ],
            [
                'slug' => 'ecr-r',
                'name' => 'Experiences in Close Relationships-Revised',
                'version' => 'ECR-R',
                'domain' => 'attachment',
                'scoring_config' => ['threshold' => 4.0, 'method' => 'subscale_mean', 'subscale' => 'anxiety'],
            ],
            [
                'slug' => 'ecr-rs',
                'name' => 'Experiences in Close Relationships-Revised Short Form',
                'version' => 'ECR-RS',
                'domain' => 'attachment',
                'items' => $this->demoItems('ecr_rs', 9, 'I feel secure and connected in close relationships.'),
                'scoring_config' => [
                    'threshold' => 36,
                    'method' => 'sum',
                    'response_labels' => [
                        1 => 'Strongly disagree',
                        2 => 'Disagree',
                        3 => 'Slightly disagree',
                        4 => 'Neutral',
                        5 => 'Slightly agree',
                        6 => 'Agree',
                        7 => 'Strongly agree',
                    ],
                ],
            ],
            [
                'slug' => 'sccs',
                'name' => 'Self Concept Clarity Scale',
                'version' => 'SCCS',
                'domain' => 'self_concept',
                'items' => $this->demoItems('sccs', 12, 'My beliefs about myself feel clear and consistent.'),
                'scoring_config' => [
                    'threshold' => 36,
                    'method' => 'sum',
                    'direction' => 'below',
                    'response_labels' => [
                        1 => 'Almost never',
                        2 => 'Rarely',
                        3 => 'Sometimes',
                        4 => 'Often',
                        5 => 'Almost always',
                    ],
                ],
            ],
        ];

        foreach ($instruments as $instrument) {
            Instrument::query()->updateOrCreate(
                ['slug' => $instrument['slug']],
                [
                    'name' => $instrument['name'],
                    'version' => $instrument['version'],
                    'domain' => $instrument['domain'],
                    'items' => $instrument['items'] ?? [],
                    'scoring_config' => $instrument['scoring_config'],
                ]
            );
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@connectionscounseling.test'],
            [
                'name' => 'Portal Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        $adminUsers = [
            ['name' => 'Ruth Campbell', 'email' => 'rucampbell@connectionscounseling.org'],
            ['name' => 'Rich Mohrmann', 'email' => 'rmohrmann@gmail.com'],
        ];

        foreach ($adminUsers as $adminUser) {
            User::query()->updateOrCreate(
                ['email' => $adminUser['email']],
                [
                    'name' => $adminUser['name'],
                    'password' => Hash::make('bptipass1'),
                    'role' => UserRole::Admin,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        $supervisor = User::query()->updateOrCreate(
            ['email' => 'supervisor@connectionscounseling.test'],
            [
                'name' => 'Clinical Supervisor',
                'password' => Hash::make('password'),
                'role' => UserRole::ClinicalSupervisor,
                'email_verified_at' => now(),
            ]
        );

        $clinician = User::query()->updateOrCreate(
            ['email' => 'clinician@connectionscounseling.test'],
            [
                'name' => 'Primary Clinician',
                'password' => Hash::make('password'),
                'role' => UserRole::Clinician,
                'email_verified_at' => now(),
            ]
        );

        $clinician->treatmentTracks()->sync([$ptsdTrack->id, $generalTrack->id]);

        $participantUser = User::query()->updateOrCreate(
            ['email' => 'participant@connectionscounseling.test'],
            [
                'name' => 'Demo Participant',
                'password' => Hash::make('password'),
                'role' => UserRole::Participant,
                'email_verified_at' => now(),
            ]
        );

        Participant::query()->updateOrCreate(
            ['user_id' => $participantUser->id],
            [
                'treatment_track_id' => $ptsdTrack->id,
                'primary_clinician_id' => $clinician->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]
        );

        $joanUser = User::query()->updateOrCreate(
            ['email' => 'joan.x@example.com'],
            [
                'name' => 'Joan X',
                'password' => Hash::make('bptipass1'),
                'role' => UserRole::Participant,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $joanParticipant = Participant::query()->updateOrCreate(
            ['user_id' => $joanUser->id],
            [
                'treatment_track_id' => $ptsdTrack->id,
                'primary_clinician_id' => $clinician->id,
                'enrolled_at' => now()->subYear(),
                'status' => 'active',
            ]
        );

        $this->seedJoanLongitudinalResults($joanParticipant, $ptsdTrack, $clinician);

        $francisUser = User::query()->updateOrCreate(
            ['email' => 'francis.y@example.com'],
            [
                'name' => 'Francis Y',
                'password' => Hash::make('bptipass1'),
                'role' => UserRole::Participant,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $francisParticipant = Participant::query()->updateOrCreate(
            ['user_id' => $francisUser->id],
            [
                'treatment_track_id' => $ptsdTrack->id,
                'primary_clinician_id' => $clinician->id,
                'enrolled_at' => now()->subYear(),
                'status' => 'active',
            ]
        );

        $this->seedFrancisLongitudinalResults($francisParticipant, $ptsdTrack, $clinician);
    }

    /**
     * @return array<int, array{id: string, text: string}>
     */
    private function demoItems(string $prefix, int $count, string $text): array
    {
        return collect(range(1, $count))
            ->map(fn (int $number): array => [
                'id' => "{$prefix}_{$number}",
                'text' => "{$text} (demo item {$number})",
            ])
            ->all();
    }

    private function seedJoanLongitudinalResults(Participant $participant, TreatmentTrack $track, User $clinician): void
    {
        $start = now()->subYear()->startOfDay();
        $timeline = [
            ['label' => 'baseline', 'date' => $start, 'type' => AdministrationType::Baseline],
            ['label' => '4_month', 'date' => $start->copy()->addMonths(4), 'type' => AdministrationType::Reassessment],
            ['label' => '8_month', 'date' => $start->copy()->addMonths(8), 'type' => AdministrationType::Reassessment],
            ['label' => '12_month', 'date' => $start->copy()->addYear(), 'type' => AdministrationType::Reassessment],
        ];
        $scores = [
            'pcl-5' => [42, 36, 39, 24],
            'gse-10' => [17, 26, 23, 34],
            'sccs' => [24, 35, 31, 47],
            'ecr-rs' => [46, 38, 42, 27],
        ];
        $responses = [
            'pcl-5' => [3, 2, 2, 1],
            'gse-10' => [2, 2, 3, 4],
            'sccs' => [2, 3, 4, 4],
            'ecr-rs' => [5, 4, 4, 3],
        ];
        $subscaleScores = [
            'pcl-5' => [
                ['intrusion_b' => 11, 'avoidance_c' => 5, 'negative_cognition_mood_d' => 14, 'arousal_reactivity_e' => 12],
                ['intrusion_b' => 8, 'avoidance_c' => 6, 'negative_cognition_mood_d' => 12, 'arousal_reactivity_e' => 10],
                ['intrusion_b' => 12, 'avoidance_c' => 4, 'negative_cognition_mood_d' => 13, 'arousal_reactivity_e' => 10],
                ['intrusion_b' => 5, 'avoidance_c' => 3, 'negative_cognition_mood_d' => 9, 'arousal_reactivity_e' => 7],
            ],
            'ecr-rs' => [
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 5.4, 'avoidance' => 5.1],
                    'father' => ['anxiety' => 4.9, 'avoidance' => 4.7],
                    'partner' => ['anxiety' => 5.6, 'avoidance' => 4.0],
                    'best_friend' => ['anxiety' => 3.9, 'avoidance' => 3.5],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 4.2, 'avoidance' => 3.8],
                    'father' => ['anxiety' => 4.0, 'avoidance' => 3.6],
                    'partner' => ['anxiety' => 4.5, 'avoidance' => 3.2],
                    'best_friend' => ['anxiety' => 3.2, 'avoidance' => 3.0],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 4.6, 'avoidance' => 4.3],
                    'father' => ['anxiety' => 4.3, 'avoidance' => 4.0],
                    'partner' => ['anxiety' => 5.0, 'avoidance' => 3.8],
                    'best_friend' => ['anxiety' => 3.5, 'avoidance' => 3.3],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 3.1, 'avoidance' => 2.8],
                    'father' => ['anxiety' => 2.8, 'avoidance' => 3.2],
                    'partner' => ['anxiety' => 3.4, 'avoidance' => 2.6],
                    'best_friend' => ['anxiety' => 2.5, 'avoidance' => 2.4],
                ]),
            ],
        ];

        $instruments = Instrument::query()
            ->whereIn('slug', array_keys($scores))
            ->get()
            ->keyBy('slug');

        AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->whereIn('instrument_id', $instruments->pluck('id'))
            ->delete();

        foreach ($timeline as $index => $window) {
            foreach ($scores as $slug => $series) {
                $instrument = $instruments[$slug] ?? null;
                if (! $instrument) {
                    continue;
                }

                AssessmentResult::query()->create([
                    'participant_id' => $participant->id,
                    'instrument_id' => $instrument->id,
                    'administration_type' => $window['type'],
                    'total_score' => $series[$index],
                    'subscale_scores' => [
                        'window' => $window['label'],
                        'dimensions' => $subscaleScores[$slug][$index] ?? [],
                    ],
                    'item_responses' => [
                        'fields' => ['demo_window' => $window['label']],
                        'items' => $this->demoResponses($instrument, $responses[$slug][$index]),
                    ],
                    'treatment_track_id' => $track->id,
                    'primary_clinician_id' => $clinician->id,
                    'threshold_met' => $this->thresholdMet($instrument, $series[$index]),
                    'threshold_flags' => ['development_demo' => true],
                    'administered_at' => $window['date']->copy()->setTime(9, 0),
                ]);
            }
        }
    }

    private function seedFrancisLongitudinalResults(Participant $participant, TreatmentTrack $track, User $clinician): void
    {
        $start = now()->subYear()->startOfDay();
        $timeline = [
            ['label' => 'baseline', 'date' => $start, 'type' => AdministrationType::Baseline],
            ['label' => '4_month', 'date' => $start->copy()->addMonths(4), 'type' => AdministrationType::Reassessment],
            ['label' => '8_month', 'date' => $start->copy()->addMonths(8), 'type' => AdministrationType::Reassessment],
            ['label' => '12_month', 'date' => $start->copy()->addYear(), 'type' => AdministrationType::Reassessment],
        ];
        $scores = [
            'pcl-5' => [46, 31, 57, 42],
            'gse-10' => [18, 27, 14, 22],
            'sccs' => [27, 38, 19, 31],
            'ecr-rs' => [48, 36, 56, 44],
        ];
        $responses = [
            'pcl-5' => [3, 2, 4, 3],
            'gse-10' => [2, 3, 1, 2],
            'sccs' => [2, 3, 2, 3],
            'ecr-rs' => [5, 4, 6, 5],
        ];
        $subscaleScores = [
            'pcl-5' => [
                ['intrusion_b' => 12, 'avoidance_c' => 5, 'negative_cognition_mood_d' => 16, 'arousal_reactivity_e' => 13],
                ['intrusion_b' => 7, 'avoidance_c' => 4, 'negative_cognition_mood_d' => 11, 'arousal_reactivity_e' => 9],
                ['intrusion_b' => 15, 'avoidance_c' => 7, 'negative_cognition_mood_d' => 19, 'arousal_reactivity_e' => 16],
                ['intrusion_b' => 10, 'avoidance_c' => 6, 'negative_cognition_mood_d' => 15, 'arousal_reactivity_e' => 11],
            ],
            'ecr-rs' => [
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 5.2, 'avoidance' => 5.0],
                    'father' => ['anxiety' => 4.8, 'avoidance' => 4.6],
                    'partner' => ['anxiety' => 5.4, 'avoidance' => 4.2],
                    'best_friend' => ['anxiety' => 4.1, 'avoidance' => 3.8],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 3.9, 'avoidance' => 3.7],
                    'father' => ['anxiety' => 3.6, 'avoidance' => 3.4],
                    'partner' => ['anxiety' => 4.0, 'avoidance' => 3.1],
                    'best_friend' => ['anxiety' => 3.2, 'avoidance' => 3.0],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 5.8, 'avoidance' => 5.4],
                    'father' => ['anxiety' => 5.1, 'avoidance' => 4.9],
                    'partner' => ['anxiety' => 6.2, 'avoidance' => 4.8],
                    'best_friend' => ['anxiety' => 4.7, 'avoidance' => 4.3],
                ]),
                $this->ecrTargetDimensions([
                    'mother' => ['anxiety' => 4.6, 'avoidance' => 4.4],
                    'father' => ['anxiety' => 4.3, 'avoidance' => 4.1],
                    'partner' => ['anxiety' => 4.9, 'avoidance' => 3.9],
                    'best_friend' => ['anxiety' => 3.8, 'avoidance' => 3.5],
                ]),
            ],
        ];

        $instruments = Instrument::query()
            ->whereIn('slug', array_keys($scores))
            ->get()
            ->keyBy('slug');

        AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->whereIn('instrument_id', $instruments->pluck('id'))
            ->delete();

        foreach ($timeline as $index => $window) {
            foreach ($scores as $slug => $series) {
                $instrument = $instruments[$slug] ?? null;
                if (! $instrument) {
                    continue;
                }

                AssessmentResult::query()->create([
                    'participant_id' => $participant->id,
                    'instrument_id' => $instrument->id,
                    'administration_type' => $window['type'],
                    'total_score' => $series[$index],
                    'subscale_scores' => [
                        'window' => $window['label'],
                        'dimensions' => $subscaleScores[$slug][$index] ?? [],
                    ],
                    'item_responses' => [
                        'fields' => [
                            'demo_window' => $window['label'],
                            'development_context' => 'Alcohol use disorder with intermittent recovery attempts, relapse periods, and unstable symptom course.',
                        ],
                        'items' => $this->demoResponses($instrument, $responses[$slug][$index]),
                    ],
                    'treatment_track_id' => $track->id,
                    'primary_clinician_id' => $clinician->id,
                    'threshold_met' => $this->thresholdMet($instrument, $series[$index]),
                    'threshold_flags' => [
                        'development_demo' => true,
                        'active_alcohol_use_not_in_recovery' => true,
                        'symptoms_progressing' => true,
                    ],
                    'administered_at' => $window['date']->copy()->setTime(10, 30),
                ]);
            }
        }
    }

    /**
     * @param  array<string, array{anxiety: float, avoidance: float}>  $targets
     * @return array<string, mixed>
     */
    private function ecrTargetDimensions(array $targets): array
    {
        return [
            'targets' => $targets,
            'attachment_anxiety' => round(collect($targets)->avg(fn (array $target): float => $target['anxiety']), 1),
            'attachment_avoidance' => round(collect($targets)->avg(fn (array $target): float => $target['avoidance']), 1),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function demoResponses(Instrument $instrument, int $value): array
    {
        return collect($instrument->items ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $value])
            ->all();
    }

    private function thresholdMet(Instrument $instrument, int $score): bool
    {
        $threshold = $instrument->scoring_config['threshold'] ?? null;
        $direction = $instrument->scoring_config['direction'] ?? 'above';

        if (! is_numeric($threshold)) {
            return false;
        }

        return $direction === 'below' ? $score <= $threshold : $score >= $threshold;
    }
}
