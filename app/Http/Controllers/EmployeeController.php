<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
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
            $sort = request()->query('sort') ?? 'employee_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');

            $query = DB::table('employees')
                ->select(
                    'employee_id',
                    'employee_code',
                    'employee_name',
                    'employee_address',
                    'employee_identity_address',
                    'employee_identity_number',
                    'employee_npwp',
                    'employee_bpjs_kes',
                    'employee_bpjs_tk',
                    'employee_place_birth',
                    'employee_position',
                    'employee_status',
                    'created_at',
                )
                ->where('deleted_at', null);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('employee_code', 'like', '%' . $search . '%')
                        ->orWhere('employee_name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('users')
                ->select('1 as total')
                ->where('deleted_at', null);
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => $res->items(),
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
        $validator = Validator::make(request()->all(), [
            'employee_name' => 'required',
            'employee_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('employee_image')) {
            $image = request()->file('employee_image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/employees', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }
        $employee_code = $this->generate_number();

        $user = Employee::create([
            'employee_code' => $employee_code,
            'employee_name' => request('employee_name'),
            'employee_address' => request('employee_address'),
            'employee_identity_address' => request('employee_identity_address'),
            'employee_identity_number' => request('employee_identity_number'),
            'employee_bpjs_kes' => request('employee_bpjs_kes'),
            'employee_bpjs_tk' => request('employee_bpjs_tk'),
            'employee_place_birth' => request('employee_place_birth'),
            'employee_education_json' => request('employee_education_json'),
            'employee_marital_status' => request('employee_marital_status'),
            'employee_number_children' => request('employee_number_children'),
            'employee_emergency_contact' => request('employee_emergency_contact'),
            'employee_entry_year' => request('employee_entry_year'),
            'employee_position' => request('employee_position'),
            'employee_image' => isset($image_url) ? $image_url : null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $data = DB::table('employees')
            ->select(
                'employee_id',
                'employee_code',
                'employee_name',
                'employee_address',
                'employee_identity_address',
                'employee_identity_number',
                'employee_npwp',
                'employee_bpjs_kes',
                'employee_bpjs_tk',
                'employee_place_birth',
                'employee_education_json',
                'employee_marital_status',
                'employee_number_children',
                'employee_emergency_contact',
                'employee_entry_year',
                'employee_position',
                'employee_status',
                'employee_image'
            )
            ->where('employee_id', $id)
            ->first();

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

    public function update(Request $request, $id)
    {
        $check_data = Employee::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $rules = [
            'employee_name' => 'required',
            'employee_status' => 'required|in:active,inactive',
            'employee_marital_status' => 'in:single,married,divorced',
        ];

        if ($request->hasFile('employee_image')) {
            $rules['employee_image'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules);


        //define validation rules
        if ($check_data->employee_image !== request()->file('employee_image')) {
            $validator = Validator::make(request()->all(), [
                'employee_name' => 'required',
                'employee_status' => 'required|in:active,inactive',
                'employee_marital_status' => 'in:single,married,divorced',
                'employee_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } else {
            $validator = Validator::make(request()->all(), [
                'employee_name' => 'required',
                'employee_status' => 'required|in:active,inactive',
                'employee_marital_status' => 'in:single,married,divorced',
            ]);
        }

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if ($request->file('employee_image')) {
            //upload image
            $image = request()->file('employee_image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/employees', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->employee_image) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->employee_image);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }
        }
        
        $res = $check_data->update([
            'employee_name'             => $request->employee_name,
            'employee_address'          => $request->employee_address,
            'employee_identity_address' => $request->employee_identity_address,
            'employee_identity_number'  => $request->employee_identity_number,
            'employee_npwp'             => $request->employee_npwp,
            'employee_bpjs_kes'         => $request->employee_bpjs_kes,
            'employee_bpjs_tk'          => $request->employee_bpjs_tk,
            'employee_place_birth'      => $request->employee_place_birth,
            'employee_education_json'   => $request->employee_education_json,
            'employee_marital_status'   => $request->employee_marital_status,
            'employee_number_children'  => $request->employee_number_children,
            'employee_emergency_contact'    => $request->employee_emergency_contact,
            'employee_entry_year'       => $request->employee_entry_year,
            'employee_position'         => $request->employee_position,
            'employee_status'           => $request->employee_status,
            'employee_image'            => isset($image_url) ? $image_url : $check_data->employee_image,
            'updated_at'                => date('Y-m-d H:i:s'),
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
        $check_data = Employee::find($id);

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
            'deleted_at' => date('Y-m-d H:i:s'),
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

    function generate_number()
    {
        $sequence = DB::table('employees')->count();

        $sequence = $sequence + 1;

        $number = "LBI" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        return $number;
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
