<?php

namespace App\Helpers;

use App\Enums\ProductTypes;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;

class PokemonHelper
{
    // class variables for saving the product type and other details
    private static ProductTypes $product_type = ProductTypes::Other;
    private static string $language = '';
    private static int $multiplier = 1;

    // class variables for storing database data
    private static $sets;
    private static $series_en;
    private static $series_de;
    private static $variants;

    public function __construct()
    {
        // Fetch all necessary data once and store it in class properties
        self::$sets = DB::table('pokemon_sets')->get();
        self::$series_en = DB::table('pokemon_series')->pluck('name_en')->toArray();
        self::$series_de = DB::table('pokemon_series')->pluck('name_de')->toArray();
        self::$variants = DB::table('pokemon_product_variants')->get();
    }

    public static function determineProductDetails(string $title, ?string $variant_title = null): array
    {
        // make sure the static variables don't have anything saved
        self::$product_type = ProductTypes::Other;
        self::$language = '';
        self::$multiplier = 1;

        // for the purpose of detection, replace Scarlet & Violet: Set 9 with Scarlet & Violet: Journey Together
        if (stripos($title, 'Scarlet & Violet: Set 9') !==
            false) {
            $title = str_replace('Scarlet & Violet: Set 9', 'Scarlet & Violet: Journey Together', $title);
        }

        // Scarlet & Violet - Set 9
        if (stripos($title, 'Scarlet & Violet - Set 9') !==
            false) {
            $title = str_replace('Scarlet & Violet - Set 9', 'Scarlet & Violet: Journey Together', $title);
        }

        // Adventures Together => Journey Together
        if (stripos($title, 'Adventures Together') !== false) {
            $title = str_replace('Adventures Together', 'Journey Together', $title);
        }

        //Aventures Ensemble => Journey Together
        if (stripos($title, 'Aventures Ensemble') !== false) {
            $title = str_replace('Aventures Ensemble', 'Journey Together', $title);
        }

        // Karmesin & Purpur: Set 10 => Karmesin & Purpur: Vorbestimmte Rivalen
        if (stripos($title, 'Karmesin & Purpur: Set 10') !== false) {
            $title = str_replace(' Karmesin & Purpur: Set 10', 'Karmesin & Purpur: Vorbestimmte Rivalen', $title);
        }

        // Scarlet & Violet: Set 10 => Scarlet & Violet: Destined Rivals$
        if (stripos($title, 'Scarlet & Violet: Set 10') !== false) {
            $title = str_replace('Scarlet & Violet: Set 10', 'Scarlet & Violet: Destined Rivals', $title);
        }

        // Obsidianflammen => Obsidian Flammen
        if (stripos($title, 'Obsidianflammen') !== false) {
            $title = str_replace('Obsidianflammen', 'Obsidian Flammen', $title);
        }

        // mask of change => transformation mask
        if (stripos($title, 'mask of change') !== false) {
            $title = str_replace('mask of change', 'transformation mask', $title);
            $title = str_replace('Mask of Change', 'transformation mask', $title);
        }

        // paldeas schicksal => paldeas schicksale
        if (stripos($title, 'paldeas schicksal') !== false) {
            // replace non case sensitive
            $title = str_replace('paldeas schicksal', 'paldeas schicksale', $title);
            $title = str_replace('Paldeas Schicksal', 'paldeas schicksale', $title);
        }

        // prismatic evolution => prismatic evolutions
        if (stripos($title, 'prismatic evolution') !== false) {
            $title = str_replace('prismatic evolution', 'prismatic evolutions', $title);
            $title = str_replace('Prismatic Evolution', 'prismatic evolutions', $title);
        }

        self::determineProductType($title, $variant_title);

        // Language-specific strings with priority matches
        $highPriority = [
            'fr' => ['français', 'francais', 'french', 'französisch'],
            'en' => ['english', 'anglais', 'anglais:', 'englisch'],
            'de' => ['deutsch', 'german', 'allemand'],
            'ja' => ['japanese', 'japonais', 'japanisch'],
            'cn' => ['chinese', 'chinois', 'chinesisch']
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
            if (strpos($title, 'CN') !== false) self::$language = 'cn';
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

        // manual fix for stupidity
        // replace flash of the future with future flash
        if (stripos($title, 'flash of the future') !== false) {
            $title = str_replace('flash of the future', 'future flash', $title);
        }

        $set_identifier = self::determineSetIdentifier($title, $variant_title);

        // get the set
        $set = self::$sets->firstWhere('set_identifier', $set_identifier);

        // check if the set_identifier is a series
        if (in_array($set->title_en, self::$series_en) || in_array($set->title_de, self::$series_de)) {
            // if the set_identifier is a series, we should skip the series check
            $specific_identifier = self::determineSetIdentifier($title, $variant_title, true);
            if ($specific_identifier !== 'other') {
                $set_identifier = $specific_identifier;
            }
        }

        // if the language is japanese, and the type is booster box, it should be a japanese booster box
        if (self::$product_type === 'display_box' && self::$language === 'ja') {
            self::$product_type = 'japanese_display_box';
        }

        // check if title contains numberx 
        if (preg_match('/\d+x/', $title, $matches)) {
            self::$multiplier = (int) $matches[0];
        }

        // match variant from a list of variants
        $variant = 'other';

        // filter out variants that are of the wrong type

        $variants = self::$variants->filter(function ($v) {
            return $v->product_type === self::$product_type->value;
        });

        foreach ($variants as $v) {
            // check if en_matches is inside our title
            $en_strings = json_decode($v->en_strings);
            $de_strings = json_decode($v->de_strings);

            foreach ($en_strings as $string) {
                if ($string === "") {
                    continue;
                }
                if (stripos($title, $string) !== false) {
                    if (self::$language === '') {
                        self::$language = 'en';
                    }
                    // if the set is defined on the variant, overwrite it
                    if ($v->set !== 'other') {
                        $set_identifier = $v->set;
                    }
                    $variant = $v->en_short;
                    break 2;
                }
            }

            foreach ($de_strings as $string) {
                if ($string === "") {
                    continue;
                }
                if (stripos($title, $string) !== false) {
                    if (self::$language === '') {
                        self::$language = 'de';
                    }
                    // if the set is defined on the variant, overwrite it
                    if ($v->set !== 'other') {
                        $set_identifier = $v->set;
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

    private static function determineSetIdentifier(string $title, ?string $variant_title, $skip_series = false): ?string
    {
        // Normalize the input title
        $normalizedTitle = self::normalizeDashes($title);
        $normalizedVariantTitle = $variant_title ? self::normalizeDashes($variant_title) : '';

        // Break the title into parts (e.g., "Scarlet & Violet-Temporal Forces")
        $titleParts = preg_split('/[:-]/', $normalizedTitle);
        $specificPart = trim(end($titleParts)); // Focus on the most specific part

        $potentialMatches = [];

        foreach (self::$sets as $set) {
            $set_title_en = self::normalizeDashes($set->title_en ?? '');
            $set_title_de = self::normalizeDashes($set->title_de ?? '');
            $set_title_ja = self::normalizeDashes($set->title_ja ?? '');

            // check if this set is the same as a series
            if (in_array($set_title_en, self::$series_en) || in_array($set_title_de, self::$series_de)) {
                if ($skip_series) {
                    continue;
                }
            }

            if (!empty($set_title_en) && empty($set_title_ja)) {
                // Prioritize matches with the specific part of the title
                if (stripos($specificPart, $set_title_en) !== false) {
                    if (self::$language === '') {
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
                    if (in_array($set_title_en, self::$series_en) || in_array($set_title_de, self::$series_de)) {
                        if ($skip_series) {
                            return $set->set_identifier;
                        }
                    } else {
                        return $set->set_identifier; // Immediate match for specificity
                    }
                }

                // Fall back to general matching
                if (stripos($normalizedTitle, $set_title_en) !== false ||
                    stripos($normalizedVariantTitle, $set_title_en) !== false) {
                    if ($set->set_identifier === 'evolutions') {
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
                    if (self::$language === '') {
                        self::$language = 'de';
                    }
                    if (in_array($set_title_en, self::$series_en) || in_array($set_title_de, self::$series_de)) {
                        if ($skip_series) {
                            return $set->set_identifier;
                        }
                    } else {
                        return $set->set_identifier; // Immediate match for specificity
                    }
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
                    if (self::$language === '') {
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
            ProductTypes::CodeCard->value => ['code card', 'Code-Karte', 'Code Card'],
            ProductTypes::EliteTrainerBox->value => ['elite trainer box', 'etb', 'ttb', 'Top-Trainer-Box', 'Trainer Box', 'Elite-Trainer-Box', 'Top-Trainer Box', 'Elite Trainer-Box'],
            ProductTypes::ThreePackBlisterCase->value => ['Three Pack Blister Case'],
            ProductTypes::ThreePackBlister->value => ['Three Pack Blister', '3 Booster Packs', '3-Pack Blister', '3-Pack Booster Blister', '3er-Boosterpack-Blister'],
            ProductTypes::HalfBoosterBox->value => ['18 Booster Display','half booster box', 'booster box 18 booster', 'Half Booster Display Box', '18er display', 'Booster Box (18 Boosters)', 'Booster Display (18er)', 'Display Box (18 Packs)'],
            ProductTypes::SleevedBoosterCase->value => ['sleeved booster case', 'Sleeved Booster Display', '24 Booster'],
            ProductTypes::EnhancedDisplayBoxCase->value => ['Enhanced Display Box Case', 'Enhanced Booster Box Case'],
            ProductTypes::DisplayBoxCase->value => ['Booster Display Case', 'Booster Box Case'],
            ProductTypes::MiniTinDisplay->value => ['Mini-Tin 8er Set','Alle 8 Mini Tins', 'mini tin display', 'mini tins display', 'Mini-Tin-Box Display'],
            ProductTypes::PokeBallTinDisplay->value => ['poke ball tin display'],
            ProductTypes::BoosterBundleCase->value => ['booster bundle case', 'booster bundle display'],
            ProductTypes::BuildBattleBoxDisplay->value => ['build & battle box display', 'build & battle box display'],
            ProductTypes::EnhancedDisplayBox->value => ['enhanced display box', 'enhanced booster box', 'enhanced booster display', 'enhanced display'],
            ProductTypes::DisplayBox->value => ['booster display box', 'booster box', '36 packs', 'display'],
            ProductTypes::JapaneseDisplayBox->value => ['Box - Japanese', 'Booster Box (JPN)'],
            ProductTypes::BoosterBundle->value => ['booster bundle'],
            ProductTypes::FiveSleevedBoosterPackArtBundle->value => ['Sleeved Booster Pack Art Bundle [Set of 5]'],
            ProductTypes::FourSleevedBoosterPackArtBundle->value => ['Sleeved Booster Pack Art Bundle [Set of 4]'],
            ProductTypes::SleevedBooster->value => ['sleeved booster', 'booster blister'],
            ProductTypes::EraserBlister->value => ['eraser blister'],
            ProductTypes::SingleBlister->value => ['checklane blister', 'blister', 'premium checklane'],
            ProductTypes::DoubleBlister->value => ['2 booster packs'],
            ProductTypes::FiveBoosterTin->value => ['5 booster tin', 'collectors tin', 'US tin'],
            ProductTypes::FiveBoosterPackArtBundle->value => ['Booster Pack Art Bundle [Set of 5]'],
            ProductTypes::FourBoosterPackArtBundle->value => ['Booster Pack Art Bundle [Set of 4]'],
            ProductTypes::BoosterPack->value => ['booster pack', 'single pack', 'booster'],
            ProductTypes::CollectorChest->value => ['collector chest', 'Sammelkoffer', 'Collector\'s Chest', 'Collector’s Chest'],
            ProductTypes::PencilCase->value => ['pencil case', 'pencil tin'],
            ProductTypes::MiniTin->value => ['mini tin', 'Mini-Tin'],
            ProductTypes::PokeBallTin->value => ['ball tin'],
            ProductTypes::StackingTin->value => ['stacking tin', 'Stackable Tins'],
            ProductTypes::Tin->value => ['tin'],
            ProductTypes::UltraPremiumCollection->value => ['ultra-premium collection', 'ultra-premium collection', 
            'ultra premium collection','Ultra-Premium-Kollektion','Ultra Premium Kollektion', 'Ultra Premium Glurak Kollektion'],
            ProductTypes::SuperPremiumCollection->value => ['Super Premium-Kollektion','Super Premium Coll.','super-premium collection', 'super premium collection'],
            ProductTypes::PremiumFigureCollection->value => ['premium figure collection'],
            ProductTypes::PremiumCollection->value => ['premium collection', 'premium playmat collection', 'Morpeko V-UNION Playmat Collection', 'Morpeko V-Union Collection', 'Premium Kollektion', 'Premium-Kollektion', 'Premium' ],
            ProductTypes::SpecialCollection->value => ['Spezial-Kollektion', 'special collection', 'Regieleki V Box', 'Regidrago V Box'],
            ProductTypes::BattleDeck->value => ['battle deck'],
            ProductTypes::BuildBattleStadium->value => ['build & battle stadium', 'battle stadium'],
            ProductTypes::BuildBattleBox->value => ['build & battle box', 'Build & Battle Kit', 'battle box'],
            ProductTypes::PosterCollection->value => ['poster collection', 'Poster Kollektion', 'Poster-Kollektion'],
            ProductTypes::BinderCollection->value => ['binder collection', 'Binder Kollektion', 'Ordner Kollektion', '9-Pocket Portfolio'],
            ProductTypes::PinCollection->value => ['pin collection', 'Pin - Kollektion'],
            ProductTypes::SpecialIllustrationCollection->value => ['special illustration collection', 'Spezial-Illustrations-Kollektion', 'Spezial Illustration Rare Kollektion'],
            ProductTypes::IllustrationCollection->value => ['illustration collection', 'Illustrations-Kollektion', 'Illustration Rare Box', 'Illustration Rare Kollektion', 'Illustrations Kollektion'],
            ProductTypes::TechStickerCollection->value =>
                ['tech sticker collection', 'Tech Sticker Glaceon Collection', 'Tech Sticker Leafon Collection',
                    'Tech Sticker Leafeon Collection', 'Tech Sticker Sylveon Collection', 'Tech Sticker Kollektion', 'Tech-Sticker-Kollektion', 'Sticker Kollektion'],
            ProductTypes::SurpriseBox->value => ['surprise box', 'Überraschungsbox'],
            ProductTypes::Collection->value => ['collection', 'Kollektion'],
            ProductTypes::ExBox->value => ['ex box', 'ex Kollektion', 'ex-box'],
        ];

        // Normalize titles for consistent matching
        $normalizedTitle = strtolower($title);
        $normalizedVariantTitle = $variant_title ? strtolower($variant_title) : '';

        // Check each product type and its keywords
        $match = false;
        foreach ($productTypeKeywords as $productType => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($normalizedTitle, $keyword) !== false || stripos($normalizedVariantTitle, $keyword) !== false) {
                    // make sure tinkaton/victini isnt matched as a tin
                    if (stripos($normalizedTitle, 'tinkaton') !== false || 
                        stripos($normalizedTitle, 'victini') !== false ||
                        stripos($normalizedTitle, 'giratina') !== false
                    ) {
                        if($productType === ProductTypes::Tin->value) {
                            continue;
                        }
                    }
                    // same for 
                    self::$product_type = ProductTypes::from($productType);
                    $match = true;
                    break 2;
                } else {
                    // we are running a command, inform the command line of what's going on
                    // echo "No match found for $keyword in $title\n";
                }
            }
            if ($match) {
                break;
            }
        }

        if ($match) {
            return;
        }

        if (
            preg_match('/\b[A-Z]{2,4}\s?\d{1,3}\b/', $normalizedTitle) ||  // e.g. LOR136
            preg_match('/\d{1,3}\/\d{1,3}/', $normalizedTitle) ||          // e.g. 130/196
            preg_match('/\b(NM|LP|MP|HP|DMG)\b/i', $normalizedTitle)       // Card conditions
        ) {
            self::$product_type = ProductTypes::SingleCard;
            return;
        }

        // Default to "Other" if no match is found
        self::$product_type = ProductTypes::Other;
    }

    public static function determineProductCategory($product): string
    {
        // try to detect the type of product
        $possible_product_types = [
            'basketball', 'pokemon', 'yugioh', 'magic', 'one piece', 'lorcana', "weiss schwarz", "psa 10", "mystery",
            'union arena', "MTG", "dragon ball", "Postal Stamp", 'plüsch', 'Squishmallows', 'Weiß Schwarz', 'Card Case',
            'Magnetic Holder', 'Card Holder', 'Battle Spirits', 'Build Divide', 'Funko Pop', 'Gundam', 'Panini', 'Naruto', 'Bandai', 'Yu-Gi-Oh',
            'Versandkosten', 'Ultra Pro', 'Ultra-Pro', 'Ulta Pro', 'Star Wars', 'Acryl Case', 'PRO-BINDER', 'KEYCHAIN',
            'Dragon Shield', 'Store Card', 'Duskmourn', 'Van Gogh', 'Plush', 'Sleeves', 'Gutschein', 'Attack On Titan', 'Bleach', 'Digimon',
            'Sidewinder 100+', 'Spendenaktion', 'ZipFolio', 'Sleeves', 'Altered TCG', 'Card Preserver', 'Flip\'n\'Tray', 'Nanoblock',
            'PSA Card', 'XenoSkin', 'Ultra Clear', 'gamegenic', 'ultimate guard', 'into the inklands', 'the first chapter',
            'plushy', 'Legler', 'Trefl', 'Ravensburger', 'Puzzle', 'Plüsch', 'Quarter Century Stampede', 'Paramount War', 'Star Realms',
            'A Song of Ice & Fire', '7te See', 'Grundregelwerk', 'White Goblin Games', 'Alien Artifacts', 'Alte dunkle Dinge', 'Altiplano', 'Andor', 'Kosmos',
            'Antarctica', 'Acrylic', 'Stichkabinett', 'carta.media', 'painting', 'Wasgij', 'Warhammer','dobble', 'clementoni', 'eurographics',
            'D&D', 'Räuchermischung', 'Armband', 'strampler', 'seife', 'Dusch', 'shampoo', 'baby','Räucherstäbchen','Spardose',
            'socks', 'becher', 'Ätherisch', 'Teelichthalter', 'duftstein', 'Terra Mystica', 'vegas','penis','anhänger',
            'schmidt', 'grablicht', 'kerze', 'katze', 'Metallschild', 'Knisterbad', 'Windlicht', 'Halskette',
            'Brillenetui', 'Tasse', 'Wortlicht', 'Gedanken', 'Geschenktasche', 'Glückwunschkarte', 'Master Pieces',
            'Wooden.City','Bluebird', 'Educa', 'Lais', 'Gasanzünder', 'Konfettikanone', 'Filztasche', 'Outdoorgrill', 'Body Wash',
            'Flaschenöffner', 'Doppelmeter', 'Männerhandtasche', 'Mädelsabend', 'Grafika', 'Water Wow', 'Melissa&Doug', 'Glue',
            'Castorland', 'Bigjigs', 'Windspiel', 'Tier-Memory', 'Portemonnaie', 'Kinder-Rucksack', 'by Laona', 'Wichtel',
            'Bilderrahmen', 'engel', 'Servietten', 'Ohrschmuck', 'Kugelschreiber', 'Gürteltasche', 'Teelicht', 'Hochzeits',
            'Schutzengel', 'Wespen Stop', 'Piraten Duell', 'Elfenland', 'Sun Catcher', 'Wetterstein', 'Schürze', 'Party Spiel',
            'LED-Lichter', 'Sparschwein', 'Ich denke an dich', 'Braut-Herz', 'Geschenketasche', 'Feuerzeug', 'Holz-Herz',
            'Dekorationsherz', 'Poly Gold', 'Crados', 'Secret Lair', 'dragonball', 'metazoo'

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