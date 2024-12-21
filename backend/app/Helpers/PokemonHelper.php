<?php

namespace App\Helpers;

use App\Enums\ProductTypes;
use Illuminate\Support\Facades\DB;



class PokemonHelper{
    // class variable for saving the product type
    private static ProductTypes $product_type = ProductTypes::Other;
    private static string $language = '';
    private static int $multiplier = 1;

    public static function determineProductDetails(string $title, ?string $variant_title = null): array{
        // make sure the static variables dont have anything saved
        self::$product_type = ProductTypes::Other;
        self::$language = '';
        self::$multiplier = 1;

        self::determineProductType($title, $variant_title);
        
        // Language-specific strings with priority matches
        $highPriority = [
            'fr' => ['français', 'francais', 'french', 'französisch'],
            'en' => ['english', 'anglais', 'anglais:', 'englisch'],
            'de' => ['deutsch', 'german', 'allemand'],
            'ja' => ['japanese', 'japonais', 'japanisch'],
        ];

        // High-priority matches (case-insensitive)
        foreach ($highPriority as $lang => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($title, $keyword) !== false || stripos($variant_title, $keyword) !== false) {
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
            if (strpos($title, 'JP') !== false) self::$language = 'ja';
            if (strpos($title, 'JPN') !== false) self::$language = 'ja';
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

        // if the language is japanese, and the type is booster box, it should be a japanese booster box
        if (self::$product_type === 'display_box' && self::$language === 'ja') {
            self::$product_type  = 'japanese_display_box';
        }

        // check if title contains numberx 
        if (preg_match('/\d+x/', $title, $matches)) {
            self::$multiplier = (int) $matches[0];
        }

        // match variant from a list of variants
        // lookup variants for this product type
        $variants = DB::table('pokemon_product_variants')->where('product_type', self::$product_type)->get();
        $variant = 'other';
        foreach($variants as $v) {
            // check if en_matches is inside our title
            $en_strings = json_decode($v->en_strings);
            $de_strings = json_decode($v->de_strings);
            
            foreach($en_strings as $string) {
                if($string === ""){
                    continue;
                }
                if (stripos($title, $string) !== false) {
                    if(self::$language === '') {
                        self::$language = 'en';
                    }
                    $variant = $v->en_short;
                    break 2;
                }
            }

            foreach($de_strings as $string) {
                if($string === "") {
                    continue;
                }
                if (stripos($title, $string) !== false) {
                    if(self::$language === '') {
                        self::$language = 'de';
                    }
                    $variant = $v->en_short;
                    break 2;
                }
            }
        }

    

        return [
            'variant' => $variant,
            'product_type' => self::$product_type,
            'set_identifier' => $set_identifier,
            'language' => self::$language,
            'multiplier' => self::$multiplier
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
            $set_title_ja = self::normalizeDashes($set->title_ja ?? '');

            if (!empty($set_title_en) && empty($set_title_ja)) {
                // Prioritize matches with the specific part of the title
                if (stripos($specificPart, $set_title_en) !== false) {
                    if (self::$language === ''){
                        self::$language = 'en';
                    } 
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
                    if($set->set_identifier === 'evolutions') {
                        // manually check the title for prismatic evolutions
                        if (stripos($normalizedTitle, 'prismatic evolutions') !== false || 
                            stripos($normalizedVariantTitle, 'prismatic evolutions') !== false) {
                                
                                return 'prismatic_evolutions';
                            }
                    }   
                    $potentialMatches[] = $set->set_identifier;
                }
            }

            if (!empty($set_title_de)) {
                if (stripos($specificPart, $set_title_de) !== false) {
                    if (self::$language === ''){
                        self::$language = 'de';
                    }
                    return $set->set_identifier;
                }

                if (stripos($normalizedTitle, $set_title_de) !== false || 
                    stripos($normalizedVariantTitle, $set_title_de) !== false) {
                    $potentialMatches[] = $set->set_identifier;
                }
            }

            // Check for a match with the Japanese title
            if (!empty($set_title_ja)) {
                // we actually have the english version of the japanese title in title_en
                $set_title_ja = self::normalizeDashes($set->title_en);
                if (stripos($specificPart, $set_title_ja) !== false) {
                    if (self::$language === ''){
                        self::$language = 'ja';
                    }
                    

                    return $set->set_identifier;
                }

                if (stripos($normalizedTitle, $set_title_ja) !== false || 
                    stripos($normalizedVariantTitle, $set_title_ja) !== false) {
                    $potentialMatches[] = $set->set_identifier;
                }
            }
        }

        // Return the first potential match if no exact match found
        return isset($potentialMatches) && count($potentialMatches) > 0 ? $potentialMatches[0] : 'other';
    }

    private static function determineProductType(string $title, ?string $variant_title = null): void
    {
        // Map of product types to their associated keywords
        $productTypeKeywords = [
            ProductTypes::EliteTrainerBox->value => ['elite trainer box', 'etb', 'ttb', 'Top-Trainer-Box', 'Top Trainer Box', 'Elite-Trainer-Box'],
            ProductTypes::ThreePackBlister->value => ['Three Pack Blister', '3 Booster Packs', '3-Pack Blister', '3-Pack Booster Blister', '3er-Boosterpack-Blister'],
            ProductTypes::DisplayBox->value => ['booster display box', 'booster box', '36 packs','display'],
            ProductTypes::HalfBoosterBox->value => ['half booster box'],
            ProductTypes::JapaneseDisplayBox->value => ['Box - Japanese', 'Booster Box (JPN)'],
            ProductTypes::BoosterBundle->value => ['booster bundle'],
            ProductTypes::SleevedBoosterCase->value => ['sleeved booster case'],
            ProductTypes::SleevedBooster->value => ['sleeved booster'],
            ProductTypes::SingleBlister->value => ['checklane blister', 'blister', 'premium checklane'],
            ProductTypes::DoubleBlister->value => ['2 booster packs'],
            ProductTypes::BoosterPack->value => ['booster pack', 'single pack', 'booster'],
            ProductTypes::CollectorChest->value => ['collector chest', 'Sammelkoffer'],
            ProductTypes::PencilCase->value => ['pencil case'],
            ProductTypes::MiniTin->value => ['mini tin'],
            ProductTypes::PokeBallTin->value => ['ball tin'],
            ProductTypes::StackingTin->value => ['stacking tin'],
            ProductTypes::Tin->value => ['tin'],
            ProductTypes::UltraPremiumCollection->value => ['ultra-premium collection', 'ultra-premium collection', 'ultra premium collection'],
            ProductTypes::PremiumCollection->value => ['premium collection'],
            ProductTypes::BuildBattleStadium->value => ['build & battle stadium', 'battle stadium'],  
            ProductTypes::BuildBattleBox->value => ['build & battle box', 'Build & Battle Kit','battle box'],
            ProductTypes::PremiumFigureCollection->value => ['premium figure collection'],
            ProductTypes::PosterCollection->value => ['poster collection'],
            ProductTypes::BinderCollection->value => ['binder collection'],
            ProductTypes::SpecialIllustrationCollection->value => ['special illustration collection', 'Spezial-Illustrations-Kollektion'],
            ProductTypes::IllustrationCollection->value => ['illustration collection', 'Illustrations-Kollektion', 'Illustration Rare Box'],
            ProductTypes::TechStickerCollection->value => 
            ['tech sticker collection', 'Tech Sticker Glaceon Collection', 'Tech Sticker Leafon Collection',
            'Tech Sticker Leafeon Collection', 'Tech Sticker Sylveon Collection'],
            ProductTypes::SurpriseBox->value => ['surprise box'],
        ];

        // Normalize titles for consistent matching
        $normalizedTitle = strtolower($title);
        $normalizedVariantTitle = $variant_title ? strtolower($variant_title) : '';

        // Check each product type and its keywords
        $match = false;
        foreach ($productTypeKeywords as $productType => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($normalizedTitle, $keyword) !== false || stripos($normalizedVariantTitle, $keyword) !== false) {
                    self::$product_type = ProductTypes::from($productType);
                    $match = true;
                    break 2;
                } else {
                    // we are running a command, inform the command line of whats going on
                    // echo "No match found for $keyword in $title\n";
                }
            }
            if($match) {
                break;
            }
        }

        if($match) {
            return;
        }

        // Default to "Other" if no match is found
        self::$product_type = ProductTypes::Other;
    }

    public static function determineProductCategory($product): string
    {
            // try to detect the type of product
            $possible_product_types = [
                'basketball', 'pokemon', 'yugioh', 'magic', 'one piece', 'disney lorcana', "weiss schwarz",  "psa 10", "mystery",
                'union arena', "accessory", "MTG", "dragon ball", "Postal Stamp", 'plüsch', 'Squishmallows', 'Weiß Schwarz', 'Card Case', 
                'Magnetic Holder','Card Holder','Battle Spirits','Build Divide','Funko Pop','Gundam','Panini','Naruto','Bandai','Yu-Gi-Oh',
                'Versandkosten', 'Ultra Pro', 'Ultra-Pro', 'Ulta Pro','Star Wars','Acryl Case','PRO-BINDER','KEYCHAIN',
                'Dragon Shield', 'Store Card', 'Duskmourn', 'Van Gogh', 'Plush', 'Sleeves','Gutschein', 'Attack On Titan', 'Bleach', 'Digimon',
                'Sidewinder 100+', 'Spendenaktion', 'ZipFolio', 'Sleeves', 'Altered TCG', 'Card Preserver','Flip\'n\'Tray', 'Nanoblock',
                'PSA Card','XenoSkin','Ultra Clear','gamegenic', 'ultimate guard', 'into the inklands', 'the first chapter'
                ];
        
            $title = $product['title'];    

            foreach ($possible_product_types as $type) {
                if (stripos($title, $type) !== false) {
                    return $type;     
                }
            }

            // also check the product type reported by the store
            if (isset($product['product_type'])) {
                return $product['product_type'];
            }
            // if we matched nothing it COULD be pokemon
            return 'unknown';

        }


    private static function normalizeDashes(string $input): string
    {
        // Replace different types of dashes with a standard dash
        return str_replace(['—', '–', '‒'], '-', $input);
    }
}