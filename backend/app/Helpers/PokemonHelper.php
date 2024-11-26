<?php

namespace App\Helpers;

use App\Enums\ProductTypes;
use Illuminate\Support\Facades\DB;

class PokemonHelper
{
    public static function determineProductDetails(string $title, ?string $variant_title = null): array
{
    $product_type = self::determineProductType($title, $variant_title);

    $sets = DB::table('pokemon_sets')->get();
    $set_identifier = self::determineSetIdentifier($title, $variant_title, $sets);

    return [
        'product_type' => $product_type,
        'set_identifier' => $set_identifier,
    ];
}
private static function determineSetIdentifier(string $title, ?string $variant_title, $sets): ?string
{
    // Normalize the input title
    $normalizedTitle = self::normalizeDashes($title);
    $normalizedVariantTitle = $variant_title ? self::normalizeDashes($variant_title) : '';

    // Break the title into parts (e.g., "Scarlet & Violet-Temporal Forces")
    $titleParts = preg_split('/[:-]/', $normalizedTitle);
    $specificPart = trim(end($titleParts)); // Focus on the most specific part

    $potentialMatches = [];

    foreach ($sets as $set) {
        $set_title_en = self::normalizeDashes($set->title_en ?? '');
        $set_title_de = self::normalizeDashes($set->title_de ?? '');

        if (!empty($set_title_en)) {
            // Prioritize matches with the specific part of the title
            if (stripos($specificPart, $set_title_en) !== false) {
                // if we are evolutions, maybe we are actually prismatic evolutions
                if ($set->set_identifier === 'evolutions') {
                    // manually check the title for prismatic evolutions
                    if (stripos($normalizedTitle, 'prismatic evolutions') !== false || 
                        stripos($normalizedVariantTitle, 'prismatic evolutions') !== false) {
                        return 'prismatic_evolutions';
                        }
                }
                return $set->set_identifier; // Immediate match for specificity
            }

            // Fall back to general matching
            if (stripos($normalizedTitle, $set_title_en) !== false || 
                stripos($normalizedVariantTitle, $set_title_en) !== false) {
                $potentialMatches[] = $set->set_identifier;
            }
        }

        if (!empty($set_title_de)) {
            if (stripos($specificPart, $set_title_de) !== false) {
                return $set->set_identifier;
            }

            if (stripos($normalizedTitle, $set_title_de) !== false || 
                stripos($normalizedVariantTitle, $set_title_de) !== false) {
                $potentialMatches[] = $set->set_identifier;
            }
        }
    }

    // Return the first potential match if no exact match found
    return $potentialMatches[0] ?? null;
}





    private static function determineProductType(string $title, ?string $variant_title = null): string
    {
        // Map of product types to their associated keywords
        $productTypeKeywords = [
            ProductTypes::EliteTrainerBox->value => ['elite trainer box', 'etb'],
            ProductTypes::DisplayBox->value => ['booster display box', 'booster box', '36 packs','display'],
            ProductTypes::HalfBoosterBox->value => ['half booster box'],
            ProductTypes::BoosterBundle->value => ['booster bundle'],
            ProductTypes::ThreePackBlister->value => ['3 Booster Packs', '3-Pack Blister'],
            ProductTypes::SleevedBoosterCase->value => ['sleeved booster case'],
            ProductTypes::SleevedBooster->value => ['sleeved booster'],
            ProductTypes::SingleBlister->value => ['checklane blister'],
            ProductTypes::BoosterPack->value => ['booster pack', 'single pack'],
            ProductTypes::CollectorChest->value => ['collector chest'],
            ProductTypes::PencilCase->value => ['pencil case'],
            ProductTypes::MiniTin->value => ['mini tin'],
            ProductTypes::PokeBallTin->value => ['ball tin'],
            ProductTypes::StackingTin->value => ['stacking tin'],
            ProductTypes::Tin->value => ['tin'],
        
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
