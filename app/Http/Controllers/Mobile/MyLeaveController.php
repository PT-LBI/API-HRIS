<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Storage;

class MyLeaveController extends Controller
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
            $type = request()->query('type');
            
            $query = Leave::query()
                ->select(
                    'leave_id',
                    'leave_type',
                    'leave_start_date',
                    'leave_end_date',
                    'leave_status',
                    'leave_desc',
                )
                ->where('deleted_at', null)
                ->where('leave_user_id', auth()->user()->user_id);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('leave_desc', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('leave_status', $status);
            }

            if ($type && $type !== null) {
                $query->where('leave_type', $type);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Leave::query()
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
            'leave_type' => 'required|in:leave,permission',
            'leave_start_date' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('leave_image')) {
            $image = request()->file('leave_image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/leave', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }

        $data = Leave::create([
            'leave_user_id'     => auth()->user()->user_id,
            'leave_type'        => request('leave_type'),
            'leave_start_date'  => request('leave_start_date'),
            'leave_end_date'    => request('leave_end_date '),
            'leave_desc'        => request('leave_desc'),
            'leave_image'       => isset($image_url) ? $image_url : null,
            'leave_status'      => 'waiting',
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
			
        $data = Leave::query()
        ->select(
            'leave_id',
            'leave_user_id',
            'user_name',
            'user_position',
            'company_id',
            'company_name',
            'leave_type',
            'leave_start_date',
            'leave_end_date',
            'leave_status',
            'leave_desc',
            'leave_image',
            'leave.created_at',
        )
        ->where('leave_id', $id)
        ->leftjoin('users', 'leave.leave_user_id', '=', 'users.user_id')
        ->leftjoin('companies', 'users.user_company_id', '=', 'companies.company_id')
        ->first();
        
        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => convertResponseSingle($data),
        ];

        return response()->json($output, 200);
    }
}
