<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PokemonProductController extends Controller
{
    public function index()
    {


        
        $products = DB::table('external_products as ep')
            ->join('pokemon_products as pp', function ($join) {
                $join->on('pp.type', '=', 'ep.type')
                    ->on('pp.set_identifier', '=', 'ep.set_identifier')
                    ->on('pp.variant', '=', 'ep.variant')
                    ;
            }) 
            ->leftJoin('pokemon_sets as ps', 'pp.set_identifier', '=', 'ps.set_identifier')
            // join the amonunt of packs from the product_types table
            ->leftJoin('product_types as pt', 'pp.type', '=', 'pt.product_type')
            // join the variant
            ->leftJoin('pokemon_product_variants as pv', 'pp.variant', '=', 'pv.en_short')
            ->select(
                'pp.sku',
                'pp.title',
                'pp.set_identifier',
                'pp.price',
                'pp.product_url',
                'pp.images',
                'ep.shop_id',
                'ep.title as match_title',
                'ep.price as match_price',
                'ep.external_id as match_external_id',
                'ep.language as match_language',
                'ep.url as match_url',
                'ps.release_date',
                'ps.series_id',
                'pt.pack_count',
                'pv.pack_count as variant_pack_count',
                'pv.product_type as variant_product_type',
                'pt.product_type',
                'pt.swh_modifier'
            )
            ->where('pp.type', '<>', 'Other')
            ->where('ep.stock', '>', 0)
            ->where('pt.pack_count', '>', 0)
            // where either set_identifier or variant is not other
            ->where(function ($query) {
                $query->where('pp.set_identifier', '<>', 'Other')
                    ->orWhere('pp.variant', '<>', 'Other');
            })
            ->orderBy('ps.release_date', 'desc')   
            ->get();

        // Transform the products into the expected structure
        $groupedProducts = $products->groupBy('sku')->map(function ($productGroup) {
            $product = $productGroup->first();

            // change the pack count if the product is from the sword and shield set
            if ($product->swh_modifier && $product->series_id === 'swsh') {
                $product->pack_count += $product->swh_modifier;
            }

            // Extract matches for this product
            $matches = $productGroup->map(function ($match) {
                return [
                    'shop_id' => $match->shop_id,
                    'external_id' => $match->match_external_id,
                    'title' => $match->match_title,
                    'price' => $match->match_price,
                    'language' => $match->match_language,
                    'external_product' => [
                        'url' => $match->match_url,
                    ],
                ];
            })->toArray();

            return [
                'title' => $product->title,
                'id' => $product->sku,
                'price' => $product->price,
                'pack_count' => $product->variant_pack_count && $product->variant_product_type === $product->product_type
                    ? $product->variant_pack_count
                    : $product->pack_count,
                'set_identifier' => $product->set_identifier,
                'product_url' => $product->product_url,
                'product_type' => $product->product_type,
                'release_date' => $product->release_date,
                'images' => json_decode($product->images, true) ?? [], // Decode JSON images
                'matches' => $matches,
            ];
        })->values(); // Re-index the collection

        $response = [
            'data' => $groupedProducts,
            'meta' => [
                'total' => $groupedProducts->count(),
            ],
        ];

        return response()->json($response);
    }
}
