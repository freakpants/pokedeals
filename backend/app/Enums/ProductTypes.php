<?php

namespace App\Enums;

enum ProductTypes: string
{
    case DisplayBox = 'display_box';
    case HalfBoosterBox = 'half_booster_box';
    case JapaneseDisplayBox = 'japanese_display_box';
    case BoosterBundleCase = 'booster_bundle_case';
    case BoosterBundle = 'booster_bundle';
    case BoosterPack = 'booster_pack';
    case SleevedBoosterCase = 'sleeved_booster_case';
    case SleevedBooster = 'sleeved_booster';
    case SingleBlister = 'single_blister';
    case DoubleBlister = 'double_blister';
    case ThreePackBlister = 'three_pack_blister';
    case EliteTrainerBox = 'elite_trainer_box';
    case StackingTin = 'stacking_tin';
    case ThemeDeck = 'theme_deck';
    case SingleCard = 'single_card';
    case CollectionBox = 'collection_box';
    case Collection = 'collection';
    case PinCollection = 'pin_collection';
    case CollectorChest = 'collector_chest';
    case PencilCase = 'pencil_case';
    case FiveBoosterTin = 'five_booster_tin';
    case Tin = 'tin';
    case MiniTin = 'mini_tin';
    case PokeBallTin = 'poke_ball_tin';
    case SuperPremiumCollection = 'super_premium_collection';
    case UltraPremiumCollection = 'ultra_premium_collection';
    case SpecialCollection = 'special_collection';
    case PremiumCollection = 'premium_collection';
    case BuildBattleStadium = 'build_battle_stadium';
    case BuildBattleBox = 'build_battle_box';
    case PremiumFigureCollection = 'premium_figure_collection';
    case PosterCollection = 'poster_collection';
    case BinderCollection = 'binder_collection';
    case SpecialIllustrationCollection = 'special_illustration_collection';
    case IllustrationCollection = 'illustration_collection';
    case TechStickerCollection = 'tech_sticker_collection';
    case SurpriseBox = 'surprise_box';
    case Other = 'other';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
