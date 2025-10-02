<?php

function getCurrencySymbol($currencyCode) {
    $currencies = [
        'USD' => '$',
        'PHP' => '₱',
        'AED' => 'د.إ',
        // Add other currencies as needed
    ];

    return $currencies[$currencyCode] ?? '$';
}