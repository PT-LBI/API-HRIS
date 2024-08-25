<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;

class EmployeeController extends Controller
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
            $status = request()->query('status');
            $company_id = request()->query('company_id');
            $division_id = request()->query('division_id');

            $query = User::query()
                ->select(
                    'user_id',
                    'user_code',
                    'email',
                    'user_name',
                    'user_address',
                    'user_identity_address',
                    'user_identity_number',
                    'user_npwp',
                    'user_bpjs_kes',
                    'user_bpjs_tk',
                    'user_place_birth',
                    'user_company_id',
                    'company_name',
                    'user_division_id',
                    'division_name',
                    'user_status',
                    'users.created_at',
                )
                ->leftJoin('companies', 'user_company_id', '=', 'company_id')
                ->leftJoin('divisions', 'user_division_id', '=', 'division_id')
                ->where('user_role', 'staff')
                ->where('users.deleted_at', null);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_code', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('user_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('user_status', $status);
            }

            if ($division_id && $division_id !== null) {
                $query->where('user_division_id', $division_id);
            }

            if ($company_id && $company_id !== null) {
                $query->where('user_company_id', $company_id);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = User::query()
                ->select('1 as total')
                ->where('user_role', 'staff')
                ->where('deleted_at', null);
            $total_all = $queryTotal->count();

            $response = [
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
                'result' => $response,
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
        $validator = Validator::make(request()->all(), [
            'user_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|max:20',
            'user_company_id' => 'required|integer',
            'user_division_id' => 'required|integer',
            'user_role' => 'required',
            'user_profile_url' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('user_profile_url')) {
            $image = request()->file('user_profile_url');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/users', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }
        $user_code = generateCode();

        $user = User::create([
            'user_code' => $user_code,
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'user_name' => request('user_name'),
            'user_address' => request('user_address'),
            'user_identity_address' => request('user_identity_address'),
            'user_identity_number' => request('user_identity_number'),
            'user_bpjs_kes' => request('user_bpjs_kes'),
            'user_bpjs_tk' => request('user_bpjs_tk'),
            'user_place_birth' => request('user_place_birth'),
            'user_education_json' => request('user_education_json'),
            'user_marital_status' => request('user_marital_status'),
            'user_number_children' => request('user_number_children'),
            'user_phone_number' => request('user_phone_number'),
            'user_emergency_contact' => request('user_emergency_contact'),
            'user_entry_year' => request('user_entry_year'),
            'user_company_id' => request('user_company_id'),
            'user_division_id' => request('user_division_id'),
            'user_position' => request('user_position'),
            'user_role' => request('user_role'),
            'user_status' => 'active',
            'user_profile_url' => isset($image_url) ? $image_url : null,
            'created_at' => now()->addHours(7),
        ]);

        if ($user) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'    => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menambahkan data',
                'result'    => []
            ];
        }

        return response()->json($output, 200);
    }

    public function detail($id)
    {
        $data = User::query()
            ->select(
                'user_id',
                'user_code',
                'email',
                'user_name',
                'user_address',
                'user_identity_address',
                'user_identity_number',
                'user_npwp',
                'user_bpjs_kes',
                'user_bpjs_tk',
                'user_place_birth',
                'user_education_json',
                'user_marital_status',
                'user_number_children',
                'user_phone_number',
                'user_emergency_contact',
                'user_entry_year',
                'user_company_id',
                'company_name',
                'user_division_id',
                'division_name',
                'user_position',
                'user_status',
                'user_profile_url'
            )
            ->leftJoin('companies', 'user_company_id', '=', 'company_id')
            ->leftJoin('divisions', 'user_division_id', '=', 'division_id')
            ->where('user_id', $id)
            ->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data ? convertResponseSingle($data) :'',
            ];
        } else {
            $output = [
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => '',
            ];
        }
        return response()->json($output, 200);

    }

    public function update(Request $request, $id)
    {
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $rules = [
            'user_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id . ',user_id',
            'user_company_id' => 'required|integer',
            'user_division_id' => 'required|integer',
            'user_status' => 'required|in:active,inactive',
            'user_marital_status' => 'in:single,married,divorced',
        ];

        if ($request->hasFile('user_profile_url')) {
            $rules['user_profile_url'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules);


        //define validation rules
        // if ($check_data->user_profile_url !== request()->file('user_profile_url')) {
        //     $validator = Validator::make(request()->all(), [
        //         'user_name' => 'required',
        //         'user_status' => 'required|in:active,inactive',
        //         'user_marital_status' => 'in:single,married,divorced',
        //         'user_profile_url' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //     ]);
        // } else {
        //     $validator = Validator::make(request()->all(), [
        //         'user_name' => 'required',
        //         'user_status' => 'required|in:active,inactive',
        //         'user_marital_status' => 'in:single,married,divorced',
        //     ]);
        // }

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
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
            'user_name'             => $request->user_name,
            'email'                 => $request->email,
            'password'              => isset($request->password) ? Hash::make($request->password) : $check_data->password,
            'user_address'          => $request->user_address,
            'user_identity_address' => $request->user_identity_address,
            'user_identity_number'  => $request->user_identity_number,
            'user_npwp'             => $request->user_npwp,
            'user_bpjs_kes'         => $request->user_bpjs_kes,
            'user_bpjs_tk'          => $request->user_bpjs_tk,
            'user_place_birth'      => $request->user_place_birth,
            'user_date_birth'       => $request->user_date_birth,
            'user_education_json'   => $request->user_education_json,
            'user_marital_status'   => $request->user_marital_status,
            'user_number_children'  => $request->user_number_children,
            'user_emergency_contact'    => $request->user_emergency_contact,
            'user_entry_year'       => $request->user_entry_year,
            'user_company_id'       => $request->user_company_id,
            'user_division_id'      => $request->user_division_id,
            'user_position'         => $request->user_position,
            'user_position'         => $request->user_position,
            'user_status'           => $request->user_status,
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

    public function delete($id)
    {
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        // //delete image
        // Storage::delete('public/posts/'.basename($post->image));
        
        //soft delete post
        $res = $check_data->update([
            'deleted_at' => now()->addHours(7),
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

    // public function export_excel()
    // {
    //     $date = date('ymd');
    //     $fileName = 'Employee-' . $date . '.xlsx';
    //     Excel::store(new EmployeeExport, $fileName, 'public');
    //     $url = env('APP_URL'). '/storage/' . $fileName;

    //     $output = [
    //         'code'      => 200,
    //         'status'    => 'success',
    //         'message'   => 'Berhasil mendapatkan data',
    //         'result'     => $url
    //     ];

    //     return response()->json($output, 200);
    // }

    // public function export_pdf()
    // {
    //     $search = request()->query('search');
    //     $province_id = request()->query('province_id');
    //     $district_id = request()->query('district_id');

    //     $query = DB::table('users')
    //         ->select(
    //             'user_id',
    //             'user_name',
    //             'user_phone_number',
    //             'user_profile_url',
    //             'user_role',
    //             'user_identity_number',
    //             'user_npwp',
    //             'user_status',
    //             'user_join_date',
    //             'user_position',
    //             'user_province_id',
    //             'user_province_name',
    //             'user_district_id',
    //             'user_district_name',
    //             'user_address',
    //             'user_desc',
    //             'created_at',
    //             'updated_at'
    //         )
    //         ->where('user_role', 'Employee')
    //         ->where('deleted_at', null);

    //     if (!empty($search)) {
    //         $query->where(function ($query) use ($search) {
    //             $query->where('email', 'like', '%' . $search . '%')
    //                 ->orWhere('user_name', 'like', '%' . $search . '%');
    //         });
    //     }

    //     if ($province_id && $province_id !== null) {
    //         $query->where('user_province_id', $province_id);
    //     }
    //     if ($district_id && $district_id !== null) {
    //         $query->where('user_district_id', $district_id);
    //     }

    //     $data = $query->get();

    //     $number = 1;
    //     foreach ($data as $value) {
    //         $value->number = $number;
    //         $number++;
    //     }

    //     $data = [
    //         'title' => 'Data Employee',
    //         'date' => date('d/m/Y'),
    //         'result' => $data
    //     ];

    //     $pdf = PDF::loadView('Employee', $data)->setPaper('a4', 'landscape');

    //     $date = date('ymd');
    //     $fileName = 'Employee-' . $date . '.pdf';
    //     $pdf->save(storage_path('app/public/' . $fileName));
    //     $url = env('APP_URL'). '/storage/' . $fileName;

    //     $output = [
    //         'code'      => 200,
    //         'status'    => 'success',
    //         'message'   => 'Berhasil mendapatkan data',
    //         'result'     => $url
    //     ];

    //     return response()->json($output, 200);
    // }
}
