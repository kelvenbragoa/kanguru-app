<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Kanguro</title>
        <meta name="theme-color" content="#FFD200">

        <link rel="icon" type="image/png" sizes="64x64" href="/site/assets/img/favicon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/site/assets/img/favicon-192.png">
        <link rel="apple-touch-icon" href="/site/assets/img/apple-touch-icon.png">
        <link rel="icon" href="/favicon.ico" sizes="any">

        @vite(['resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
