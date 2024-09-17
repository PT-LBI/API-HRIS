<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
// use App\Models\MasterPayroll;
use App\Models\UserPayroll;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\DB;

class UserPayrollController extends Controller
{
    public function create()
    {
        $validator = Validator::make(request()->all(),[
            'user_payroll_month' => 'required',
            'user_payroll_year' => 'required',
            'user_payroll_user_id' => 'required',
            'user_payroll_payroll_id' => 'required',
            'user_payroll_value' => 'required',
            'user_payroll_overtime_hour' => 'required',
            'user_payroll_overtime_hour_total' => 'required',
            'user_payroll_total_accepted' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $data = UserPayroll::create([
            'user_payroll_month' => request('user_payroll_month'),
            'user_payroll_year' => request('user_payroll_year'),
            'user_payroll_user_id' => request('user_payroll_user_id'),
            'user_payroll_payroll_id' => request('user_payroll_payroll_id'),
            'user_payroll_value' => request('user_payroll_value'),
            'user_payroll_overtime_hour' => request('user_payroll_overtime_hour'),
            'user_payroll_overtime_hour_total' => request('user_payroll_overtime_hour_total'),
            'user_payroll_communication' => request('user_payroll_communication'),
            'user_payroll_meal_allowance' => request('user_payroll_meal_allowance'),
            'user_payroll_transport' => request('user_payroll_transport'),
            'user_payroll_income_json' => json_encode(request('user_payroll_income_json')),
            'user_payroll_income_total' => json_encode(request('user_payroll_income_total')),
            'user_payroll_absenteeism_cut' => request('user_payroll_absenteeism_cut'),
            'user_payroll_bpjs' => request('user_payroll_bpjs'),
            'user_payroll_deduct_json' => json_encode(request('user_payroll_deduct_json')),
            'user_payroll_deduct_total' => json_encode(request('user_payroll_deduct_total')),
            'user_payroll_total_accepted' => request('user_payroll_total_accepted'),
            'user_payroll_status' => 'waiting',
            'created_at' => now()->addHours(7),
            'updated_at' => null,
        ]);

        if ($data) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menambahkan data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }

    public function detail($id)
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = UserPayroll::query()
            ->select(
                'user_payrolls.*',
            )
            ->where('user_payroll_id', $id)
            ->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => convertResponseJson($data),
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

    public function update(Request $request, $id)
    {
        $check_data = UserPayroll::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        } else {
            if ($check_data->user_payroll_status == 'sent') {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Gaji sudah dikirim, tidak bisa diubah',
                    'result' => []
                ], 200);
            }
        }

        $validator = Validator::make($request->all(), [
            'user_payroll_value' => 'required',
            'user_payroll_overtime_hour' => 'required',
            'user_payroll_overtime_hour_total' => 'required',
            'user_payroll_total_accepted' => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }
       
        $res = $check_data->update([
            'user_payroll_value' => $request->user_payroll_value,
            'user_payroll_overtime_hour' => $request->user_payroll_overtime_hour,
            'user_payroll_overtime_hour_total' => $request->user_payroll_overtime_hour_total,
            'user_payroll_communication' => $request->user_payroll_communication,
            'user_payroll_meal_allowance' => $request->user_payroll_meal_allowance,
            'user_payroll_transport' => $request->user_payroll_transport,
            'user_payroll_income_json' => json_encode($request->user_payroll_income_json),
            'user_payroll_income_total' => $request->user_payroll_income_total,
            'user_payroll_absenteeism_cut' => $request->user_payroll_absenteeism_cut,
            'user_payroll_bpjs' => $request->user_payroll_bpjs,
            'user_payroll_deduct_json' => json_encode($request->user_payroll_deduct_json),
            'user_payroll_deduct_total' => $request->user_payroll_deduct_total,
            'user_payroll_total_accepted' => $request->user_payroll_total_accepted,
            'updated_at' => now()->addHours(7),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => convertResponseJson($check_data)
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal mengubah data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }

    public function send($id)
    {
        $check_data = UserPayroll::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        } else {
            if ($check_data->user_payroll_status == 'sent') {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Gaji sudah dikirim',
                    'result' => []
                ], 200);
            }
        }

        $res = $check_data->update([
            'user_payroll_status' => 'sent',
            'user_payroll_sent_at' => now()->addHours(7),
        ]);

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

        return response()->json($output, 200);
    }
}
