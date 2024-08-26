<?php

namespace App\Http\Controllers\superadmin;

use Illuminate\Http\Request;
use App\Models\Presence;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
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
            $date = request()->query('date');
            $company_id = request()->query('company_id');
            $status = request()->query('status');
            
            $query = Presence::query()
                ->select(
                    'presence.presence_id',
                    'presence.presence_user_id',
                    'users.user_name',
                    'users.user_company_id',
                    'companies.company_name',
                    'users.user_position',
                    'presence.presence_schedule_id',
                    'schedule_date',
                    DB::raw("CONCAT(shifts.shift_start_time, ' - ', shifts.shift_finish_time) as working_hours"),
                    DB::raw("TIME(presence.presence_in_time) as presence_in_time"),
                    DB::raw("TIME(presence.presence_out_time) as presence_out_time"),
                    'presence.presence_extra_time',
                    'schedules.schedule_status',
                    DB::raw("
                        CASE 
                            WHEN presence.presence_status IN ('in', 'out') THEN 'reguler'
                            ELSE 'overtime'
                        END as presence_status
                    ")
                )
                ->leftjoin('users', 'presence_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id')
                ->leftjoin('schedules', 'schedule_id', '=', 'presence_schedule_id')
                ->leftjoin('shifts', 'shift_id', '=', 'schedule_shift_id')
                ->whereNull('presence.deleted_at');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%');
                });
            }

            // Status condition
            if ($status && $status !== null) {
                $query->where(function ($query) use ($status) {
                    if ($status == 'regular') {
                        $query->whereIn('presence.presence_status', ['in', 'out']);
                    } elseif ($status == 'overtime') {
                        $query->whereIn('presence.presence_status', ['ovt_in', 'ovt_out']);
                    }
                });
            }

            if ($date && $date !== null) {
                $query->where('schedule_date', $date);
            }
            
            if ($company_id && $company_id !== null) {
                $query->where('company_id', $company_id);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Presence::query()
                ->select('1 as total')
                ->leftjoin('users', 'presence_user_id', '=', 'user_id')
                ->leftjoin('companies', 'user_company_id', '=', 'company_id')
                ->leftjoin('schedules', 'schedule_id', '=', 'presence_schedule_id')
                ->leftjoin('shifts', 'shift_id', '=', 'schedule_shift_id')
                ->whereNull('presence.deleted_at');
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
			
        $data = Presence::query()
        ->select(
            'presence.presence_id',
            'presence.presence_user_id',
            'users.user_code',
            'users.user_name',
            'users.user_company_id',
            'companies.company_name',
            'users.user_position',
            'presence.presence_schedule_id',
            'schedule_date',
            DB::raw('TIMEDIFF(shifts.shift_finish_time, shifts.shift_start_time) as shift_duration'),
            DB::raw("CONCAT(shifts.shift_start_time, ' - ', shifts.shift_finish_time) as working_hours"),
            DB::raw("TIME(presence.presence_in_time) as presence_in_time"),
            DB::raw("TIME(presence.presence_out_time) as presence_out_time"),
            'presence.presence_in_photo',
            'presence.presence_out_photo',
            'presence.presence_in_longitude',
            'presence.presence_in_latitude',
            'presence.presence_out_longitude',
            'presence.presence_out_latitude',
            'presence.presence_extra_time',
            'schedules.schedule_status',
            DB::raw("
                CASE 
                    WHEN presence.presence_status IN ('in', 'out') THEN 'reguler'
                    ELSE 'overtime'
                END as presence_status
            ")
        )
        ->leftjoin('users', 'presence_user_id', '=', 'user_id')
        ->leftjoin('companies', 'user_company_id', '=', 'company_id')
        ->leftjoin('schedules', 'schedule_id', '=', 'presence_schedule_id')
        ->leftjoin('shifts', 'shift_id', '=', 'schedule_shift_id')
        ->where('presence_id', $id)
        ->whereNull('presence.deleted_at')
        ->first();
        
        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data ? convertResponseSingle($data) : new \stdClass(),
        ];

        return response()->json($output, 200);
    }
}
