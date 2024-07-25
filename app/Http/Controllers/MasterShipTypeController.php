<?php

namespace App\Http\Controllers;

use App\Models\MasterShipType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MasterShipTypeController extends Controller
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
            $sort = request()->query('sort') ?? 'ship_type_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');

            $query = DB::table('master_ship_types')
                ->select(
                    'ship_type_id',
                    'ship_type_name',
                    'created_at',
                    'updated_at',
                )
                ->where('ship_type_is_deleted', 0);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('ship_type_name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('master_ship_types')
                ->select('1 as total')
                ->where('ship_type_is_deleted', 0);
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'result' => $res->items(),
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
            'ship_type_name' => 'required|unique:master_ship_types',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $data = MasterShipType::create([
            'ship_type_name'    => request('ship_type_name'),
            'created_at'        => date('Y-m-d H:i:s'),
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
        //find data by ID
        $data = DB::table('master_ship_types')
            ->select(
                'ship_type_id',
                'ship_type_name',
                'created_at',
                'updated_at',
            )
            ->where('ship_type_id', $id)
            ->get()->first();

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
        $check_data = MasterShipType::find($id);

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
            'ship_type_name' => 'required|unique:master_ship_types,ship_type_name,' . $id . ',ship_type_id',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $res = $check_data->update([
            'ship_type_name'    => $request->ship_type_name,
            'updated_at'        => date('Y-m-d H:i:s'),
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
        $check_data = MasterShipType::find($id);

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
            'ship_type_is_deleted'   => 1,
            'updated_at'            => date('Y-m-d H:i:s'),
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
