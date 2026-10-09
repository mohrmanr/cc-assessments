<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Informed Consent</h2>
    </x-slot>

    @php
        $agreed = $consent?->decision === \App\Models\ParticipantConsent::AGREED;
        $declined = $consent?->decision === \App\Models\ParticipantConsent::DECLINED;
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800 print:hidden">{{ session('status') }}</div>
            @endif

            @if ($agreed)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900 print:hidden">
                    <p>You agreed to participate on {{ $consent->created_at->format('M j, Y') }}. This is your copy of the signed form.</p>
                    <div class="flex gap-2">
                        <button type="button" onclick="window.print()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Print / save as PDF</button>
                        <a href="{{ route('participant.dashboard') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to dashboard</a>
                    </div>
                </div>
            @elseif ($declined)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 print:hidden">
                    On {{ $consent->created_at->format('M j, Y') }} you chose not to participate. If you have changed your mind, you can click <strong>Yes</strong> at the end of this form.
                </div>
            @else
                <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900 print:hidden">
                    Please read the full form, then choose <strong>Yes</strong> or <strong>No</strong> at the end. Your surveys unlock after you click Yes.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 sm:p-8 print:shadow-none print:p-0">
                @include('participant.consent-text')

                @if ($agreed)
                    <div class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-900">
                        <p class="font-semibold">I voluntarily agree to participate in this research study.</p>
                        <p class="mt-2">Signed electronically by <strong>{{ $consent->signed_name }}</strong> (User ID {{ $participant->user_id }})</p>
                        <p>Date: {{ $consent->created_at->format('F j, Y g:i A T') }}</p>
                        <p class="text-xs text-gray-500">Consent form version {{ $consent->consent_version }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('participant.consent.store') }}" class="mt-6 space-y-3 border-t border-gray-200 pt-4">
                        @csrf
                        <x-input-error :messages="$errors->get('decision')" />
                        <button type="submit" name="decision" value="yes" class="block w-full rounded-md bg-indigo-600 px-4 py-3 text-left text-sm font-semibold text-white hover:bg-indigo-500">
                            YES &mdash; I voluntarily agree to participate in this research study.
                        </button>
                        <button type="submit" name="decision" value="no" class="block w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            NO &mdash; No, I do not want to participate in this research study.
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
