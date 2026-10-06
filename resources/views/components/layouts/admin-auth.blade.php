@props(['title'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Admin · {{ setting('site_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hero-glow flex min-h-full items-center justify-center px-4 py-12">
    <x-flash />
    <div class="w-full max-w-sm">
        <div class="mb-8 flex flex-col items-center gap-3 text-center">
            <x-logo :href="route('home')" />
            <p class="text-xs font-semibold tracking-[0.25em] text-gold-300 uppercase">Administration</p>
        </div>
        <div class="card p-6 sm:p-8">
            <h1 class="mb-6 font-sans text-xl font-semibold">{{ $title }}</h1>
            {{ $slot }}
        </div>
    </div>
</body>
</html>
