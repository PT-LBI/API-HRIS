<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
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
            $sort = request()->query('sort') ?? 'company_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $status = request()->query('status');
            
            $query = Company::query()
                ->select('companies.*')
                ->where('deleted_at', null);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('company_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('company_status', $status);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('companies')
                ->select('1 as total')
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
        $validator = Validator::make(request()->all(),[
            'company_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        // if (request()->file('image')) {
        //     $image = request()->file('image');
        //     $image_name = time() . '-' . $image->getClientOriginalName();
        //     $image_path = $image->storeAs('images/users', $image_name, 'public');
        //     $image_url = env('APP_URL'). '/storage/' . $image_path;
        // }

        $data = Company::create([
            'company_name'      => request('company_name'),
            'company_address'   => request('company_address'),
            'company_status'    => 'active',
            'created_at'        => now()->addHours(7),
            'updated_at'        => null,
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
			
        $data = Company::query()
            ->select('companies.*')
            ->where('company_id', $id)
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
                'result' => '',
            ];
        }

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = Company::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //define validation rules
        // if ($check_data->user_profile_url !== request()->file('image')) {
        //     $validator = Validator::make($request->all(), [
        //         'email' => 'required|email|unique:users,email,' . $id . ',user_id',
        //         'user_name' => 'required',
        //         'user_status' => 'required|in:active,inactive',
        //         // 'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //     ]);
        // } else {
            $validator = Validator::make($request->all(), [
                'company_name'      => 'required',
                'company_status'    => 'required|in:active,inactive',
            ]);
        // }

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        // if ($request->file('image')) {
        //     //upload image
        //     $image = request()->file('image');
        //     $image_name = time() . '-' . $image->getClientOriginalName();
        //     $image_path = $image->storeAs('images/users', $image_name, 'public');
        //     $image_url = env('APP_URL'). '/storage/' . $image_path;

        //     // Delete old image if it exists
        //     if ($check_data && $check_data->user_profile_url) {
        //         // Extract the relative path of the old image from the URL
        //         $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->user_profile_url);

        //         // Delete the old image
        //         Storage::disk('public')->delete($old_image_path);
        //     }

        // }
       
        $res = $check_data->update([
            'company_name'          => $request->company_name,
            'company_address'       => $request->company_address,
            // 'user_profile_url'      => isset($image_url) ? $image_url : $check_data->user_profile_url,
            'company_status'        => $request->company_status,
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
        $check_data = Company::find($id);

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
}
