<x-portal-layout>
    <div class="bg-white shadow-sm rounded-lg p-8 space-y-6">
        <div>
            <p class="text-sm text-indigo-600 font-semibold uppercase tracking-wide">
                @if ($submission->outcome->value === 'eligible')
                    Account setup
                @else
                    Screening result
                @endif
            </p>
            <h1 class="text-2xl font-bold mt-1">
                @if ($submission->outcome->value === 'eligible')
                    Activate your account
                @elseif ($submission->outcome->value === 'pending_review')
                    Under clinical review
                @else
                    Not eligible at this time
                @endif
            </h1>
            <p class="mt-2 text-gray-600">{{ $submission->auto_decision_reason }}</p>
        </div>

        @if ($submission->safety_flag)
            <div class="rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
                <strong>Safety flag:</strong> A clinical supervisor has been alerted for follow-up.
            </div>
        @endif

        @if ($submission->outcome->value === 'eligible' && $invitation)
            <div class="rounded-md bg-green-50 border border-green-200 p-4 space-y-3">
                <p class="text-sm text-green-900"><strong>Step 2 — Activate your account</strong></p>
                <p class="text-sm text-green-800">In production, this link would be emailed. For demo, open it now:</p>
                <a href="{{ route('activate.show', $invitation->token) }}" class="inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-600">
                    Set password &amp; activate account
                </a>
                <p class="text-xs text-green-700 break-all">{{ route('activate.show', $invitation->token) }}</p>
            </div>
        @elseif ($submission->outcome->value === 'pending_review')
            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-900">
                <p>A clinical supervisor will review your responses.</p>
                <p class="mt-2"><strong>Demo:</strong> Log in as <code>supervisor@connectionscounseling.test</code> / <code>password</code> to approve this case, then return here for the activation link.</p>
                <a href="{{ route('login') }}" class="mt-3 inline-flex text-sm font-semibold text-yellow-800 underline">Supervisor login</a>
            </div>
        @elseif ($submission->outcome->value === 'not_eligible')
            <div class="rounded-md bg-gray-50 border p-4 text-sm text-gray-700">
                <p>Referral resources would appear here. Screening data is retained until a supervisor or admin deletes it.</p>
            </div>
        @endif

        <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-500">← Back to home</a>
    </div>
</x-portal-layout>
