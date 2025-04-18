<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Leave;
use App\Models\Presence;
use App\Models\Announcement;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function widget()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        // $last_month = DATE('Y-m')-1;
        $last_month = Carbon::now()->subMonth()->format('Y-m');
        $today = request()->query('today') ?? Carbon::now()->day;

        try {
            $queryPresence = DB::table('presence')
                ->whereRaw('DATE(presence_in_time) = ?', [$today])
                ->whereIn('presence_status', ['in', 'out'])
                ->whereNull('presence.deleted_at')
                ->count();
            
            $queryLeave = DB::table('leave_details')
                ->leftJoin('leave', 'leave_id', '=', 'leave_detail_leave_id')
                ->where('leave_detail_date', "$today%")
                ->where('leave_status', 'approved')
                ->whereNull('leave.deleted_at')
                ->count('leave_detail_id');

            $queryPresencelate = DB::table('presence')
                ->join('schedules', 'presence_schedule_id', 'schedule_id')
                ->join('shifts', 'schedule_shift_id', '=', 'shift_id')
                ->whereRaw('DATE(presence_in_time) = ?', [$today])
                ->whereIn('presence_status', ['in', 'out'])
                ->whereRaw('TIME(presence.presence_in_time) > TIME(shifts.shift_start_time)') // Compare the times
                ->whereNull('presence.deleted_at')
                ->count('presence.presence_id');
            
            $queryEmployee = DB::table('users')
                ->where('user_status', 'active')
                ->whereNull('deleted_at')
                ->where('user_role', '<>', 'superadmin')
                // ->where('user_type', '!==', 'trainee')
                ->count('user_id');
                
            $queryEmployee = DB::table('users')
                ->where('user_status', 'active')
                ->whereNull('deleted_at')
                ->where('user_role', '<>', 'superadmin')
                // ->where('user_type', '!==', 'trainee')
                ->count('user_id');

            $queryEmployeeTrainee = DB::table('users')
                ->where('user_status', 'active')
                ->whereNull('deleted_at')
                ->where('user_role', '<>', 'superadmin')
                ->where('user_type', 'trainee')
                ->count('user_id');

            $data = [
                'total_presence' => intval($queryPresence),
                'total_leave' => intval($queryLeave),
                'total_presence_late' => $queryPresencelate,
                'total_payroll_last_month' => 0,
                'total_employee' => $queryEmployee,
                'total_employee_trainee' => $queryEmployeeTrainee,
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

    public function stock_warning()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $page = request()->query('page');
            $limit = request()->query('limit') ?? 5;
            $sort = request()->query('sort') ?? 'product_stock';
            $dir = request()->query('dir') ?? 'ASC';

            $query = MasterProduct::select(
                'product_id',
                'product_name',
                'product_sku',
                'product_unit',
                'product_stock',
            )
            ->where('product_is_deleted', 0)
            ->where('product_stock', '<', 10);
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            $data = [
                'result' => $res->items(),
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
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

    public function chart()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $today = Carbon::now()->day;

        try {
            // Generate a list of dates from the 1st to today
            $po_dates = [];
            for ($day = 1; $day <= $today; $day++) {
                $po_dates[] = Carbon::create($currentYear, $currentMonth, $day)->format('Y-m-d');
            }

            // Perform the query
            $queryResults = PurchaseOrder::select(
                DB::raw('DATE(po_date) as date'),
                DB::raw('CAST(SUM(CASE WHEN po_status = "completed" THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal')
            )
            ->whereYear('po_date', $currentYear)
            ->whereMonth('po_date', $currentMonth)
            ->groupBy(DB::raw('DATE(po_date)'))
            ->get()
            ->keyBy('date')
            ->toArray();

            // Merge generated dates with query results
            $data_po = [];
            foreach ($po_dates as $date) {
                $data_po[] = [
                    'date' => $date,
                    'total_nominal' => $queryResults[$date]['total_nominal'] ?? 0
                ];
            }

            // Generate a list of dates from the 1st to today
            $sales_dates = [];
            for ($day = 1; $day <= $today; $day++) {
                $sales_dates[] = Carbon::create($currentYear, $currentMonth, $day)->format('Y-m-d');
            }

            // Perform the query
            $queryResults = Transaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('CAST(SUM(CASE WHEN transaction_status = "completed" THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal')
            )
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->get()
            ->keyBy('date')
            ->toArray();

            // Merge generated dates with query results
            $data_sales = [];
            foreach ($sales_dates as $date) {
                $data_sales[] = [
                    'date' => $date,
                    'total_nominal' => $queryResults[$date]['total_nominal'] ?? 0
                ];
            }

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => [
                    'po' => $data_po,
                    'sales' => $data_sales,
                ]
            ];
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }
       
        return response()->json($output, 200);
    }

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
                    'users.user_profile_url',
                    'presence.presence_schedule_id',
                    'schedule_date',
                    DB::raw("CONCAT(shifts.shift_start_time, ' - ', shifts.shift_finish_time) as working_hours"),
                    'presence_in_time',
                    'presence_out_time',
                    'schedules.schedule_status',
                    DB::raw("
                        CASE 
                            WHEN presence.presence_status IN ('in', 'out') THEN 'reguler'
                            ELSE 'overtime'
                        END as presence_status
                    "),
                )
                ->leftjoin('users', 'presence_user_id', '=', 'user_id')
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

    public function leave()
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
            $sort = request()->query('sort') ?? 'leave.created_at';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $start_date = request()->query('start_date');
            $end_date = request()->query('end_date');
            $status = request()->query('status');
            
            $query = Leave::query()
                ->select(
                    'leave_id',
                    'leave_user_id',
                    'user_name',
                    'user_profile_url',
                    'leave_type',
                    'leave_start_date',
                    'leave_end_date',
                    'leave_status',
                    'leave_desc',
                    'leave.created_at',
                )
                ->where('leave.deleted_at', null)
                ->leftjoin('users', 'leave_user_id', '=', 'user_id');
            
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
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Leave::query()
                ->select('1 as total')
                ->where('leave.deleted_at', null)
                ->leftjoin('users', 'leave_user_id', '=', 'user_id');
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

    public function announcement()
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
            $sort = request()->query('sort') ?? 'announcement_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            
            $query = Announcement::query()
                ->select('announcement_id', 'announcement_title', 'created_at')
                ->where('deleted_at', null);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('announcement_title', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);
            
            //get total data
            $queryTotal = Announcement::query()
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
}
