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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function download(): StreamedResponse
    {
        $filename = 'user-ids-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['user_id', 'participant_id', 'name', 'email', 'roles', 'is_active', 'created_at']);

            User::query()
                ->with(['assignedRoles', 'participantProfile'])
                ->orderBy('id')
                ->chunkById(200, function ($users) use ($handle): void {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->participantProfile?->id,
                            $user->name,
                            $user->email,
                            collect($user->roleList())->map(fn (UserRole $role) => $role->label())->implode('; '),
                            $user->is_active ? 'yes' : 'no',
                            $user->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
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

        if ($selected->contains(UserRole::Learner) && $selected->contains(UserRole::Participant)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['roles' => 'Learner and Participant cannot both be enabled on the same account.']);
        }

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
        $user->loadMissing('assignedRoles');
        if ($user->isParticipant()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['roles' => "Cannot grant course access while {$user->name} is a Participant. Remove Participant first, or use a Learner account."]);
        }

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

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->loadMissing('assignedRoles');
        if ($user->isAdmin()) {
            $otherAdmins = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($query): void {
                    $query->where('role', UserRole::Admin->value)
                        ->orWhereHas('assignedRoles', fn ($roles) => $roles->where('role', UserRole::Admin->value));
                })
                ->exists();

            if (! $otherAdmins) {
                return redirect()
                    ->route('admin.users.index')
                    ->withErrors(['user' => 'Cannot delete the last Admin account.']);
            }
        }

        $label = "{$user->name} ({$user->email})";
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "Deleted account {$label}.");
    }
}
