<?php

namespace App\Helpers;

use App\Enums\ProductTypes;
use Illuminate\Support\Facades\DB;

class PokemonHelper
{
    public static function determineProductDetails(string $title, ?string $variant_title = null): array
    {
        $product_type = ProductTypes::Other->value;

        // Normalize dashes in the title and variant title
        $title = self::normalizeDashes($title);
        $variant_title = $variant_title ? self::normalizeDashes($variant_title) : null;

        // Determine the product type using a keyword map
        $product_type = self::determineProductType($title, $variant_title);

        // Determine the set identifier
        $set_identifier = null;
        $sets = DB::table('pokemon_sets')->get();

        foreach ($sets as $set) {
            $set_title_en = self::normalizeDashes($set->title_en ?? '');
            $set_title_de = self::normalizeDashes($set->title_de ?? '');
            $set_title_jp = self::normalizeDashes($set->title_ja ?? '');

            if (
                (!empty($set_title_en) && (stripos($title, $set_title_en) !== false || ($variant_title && stripos($variant_title, $set_title_en) !== false))) ||
                (!empty($set_title_de) && (stripos($title, $set_title_de) !== false || ($variant_title && stripos($variant_title, $set_title_de) !== false))) ||
                (!empty($set_title_jp) && (stripos($title, $set_title_jp) !== false || ($variant_title && stripos($variant_title, $set_title_jp) !== false)))
            ) {
                $set_identifier = $set->identifier;
                break;
            }
        }

        return [
            'product_type' => $product_type,
            'set_identifier' => $set_identifier,
        ];
    }

    private static function determineProductType(string $title, ?string $variant_title = null): string
    {
        // Map of product types to their associated keywords
        $productTypeKeywords = [
            ProductTypes::EliteTrainerBox->value => ['elite trainer box', 'etb'],
            ProductTypes::DisplayBox->value => ['booster display box', 'booster box', '36 packs','display'],
            ProductTypes::BoosterPack->value => ['booster pack', 'single pack'],
        ];

        // Normalize titles for consistent matching
        $normalizedTitle = strtolower($title);
        $normalizedVariantTitle = $variant_title ? strtolower($variant_title) : '';

        // Check each product type and its keywords
        foreach ($productTypeKeywords as $productType => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($normalizedTitle, $keyword) !== false || stripos($normalizedVariantTitle, $keyword) !== false) {
                    return $productType;
                }
            }
        }

        // Default to "Other" if no match is found
        return ProductTypes::Other->value;
    }

    private static function normalizeDashes(string $input): string
    {
        // Replace different types of dashes with a standard dash
        return str_replace(['—', '–', '‒'], '-', $input);
    }
}
