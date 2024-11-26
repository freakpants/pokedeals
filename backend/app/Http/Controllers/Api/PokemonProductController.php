<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PokemonProductController extends Controller
{
    public function index()
    {

        // Fetch products that have matches in the external_products table
        $products = DB::table('pokemon_products as pp')
            ->join('external_products as ep', function ($join) {
                $join->on('pp.type', '=', 'ep.type')
                    ->on('pp.set_identifier', '=', 'ep.set_identifier');
            })
            ->leftJoin('pokemon_sets as ps', 'pp.set_identifier', '=', 'ps.set_identifier')
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
                'ep.language as match_language',
                'ep.url as match_url',
                'ps.release_date'
            )
            ->where('pp.type', '<>', 'Other')
            ->where('ep.stock', '>', 0)
            ->whereNotNull('pp.set_identifier')
            ->orderBy('ps.release_date', 'desc')
            ->get();

        // Transform the products into the expected structure
        $groupedProducts = $products->groupBy('sku')->map(function ($productGroup) {
            $product = $productGroup->first();

            // Extract matches for this product
            $matches = $productGroup->map(function ($match) {
                return [
                    'shop_id' => $match->shop_id,
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
                'price' => $product->price,
                'set_identifier' => $product->set_identifier,
                'product_url' => $product->product_url,
                'images' => json_decode($product->images, true) ?? [], // Decode JSON images
                'matches' => $matches,
            ];
        })->values(); // Re-index the collection

        return response()->json($groupedProducts);
    }
}
