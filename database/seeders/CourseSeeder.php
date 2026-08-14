<?php

namespace Database\Seeders;

use App\Enums\CourseQuizKind;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::query()->get() as $user) {
            if ($user->role instanceof UserRole) {
                $user->grantRole($user->role);
            }
            if ($user->role === UserRole::Admin) {
                $user->grantRole(UserRole::Learner);
            }
        }

        $course = Course::query()->updateOrCreate(
            ['slug' => 'mi-basic-skills-2026'],
            [
                'title' => 'Motivational Interviewing Introduction to the Basic Skills 2026',
                'description' => 'Placeholder Course Quiz items. Full MI content stays on the legacy CE site until this course is migrated.',
                'price_cents' => 7500,
                'requires_payment' => true,
                'pass_percent' => 75,
                'video_placeholder' => 'MI Basic Skills webinar (placeholder).',
                'is_active' => true,
            ]
        );

        $placeholderItems = [
            [
                'id' => 'q1',
                'prompt' => 'Which skill is a core Motivational Interviewing practice?',
                'choices' => [
                    'a' => 'Reflective listening',
                    'b' => 'Immediate advice-giving',
                    'c' => 'Confronting resistance first',
                ],
                'correct' => 'a',
            ],
            [
                'id' => 'q2',
                'prompt' => 'OARS includes which of the following?',
                'choices' => [
                    'a' => 'Open questions',
                    'b' => 'Ordering homework',
                    'c' => 'Omitting summaries',
                ],
                'correct' => 'a',
            ],
            [
                'id' => 'q3',
                'prompt' => 'Change talk is best described as:',
                'choices' => [
                    'a' => 'The client arguing against change',
                    'b' => 'The client’s own language in favor of change',
                    'c' => 'The clinician’s treatment plan',
                ],
                'correct' => 'b',
            ],
            [
                'id' => 'q4',
                'prompt' => 'Placeholder item: select the keyed answer to pass.',
                'choices' => [
                    'a' => 'This is not the keyed answer',
                    'b' => 'This is the keyed answer',
                    'c' => 'Also not keyed',
                ],
                'correct' => 'b',
            ],
        ];

        CourseQuiz::query()->updateOrCreate(
            ['course_id' => $course->id, 'kind' => CourseQuizKind::Pretest],
            [
                'title' => 'MI Basic Skills pretest',
                'items' => $placeholderItems,
            ]
        );

        CourseQuiz::query()->updateOrCreate(
            ['course_id' => $course->id, 'kind' => CourseQuizKind::Posttest],
            [
                'title' => 'MI Basic Skills posttest',
                'items' => $placeholderItems,
            ]
        );

        $learner = User::query()->updateOrCreate(
            ['email' => 'learner@connectionscounseling.test'],
            [
                'name' => 'Demo Learner',
                'password' => Hash::make('password'),
                'role' => UserRole::Learner,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $learner->grantRole(UserRole::Learner);
    }
}
