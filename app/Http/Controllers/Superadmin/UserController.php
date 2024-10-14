<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserExport;
use PDF;

class UserController extends Controller
{
    public function index()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $page = request()->query('page');
            $limit = request()->query('limit') ?? 10;
            $sort = request()->query('sort') ?? 'user_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $role = request()->query('role');
            
            $query = User::query()
                ->select(
                    'user_id',
                    'email',
                    'user_name',
                    'user_phone_number',
                    'user_profile_url',
                    'user_identity_number',
                    'user_npwp',
                    'user_role',
                    'user_status',
                    'user_join_date',
                    'user_position',
                    'user_province_id',
                    'user_province_name',
                    'user_district_id',
                    'user_district_name',
                    'user_address',
                    'user_location_id',
                    'location_name',
                    'users.created_at',
                    'users.updated_at'
                )
                ->whereIn('user_role', ['admin', 'finance', 'warehouse', 'owner'])
                ->where('users.deleted_at', null)
                ->leftJoin('master_locations', 'user_location_id', '=', 'location_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('user_phone_number', 'like', '%' . $search . '%');
                });
            }

            if ($role && $role !== null) {
                $query->where('user_role', $role);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = User::query()
                ->select('1 as total')
                ->whereIn('user_role', ['admin', 'finance', 'warehouse', 'owner'])
                ->where('users.deleted_at', null)
                ->leftJoin('master_locations', 'user_location_id', '=', 'location_id');;
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => convertResponseArray($res->items()),
            ];

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function create()
    {
        $validator = Validator::make(request()->all(),[
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'user_name' => 'required',
            'user_role' => 'required|in:admin,finance,warehouse,owner',
            'user_position' => 'required',
            'user_location_id' => 'required',
            'user_profile_url' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        if (request()->file('user_profile_url')) {
            $image = request()->file('user_profile_url');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/users', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }
        $user_code = generateCode();

        $data = User::create([
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'user_name' => request('user_name'),
            'user_code' => $user_code,
            'user_phone_number' => request('user_phone_number'),
            'user_role' => request('user_role'),
            'user_position' => request('user_position'),
            'user_province_id' => request('user_province_id'),
            'user_province_name' => request('user_province_name'),
            'user_district_id' => request('user_district_id'),
            'user_district_name' => request('user_district_name'),
            'user_address' => request('user_address'),
            'user_identity_number' => request('user_identity_number'),
            'user_npwp' => request('user_npwp'),
            'user_profile_url' => isset($image_url) ? $image_url : null,
            'user_location_id' => request('user_location_id'),
            'user_desc' => request('user_desc'),
            'user_status' => 'active',
            'user_join_date' => date('Y-m-d'),
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
                'user_address',
                'user_profile_url',
                'user_location_id',
                'location_name',
                'user_desc',
                'users.created_at',
                'users.updated_at'
            )
            ->leftJoin('master_locations', 'user_location_id', '=', 'location_id')
            ->where('user_id', $id)
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
                'result' => [],
            ];
        }

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        //define validation rules
        if ($check_data->user_profile_url !== request()->file('user_profile_url')) {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $id . ',user_id',
                'user_name' => 'required',
                'user_location_id' => 'required',
                'user_status' => 'required|in:active,inactive',
                // 'user_profile_url' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $id . ',user_id',
                'user_name' => 'required',
                'user_location_id' => 'required',
                'user_status' => 'required|in:active,inactive',
            ]);
        }

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        if ($request->file('user_profile_url')) {
            //upload image
            $image = request()->file('user_profile_url');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/users', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->user_profile_url) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->user_profile_url);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }

        }
       
        $res = $check_data->update([
            'email'                 => $request->email,
            'password'              => Hash::make($request->password),
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
            'user_location_id'      => $request->user_location_id,
            'user_desc'             => $request->user_desc,
            'user_profile_url'      => isset($image_url) ? $image_url : $check_data->user_profile_url,
            'user_status'           => $request->user_status,
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

    public function delete($id)
    {
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        //soft delete post
        $res = $check_data->update([
            'deleted_at'        => now()->addHours(7),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menghapus data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menghapus data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);

    }

    public function export_excel()
    {
        $date = date('ymd');
        $fileName = 'User-' . $date . '.xlsx';
        Excel::store(new UserExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);
    }

    public function export_pdf()
    {
        $search = request()->query('search');
        $role = request()->query('role');
        
        $query = DB::table('users')
            ->select(
                'user_id',
                'email',
                'user_name',
                'user_phone_number',
                'user_profile_url',
                'user_identity_number',
                'user_npwp',
                'user_role',
                'user_status',
                'user_join_date',
                'user_position',
                'user_province_id',
                'user_province_name',
                'user_district_id',
                'user_district_name',
                'user_address',
            )
            ->whereIn('user_role', ['admin', 'finance', 'warehouse', 'owner'])
            ->where('deleted_at', null);
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('user_phone_number', 'like', '%' . $search . '%');
            });
        }

        if ($role && $role !== null) {
            $query->where('user_role', $role);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data User',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('user', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'user-' . $date . '.pdf';
        $pdf->save(storage_path('app/public/' . $fileName));
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);
    }
}
