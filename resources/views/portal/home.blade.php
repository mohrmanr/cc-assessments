<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="max-w-xl w-full bg-white shadow-sm rounded-lg p-8 space-y-6">
            <div>
                <p class="text-sm uppercase tracking-wide text-indigo-600 font-semibold">Connections Counseling</p>
                <h1 class="text-3xl font-bold mt-2">Assessment Portal</h1>
                <p class="mt-3 text-gray-600">
                    Create your participant account or log in to continue your assessments and care-team messages.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('screening.start') }}" class="rounded-md bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                    Create an account
                    <span class="block text-xs font-normal text-indigo-100">Start with eligibility screening</span>
                </a>
                <a href="{{ route('login') }}" class="rounded-md border border-gray-300 px-4 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Log in
                    <span class="block text-xs font-normal text-gray-500">Return to your assessment portal</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
