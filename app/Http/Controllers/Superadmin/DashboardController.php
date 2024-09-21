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

        $date = request()->query('date') ?? DATE('Y-m');

        try {
            $queryTotalTrx = DB::table('transactions')
                ->where('transaction_date', 'like', "$date%")
                ->where('transaction_status', 'completed');
            
            $totalTrx = $queryTotalTrx->sum('transaction_grandtotal');
            $totalTrxTax = $queryTotalTrx->sum('transaction_tax');
            
            $queryTotalPo = DB::table('pos')
                ->where('po_date', 'like', "$date%")
                ->where('po_status', 'completed');

            $totalPo = $queryTotalPo->sum('po_grandtotal');
            $totalPoTax = $queryTotalPo->sum('po_tax');
            
            $queryTotalExpenses = DB::table('expenses')
                ->where('expenses_date', 'like', "$date%")
                ->where('expenses_status', 'approved')
                ->sum('expenses_amount');

            $queryTotalProduct = DB::table('master_products')
                ->where('product_is_deleted', 0)
                ->count('product_id');
           
            $queryTotalCustomer = DB::table('users')
                ->where('user_is_deleted', 0)
                ->where('user_role', 'customer')
                ->count('user_id');
            
            $queryTotalSupplier = DB::table('suppliers')
                ->where('supplier_is_deleted', 0)
                ->count('supplier_id');

            $total_tax = $totalTrxTax + $totalPoTax;
            $income = $totalTrx - $totalPo - $queryTotalExpenses - $total_tax;
            $total_profit = 0;
            $total_loss = 0;

            if ($income > 0) {
                $total_profit = $income;
            } else {
                $total_loss = $income;
            }

            $data = [
                'total_trx' => intval($totalTrx),
                'total_po' => intval($totalPo),
                'total_expenses' => intval($queryTotalExpenses),
                'total_product' => $queryTotalProduct,
                'total_customer' => $queryTotalCustomer,
                'total_supplier' => $queryTotalSupplier,
                'total_tax' => $total_tax,
                'total_profit' => $total_profit,
                'total_loss' => $total_loss,
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
                    'presence.presence_schedule_id',
                    'schedule_date',
                    DB::raw("CONCAT(shifts.shift_start_time, ' - ', shifts.shift_finish_time) as working_hours"),
                    'presence_in_time',
                    DB::raw("
                        CASE
                            WHEN TIME(presence_in_time) > TIME(shifts.shift_start_time) THEN 'Late'
                            ELSE 'On Time'
                        END as presence_in_status
                    "),
                    'presence_out_time',
                    DB::raw("
                        CASE
                            WHEN TIME(presence_out_time) >= TIME(shifts.shift_finish_time) THEN 'On Time'
                            ELSE 'Late'
                        END as presence_out_status
                    "),
                    'presence.presence_extra_time',
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
                ->select('announcements.*')
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
