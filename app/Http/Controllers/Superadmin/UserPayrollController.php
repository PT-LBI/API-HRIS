<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
// use App\Models\MasterPayroll;
use App\Models\UserPayroll;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;

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
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        $check_data = UserPayroll::where('user_payroll_user_id', request('user_payroll_user_id'))
            ->where('user_payroll_month', request('user_payroll_month'))
            ->where('user_payroll_year', request('user_payroll_year'))
            ->first();

        if ($check_data) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => 'Data sudah ada',
                'result' => []
            ], 422);
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
            'user_payroll_bpjs_kes' => request('user_payroll_bpjs_kes'),
            'user_payroll_bpjs_tk' => request('user_payroll_bpjs_tk'),
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
                DB::raw('TIMESTAMPDIFF(YEAR, user_join_date, CURDATE()) as years_since_join'),
                DB::raw('DATEDIFF(CURDATE(), DATE_ADD(user_join_date, INTERVAL TIMESTAMPDIFF(YEAR, user_join_date, CURDATE()) YEAR)) as days_since_join'),
            )
            ->leftjoin('users', 'user_id', '=', 'user_payroll_user_id')
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
                'code' => 404,
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
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        } else {
            if ($check_data->user_payroll_status == 'sent') {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Gaji sudah dikirim, tidak bisa diubah',
                    'result' => []
                ], 422);
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
            ], 422);
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
            'user_payroll_bpjs_kes' => $request->user_payroll_bpjs_kes,
            'user_payroll_bpjs_tk' => $request->user_payroll_bpjs_tk,
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
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        } else {
            if ($check_data->user_payroll_status == 'sent') {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Gaji sudah dikirim',
                    'result' => []
                ], 422);
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

    public function slip_payroll($id)
    {
        $data = UserPayroll::query()
            ->select(
                'user_payrolls.*',
                DB::raw("MONTHNAME(STR_TO_DATE(user_payroll_month, '%m')) as user_payroll_month_name"),
                'user_name',
                'user_code',
                'user_position',
                'company_name',
            )
            ->join('users', 'user_id', '=', 'user_payroll_user_id')
            ->join('companies', 'company_id', '=', 'user_company_id')
            ->where('user_payroll_id', $id)
            ->first();

        $data->formatted_sent_at = Carbon::parse($data->sent_at)->translatedFormat('d F Y');
        $pdf = PDF::loadView('slip_payroll', ['result' => $data]);
        $fileName = 'slip_gaji_'.$data['user_code'].'_'.$data['user_payroll_year'].'_'.$data['user_payroll_month'] . '.pdf';
        $pdf->save(storage_path('app/public/' . $fileName));
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'    => [
                'name'  => $fileName,
                'url' => $url
            ]
        ];

        return response()->json($output, 200);
        
    }
}
