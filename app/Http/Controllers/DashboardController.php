<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterProduct;
use App\Models\PurchaseOrder;
use App\Models\Transaction;
use App\Models\UserLog;
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

    public function user_log()
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
            $sort = request()->query('sort') ?? 'log_id';
            $dir = request()->query('dir') ?? 'DESC';

            $query = UserLog::select(
                'log_id',
                'log_user_id',
                'user_name as log_user_name',
                'log_type',
                'log_note',
                'user_logs.created_at',
            )
            ->leftjoin('users', 'user_id', '=', 'log_user_id');
            
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
}
