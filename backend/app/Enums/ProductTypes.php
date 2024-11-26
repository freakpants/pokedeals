<?php

namespace App\Enums;

enum ProductTypes: string
{
    case DisplayBox = 'display_box';
    case BoosterPack = 'booster_pack';
    case EliteTrainerBox = 'elite_trainer_box';
    case ThemeDeck = 'theme_deck';
    case SingleCard = 'single_card';
    case Other = 'other';
    case BoosterBundle = 'booster_bundle';
    case CollectionBox = 'collection_box';
    case Tin = 'tin';

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
