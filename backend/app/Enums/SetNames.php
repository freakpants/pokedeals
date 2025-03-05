<?php

namespace App\Enums;

enum SetNames: string
{
    case SurgingSparks = 'surging_sparks';
    case EvolvingSkies = 'evolving_skies';
    case FusionStrike = 'fusion_strike';
    case BattleStyles = 'battle_styles';
    case ShiningFates = 'shining_fates';
    case VividVoltage = 'vivid_voltage';
    case DarknessAblaze = 'darkness_ablaze';
    case RebelClash = 'rebel_clash';
    case ParadoxRift = 'paradox_rift';
    case ShroudedFable = 'shrouded_fable';
    case PrismaticEvolutions = 'prismatic_evolutions';
    case PaldeaEvolved = 'paldea_evolved';
    case StellarCrown = 'stellar_crown';
    case ScarletAndVioletBase = 'scarlet_and_violet_base';

    case Other = 'other';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
