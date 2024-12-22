<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PokemonSet;
use TCGdex\TCGdex;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class PokemonSetSeeder extends Seeder
{
    public function run(){

        // Fetch German sets
        $tcgdex = new TCGdex("de");
        $de_sets = $tcgdex->fetchSets();

        // Fetch English sets
        $tcgdex = new TCGdex("en");
        $en_sets = $tcgdex->fetchSets();

        // Index English sets by ID for faster lookups
        $en_sets_indexed = collect($en_sets)->keyBy(fn($set) => $set->id);

        // Combine sets
        $sets = collect($de_sets)->map(function ($set) use ($en_sets_indexed, $tcgdex) {
            $en_set = $en_sets_indexed->get($set->id);
            // create a set_identifier from the english name
            $set_identifier = strtolower(str_replace(' ', '_', $en_set->name));
            // also replace _& with nothing
            $set_identifier = str_replace('&', '', $set_identifier);
            if ($en_set) {
                // fetch the full set so we can access release date
                $en_set = $tcgdex->fetch('sets', $en_set->id);
                return [
                    'id' => $set->id,
                    'set_identifier' => $set_identifier,
                    'series_id' => $en_set->serie->id,
                    'title_de' => $set->name,
                    'title_en' => $en_set->name,    
                    'release_date' => $en_set->releaseDate,

                ];
            }
            return null; // Skip if no English equivalent
        })->filter(); // Remove null entries

        // manually add prismatic evolutions
        $sets->push([
            'id' => 'sv08.5',
            'series_id' => 'sv',
            'set_identifier' => 'prismatic_evolutions',
            'title_de' => 'Prismatische Entwicklungen',
            'title_en' => 'Prismatic Evolutions',
            'release_date' => '2025-01-17',
        ]);

        // manually add terastal festival
        $sets->push([
            'id' => 'sv8a',
            'set_identifier' => 'terastal_festival',
            'series_id' => 'sv',
            'title_de' => '',
            'title_en' => 'Terastal Festival',
            'title_ja' => 'Terastal Fest ex',
            'release_date' => '2024-12-6',
        ]);

        // manually add an other set
        $sets->push([
            'id' => 'other',
            'set_identifier' => 'other',
            'series_id' => 'other',
            'title_de' => 'Andere',
            'title_en' => 'Other',
            'release_date' => '2099-01-01',
        ]);

        // fetch the japanese sets
        $tcgdex = new TCGdex("ja");
        $ja_sets = $tcgdex->fetchSets();


        // access https://bulbapedia.bulbagarden.net/wiki/List_of_Japanese_Pok%C3%A9mon_Trading_Card_Game_expansions
        // to find the english name of the japanese sets

        $translations = $this->scrapeJapaneseSets();

        $expansion_pack_passed = false;

        // japanese sets dont have translated titles, so just use that for english and german
        foreach($ja_sets as $set){
            // fetch the full set so we can access release date
            $set = $tcgdex->fetch('sets', $set->id);

            // try to translate from japanese to english
            $translation = collect($translations)->firstWhere('ja', $set->name);
            if($translation){
                $title_en = $translation['en'];
            } else {
                // skip if we cant find a translation
                $this->command->error("No translation found for {$set->name}");
                continue;
            }

            $set_identifier = str_replace(' & ', '_', $title_en);
            $set_identifier = strtolower(str_replace(' ', '_', $set_identifier));
            

            if($set_identifier === 'expansion_pack'){
                if($expansion_pack_passed){
                    $set_identifier = 'adv_expansion_pack';
                }
                $expansion_pack_passed = true;
            }

            if($set_identifier === 'forbidden_light'){
                $set_identifier = 'forbidden_light_jp';
            }

            if($set_identifier === 'sun_moon'){
                $set_identifier = 'sun_moon_jp';
            }

            // shining legends is the same in japanese
            if($set_identifier === 'shining_legends'){
                $set_identifier = 'shining_legends_jp';
            }


            $broken_identifiers = ['facing_a_new_trial', 'triplet_beat','raging_surf',
            'gaia_volcano','cruel_traitor', 'alolan_moonlight'];
            if(in_array($set_identifier, $broken_identifiers)){
               // skip until we fix
                continue;
            }


            $sets->push([
                'id' => $set->id,
                'set_identifier' => $set_identifier,
                'series_id' => $set->serie->id,
                'title_en' => $title_en,
                'title_ja' => $set->name,
                'release_date' => $set->releaseDate,
            ]);
        }

    


        // Create a set for each entry
        foreach ($sets as $set) {
            PokemonSet::create($set);
        }

    }

    private function scrapeJapaneseSets()
{
    $response = Http::get('https://bulbapedia.bulbagarden.net/wiki/List_of_Japanese_Pok%C3%A9mon_Trading_Card_Game_expansions');

    if ($response->failed()) {
        $this->command->error("Failed to fetch the Bulbapedia page. Status: {$response->status()}");
        return [];
    }

    $html = $response->body();
    $crawler = new Crawler($html);

    $ja_to_en_mapping = [];

    $index = 0;
    $stop = false;

    $crawler->filter('table')->each(function (Crawler $table) use (&$ja_to_en_mapping, &$index, &$stop) {
        if($stop){
            return false;
        }
        $index = 0;
        $table->filter('tr')->each(function (Crawler $row) use (&$ja_to_en_mapping, &$index, &$stop) {
            if($stop){
                return false;
            }
            // get the headers
            $headers = $row->filter('th')->each(function (Crawler $header) {
                return $header->text();
            });

            foreach($headers as $header){
                // if the header contains "japanese name", remember the index
                if(stripos($header, 'japanese name') !== false){
                    $index = array_search($header, $headers);
                } 
            }

            if($index === 0){
                die('no japanese name column found');
            }

            // get the data
            $data = $row->filter('td')->each(function (Crawler $cell) {
                return $cell->text();
            });

            // output data at the index we found earlier
            if(isset($data[$index])){
                // split the string where kanji ends and english letters start
                // if VMAX or VSTAR is in the name, remove that and remember it
                $vmax = false;
                $vstar = false;
                $gx = false;
                $vs = false;
                $anniversary = false;
                $ex = false;
                $tag_team = false;

                // if the string contains  • , split the string and use both
                if(stripos($data[$index], '•') !== false){
                    $parts = explode('•', $data[$index]);

                    // trim the parts
                    $parts = array_map('trim', $parts);

                    // also split the string at 1 between english and kanji
                    $moreparts = preg_split('/(?=[A-Z])/', $parts[1], 2);
                    
                    $ja_to_en_mapping[] = [
                        'ja' => $parts[0],
                        'en' => $moreparts[1]
                    ];

                    // if it contains gaia volcano
                    if(stripos($moreparts[1] , 'Gaia Volcano') !== false){
                        echo 'test';
                    }


                    $ja_to_en_mapping[] = [
                        'ja' => $moreparts[0],
                        'en' => $parts[2]
                    ];
                    return false;
                }

                if(stripos($data[$index], 'VMAX') !== false){
                    $data[$index] = str_replace('VMAX', '', $data[$index]);
                    $vmax = true;
                }
                if(stripos($data[$index], 'VSTAR') !== false){
                    $data[$index] = str_replace('VSTAR', '', $data[$index]);
                    $vstar = true;
                }
                if(stripos($data[$index], 'GX') !== false){
                    $data[$index] = str_replace('GX', '', $data[$index]);
                    $gx = true;
                }
                if(stripos($data[$index], 'VS') !== false){
                    $data[$index] = str_replace('VS', '', $data[$index]);
                    $vs = true;
                }
                if(stripos($data[$index], 'EX') !== false){
                    $data[$index] = str_replace('EX', '', $data[$index]);
                    $ex = true;
                }
                if(stripos($data[$index], '20th Anniversary') !== false){
                    $data[$index] = str_replace('20th Anniversary', '', $data[$index]);
                    $anniversary = true;
                }
                if(stripos($data[$index], 'TAG TEAM') !== false){
                    $data[$index] = str_replace('TAG TEAM', '', $data[$index]);
                    $tag_team = true;
                }
                $parts = preg_split('/(?=[A-Z])/', $data[$index], 2);
                // if the first part is an empy string, there might have been a colspan, so decrease the index
                if($parts[0] === ''){
                    $parts = preg_split('/(?=[A-Z])/', $data[$index - 1], 2);
                }
                if(count($parts) !== 2){
                    // if the title contains THE BEST OF XY, just skip it
                    if(stripos($data[$index], 'THE BEST OF XY') !== false ||
                        stripos($data[$index], 'Pokémon GO') !== false){
                        return false;
                    }
                    $stop = true;
                    return false;
                }

                if($vmax){
                    // add it in front of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => 'VMAX'.$parts[0],
                        'en' => 'VMAX '.$parts[1]
                    ];
                }
                if($vstar){
                    // add it in front of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => 'VSTAR'.$parts[0],
                        'en' => 'VSTAR '.$parts[1]
                    ];
                }
                if($gx){
                    // add it in front of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => 'GX'.$parts[0],
                        'en' => 'GX '.$parts[1]
                    ];
                }
                if($vs){
                    // add it at the end of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => $parts[0].' VS',
                        'en' => $parts[1].' VS'
                    ];
                }
                if($ex){
                    // add it at the start of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => 'EX'.$parts[0],
                        'en' => 'EX '.$parts[1]
                    ];
                }
                if($anniversary){
                    // add it at the end of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => $parts[0].' 20th Anniversary',
                        'en' => $parts[1].' 20th Anniversary'
                    ];
                }
                if($tag_team){
                    // add it at the start of both strings
                    $ja_to_en_mapping[] = [
                        'ja' => 'TAG TEAM '.$parts[0],
                        'en' => 'TAG TEAM '.$parts[1]
                    ];
                }


                if(!$vmax && !$vstar && !$gx && !$vs && !$anniversary && !$ex && !$tag_team){
                    $ja_to_en_mapping[] = [
                        'ja' => $parts[0],
                        'en' => $parts[1]
                    ];
                }

            }

        });
    });
    return $ja_to_en_mapping;
}

}   

        