@props(['title'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ setting('site_name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-ink-50 py-8 print:bg-white print:py-0">
    <div class="mx-auto max-w-xl px-4">
        <div class="no-print mb-4 flex justify-between gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn btn-outline btn-sm">← Back</a>
            <button type="button" onclick="window.print()" class="btn btn-ink btn-sm">Print / Save as PDF</button>
        </div>
        <div class="card overflow-hidden print:border-0 print:shadow-none">
            <div class="flex items-center justify-between bg-ink-950 px-6 py-5 text-white print:bg-white print:text-ink-900 print:border-b print:border-ink-200">
                <x-logo :href="route('home')" class="print:text-ink-900" />
                <p class="text-xs font-semibold tracking-[0.25em] text-gold-300 uppercase print:text-ink-600">{{ $title }}</p>
            </div>
            {{ $slot }}
            <p class="border-t border-dashed border-ink-200 px-6 py-4 text-center text-xs text-ink-400">
                Thank you for your support. Questions? {{ setting('contact_email') }}
            </p>
        </div>
    </div>
</body>
</html>
