<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Workforce OS') : config('app.name', 'Workforce OS') }}
</title>

<link rel="icon" type="image/png" href="{{ asset('assets/neuragent-logo.png') }}">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('assets/neuragent-logo.png') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
