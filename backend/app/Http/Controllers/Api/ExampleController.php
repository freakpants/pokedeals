<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Hello, API!']);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        return response()->json(['received_data' => $data]);
    }
}
?>