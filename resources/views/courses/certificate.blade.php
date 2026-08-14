<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate - {{ $course->title }}</title>
    <style>
        body { font-family: Georgia, serif; color: #111; margin: 0; background: #f3f4f6; }
        .page { max-width: 800px; margin: 2rem auto; background: #fff; border: 12px solid #312e81; padding: 3rem; }
        h1 { font-size: 1.75rem; text-align: center; margin: 0 0 0.5rem; }
        .org { text-align: center; letter-spacing: 0.2em; text-transform: uppercase; font-size: 0.8rem; color: #4f46e5; }
        .name { text-align: center; font-size: 2rem; margin: 2rem 0 0.5rem; }
        .meta { text-align: center; color: #374151; line-height: 1.6; }
        .actions { text-align: center; margin: 1.5rem 0; }
        button, a { font-family: system-ui, sans-serif; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .page { margin: 0; border-width: 8px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('courses.show', $course) }}">Back to course</a>
    </div>
    <div class="page">
        <p class="org">Connections Counseling</p>
        <h1>Certificate of Completion</h1>
        <p class="meta">This certifies that</p>
        <p class="name">{{ $user->name }}</p>
        <p class="meta">
            has completed<br>
            <strong>{{ $course->title }}</strong><br>
            Posttest score: {{ number_format($posttest->score, 1) }}%<br>
            {{ $posttest->submitted_at?->timezone(config('app.timezone'))->format('F j, Y') }}
        </p>
    </div>
</body>
</html>
