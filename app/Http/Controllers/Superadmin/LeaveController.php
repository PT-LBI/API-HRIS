<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
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
            $start_date = request()->query('start_date');
            $end_date = request()->query('end_date');
            $company_id = request()->query('company_id');
            $status = request()->query('status');
            
            $query = Leave::query()
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
                )
                ->where('leave.deleted_at', null)
                ->leftjoin('users', 'leave_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('leave_status', $status);
            }
            
            if ($company_id && $company_id !== null) {
                $query->where('company_id', $company_id);
            }

            if ($start_date && $start_date !== null) {
                if ($end_date && $end_date !== null) {
                    $query->whereBetween('leave_start_date', [$start_date, $end_date]);
                } else{
                    $query->where('leave_start_date', $start_date);
                }
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Leave::query()
                ->select('1 as total')
                ->where('leave.deleted_at', null)
                ->leftjoin('users', 'leave_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id');
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
        ->leftjoin('users', 'leave_user_id', '=', 'user_id')
        ->leftjoin('companies', 'user_company_id', '=', 'company_id')
        ->first();
        
        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => convertResponseSingle($data),
        ];

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = Leave::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'leave_status'      => 'required|in:approved,rejected',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        try {
            DB::beginTransaction();

            $check_data->update([
                'leave_status'  => $request->leave_status,
                'updated_at'    => now()->addHours(7),
            ]);
    
            if ($request->leave_status == 'approved') {
                $this->create_schedule($check_data);
            }

            DB::commit();
    
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => convertResponseSingle($check_data)
            ];
        
        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function create_schedule($data)
    {
       $get_leave = LeaveDetail::where('leave_detail_leave_id', $data->leave_id)
            ->leftJoin('leave', 'leave_detail_leave_id', '=', 'leave_id')
            ->select('leave_detail_date', 'leave_user_id', 'leave_type', 'leave_desc')
            ->get();

        if ($get_leave->count() > 0) {
           foreach ($get_leave as $value) {
                $check_schedule = Schedule::where('schedule_date', $value['leave_detail_date'])
                    ->where('schedule_user_id', $value['leave_user_id'])
                    ->first();

                if ($check_schedule) {
                    $check_schedule->update([
                        'schedule_status' => $value['leave_type'],
                        'schedule_leave_id' => $data->leave_id,
                        'updated_at' => now()->addHours(7),
                    ]);
                } else {
                    Schedule::create([
                        'schedule_user_id'  => $value['leave_user_id'],
                        'schedule_date'     => $value['leave_detail_date'],
                        'schedule_status'   => $value['leave_type'],
                        'schedule_note'     => $value['leave_desc'],
                        'schedule_leave_id' => $data->leave_id,
                        'created_at'        => now()->addHours(7),
                        'updated_at'        => null,
                    ]);
                }
           }
        }

        return true;
    }

}