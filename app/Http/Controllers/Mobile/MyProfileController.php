<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Support\Facades\DB;

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
                'user_join_date',
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
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ];
        }

        return response()->json($output, 200);
    }
}