<?php

namespace App\Services;

use App\Enums\CourseQuizKind;
use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseProgress;
use App\Models\CoursePurchase;
use App\Models\CourseQuiz;
use App\Models\CourseQuizSubmission;
use App\Models\User;

class CourseWorkflow
{
    public function hasAccess(User $user, Course $course): bool
    {
        if (! $course->requires_payment) {
            return true;
        }

        return CourseAccess::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    public function grantAccess(User $user, Course $course, string $source): CourseAccess
    {
        return CourseAccess::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            ['source' => $source]
        );
    }

    public function stubPurchase(User $user, Course $course): void
    {
        CoursePurchase::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount_cents' => $course->price_cents,
            'is_stub' => true,
            'purchased_at' => now(),
        ]);

        $this->grantAccess($user, $course, 'purchase');
    }

    public function pretestSubmission(User $user, Course $course): ?CourseQuizSubmission
    {
        return $this->submission($user, $course, CourseQuizKind::Pretest);
    }

    public function posttestSubmission(User $user, Course $course): ?CourseQuizSubmission
    {
        return $this->submission($user, $course, CourseQuizKind::Posttest);
    }

    public function videoCompleted(User $user, Course $course): bool
    {
        return CourseProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('video_completed_at')
            ->exists();
    }

    public function markVideoComplete(User $user, Course $course): void
    {
        CourseProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            ['video_completed_at' => now()]
        );
    }

    /**
     * @return array<string, bool>
     */
    public function stepState(User $user, Course $course): array
    {
        $hasAccess = $this->hasAccess($user, $course);
        $hasPretest = $course->pretest()->exists();
        $pretestDone = ! $hasPretest || $this->pretestSubmission($user, $course) !== null;
        $videoDone = $this->videoCompleted($user, $course);
        $posttest = $this->posttestSubmission($user, $course);

        return [
            'pay_enabled' => $course->requires_payment && ! $hasAccess,
            'pay_done' => $hasAccess,
            'pretest_enabled' => $hasAccess && $hasPretest && $this->pretestSubmission($user, $course) === null,
            'pretest_done' => $pretestDone,
            'pretest_present' => $hasPretest,
            'video_enabled' => $hasAccess && $pretestDone && ! $videoDone,
            'video_done' => $videoDone,
            'posttest_enabled' => $hasAccess && $pretestDone && $videoDone && $posttest === null,
            'posttest_done' => $posttest !== null,
            'score_enabled' => $posttest !== null,
            'certificate_enabled' => $posttest !== null && $posttest->passed,
        ];
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function grade(CourseQuiz $quiz, array $answers, int $passPercent): array
    {
        $items = $quiz->items ?? [];
        $correct = 0;
        $total = count($items);

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id !== '' && ($answers[$id] ?? null) === ($item['correct'] ?? null)) {
                $correct++;
            }
        }

        $score = $total === 0 ? 0.0 : round(($correct / $total) * 100, 1);
        $passed = $quiz->kind === CourseQuizKind::Posttest
            ? $score >= $passPercent
            : true;

        return [
            'score' => $score,
            'passed' => $passed,
        ];
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function submitQuiz(User $user, Course $course, CourseQuiz $quiz, array $answers): CourseQuizSubmission
    {
        $graded = $this->grade($quiz, $answers, $course->pass_percent);

        return CourseQuizSubmission::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_quiz_id' => $quiz->id,
            'kind' => $quiz->kind,
            'answers' => $answers,
            'score' => $graded['score'],
            'passed' => $graded['passed'],
            'submitted_at' => now(),
        ]);
    }

    public function resetPosttest(User $user, Course $course): void
    {
        CourseQuizSubmission::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('kind', CourseQuizKind::Posttest)
            ->delete();
    }

    protected function submission(User $user, Course $course, CourseQuizKind $kind): ?CourseQuizSubmission
    {
        return CourseQuizSubmission::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('kind', $kind)
            ->latest('submitted_at')
            ->first();
    }
}
