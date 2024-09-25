<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function presence()
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
            $sort = request()->query('sort') ?? 'user_name';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $company_id = request()->query('company_id');
            $month = request()->query('month') ?? date('m');
            $year = request()->query('year') ?? date('Y');
            
            $date = $year . '-' . $month;
            
            $query = User::query()
            ->select(
                'user_id',
                'user_name',
                'user_code',
                'user_division_id',
                'division_name',
                'user_position',
                'user_type',
                DB::raw("
                    (SELECT COUNT(presence_id) 
                    FROM presence 
                    WHERE presence_user_id = users.user_id 
                    AND presence_in_time LIKE '%$date%' 
                    AND presence_status IN ('in', 'out') 
                    AND deleted_at IS NULL) as total_presence
                "),
                DB::raw("
                    (SELECT COUNT(schedule_id) 
                        FROM schedules 
                        WHERE schedule_user_id = users.user_id 
                        AND schedule_date LIKE '%$date%' 
                        AND schedule_status = 'in' 
                        AND deleted_at IS NULL) - 
                    (SELECT COUNT(presence_id) 
                        FROM presence 
                        WHERE presence_user_id = users.user_id 
                        AND presence_in_time LIKE '%$date%' 
                        AND presence_status IN ('in', 'out') 
                        AND deleted_at IS NULL
                    ) as total_absence
                "),
                DB::raw("
                    (SELECT COUNT(schedule_id) 
                        FROM schedules 
                        WHERE schedule_user_id = users.user_id 
                        AND schedule_date LIKE '%$date%' 
                        AND schedule_status IN ('leave', 'permission') 
                        AND deleted_at IS NULL
                    ) as total_leave
                "),
                DB::raw("
                    (SELECT COUNT(CASE 
                        WHEN TIME(presence_in_time) > TIME(shifts.shift_start_time) THEN 1 
                        ELSE NULL 
                        END)
                    FROM presence 
                    LEFT JOIN schedules ON schedule_id = presence.presence_schedule_id
                    LEFT JOIN shifts ON shift_id = schedules.schedule_shift_id
                    WHERE presence_user_id = users.user_id 
                    AND presence_in_time LIKE '%$date%' 
                    AND presence_status IN ('in', 'out') 
                    AND presence.deleted_at IS NULL) as total_presence_late
                ")
            )
                ->whereNotIn('user_role', ['superadmin', 'owner'])
                ->where('users.deleted_at', null)
                ->leftJoin('divisions', 'user_division_id', '=', 'division_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%')
                        ->orWhere('user_code', 'like', '%' . $search . '%');
                });
            }

            if ($company_id && $company_id !== null) {
                $query->where('user_company_id', $company_id);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = User::query()
                ->select('1 as total')
                ->whereNotIn('user_role', ['superadmin', 'owner'])
                ->where('users.deleted_at', null)
                ->leftJoin('divisions', 'user_division_id', '=', 'division_id');
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

    public function presence_user()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $user_id = request()->query('user_id');
            $month = request()->query('month') ?? date('m');
            $year = request()->query('year') ?? date('Y');
            
            $dates = $this->getDatesOfMonth($year, $month);

            // Fetch all schedules and presences in a single query
            $schedules = DB::table('schedules')
                ->select('schedule_date', 'schedule_status', 'shift_start_time', 'shift_finish_time')
                ->leftJoin('shifts', 'schedule_shift_id', '=', 'shift_id')
                ->where('schedule_user_id', $user_id)
                ->where('schedule_date', 'like', "$year-$month%")
                ->whereNull('schedules.deleted_at')
                ->get()
                ->keyBy('schedule_date'); // Index by schedule_date for faster lookup

            $presences = DB::table('presence')
                ->select(
                    DB::raw('DATE(presence_in_time) as presence_date'), 
                    DB::raw('DATE_FORMAT(presence_in_time, "%H:%i") as presence_in_time'), 
                    DB::raw('DATE_FORMAT(presence_out_time, "%H:%i") as presence_out_time'),
                    'presence_status'
                )
                ->where('presence_user_id', $user_id)
                ->where('presence_in_time', 'like', "$year-$month%")
                ->whereIn('presence_status', ['in', 'out'])
                ->whereNull('deleted_at')
                ->get()
                ->keyBy('presence_date'); // Index by presence_date for faster lookup

            $results = [];
            $absence = 0;
            $alpha = 0;
            $leave = 0;
            $vacation = 0;

            foreach($dates as $day) {
                $schedule = $schedules->get($day);
                $presence = $presences->get($day);

                if ($schedule) {
                    // Check for leave or permission
                    if ($schedule->schedule_status == 'leave' || $schedule->schedule_status == 'permission') {
                        $status = 'Ijin/Cuti';
                        $remark = '';
                        $leave++;
                    } else {
                        // If presence exists, check the time
                        if ($presence) {
                            $inTime = $presence->presence_in_time;
                            $shiftStart = date('H:i:s', strtotime($schedule->shift_start_time));

                            if ($inTime > $shiftStart) {
                                $status = $presence->presence_in_time . ' - ' . $presence->presence_out_time;
                                $remark = 'Late';
                            } else {
                                $status = $presence->presence_in_time . ' - ' . $presence->presence_out_time;
                                $remark = 'On Time';
                            }
                            $absence++;
                        } else {
                            $status = 'Alpha'; // No presence data, considered absent
                            $remark = '';
                            $alpha++;
                        }
                    }
                } else {
                    $status = 'Libur'; // No schedule for this day
                    $remark = '';
                    $vacation++;
                }

                // Save the result for this day
                $results[] = [
                    'date' => $day,
                    'status' => $status,
                    'remark' => $remark
                ];
            }

            $user = User::query()
                ->select(
                    'user_id',
                    'user_code',
                    'user_name',
                    DB::raw('DATE(user_join_date) as user_join_date'),
                    'user_company_id',
                    'company_name',
                    'user_division_id',
                    'division_name',
                    'user_position',
                    'user_role',
                    'user_type',
                    'user_profile_url'
                )
            ->leftJoin('companies', 'user_company_id', '=', 'company_id')
            ->leftJoin('divisions', 'user_division_id', '=', 'division_id')
            ->where('user_id', $user_id)
            ->first();

            $user['period_presence'] = $year . '-' . $month;
            $user['presence'] = $absence;
            $user['alpha'] = $alpha;
            $user['leave'] = $leave;
            $user['vacation'] = $vacation;

            $data = [
                'header' => convertResponseSingle($user),
                'data' => $results
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

    private function getDatesOfMonth($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dates = [];
        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->copy()->toDateString();
            $startDate->addDay();
        }

        return $dates;
    }
}
