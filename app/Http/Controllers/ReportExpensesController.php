<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expenses;
use Illuminate\Support\Facades\DB;

class ReportExpensesController extends Controller
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
            $where = [['expenses_status', '=', 'approved'], ['expenses_is_deleted', '=', 0]];
            $where_widget = [];

            if (isset($month) && $month !== null) {
                $select = "DATE(expenses_date) as date";
                $group = "DATE(expenses_date) ";
                $where[] = [DB::raw('MONTH(expenses_date)'), '=', $month];
                $where_widget[] = [DB::raw('MONTH(expenses_date)'), '=', $month];
            } else {
                $select = "DATE_FORMAT(expenses_date, '%Y-%m') as date";
                $group = "DATE_FORMAT(expenses_date, '%Y-%m')";
            }

            if (isset($year) && $year !== null) {
                $where[] = [DB::raw('YEAR(expenses_date)'), '=', $year];
                $where_widget[] = [DB::raw('YEAR(expenses_date)'), '=', $year];
            }

            $query = Expenses::selectRaw("
                $select,
                COUNT(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_id ELSE NULL END) as total_transaction,
                CAST(SUM(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_amount ELSE 0 END) AS UNSIGNED) as total
            ")
            ->where($where)
            ->groupByRaw($group)
            ->orderBy($sort, $dir);

            $res = $query->paginate($limit, ['*'], 'page', $page);

            // get total data
            $queryTotal = Expenses::selectRaw('1 as total')
            ->where($where)
            ->groupByRaw($group);
            $total_all = $queryTotal->get()->count();

            // get widget
            $queryWidget = Expenses::selectRaw("
                COUNT(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_id ELSE NULL END) as total_transaction,
                CAST(SUM(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_amount ELSE 0 END) AS UNSIGNED) as total_nominal,
                0 as total_internal,
                0 as total_ship_note
            ")
            ->where($where_widget);

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_transaction' => $get_widget->total_transaction ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_internal' => $get_widget->total_internal ?? 0,
                'total_ship_note' => $get_widget->total_ship_note ?? 0
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
            $sort = request()->query('sort') ?? 'expenses_id';
            $dir = request()->query('dir') ?? 'DESC';
            $date = request()->query('date');
            $expenses_chart_account_id = request()->query('expenses_chart_account_id');
            $bank_id = request()->query('bank_id');

            $query = Expenses::select(
                'expenses_id',
                'expenses_number',
                'expenses_date',
                'expenses_chart_account_id',
                'master_chart_account_name',
                'master_chart_account_code',
                'expenses_amount',
                'expenses_status',
                'expenses_bank_id',
                'bank_name as expenses_bank_name',
                'expenses_image',
                'expenses_note',
                'expenses_pic_id',
                'user_name as expenses_pic_name',
                'expenses.created_at',
            )
            ->leftJoin('users', 'user_id', '=', 'expenses_pic_id')
            ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'expenses_chart_account_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'expenses_bank_id')
            ->where('expenses_status', 'approved')
            ->where('expenses_is_deleted', 0);
            
            if ($date && $date !== null) {
                $query->whereRaw('DATE(expenses_date) = ?', [$date]);
            }

            if ($bank_id && $bank_id !== null) {
                $query->where('expenses_bank_id', $bank_id);
            }
            
            if ($expenses_chart_account_id && $expenses_chart_account_id !== null) {
                $query->where('expenses_chart_account_id', $expenses_chart_account_id);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Expenses::select('1 as total')
            ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'expenses_chart_account_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'expenses_bank_id')
            ->where('expenses_status', 'approved')
            ->where('expenses_is_deleted', 0);

            if ($date && $date !== null) {
                $queryTotal->whereRaw('DATE(expenses_date) = ?', [$date]);
            }

            if ($bank_id && $bank_id !== null) {
                $queryTotal->where('expenses_bank_id', $bank_id);
            }
            
            if ($expenses_chart_account_id && $expenses_chart_account_id !== null) {
                $queryTotal->where('expenses_chart_account_id', $expenses_chart_account_id);
            }
            
            $total_all = $queryTotal->count();

            // get widget
            $queryWidget = Expenses::selectRaw("
                COUNT(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_id ELSE NULL END) as total_transaction,
                CAST(SUM(CASE WHEN expenses_status = 'approved' AND expenses_is_deleted = 0 THEN expenses_amount ELSE 0 END) AS UNSIGNED) as total_nominal,
                0 as total_internal,
                0 as total_ship_note
            ");

            if ($date && $date !== null) {
                $queryWidget->whereRaw('DATE(expenses_date) = ?', [$date]);
            }

            if ($bank_id && $bank_id !== null) {
                $queryWidget->where('expenses_bank_id', $bank_id);
            }
            
            if ($expenses_chart_account_id && $expenses_chart_account_id !== null) {
                $queryWidget->where('expenses_chart_account_id', $expenses_chart_account_id);
            }

            $get_widget = $queryWidget->get()->first();

            $widget = [
                'total_transaction' => $get_widget->total_transaction ?? 0,
                'total_nominal' => $get_widget->total_nominal ?? 0,
                'total_internal' => $get_widget->total_internal ?? 0,
                'total_ship_note' => $get_widget->total_ship_note ?? 0
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
}
