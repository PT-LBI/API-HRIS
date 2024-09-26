<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;
use App\Models\UserPayroll;
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

    public function payroll()
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
            $sort = request()->query('sort') ?? 'company_name';
            $dir = request()->query('dir') ?? 'DESC';
            $year = request()->query('year') ?? date('Y');
            
            $query = Company::query()
            ->select(
                'company_id',
                'company_name',
                DB::raw("
                    (SELECT COALESCE(CAST(COUNT(user_id) AS SIGNED), 0)
                    FROM users
                    WHERE user_company_id = company_id
                    AND user_role NOT IN ('superadmin', 'owner')
                    AND deleted_at IS NULL
                    AND user_status = 'active'
                    ) AS total_employee
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_value) AS SIGNED), 0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_payroll_value
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_overtime_hour_total) AS SIGNED), 0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_overtime_hours
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_transport) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_transport
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_communication) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_communication
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_meal_allowance) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_meal_allowance
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_income_total) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_income
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_absenteeism_cut) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_absenteeism_cut
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_kes) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_kes
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_tk) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_tk
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_deduct_total) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_deductions
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_total_accepted) AS SIGNED),0)
                    FROM user_payrolls
                    LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                    WHERE users.user_company_id = company_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_accepted
                "),
            )
            ->whereNull('companies.deleted_at');
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = Company::query()
                ->select('1 as total')
                ->where('companies.deleted_at', null);
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

    public function payroll_company()
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
            $sort = request()->query('sort') ?? 'user_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $year = request()->query('year') ?? date('Y');
            $company_id = request()->query('company_id') ?? 1;
            
            $query = User::query()
            ->select(
                'users.user_id',
                'users.user_name',
                'users.user_code',
                'users.user_division_id',
                'divisions.division_name',
                'users.user_position',
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_value) AS SIGNED), 0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_payroll_value
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_overtime_hour_total) AS SIGNED), 0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_overtime_hours
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_transport) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_transport
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_communication) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_communication
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_meal_allowance) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_meal_allowance
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_income_total) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_income
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_absenteeism_cut) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_absenteeism_cut
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_kes) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_kes
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_tk) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_tk
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_deduct_total) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_deductions
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_total_accepted) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_accepted
                "),
            )
            ->leftjoin('divisions', 'user_division_id', 'division_id')
            ->leftJoin('companies', 'user_company_id', 'company_id')
            ->whereNull('users.deleted_at')
            ->whereNotIn('user_role', ['superadmin', 'owner'])
            ->where('user_status', 'active')
            ->where('user_company_id', $company_id);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%')
                        ->orWhere('user_code', 'like', '%' . $search . '%');
                });
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = User::query()
                ->select('1 as total')
                ->leftjoin('divisions', 'user_division_id', 'division_id')
                ->leftJoin('companies', 'user_company_id', 'company_id')
                ->whereNull('users.deleted_at')
                ->whereNotIn('user_role', ['superadmin', 'owner'])
                ->where('user_status', 'active')
                ->where('user_company_id', $company_id);
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

    public function payroll_user()
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
            $year = request()->query('year') ?? date('Y');
            $user_id = request()->query('user_id');
            
            $query = UserPayroll::query()
            ->select(
                DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%M %Y') as payroll_period"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_value) AS SIGNED), 0) AS total_payroll_value"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_overtime_hour_total) AS SIGNED), 0) AS total_overtime_hours"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_transport) AS SIGNED), 0) AS total_transport"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_communication) AS SIGNED), 0) AS total_communication"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_meal_allowance) AS SIGNED), 0) AS total_meal_allowance"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_income_total) AS SIGNED), 0) AS total_income"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_absenteeism_cut) AS SIGNED), 0) AS total_absenteeism_cut"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_bpjs_kes) AS SIGNED), 0) AS total_bpjs_kes"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_bpjs_tk) AS SIGNED), 0) AS total_bpjs_tk"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_deduct_total) AS SIGNED), 0) AS total_deductions"),
                DB::raw("COALESCE(CAST(SUM(user_payroll_total_accepted) AS SIGNED), 0) AS total_accepted")
            )
            ->where('user_payroll_user_id', $user_id)
            ->where('user_payroll_year', $year)
            ->whereNull('user_payrolls.deleted_at')
            ->groupBy('user_payroll_year', 'user_payroll_month')
            ->orderBy(DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%Y-%m')"), 'desc');

            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = UserPayroll::query()
                ->select('1 as total')
                ->where('user_payroll_user_id', $user_id)
                ->where('user_payroll_year', $year)
                ->whereNull('user_payrolls.deleted_at');
            $total_all = $queryTotal->count();

            $user = User::query()
                ->select(
                    'user_id',
                    'user_code',
                    'email',
                    'user_name',
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

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'header' => convertResponseSingle($user),
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
}
