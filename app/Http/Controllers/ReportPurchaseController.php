<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportPurchaseExport;
use PDF;

class ReportPurchaseController extends Controller
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
            $where = [['po_status', '=', 'completed']];
            $where_widget = [];

            if (isset($month) && $month !== null) {
                $select = "DATE(po_date) as date";
                $group = "DATE(po_date) ";
                $where[] = [DB::raw('MONTH(po_date)'), '=', $month];
                $where_widget[] = [DB::raw('MONTH(po_date)'), '=', $month];
            } else {
                $select = "DATE_FORMAT(po_date, '%Y-%m') as date";
                $group = "DATE_FORMAT(po_date, '%Y-%m')";
            }

            if (isset($year) && $year !== null) {
                $where[] = [DB::raw('YEAR(po_date)'), '=', $year];
                $where_widget[] = [DB::raw('YEAR(po_date)'), '=', $year];
            }

            $query = PurchaseOrder::selectRaw("
                $select,
                COUNT(CASE WHEN po_status = 'completed' THEN po_id ELSE NULL END) as total_po,
                COUNT(DISTINCT po_supplier_id) as total_supplier,
                CAST(SUM(CASE WHEN po_payment_bank_id = 9 THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_cash,
                CAST(SUM(CASE WHEN po_payment_bank_id != 9 THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_transfer,
                CAST(SUM(po_grandtotal) AS UNSIGNED) as total
            ")
            ->where($where)
            ->groupByRaw($group)
            ->orderBy($sort, $dir);

            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = PurchaseOrder::selectRaw('1 as total')
            ->where($where)
            ->groupByRaw($group);
            $total_all = $queryTotal->get()->count();

            // get widget
            $queryWidget = PurchaseOrder::selectRaw("
                COUNT(CASE WHEN po_status = 'completed' THEN po_id ELSE NULL END) as total_po,
                CAST(SUM(CASE WHEN po_status IN ('waiting', 'processing') THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_hutang,
                CAST(SUM(CASE WHEN po_status = 'completed' THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal,
                COUNT(CASE WHEN po_status = 'cancelled' THEN po_id ELSE NULL END) as total_void
            ")
            ->where($where_widget);

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_po' => $get_widget->total_po ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_hutang' => $get_widget->total_hutang ?? 0,
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
            $sort = request()->query('sort') ?? 'po_id';
            $dir = request()->query('dir') ?? 'DESC';
            $date = request()->query('date');
            $po_payment_method = request()->query('po_payment_method');

            $query = PurchaseOrder::select(
                'po_id',
                'po_supplier_id',
                'supplier_name as po_supplier_name',
                'po_number',
                'po_pic_id',
                'user_name as po_pic_name',
                'po_date',
                'po_total_product',
                'po_total_product_qty',
                'po_subtotal',
                'po_grandtotal',
                'po_status',
                'po_status_receiving',
                'po_status_ship',
                'po_payment_status',
                'po_payment_bank_id',
                'bank_name as po_payment_bank_name',
                'pos.created_at',
            )
            ->leftJoin('suppliers', 'supplier_id', '=', 'po_supplier_id')
            ->leftJoin('users', 'user_id', '=', 'po_pic_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'po_payment_bank_id')
            ->where('po_status', 'completed');
            
            if ($date && $date !== null) {
                $query->whereRaw('DATE(po_date) = ?', [$date]);
            }

            if ($po_payment_method && $po_payment_method !== null) {
                if ($po_payment_method == 'cash') {
                    $po_payment_bank_id = 9;
                } else {
                    $po_payment_bank_id != 9;
                }
                $query->where('po_payment_bank_id', $po_payment_bank_id);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = PurchaseOrder::select('1 as total')
            ->leftJoin('suppliers', 'supplier_id', '=', 'po_supplier_id')
            ->leftJoin('users', 'user_id', '=', 'po_pic_id')
            ->where('po_status', 'completed');

            if ($date && $date !== null) {
                $queryTotal->whereRaw('DATE(po_date) = ?', [$date]);
            }
            
            $total_all = $queryTotal->count();

            // get widget
            $queryWidget = PurchaseOrder::selectRaw("
                COUNT(CASE WHEN po_status = 'completed' THEN po_id ELSE NULL END) as total_po,
                CAST(SUM(CASE WHEN po_status IN ('waiting', 'processing') THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal,
                CAST(SUM(CASE WHEN po_status = 'completed' THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_nominal,
                COUNT(CASE WHEN po_status = 'cancelled' THEN po_id ELSE NULL END) as total_void
            ");

            if ($date && $date !== null) {
                $queryWidget->whereRaw('DATE(po_date) = ?', [$date]);
            }

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_po' => $get_widget->total_po ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_hutang' => $get_widget->total_hutang ?? 0,
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
        $fileName = 'Report-Purchase-' . $date . '.xlsx';
        Excel::store(new ReportPurchaseExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mengubah data',
            'result'     => $url
        ];

        return response()->json($output, 200);

        // return Excel::download(new ReportPurchaseExport, "Report-Purchase--{$date}.xlsx");
    }

    public function export_pdf()
    {
        $year = request()->query('year');
        $month = request()->query('month');
        
        $select = '';
        $group = '';
        $where = [['po_status', '=', 'completed']];

        if (isset($month) && $month !== null) {
            $select = "DATE(po_date) as date";
            $group = "DATE(po_date) ";
            $where[] = [DB::raw('MONTH(po_date)'), '=', $month];
        } else {
            $select = "DATE_FORMAT(po_date, '%Y-%m') as date";
            $group = "DATE_FORMAT(po_date, '%Y-%m')";
        }

        if (isset($year) && $year !== null) {
            $where[] = [DB::raw('YEAR(po_date)'), '=', $year];
        }

        $data = PurchaseOrder::selectRaw("
            $select,
            COUNT(CASE WHEN po_status = 'completed' THEN po_id ELSE NULL END) as total_po,
            COUNT(DISTINCT po_supplier_id) as total_supplier,
            CAST(SUM(CASE WHEN po_payment_bank_id = 9 THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_cash,
            CAST(SUM(CASE WHEN po_payment_bank_id != 9 THEN po_grandtotal ELSE 0 END) AS UNSIGNED) as total_transfer,
            CAST(SUM(po_grandtotal) AS UNSIGNED) as total
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
            'title' => 'Data Report Purchase Order',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('report_purchase_order', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'report-purchase-order-' . $date . '.pdf';
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
