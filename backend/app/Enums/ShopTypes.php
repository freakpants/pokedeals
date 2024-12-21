<?php

namespace App\Enums;

enum ShopTypes: string
{
    case Shopify = 'shopify';
    case WebSell = 'websell';
    case PrestaShop = 'prestashop';
    case Ecwid = 'ecwid';
    case WooCommerce = 'woocommerce';
    case Shopware = 'shopware';
    case Other = 'other';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
