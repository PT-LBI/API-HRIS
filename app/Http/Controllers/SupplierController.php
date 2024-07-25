<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suppliers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SupplierExport;
use PDF;


class SupplierController extends Controller
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
            $sort = request()->query('sort') ?? 'product_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $province_id = request()->query('province_id');
            $district_id = request()->query('district_id');

            $query = DB::table('suppliers')
                ->select(
                    'supplier_id',
                    'supplier_name',
                    'supplier_type',
                    'supplier_other_name',
                    'supplier_phone_number',
                    'supplier_identity_number',
                    'supplier_npwp',
                    'supplier_address',
                    'supplier_province_id',
                    'supplier_province_name',
                    'supplier_district_id',
                    'supplier_district_name',
                    'supplier_status',
                    'supplier_image',
                    'created_at',
                    'updated_at',
                )
                ->where('deleted_at', null);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('supplier_name', 'like', '%' . $search . '%')
                        ->orWhere('supplier_other_name', 'like', '%' . $search . '%')
                        ->orWhere('supplier_type', 'like', '%' . $search . '%')
                        ->orWhere('supplier_phone_number', 'like', '%' . $search . '%');
                });
            }

            if ($province_id && $province_id !== null) {
                $query->where('supplier_province_id', $province_id);
            }
            if ($district_id && $district_id !== null) {
                $query->where('supplier_district_id', $district_id);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('suppliers')
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
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $validator = Validator::make(request()->all(),[
            'supplier_name' => 'required',
            'supplier_type' => 'required|in:jasa pengurus,bengkel,tukang,jasa docking,jasa urus dokumen,teknisi cold storage,pengangkutan',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }
        
        try {
            if (request()->file('image')) {
                $image = request()->file('image');
                $image_name = time() . '-' . $image->getClientOriginalName();
                $image_path = $image->storeAs('images/suppliers', $image_name, 'public');
                $image_url = env('APP_URL'). '/storage/' . $image_path;
            }

            $data = Suppliers::create([
                'supplier_name' => request('supplier_name'),
                'supplier_type' => request('supplier_type'),
                'supplier_other_name' => request('supplier_other_name'),
                'supplier_phone_number' => request('supplier_phone_number'),
                'supplier_identity_number' => request('supplier_identity_number'),
                'supplier_npwp' => request('supplier_npwp'),
                'supplier_address' => request('supplier_address'),
                'supplier_province_id' => request('supplier_province_id'),
                'supplier_province_name' => request('supplier_province_name'),
                'supplier_district_id' => request('supplier_district_id'),
                'supplier_district_name' => request('supplier_district_name'),
                'supplier_status' => 'active',
                'supplier_image' => isset($image_url) ? $image_url : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
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
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function detail($id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $data = DB::table('suppliers')
                ->select(
                    'supplier_id',
                    'supplier_name',
                    'supplier_type',
                    'supplier_other_name',
                    'supplier_phone_number',
                    'supplier_identity_number',
                    'supplier_npwp',
                    'supplier_address',
                    'supplier_province_id',
                    'supplier_province_name',
                    'supplier_district_id',
                    'supplier_district_name',
                    'supplier_status',
                    'supplier_image',
                    'created_at',
                    'updated_at',
                )
                ->where('supplier_id', $id)
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
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = Suppliers::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $rules = [
            'supplier_name' => 'required',
            'supplier_type' => 'required|in:jasa pengurus,bengkel,tukang,jasa docking,jasa urus dokumen,teknisi cold storage,pengangkutan',
            'supplier_status' => 'required|in:active,inactive',
        ];

        if ($request->hasFile('image')) {
            $rules['image'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if ($request->file('image')) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/suppliers', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->supplier_image) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->supplier_image);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }

        }

        $res = $check_data->update([
            'supplier_name'             => $request->supplier_name,
            'supplier_type'             => $request->supplier_type,
            'supplier_other_name'       => $request->supplier_other_name,
            'supplier_phone_number'     => $request->supplier_phone_number,
            'supplier_identity_number'  => $request->supplier_identity_number,
            'supplier_npwp'             => $request->supplier_npwp,
            'supplier_address'          => $request->supplier_address,
            'supplier_province_id'      => $request->supplier_province_id,
            'supplier_province_name'    => $request->supplier_province_name,
            'supplier_district_id'      => $request->supplier_district_id,
            'supplier_district_name'    => $request->supplier_district_name,
            'supplier_status'           => $request->supplier_status,
            'supplier_image'            => isset($image_url) ? $image_url : $check_data->supplier_image,
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
        $check_data = Suppliers::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

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
        $fileName = 'Supplier-' . $date . '.xlsx';
        Excel::store(new SupplierExport, $fileName, 'public');
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
        $province_id = request()->query('province_id');
        $district_id = request()->query('district_id');
        $search = request()->query('search');

        $query = DB::table('suppliers')
            ->select(
                'supplier_name',
                'supplier_type',
                'supplier_other_name',
                'supplier_phone_number',
                'supplier_identity_number',
                'supplier_npwp',
                'supplier_address',
                'supplier_province_name',
                'supplier_district_name',
                'supplier_status',
                'created_at',
            )
            ->where('deleted_at', null);

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('supplier_name', 'like', '%' . $search . '%')
                    ->orWhere('supplier_other_name', 'like', '%' . $search . '%')
                    ->orWhere('supplier_type', 'like', '%' . $search . '%')
                    ->orWhere('supplier_phone_number', 'like', '%' . $search . '%');
            });
        }

        if ($province_id && $province_id !== null) {
            $query->where('supplier_province_id', $province_id);
        }
        if ($district_id && $district_id !== null) {
            $query->where('supplier_district_id', $district_id);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Supplier',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('supplier', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'supplier-' . $date . '.pdf';
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
