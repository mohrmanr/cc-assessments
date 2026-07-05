<x-portal-layout>
    <div class="bg-white shadow-sm rounded-lg p-8 space-y-6">
        <h1 class="text-2xl font-bold">Stakeholder demo script</h1>
        <p class="text-gray-600">End-to-end path: screen → approve (if needed) → activate → PCL-5 → clinician review.</p>

        <ol class="list-decimal pl-5 space-y-4 text-sm text-gray-800">
            <li>
                <strong>Auto-eligible path</strong> — Go to <a href="{{ route('screening.start') }}" class="text-indigo-600 underline">screening</a>.
                Use defaults (distress 8, impairment 7). Submit → click activation link → set password.
            </li>
            <li>
                <strong>Supervisor path</strong> — Screening with distress/impairment 5–6 → pending review.
                Log in as <code>supervisor@connectionscounseling.test</code> → Approve → open screening result URL again for activation link.
            </li>
            <li>
                <strong>Baseline PCL-5</strong> — As new participant, open dashboard → Complete PCL-5. Rate items 3–4 to trigger treatment threshold.
            </li>
            <li>
                <strong>Clinician review</strong> — Log out → <code>clinician@connectionscounseling.test</code> / <code>password</code> → confirm treatment recommendation and view score.
            </li>
        </ol>

        <div class="rounded-md bg-gray-50 p-4 text-sm">
            <p class="font-semibold">Demo accounts</p>
            <ul class="mt-2 space-y-1">
                <li>Supervisor: supervisor@connectionscounseling.test</li>
                <li>Clinician: clinician@connectionscounseling.test</li>
                <li>Admin: admin@connectionscounseling.test</li>
            </ul>
            <p class="mt-2">Password for all: <code>password</code></p>
        </div>

        <a href="{{ route('screening.start') }}" class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Start screening
        </a>
    </div>
</x-portal-layout>
