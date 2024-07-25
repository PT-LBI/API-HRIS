<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CardStock;
use App\Exports\StockInOutExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class StockInOutController extends Controller
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
            $sort = request()->query('sort') ?? 'card_stock_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            // $product_id = request()->query('product_id');
            $category_id = request()->query('category_id');
            $status = request()->query('status');

            $query = CardStock::select(
                'card_stock_id',
                'card_stock_product_id',
                'product_name',
                'product_sku',
                'category_id',
                'category_name',
                'card_stock_in',
                'card_stock_out',
                'card_stock_adjustment_total',
                'card_stock_adjustment_total_label',
                'card_stock_nominal',
                'card_stock_nominal_label',
                'card_stock_type',
                'card_stock_status',
                'card_stocks.created_at',
            )
            ->leftJoin('master_products', 'product_id', '=', 'card_stock_product_id')
            ->leftJoin('product_categories', 'category_id', '=', 'product_category_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('product_name', 'like', '%' . $search . '%');
                });
            }

            // if ($product_id && $product_id !== null) {
            //     $query->where('card_stock_product_id', $product_id);
            // }
            
            if ($category_id && $category_id !== null) {
                $query->where('category_id', $category_id);
            }

            if ($status && $status !== null) {
                $query->where('card_stock_status', $status);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = CardStock::select('1 as total')
                ->leftJoin('master_products', 'product_id', '=', 'card_stock_product_id')
                ->leftJoin('product_categories', 'category_id', '=', 'product_category_id');
            $total_all = $queryTotal->count();

            $data = [
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
        $fileName = 'Stock-in-out-' . $date . '.xlsx';
        Excel::store(new StockInOutExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);
    }

    public function export_pdf()
    {
        $search = request()->query('search');
        $product_id = request()->query('product_id');
        $category_id = request()->query('category_id');
        $status = request()->query('status');

        $query = CardStock::select(
            'card_stock_id',
            'card_stock_product_id',
            'product_name',
            'product_sku',
            'category_name',
            'card_stock_in',
            'card_stock_out',
            'card_stock_adjustment_total',
            'card_stock_adjustment_total_label',
            'card_stock_nominal',
            'card_stock_nominal_label',
            'card_stock_type',
            'card_stock_status',
            'card_stocks.created_at',
        )
        ->leftJoin('master_products', 'product_id', '=', 'card_stock_product_id')
        ->leftJoin('product_categories', 'category_id', '=', 'product_category_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('po_number', 'like', '%' . $search . '%');
            });
        }

        if ($product_id && $product_id !== null) {
            $query->where('card_stock_product_id', $product_id);
        }
        
        if ($category_id && $category_id !== null) {
            $query->where('category_id', $category_id);
        }

        if ($status && $status !== null) {
            $query->where('card_stock_status', $status);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Stok In Out',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('stock_in_out', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'stock-in-out-' . $date . '.pdf';
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
