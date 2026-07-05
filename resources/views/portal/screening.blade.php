<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eligibility Screening — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="max-w-2xl w-full bg-white shadow-sm rounded-lg p-8 space-y-4">
            <h1 class="text-2xl font-bold">Eligibility screening</h1>
            <p class="text-gray-600">
                This flow collects contact details and routes participants to account activation.
            </p>
            <p class="text-sm text-gray-500">
                In production, the activation link would be sent by email.
            </p>
            <a href="{{ route('home') }}" class="inline-flex text-sm text-indigo-600 hover:text-indigo-500">← Back to home</a>
        </div>
    </div>
</body>
</html>
