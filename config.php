<?php
/**
 * Настройки сайта Aura Fleurs.
 * Здесь хранятся реквизиты ЮKassa и параметры фиктивной авторизации.
 * Файл можно спокойно редактировать на хостинге.
 */
return [
    // Реквизиты магазина ЮKassa
    'yookassa' => [
        'shop_id'    => '1539049',
        'secret_key' => 'test_GAvR0Cijql4ExV1sU9z9GoN3RQNj28aZkP8zwfD0g0U',
    ],

    // Данные бренда, отображаемые на сайте
    'brand' => [
        'name'     => 'AURA FLEURS',
        'tagline'  => 'Цветы и стиль',
        'phone'    => '+7 (800) 555-35-35',
        'phone_href' => '+78005553535',
        'promo_hint' => 'AURA500',
        'free_delivery_from' => 5000,
        'delivery_cost' => 490,
    ],

    // Фиктивная авторизация по телефону: код, который всегда принимается
    'auth_dev_code' => '7744',
    'default_bonuses' => 500,

    // Промокоды: AURA500 — фиксированная скидка, FLOWER10 — процент
    'promo_codes' => [
        'AURA500'  => ['type' => 'fixed',   'value' => 500],
        'FLOWER10' => ['type' => 'percent', 'value' => 10],
    ],
];
