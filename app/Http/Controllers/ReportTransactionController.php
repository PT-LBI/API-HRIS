<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportSalesExport;
use PDF;

class ReportTransactionController extends Controller
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
            $sort = request()->query('sort') ?? 'date';
            $dir = request()->query('dir') ?? 'DESC';
            $year = request()->query('year');
            $month = request()->query('month');
            
            $select = '';
            $group = '';
            $where = [['transaction_status', '=', 'completed']];
            $where_widget = [];

            if (isset($month) && $month !== null) {
                $select = "DATE(transaction_date) as date";
                $group = "DATE(transaction_date) ";
                $where[] = [DB::raw('MONTH(transaction_date)'), '=', $month];
                $where_widget[] = [DB::raw('MONTH(transaction_date)'), '=', $month];
            } else {
                $select = "DATE_FORMAT(transaction_date, '%Y-%m') as date";
                $group = "DATE_FORMAT(transaction_date, '%Y-%m')";
            } 

            if (isset($year) && $year !== null) {
                $where[] = [DB::raw('YEAR(transaction_date)'), '=', $year];
                $where_widget[] = [DB::raw('YEAR(transaction_date)'), '=', $year];
            }

            $query = Transaction::selectRaw("
                $select,
                COUNT(CASE WHEN transaction_status = 'completed' THEN transaction_id ELSE NULL END) as total_trx,
                COUNT(DISTINCT transaction_customer_id) as total_customer,
                CAST(SUM(CASE WHEN transaction_payment_bank_id = 9 THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_cash,
                CAST(SUM(CASE WHEN transaction_payment_bank_id != 9 THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_transfer,
                CAST(SUM(transaction_grandtotal) AS UNSIGNED) as total
            ")
            ->where($where)
            ->groupByRaw($group)
            ->orderBy($sort, $dir);

            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = Transaction::selectRaw('1 as total')
            ->where($where)
            ->groupByRaw($group);
            $total_all = $queryTotal->get()->count();

            // get widget
            $queryWidget = Transaction::selectRaw("
                COUNT(CASE WHEN transaction_status = 'completed' THEN transaction_id ELSE NULL END) as total_trx,
                CAST(SUM(CASE WHEN transaction_status = 'completed' THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal,
                CAST(SUM(CASE WHEN transaction_status IN ('waiting', 'processing') THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_piutang,
                COUNT(CASE WHEN transaction_status = 'cancelled' THEN transaction_id ELSE NULL END) as total_void
            ")
            ->where($where_widget);

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_trx' => $get_widget->total_trx ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_piutang' => $get_widget->total_piutang ?? 0,
                'total_void' => $get_widget->total_void ?? 0
            ];

            $data = [
                'widget' => $widget,
                'result' => $res->items(),
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem() ? $res->firstItem() : 0,
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
            ];

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function daily()
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
            $sort = request()->query('sort') ?? 'transaction_id';
            $dir = request()->query('dir') ?? 'DESC';
            $date = request()->query('date');
            $transaction_payment_method = request()->query('transaction_payment_method');

            $query = Transaction::select(
                'transaction_id',
                'transaction_customer_id',
                'user_name as transaction_customer_name',
                'transaction_number',
                'transaction_pic_id',
                'transaction_date',
                'transaction_total_product',
                'transaction_total_product_qty',
                'transaction_subtotal',
                'transaction_grandtotal',
                'transaction_status',
                'transaction_status_delivery',
                'transaction_payment_bank_id',
                'bank_name as transaction_payment_bank_name',
                'transaction_payment_status',
            )
            ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id')
            ->where('transaction_status', 'completed');
            
            if ($date && $date !== null) {
                $query->whereRaw('DATE(transaction_date) = ?', [$date]);
            }

            if ($transaction_payment_method && $transaction_payment_method !== null) {
                if ($transaction_payment_method === 'cash') {
                    $transaction_payment_bank_id = 9;
                } else {
                    $transaction_payment_bank_id !== 9;
                }
                $query->where('transaction_payment_bank_id', $transaction_payment_bank_id);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Transaction::select('1 as total')
            ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
            ->where('transaction_status', 'completed');

            if ($date && $date !== null) {
                $queryTotal->whereRaw('DATE(transaction_date) = ?', [$date]);
            }
            
            $total_all = $queryTotal->count();

            // get widget
            $queryWidget = Transaction::selectRaw("
                COUNT(CASE WHEN transaction_status = 'completed' THEN transaction_id ELSE NULL END) as total_trx,
                CAST(SUM(CASE WHEN transaction_status = 'completed' THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal,
                CAST(SUM(CASE WHEN transaction_status IN ('waiting', 'processing') THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_piutang,
                COUNT(CASE WHEN transaction_status = 'cancelled' THEN transaction_id ELSE NULL END) as total_void
            ");

            if ($date && $date !== null) {
                $queryWidget->whereRaw('DATE(transaction_date) = ?', [$date]);
            }

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_trx' => $get_widget->total_trx ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_piutang' => $get_widget->total_piutang ?? 0,
                'total_void' => $get_widget->total_void ?? 0
            ];

            $data = [
                'widget' => $widget,
                'result' => $res->items(),
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
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

    public function export_excel()
    {
        $date = date('ymd');
        $fileName = 'Report-Sales-' . $date . '.xlsx';
        Excel::store(new ReportSalesExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mengubah data',
            'result'     => $url
        ];

        return response()->json($output, 200);

    }

    public function export_pdf()
    {
        $year = request()->query('year');
        $month = request()->query('month');
        
        $select = '';
        $group = '';
        $where = [['transaction_status', '=', 'completed']];

        if (isset($month) && $month !== null) {
            $select = "DATE(transaction_date) as date";
            $group = "DATE(transaction_date) ";
            $where[] = [DB::raw('MONTH(transaction_date)'), '=', $month];
        } else {
            $select = "DATE_FORMAT(transaction_date, '%Y-%m') as date";
            $group = "DATE_FORMAT(transaction_date, '%Y-%m')";
        } 

        if (isset($year) && $year !== null) {
            $where[] = [DB::raw('YEAR(transaction_date)'), '=', $year];
        }

        $data = Transaction::selectRaw("
            $select,
            COUNT(CASE WHEN transaction_status = 'completed' THEN transaction_id ELSE NULL END) as total_trx,
            COUNT(DISTINCT transaction_customer_id) as total_customer,
            CAST(SUM(CASE WHEN transaction_payment_bank_id = 9 THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_cash,
            CAST(SUM(CASE WHEN transaction_payment_bank_id != 9 THEN transaction_grandtotal ELSE 0 END) AS UNSIGNED) as total_transfer,
            CAST(SUM(transaction_grandtotal) AS UNSIGNED) as total
        ")
        ->where($where)
        ->groupByRaw($group)
        ->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Report Transaction',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('report_transaction', $data)->setPaper('a4', 'landscape');
        // return $pdf->download('report.pdf');
        
        $date = date('ymd');
        $fileName = 'report-transaction-' . $date . '.pdf';
        $pdf->save(storage_path('app/public/' . $fileName));
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);
    }
}
