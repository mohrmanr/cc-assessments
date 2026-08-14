<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('assignedRoles')
            ->orderBy('name')
            ->get();
        $courses = Course::query()->orderBy('title')->get();
        $roles = UserRole::cases();

        return view('dashboards.admin-users', compact('users', 'courses', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Learner,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->grantRole(UserRole::Learner);

        return redirect()->route('admin.users.index')->with('status', "Created Learner {$user->email}.");
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $selected = collect($validated['roles'] ?? [])
            ->map(fn (string $role) => UserRole::from($role));

        foreach (UserRole::cases() as $role) {
            if ($selected->contains($role)) {
                $user->grantRole($role);
            } else {
                $user->revokeRole($role);
            }
        }

        return redirect()->route('admin.users.index')->with('status', "Updated roles for {$user->name}.");
    }

    public function grantCourse(Request $request, User $user, CourseWorkflow $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);
        $course = Course::query()->findOrFail($validated['course_id']);
        $user->grantRole(UserRole::Learner);
        $workflow->grantAccess($user, $course, 'admin');

        return redirect()->route('admin.users.index')->with('status', "Granted {$course->title} to {$user->name}.");
    }

    public function resetPosttest(Request $request, User $user, CourseWorkflow $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);
        $course = Course::query()->findOrFail($validated['course_id']);
        $workflow->resetPosttest($user, $course);

        return redirect()->route('admin.users.index')->with('status', "Reset posttest for {$user->name} on {$course->title}.");
    }
}
