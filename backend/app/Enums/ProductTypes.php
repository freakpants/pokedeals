<?php

namespace App\Enums;

enum ProductTypes: string
{
    case DisplayBox = 'display_box';
    case HalfBoosterBox = 'half_booster_box';
    case BoosterBundle = 'booster_bundle';
    case BoosterPack = 'booster_pack';
    case SleevedBoosterCase = 'sleeved_booster_case';
    case SleevedBooster = 'sleeved_booster';
    case SingleBlister = 'single_blister';
    case ThreePackBlister = 'three_pack_blister';
    case EliteTrainerBox = 'elite_trainer_box';
    case StackingTin = 'stacking_tin';
    case ThemeDeck = 'theme_deck';
    case SingleCard = 'single_card';
    case Other = 'other';
    case CollectionBox = 'collection_box';
    case CollectorChest = 'collector_chest';
    case PencilCase = 'pencil_case';
    case Tin = 'tin';
    case MiniTin = 'mini_tin';
    case PokeBallTin = 'poke_ball_tin';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}