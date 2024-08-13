<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function checkHealth()
    {
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Service Running on ' . now()->addHours(7),
        ], 200);
      
    }

    public function district($id)
    {
        $data = DB::table('districts')
            ->where('districts_province_id', $id)
            ->get();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
            ];
        } else {
            $output = [
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ];
        }
        return response()->json($output, 200);

    }

    public function province()
    {
        $data = DB::table('provinces')->get();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
            ];
        } else {
            $output = [
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ];
        }
        return response()->json($output, 200);

    }
}
