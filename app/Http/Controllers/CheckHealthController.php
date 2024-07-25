<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckHealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Service Running on ' . date('Y-m-d H:i:s'),
        ], 200);
      
    }
}
