<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index()
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = User::query()
            ->select(
                'user_id',
                'email',
                'user_name',
                'user_phone_number',
                'user_role',
                'user_identity_number',
                'user_npwp',
                'user_status',
                'user_join_date',
                'user_position',
                'user_province_id',
                'user_province_name',
                'user_district_id',
                'user_district_name',
                'user_profile_url',
                'created_at',
                'updated_at'
            )
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

    public function update(Request $request, $id)
    {
        $check_data = User::find(auth()->user()->user_id);

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
            'email' => 'required|email|unique:users,email,' . $id . ',user_id',
            'user_name' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if ($request->file('image')) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/users', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            //delete old image
            // Storage::delete('storage/images/users/'.basename($check_data->user_profile_url));
        }
       
        $res = $check_data->update([
            'email'                 => $request->email,
            'user_name'             => $request->user_name,
            'user_phone_number'     => $request->user_phone_number,
            'user_province_id'      => $request->user_province_id,
            'user_province_name'    => $request->user_province_name,
            'user_district_id'      => $request->user_district_id,
            'user_district_name'    => $request->user_district_name,
            'user_position'         => $request->user_position,
            'user_role'             => $request->user_role,
            'user_address'          => $request->user_address,
            'user_identity_number'  => $request->user_identity_number,
            'user_npwp'             => $request->user_npwp,
            'user_desc'             => $request->user_desc,
            'user_profile_url'      => isset($image_url) ? $image_url : $check_data->user_profile_url,
            'updated_at'            => now()->addHours(7),
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
