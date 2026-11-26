<?php

return [
    'partner_commission_rate' => (float) env('KANGURO_PARTNER_COMMISSION', 0.15),
    'hotel_commission_rate' => (float) env('KANGURO_HOTEL_COMMISSION', 0.12),
    'events_commission_rate' => (float) env('KANGURO_EVENTS_COMMISSION', 0.10),
    'services_commission_rate' => (float) env('KANGURO_SERVICES_COMMISSION', 0.15),
    'courier_delivery_share' => (float) env('KANGURO_COURIER_DELIVERY_SHARE', 0.80),
    'taxi_platform_commission' => (float) env('KANGURO_TAXI_COMMISSION', 0.20),
    'service_fee_rate' => (float) env('KANGURO_SERVICE_FEE_RATE', 0.02),
    'service_fee_flat' => (float) env('KANGURO_SERVICE_FEE_FLAT', 0),
    'taxi_cancel_fee_after_assign' => (float) env('KANGURO_TAXI_CANCEL_FEE', 50),
    'delivery_cancel_fee_after_prep' => (float) env('KANGURO_DELIVERY_CANCEL_FEE', 30),
    'partner_accept_timeout_minutes' => (int) env('KANGURO_PARTNER_ACCEPT_TIMEOUT', 5),
    'partner_accept_warn_minutes' => (int) env('KANGURO_PARTNER_ACCEPT_WARN', 3),
    'no_courier_escalate_minutes' => (int) env('KANGURO_NO_COURIER_ESCALATE', 10),
    'free_wait_minutes' => (int) env('KANGURO_FREE_WAIT_MINUTES', 5),
    'wait_fee_per_minute' => (float) env('KANGURO_WAIT_FEE_PER_MINUTE', 5),
    'require_driver_kyc' => filter_var(env('KANGURO_REQUIRE_DRIVER_KYC', true), FILTER_VALIDATE_BOOLEAN),
    /*
    | Carteira pré-paga (motoristas independentes, modelo tipo Yango):
    | comissão da plataforma é debitada do saldo; abaixo do mínimo não recebe viagens.
    */
    'driver_wallet_enabled' => filter_var(env('KANGURO_DRIVER_WALLET', true), FILTER_VALIDATE_BOOLEAN),
    'driver_wallet_min_balance' => (float) env('KANGURO_DRIVER_WALLET_MIN', 200),
    'driver_wallet_min_topup' => (float) env('KANGURO_DRIVER_WALLET_MIN_TOPUP', 50),
    'hotel_confirm_timeout_minutes' => (int) env('KANGURO_HOTEL_CONFIRM_TIMEOUT', 60),
    'cities' => [
        'Beira', 'Maputo', 'Matola', 'Nampula', 'Quelimane', 'Tete', 'Pemba', 'Chimoio', 'Inhambane', 'Xai-Xai',
    ],

    /*
    | Eventos/bilhetes: catálogo apenas via mTicket (sem venda no Kanguro).
    */
    'mticket' => [
        'api_base' => env('MTICKET_API_BASE', 'https://backend.mticket.co.mz/api'),
        'client_base' => env('MTICKET_CLIENT_BASE', 'https://backend.mticket.co.mz/api/client'),
        'storage_base' => env('MTICKET_STORAGE_BASE', 'https://backend.mticket.co.mz/storage'),
        'site_url' => env('MTICKET_SITE_URL', 'https://mticket.co.mz'),
        'play_store_url' => env('MTICKET_PLAY_STORE', 'https://play.google.com/store/apps/details?id=mz.co.mticket.client'),
        'app_store_url' => env('MTICKET_APP_STORE', 'https://apps.apple.com/mz/app/mticket/id6801146792'),
        'android_package' => 'mz.co.mticket.client',
        'timeout' => (int) env('MTICKET_TIMEOUT', 20),
    ],
];

