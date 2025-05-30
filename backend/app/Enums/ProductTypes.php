<?php

namespace App\Enums;

enum ProductTypes: string
{
    case DisplayBoxCase = 'display_box_case';
    case EnhancedDisplayBoxCase = 'enhanced_display_box_case';
    case EnhancedDisplayBox = 'enhanced_display_box';
    case DisplayBox = 'display_box';
    case HalfBoosterBox = 'half_booster_box';
    case JapaneseDisplayBox = 'japanese_display_box';
    case BoosterBundleCase = 'booster_bundle_case';
    case BoosterBundle = 'booster_bundle';
    case FiveBoosterPackArtBundle = 'five_booster_pack_art_bundle';
    case FourBoosterPackArtBundle = 'four_booster_pack_art_bundle';
    case BoosterPack = 'booster_pack';
    case SleevedBoosterCase = 'sleeved_booster_case';
    case FiveSleevedBoosterPackArtBundle = 'five_sleeved_booster_pack_art_bundle';
    case FourSleevedBoosterPackArtBundle = 'four_sleeved_booster_pack_art_bundle';
    case SleevedBooster = 'sleeved_booster';
    case SingleBlister = 'single_blister';
    case DoubleBlister = 'double_blister';
    case ThreePackBlisterCase = 'three_pack_blister_case';
    case ThreePackBlister = 'three_pack_blister';
    case EliteTrainerBox = 'elite_trainer_box';
    case StackingTin = 'stacking_tin';
    case ThemeDeck = 'theme_deck';
    case SingleCard = 'single_card';
    case ExBox = 'ex_box';
    case CollectionBox = 'collection_box';
    case Collection = 'collection';
    case PinCollection = 'pin_collection';
    case CollectorChest = 'collector_chest';
    case PencilCase = 'pencil_case';
    case FiveBoosterTin = 'five_booster_tin';
    case Tin = 'tin';
    case MiniTinDisplay = 'mini_tin_display';
    case MiniTin = 'mini_tin';
    case PokeBallTin = 'poke_ball_tin';
    case PokeBallTinDisplay = 'poke_ball_tin_display';
    case SuperPremiumCollection = 'super_premium_collection';
    case UltraPremiumCollection = 'ultra_premium_collection';
    case SpecialCollection = 'special_collection';
    case PremiumCollectionCase = 'premium_collection_case';
    case PremiumCollection = 'premium_collection';
    case BuildBattleStadium = 'build_battle_stadium';
    case BuildBattleBox = 'build_battle_box';
    case BuildBattleBoxDisplay = 'build_battle_box_display';
    case BattleDeck = 'battle_deck';
    case PremiumFigureCollection = 'premium_figure_collection';
    case PosterCollection = 'poster_collection';
    case BinderCollection = 'binder_collection';
    case SpecialIllustrationCollection = 'special_illustration_collection';
    case IllustrationCollection = 'illustration_collection';
    case TechStickerCollection = 'tech_sticker_collection';
    case SurpriseBox = 'surprise_box';
    case EraserBlister = 'eraser_blister';
    case CodeCard = 'code_card';
    case Other = 'other';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
