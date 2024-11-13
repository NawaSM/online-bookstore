<?php
define('CURRENCY_CODE', 'MYR');
define('CURRENCY_SYMBOL', 'RM');

// Main price formatting function
function formatPrice($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2, '.', ',');
}

// Special function for handling special prices
function getDisplayPrice($price, $special_price = null) {
    if ($special_price) {
        return [
            'original' => formatPrice($price),
            'special' => formatPrice($special_price)
        ];
    }
    return formatPrice($price);
}