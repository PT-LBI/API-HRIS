<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminMenuController extends Controller
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

            $query = DB::table('admin_menus')
                ->select(
                    'menu_id',
                    'menu_parent_id',
                    'menu_key',
                    'menu_title',
                    'menu_icon',
                    'menu_role',
                    'menu_status',
                    'created_at'
                );

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('menu_title', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('admin_menus')
                ->select('1 as total');
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
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $validator = Validator::make(request()->all(),[
            'menu_title' => 'required',
            'menu_role' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }
        
        try {
            $data = AdminMenu::create([
                'menu_parent_id' => request('menu_parent_id'),
                'menu_key' => request('menu_key'),
                'menu_title' => request('menu_title'),
                'menu_icon' => request('menu_icon'),
                'menu_role' => request('menu_role'),
                'menu_status' => 'active',
                'created_at' => now(),
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
            $data = DB::table('admin_menus')
                ->select(
                    'menu_id',
                    'menu_parent_id',
                    'menu_key',
                    'menu_title',
                    'menu_icon',
                    'menu_role',
                    'menu_status',
                    'created_at'
                )
                ->where('menu_id', $id)
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
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = AdminMenu::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $rules = [
            'menu_title' => 'required',
            'menu_role' => 'required',
            'menu_status' => 'required|in:active,inactive',
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
            'menu_parent_id'    => $request->menu_parent_id,
            'menu_key'          => $request->menu_key,
            'menu_title'        => $request->menu_title,
            'menu_icon'         => $request->menu_icon,
            'menu_role'         => $request->menu_role,
            'menu_status'       => $request->menu_status,
            'updated_at'        => now(),
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
        $check_data = AdminMenu::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $res = $check_data->delete();

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
