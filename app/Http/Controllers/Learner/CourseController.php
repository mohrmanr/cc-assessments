<?php

namespace App\Http\Controllers\Learner;

use App\Enums\CourseQuizKind;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(protected CourseWorkflow $workflow) {}

    public function index(): View
    {
        $courses = Course::query()->where('is_active', true)->orderBy('title')->get();
        $user = auth()->user();

        $states = $courses->mapWithKeys(
            fn (Course $course): array => [$course->id => $this->workflow->stepState($user, $course)]
        );

        return view('courses.index', compact('courses', 'states'));
    }

    public function show(Course $course): View
    {
        $user = auth()->user();
        $steps = $this->workflow->stepState($user, $course);
        $posttest = $this->workflow->posttestSubmission($user, $course);

        return view('courses.show', compact('course', 'steps', 'posttest'));
    }

    public function pay(Course $course): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->workflow->stepState($user, $course)['pay_enabled'], 403);

        $this->workflow->stubPurchase($user, $course);

        return redirect()
            ->route('courses.show', $course)
            ->with('status', 'Purchase recorded. You now have access to this course.');
    }

    public function showQuiz(Course $course, string $kind): View|RedirectResponse
    {
        $quizKind = CourseQuizKind::from($kind);
        $user = auth()->user();
        $steps = $this->workflow->stepState($user, $course);

        if ($quizKind === CourseQuizKind::Pretest && ! $steps['pretest_enabled']) {
            return redirect()->route('courses.show', $course);
        }
        if ($quizKind === CourseQuizKind::Posttest && ! $steps['posttest_enabled']) {
            return redirect()->route('courses.show', $course);
        }

        $quiz = $quizKind === CourseQuizKind::Pretest ? $course->pretest : $course->posttest;
        abort_unless($quiz, 404);

        return view('courses.quiz', compact('course', 'quiz'));
    }

    public function storeQuiz(Request $request, Course $course, string $kind): RedirectResponse
    {
        $quizKind = CourseQuizKind::from($kind);
        $user = auth()->user();
        $steps = $this->workflow->stepState($user, $course);

        if ($quizKind === CourseQuizKind::Pretest) {
            abort_unless($steps['pretest_enabled'], 403);
            $quiz = $course->pretest;
        } else {
            abort_unless($steps['posttest_enabled'], 403);
            $quiz = $course->posttest;
        }
        abort_unless($quiz, 404);

        $itemIds = collect($quiz->items)->pluck('id')->all();
        $rules = [];
        foreach ($itemIds as $id) {
            $rules[$id] = ['required', 'string'];
        }
        $answers = $request->validate($rules);

        $this->workflow->submitQuiz($user, $course, $quiz, $answers);

        $message = $quizKind === CourseQuizKind::Pretest
            ? 'Pretest submitted. You can play the webinar.'
            : 'Posttest submitted.';

        return redirect()->route('courses.show', $course)->with('status', $message);
    }

    public function showVideo(Course $course): View|RedirectResponse
    {
        $user = auth()->user();
        $steps = $this->workflow->stepState($user, $course);
        if (! $steps['video_enabled'] && ! $steps['video_done']) {
            return redirect()->route('courses.show', $course);
        }

        return view('courses.video', compact('course', 'steps'));
    }

    public function completeVideo(Course $course): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->workflow->stepState($user, $course)['video_enabled'], 403);
        $this->workflow->markVideoComplete($user, $course);

        return redirect()
            ->route('courses.show', $course)
            ->with('status', 'Webinar marked complete. You can take the posttest.');
    }

    public function certificate(Course $course): View
    {
        $user = auth()->user();
        abort_unless($this->workflow->stepState($user, $course)['certificate_enabled'], 403);
        $posttest = $this->workflow->posttestSubmission($user, $course);

        return view('courses.certificate', compact('course', 'user', 'posttest'));
    }
}
