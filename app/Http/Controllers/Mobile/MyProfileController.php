<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends Controller
{
    public function index()
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = DB::table('users')
            ->select(
                'user_id',
                'email',
                'user_name',
                'user_company_id',
                'company_name',
                'user_division_id',
                'division_name',
                'user_position',
                'user_identity_number',
                'user_phone_number',
                'user_emergency_contact',
                'user_address',
                'user_npwp',
                'user_status',
                DB::raw('DATE(user_join_date) as user_join_date'),
                'user_blood_type',
                'user_profile_url',
                'users.created_at',
            )
            ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
            ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id')
            ->where('user_id', auth()->user()->user_id)
            ->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => convertResponseSingle($data),
            ];
        } else {
            $output = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => new \stdClass(),
            ];
        }

        return response()->json($output, 200);
    }

    public function update_fcm_token(Request $request)
    {
        $id = auth()->user()->user_id;
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $rules = [
            'user_fcm_token' => 'required',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        $res = $check_data->update([
            'user_fcm_token'    => $request->user_fcm_token,
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => convertResponseSingle($check_data)
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