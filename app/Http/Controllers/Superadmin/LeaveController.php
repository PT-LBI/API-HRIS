<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Schedule;
use App\Models\LogNotif;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Notifications\SendNotif;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveExport;


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
            $sort = request()->query('sort') ?? 'leave_id';
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
                    'leave_image',
                    'leave_desc',
                )
                ->leftjoin('users', 'leave_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id')
                ->where('leave.deleted_at', null)
                ->where('users.deleted_at', null)
                ->where('users.user_status', 'active');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('leave_status', $status);
            }
            
            if ($start_date && $start_date !== null) {
                if ($end_date && $end_date !== null) {
                    $query->whereBetween('leave_start_date', [$start_date, $end_date]);
                } else{
                    $query->where('leave_start_date', $start_date);
                }
            }

            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $query->where('company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $query->where('company_id', $company_id);
                }
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Leave::query()
                ->select('1 as total')
                ->leftjoin('users', 'leave_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id')
                ->where('leave.deleted_at', null)
                ->where('users.deleted_at', null)
                ->where('users.user_status', 'active');
                
            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $queryTotal->where('company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $queryTotal->where('company_id', $company_id);
                }
            }
            
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
            'result' => $data ? convertResponseSingle($data) : new \stdClass(),
        ];

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {

        $check_data = Leave::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_status' => 'required|in:approved,rejected',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
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

            // Kirim notifikasi
            // $token = 'fO3BOQCpSYaij9eUOQShtB:APA91bFEvScHJa8hvKck6OTW2t89fK0s4viaBo4FmvGNAU4tQ7a86xu0yo-wPscJlG-JG2WS4fzhQx_VCtRIQjHiL7tsX4JXUuup0PkIrcaFBDzYJYqABWM';
            $token = 'd5ZaHgeRSAGR3MhnfA1JU7:APA91bHCf-LsNnUqHWT_iA-gX5sziLm5Fk77TSidXdvC4iG69izc8ufAIbNEThg7uuX9PzKNGN8Y_ecgxxo1rT48w770at_JcURLu8iyNxXIY92IwZySbbfCCXA991pHcDPhzNHNqM-6';
            $title = 'Cuti';
            $body = 'Status cuti Anda telah diupdate menjadi ' . $request->leave_status;

            // sendFirebaseNotification($token, $title, $body);

            // $get_user = User::find($check_data->leave_user_id);

            // if($is_send) {
                $dataNotif = [
                    'type' => 'leave',
                    'icon' => '',
                    'title' => 'Cuti anda telah di ' . $request->leave_status,
                    'body' => $body,
                    'sound' => 'default',
                    'data' => [
                        'id' => $id,
                        'code' => '',
                        'title' => 'Cuti anda telah di ' . $request->leave_status,
                        'msg' => $body,
                        'image_url' => '',
                    ],
                ];

                // // Mengonversi array menjadi JSON string
                $logNotifJson = json_encode($dataNotif);


                LogNotif::create([
                    'log_notif_user_id' => $check_data->leave_user_id,
                    'log_notif_data_json' => $logNotifJson,
                    'log_notif_is_read' => 0,
                    'created_at' => now()->addHours(7),
                ]);
            // }

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

    public function export_excel()
    {
        $date = date('ymdhm');
        $fileName = 'Leave-' . $date . '.xlsx';
        Excel::store(new LeaveExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'    => $url
        ];

        return response()->json($output, 200);
    }

}
