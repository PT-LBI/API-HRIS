<?php

namespace App\Http\Controllers;

class CheckHealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Service Running on ' . now()->addHours(7),
        ], 200);
      
    }
}
