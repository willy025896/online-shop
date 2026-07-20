<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $seo = $page['props']['seo'] ?? [];
            $seoTitle = $seo['title'] ?? config('app.name', 'Laravel');
            $seoDescription = $seo['description'] ?? '多角色電商平台，串連買家、賣家與豐富商品。';
            $seoUrl = $seo['url'] ?? url()->current();
            $seoImage = $seo['image'] ?? null;
        @endphp
        <meta name="description" content="{{ $seoDescription }}">
        <link rel="canonical" href="{{ $seoUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $seoUrl }}">
        @if ($seoImage)
            <meta property="og:image" content="{{ $seoImage }}">
        @endif
        <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">

        @if (!empty($seo['jsonLd']))
            @foreach ($seo['jsonLd'] as $jsonLdNode)
                <script type="application/ld+json">{!! json_encode($jsonLdNode, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) !!}</script>
            @endforeach
        @endif

        <!-- Dark mode: set before first paint to avoid a light->dark flash.
             Must run before the Vue bundle loads, so it duplicates the storage key
             and resolution logic from resources/js/Composables/useDarkMode.js — keep both in sync. -->
        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
