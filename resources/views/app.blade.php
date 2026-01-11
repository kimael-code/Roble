<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                /* Fondo de respaldo en modo claro */
                background-color: oklch(0.93 0.045 165);

                /* MODO CLARO: Degradado verde esmeralda claro hacia blanco grisáceo */
                background-image: linear-gradient(to bottom,
                    oklch(0.93 0.045 165) 0%,   /* Verde esmeralda muy claro */
                    oklch(0.98 0.008 165) 100%   /* Blanco grisáceo neutro en la base */
                );

                background-attachment: fixed;
                background-size: cover;
                background-repeat: no-repeat;
            }

            html.dark {
                /* Fondo de respaldo en modo oscuro */
                background-color: oklch(0.11 0.035 165);

                /* MODO OSCURO: Efecto radial con verde esmeralda profundo */
                background-image: radial-gradient(circle at 50% 0%,
                    oklch(0.30 0.08 165) 0%,   /* Luz verde esmeralda suave en el centro superior */
                    oklch(0.11 0.035 165) 100% /* Verde esmeralda muy oscuro hacia los bordes */
                );

                background-attachment: fixed;
                background-size: cover;
                background-repeat: no-repeat;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
