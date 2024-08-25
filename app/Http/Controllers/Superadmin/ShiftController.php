<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\Shift;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
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
            $sort = request()->query('sort') ?? 'shift_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $status = request()->query('status');
            
            $query = Shift::query()
                ->select(
                    'shift_id',
                    'shift_name',
                    'shift_start_time',
                    'shift_finish_time',
                    'shift_status',
                    'created_at',
                )
                ->where('deleted_at', null);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('shift_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('shift_status', $status);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Shift::query()
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
            'shift_name' => 'required',
            'shift_start_time' => 'required',
            'shift_finish_time' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $data = Shift::create([
            'shift_name'        => request('shift_name'),
            'shift_start_time'  => request('shift_start_time'),
            'shift_finish_time' => request('shift_finish_time'),
            'shift_status'      => 'active',
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
			
        $data = Shift::query()
        ->select(
            'shift_id',
            'shift_name',
            'shift_start_time',
            'shift_finish_time',
            'shift_status',
            'created_at',
        )
        ->where('shift_id', $id)
        ->first();
        
        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data ? convertResponseSingle($data) : '',
        ];

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = Shift::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'shift_name'        => 'required',
            'shift_start_time'  => 'required',
            'shift_finish_time' => 'required',
            'shift_status'      => 'required|in:active,inactive',
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
            'shift_name'        => $request->shift_name,
            'shift_start_time'  => $request->shift_start_time,
            'shift_finish_time' => $request->shift_finish_time,
            'shift_status'      => $request->shift_status,
            'updated_at'        => now()->addHours(7),
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
        $check_data = Shift::find($id);

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
            'deleted_at'    => now()->addHours(7),
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
