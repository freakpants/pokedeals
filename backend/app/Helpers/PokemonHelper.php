<?php

namespace App\Helpers;

use App\Enums\ProductTypes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;



class PokemonHelper{
    // class variable for saving the product type
    private static ProductTypes $product_type = ProductTypes::Other;
    private static string $language = '';

    public static function determineProductDetails(string $title, ?string $variant_title = null): array{
        // make sure the static variables dont have anything saved
        self::$product_type = ProductTypes::Other;
        self::$language = '';

        self::determineProductType($title, $variant_title);
        
        // Language-specific strings with priority matches
        $highPriority = [
            'fr' => ['français', 'francais', 'angl.', 'anglais'],
            'en' => ['english', 'anglais', 'anglais:'],
            'de' => ['deutsch', 'german', 'allemand'],
        ];

        // High-priority matches (case-insensitive)
        foreach ($highPriority as $lang => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($title, $keyword) !== false) {
                    self::$language = $lang;
                    break 2; // Exit both loops when a match is found
                }
            }
        }

        // Fallback lower-priority matches for "DE", "EN", "FR" codes
        if (self::$language === '') {
            if (strpos($title, 'DE') !== false) self::$language = 'de';
            if (strpos($title, 'EN') !== false) self::$language = 'en';
            if (strpos($title, 'FR') !== false) self::$language = 'fr';
        }

        // Normalize titles for broader matches
        $title = strtolower($title);
        $variant_title = $variant_title ? strtolower($variant_title) : null;

        // Lower-priority match for general language indicators
        if (self::$language === '' && preg_match('/\b('.implode('|', array_merge(...array_values($highPriority))).')\b/i', $title)) {
            foreach ($highPriority as $lang => $keywords) {
                foreach ($keywords as $keyword) {
                    if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $title)) {
                        self::$language = $lang;
                        break 2;
                    }
                }
            }
        }

        $sets = DB::table('pokemon_sets')->get();
        $set_identifier = self::determineSetIdentifier($title, $variant_title, $sets);

        return [
            'product_type' => self::$product_type,
            'set_identifier' => $set_identifier,
            'language' => self::$language
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
                    self::$language = 'en';   
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
                    self::$language = 'de';
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





    private static function determineProductType(string $title, ?string $variant_title = null): void
    {
        // Map of product types to their associated keywords
        $productTypeKeywords = [
            ProductTypes::EliteTrainerBox->value => ['elite trainer box', 'etb'],
            ProductTypes::ThreePackBlister->value => ['3 Booster Packs', '3-Pack Blister'],
            ProductTypes::DisplayBox->value => ['booster display box', 'booster box', '36 packs','display'],
            ProductTypes::HalfBoosterBox->value => ['half booster box'],
            ProductTypes::BoosterBundle->value => ['booster bundle'],
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
            ProductTypes::UltraPremiumCollection->value => ['ultra-premium collection', 'ultra-premium collection'],
            ProductTypes::PremiumCollection->value => ['premium collection'],
            ProductTypes::BuildBattleStadium->value => ['build & battle stadium'],  
            ProductTypes::BuildBattleBox->value => ['build & battle box'],
        ];

        // Normalize titles for consistent matching
        $normalizedTitle = strtolower($title);
        $normalizedVariantTitle = $variant_title ? strtolower($variant_title) : '';

        // Check each product type and its keywords
        foreach ($productTypeKeywords as $productType => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($normalizedTitle, $keyword) !== false || stripos($normalizedVariantTitle, $keyword) !== false) {
                    self::$product_type = ProductTypes::from($productType);
                    return;
                }
            }
        }

        // Default to "Other" if no match is found
        self::$product_type = ProductTypes::Other;
    }

    private static function normalizeDashes(string $input): string
    {
        // Replace different types of dashes with a standard dash
        return str_replace(['—', '–', '‒'], '-', $input);
    }
}
