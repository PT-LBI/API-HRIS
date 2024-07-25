<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Expenses;
use App\Models\Transaction;
use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportIncomeExport;
use PDF;

class ReportIncomeController extends Controller
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
            $year = request()->query('year') ?? Carbon::now()->year;

            // Transactions with status 'completed'
            $totalSales = (int) Transaction::whereYear('transaction_date', $year)
                ->where('transaction_status', 'completed')
                ->sum(DB::raw('CAST(transaction_grandtotal AS UNSIGNED)'));

            $totalSalesTax = (int) Transaction::whereYear('transaction_date', $year)
                ->where('transaction_status', 'completed')
                ->sum(DB::raw('CAST(transaction_tax AS UNSIGNED)'));

            // Purchase Orders
            $totalPurchase = (int) PurchaseOrder::whereYear('po_date', $year)
                ->where('po_status', 'completed')
                ->sum(DB::raw('CAST(po_grandtotal AS UNSIGNED)'));

            $totalPurchaseTax = (int) PurchaseOrder::whereYear('po_date', $year)
                ->where('po_status', 'completed')
                ->sum(DB::raw('CAST(po_tax AS UNSIGNED)'));

            // Expenses
            $totalExpenses = (int) Expenses::whereYear('expenses_date', $year)
                ->where('expenses_status', 'approved')
                ->sum('expenses_amount');

            // Net Income
            $netIncome = $totalSales - $totalPurchase - $totalExpenses - $totalSalesTax - $totalPurchaseTax;

            // Format data for widgets
            $widget = [
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchase,
                'total_expenses' => $totalExpenses,
                'total_income' => $netIncome
            ];

            if ($year == Carbon::now()->year) {
                $currentMonth = Carbon::now()->month;
            } else {
                $currentMonth = 12;
            }
        
            // Transactions
            $transactions = Transaction::select(
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('CAST(SUM(transaction_grandtotal) AS UNSIGNED) as total_sales'),
                DB::raw('CAST(SUM(transaction_tax) AS UNSIGNED) as total_sales_tax')
            )
            ->whereYear('transaction_date', $year)
            ->where('transaction_status', 'completed')
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->get();
        
            // Purchase Orders
            $purchaseOrders = PurchaseOrder::select(
                DB::raw('MONTH(po_date) as month'),
                DB::raw('CAST(SUM(po_grandtotal) AS UNSIGNED) as total_purchase'),
                DB::raw('CAST(SUM(po_tax) AS UNSIGNED) as total_purchase_tax')
            )
            ->whereYear('po_date', $year)
            ->where('po_status', 'completed')
            ->groupBy(DB::raw('MONTH(po_date)'))
            ->get();
        
            // Expenses
            $expenses = Expenses::select(
                DB::raw('MONTH(expenses_date) as month'),
                DB::raw('CAST(SUM(expenses_amount) AS UNSIGNED) as total_expenses')
            )
            ->whereYear('expenses_date', $year)
            ->where('expenses_status', 'approved')
            ->groupBy(DB::raw('MONTH(expenses_date)'))
            ->pluck('total_expenses', 'month');
        
            $data = [];
            for ($month = 1; $month <= $currentMonth; $month++) {
                $total_sales = $transactions->where('month', $month)->sum('total_sales');
                $total_sales_tax = $transactions->where('month', $month)->sum('total_sales_tax');
                $total_purchase = $purchaseOrders->where('month', $month)->sum('total_purchase');
                $total_purchase_tax = $purchaseOrders->where('month', $month)->sum('total_purchase_tax');
                $total_expenses = $expenses->get($month, 0);
                $total_tax = $total_sales_tax + $total_purchase_tax;
                $net_income = $total_sales - $total_purchase - $total_expenses - $total_tax;
        
                $data[] = [
                    'month' => Carbon::create($year, $month, 1)->format('F Y'),
                    'total_sales' => $total_sales,
                    'total_purchase' => $total_purchase,
                    'total_expenses' => $total_expenses,
                    'total_tax' => $total_tax,
                    'total_income' => $net_income
                ];
            }
        
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => [
                    'widget' => $widget,
                    'result' => $data
                ]
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
        $fileName = 'Report-Laba-Rugi-' . $date . '.xlsx';
        Excel::store(new ReportIncomeExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mengubah data',
            'result'     => $url
        ];

        return response()->json($output, 200);

        // return Excel::download(new ReportIncomeExport, "Report-Laba-Rugi-{$date}.xlsx");
    }

    public function export_pdf()
    {
        $year = request()->query('year') ?? Carbon::now()->year;
        if ($year == Carbon::now()->year) {
            $currentMonth = Carbon::now()->month;
        } else {
            $currentMonth = 12;
        }
    
        // Transactions
        $transactions = Transaction::select(
            DB::raw('MONTH(transaction_date) as month'),
            DB::raw('CAST(SUM(transaction_grandtotal) AS UNSIGNED) as total_sales'),
            DB::raw('CAST(SUM(transaction_tax) AS UNSIGNED) as total_sales_tax')
        )
        ->whereYear('transaction_date', $year)
        ->where('transaction_status', 'completed')
        ->groupBy(DB::raw('MONTH(transaction_date)'))
        ->get();
    
        // Purchase Orders
        $purchaseOrders = PurchaseOrder::select(
            DB::raw('MONTH(po_date) as month'),
            DB::raw('CAST(SUM(po_grandtotal) AS UNSIGNED) as total_purchase'),
            DB::raw('CAST(SUM(po_tax) AS UNSIGNED) as total_purchase_tax')
        )
        ->whereYear('po_date', $year)
        ->where('po_status', 'completed')
        ->groupBy(DB::raw('MONTH(po_date)'))
        ->get();
    
        // Expenses
        $expenses = Expenses::select(
            DB::raw('MONTH(expenses_date) as month'),
            DB::raw('CAST(SUM(expenses_amount) AS UNSIGNED) as total_expenses')
        )
        ->whereYear('expenses_date', $year)
        ->where('expenses_status', 'approved')
        ->groupBy(DB::raw('MONTH(expenses_date)'))
        ->pluck('total_expenses', 'month');
    
        $newdata = [];
        // $number = 1;
        for ($month = 1; $month <= $currentMonth; $month++) {
            $total_sales = $transactions->where('month', $month)->sum('total_sales');
            $total_sales_tax = $transactions->where('month', $month)->sum('total_sales_tax');
            $total_purchase = $purchaseOrders->where('month', $month)->sum('total_purchase');
            $total_purchase_tax = $purchaseOrders->where('month', $month)->sum('total_purchase_tax');
            $total_expenses = $expenses->get($month, 0);
            $total_tax = $total_sales_tax + $total_purchase_tax;
            $net_income = $total_sales - $total_purchase - $total_expenses - $total_tax;
    
            $newdata[] = [
                // 'number' => $number++,
                'month' => Carbon::create($year, $month, 1)->format('F Y'),
                'total_sales' => $total_sales,
                'total_purchase' => $total_purchase,
                'total_expenses' => $total_expenses,
                'total_tax' => $total_tax,
                'total_income' => $net_income
            ];
        }

        $data = [
            'title' => 'Data Report Income',
            'date' => date('d/m/Y'),
            'result' => $newdata
        ];
        $pdf = PDF::loadView('report_income', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'report-income-' . $date . '.pdf';
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
