<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ConfigTax;

class ConfigTaxController extends Controller
{
    public function create()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $validator = Validator::make(request()->all(),[
            'tax_value' => 'required',
            'tax_type' => 'required|in:nominal,percentage',
            'tax_ppn_value' => 'required',
            'tax_ppn_type' => 'required|in:nominal,percentage',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        DB::beginTransaction();
        try {

            ConfigTax::create([
                'tax_value'     => request('tax_value'),
                'tax_type'      => request('tax_type'),
                'tax_ppn_value' => request('tax_ppn_value'),
                'tax_ppn_type'  => request('tax_ppn_type'),
                'created_at'    => now(),
            ]);

            DB::commit();

            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'     => []
            ];

        } catch (Exception $e) {
            DB::rollBack();

            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function index()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $res = ConfigTax::select(
                'tax_id',
                'tax_value',
                'tax_type',
                'tax_ppn_value',
                'tax_ppn_type',
                'created_at',
                'updated_at',
            )
            ->get()->first();

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $res ? $res : '',
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = ConfigTax::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //define validation rules
        $validator = Validator::make($request->all(), [
            'tax_value' => 'required',
            'tax_type'  => 'required|in:nominal,percentage',
            'tax_ppn_value' => 'required',
            'tax_ppn_type'  => 'required|in:nominal,percentage',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        try {
            $data_update = [
                'tax_value'     => $request->tax_value,
                'tax_type'      => $request->tax_type,
                'tax_ppn_value' => $request->tax_ppn_value,
                'tax_ppn_type'  => $request->tax_ppn_type,
                'updated_at'    => now(),
            ];

            $res = $check_data->update($data_update);

            if ($res) {
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil mengubah data',
                    'result'     => $check_data
                ];
            } else {
                $output = [
                    'code'      => 500,
                    'status'    => 'error',
                    'message'   => 'Gagal mengubah data',
                    'result'     => []
                ];
            }
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }
}
