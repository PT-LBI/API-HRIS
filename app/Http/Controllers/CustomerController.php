<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerExport;
use PDF;

class CustomerController extends Controller
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
            $province_id = request()->query('province_id');
            $district_id = request()->query('district_id');

            $query = DB::table('users')
                ->select(
                    'user_id',
                    'user_name',
                    'user_phone_number',
                    'user_profile_url',
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
                    'user_desc',
                    'created_at',
                    'updated_at'
                )
                ->where('user_role', 'customer')
                ->where('deleted_at', null);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('email', 'like', '%' . $search . '%')
                        ->orWhere('user_name', 'like', '%' . $search . '%');
                });
            }

            if ($province_id && $province_id !== null) {
                $query->where('user_province_id', $province_id);
            }
            if ($district_id && $district_id !== null) {
                $query->where('user_district_id', $district_id);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('users')
                ->select('1 as total')
                ->where('user_role', 'customer')
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
            'user_name' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('image')) {
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/customers', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }

        $user = Customer::create([
            'user_name' => request('user_name'),
            'user_phone_number' => request('user_phone_number'),
            'user_role' => 'customer',
            'user_position' => '',
            'user_province_id' => request('user_province_id'),
            'user_province_name' => request('user_province_name'),
            'user_district_id' => request('user_district_id'),
            'user_district_name' => request('user_district_name'),
            'user_address' => request('user_address'),
            'user_identity_number' => request('user_identity_number'),
            'user_npwp' => request('user_npwp'),
            'user_desc' => request('user_desc'),
            'user_profile_url' => isset($image_url) ? $image_url : null,
            'user_desc' => request('user_desc'),
            'user_status' => 'active',
            'user_join_date' => date('Y-m-d'),
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
        $data = DB::table('users')
            ->select(
                'user_id',
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
                'user_desc',
                'created_at',
                'updated_at'
            )
            ->where('user_id', $id)
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
        $check_data = Customer::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $rules = [
            'user_status' => 'required|in:active,inactive',
        ];

        if ($request->hasFile('image')) {
            $rules['image'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules);


        //define validation rules
        if ($check_data->user_profile_url !== request()->file('image')) {
            $validator = Validator::make(request()->all(), [
                'user_status' => 'required|in:active,inactive',
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } else {
            $validator = Validator::make(request()->all(), [
                'user_status' => 'required|in:active,inactive',
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

        if ($request->file('image')) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/customers', $image_name, 'public');
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
            'user_phone_number'     => $request->user_phone_number,
            'user_province_id'      => $request->user_province_id,
            'user_province_name'    => $request->user_province_name,
            'user_district_id'      => $request->user_district_id,
            'user_district_name'    => $request->user_district_name,
            'user_address'          => $request->user_address,
            'user_identity_number'  => $request->user_identity_number,
            'user_npwp'             => $request->user_npwp,
            'user_desc'             => $request->user_desc,
            'user_status'           => $request->user_status,
            'user_profile_url'      => isset($image_url) ? $image_url : $check_data->user_profile_url,
            'updated_at'            => date('Y-m-d H:i:s'),
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
        $check_data = Customer::find($id);

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

    public function export_excel()
    {
        $date = date('ymd');
        $fileName = 'Customer-' . $date . '.xlsx';
        Excel::store(new CustomerExport, $fileName, 'public');
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
        $province_id = request()->query('province_id');
        $district_id = request()->query('district_id');

        $query = DB::table('users')
            ->select(
                'user_id',
                'user_name',
                'user_phone_number',
                'user_profile_url',
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
                'user_desc',
                'created_at',
                'updated_at'
            )
            ->where('user_role', 'customer')
            ->where('deleted_at', null);

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('email', 'like', '%' . $search . '%')
                    ->orWhere('user_name', 'like', '%' . $search . '%');
            });
        }

        if ($province_id && $province_id !== null) {
            $query->where('user_province_id', $province_id);
        }
        if ($district_id && $district_id !== null) {
            $query->where('user_district_id', $district_id);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Customer',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('customer', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'customer-' . $date . '.pdf';
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
