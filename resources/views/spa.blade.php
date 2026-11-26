<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Kanguro — Tudo chega até você.</title>
        <meta name="theme-color" content="#FFD102">
        <meta name="description" content="Kanguro é a super app que reúne comida, compras, transporte e serviços numa única aplicação, pensada para Moçambique.">

        <link rel="icon" type="image/png" sizes="64x64" href="/site/assets/img/favicon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/site/assets/img/favicon-192.png">
        <link rel="apple-touch-icon" href="/site/assets/img/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

        @vite(['resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
