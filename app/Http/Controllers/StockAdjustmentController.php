<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\MasterProduct;
use App\Models\UserLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockAdjustmentExport;
use PDF;

class StockAdjustmentController extends Controller
{
    public function create()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $validator = Validator::make(request()->all(),[
            'stock_adjustment_date' => 'required|date',
            'stock_adjustment_user_id' => 'required',
            'stock_adjustment_status' => 'required|in:normal,damage',
            'stock_adjustment_total_product' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_id'           => 'required|integer',
            'items.*.stock_in'             => 'required|numeric',
            'items.*.stock_actual'         => 'required|numeric',
            'items.*.diff_stock'           => 'required|numeric',
            'items.*.diff_stock_label'     => 'required|string',
            'items.*.nominal'              => 'required|numeric',
            'items.*.nominal_label'        => 'required|string',
            'items.*.type'                 => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        DB::beginTransaction();
        try {
            $adjustment_number = $this->generate_number();

            $data = StockAdjustment::create([
                'stock_adjustment_user_id'          => request('stock_adjustment_user_id'),
                'stock_adjustment_date'             => request('stock_adjustment_date'),
                'stock_adjustment_code'             => $adjustment_number,
                'stock_adjustment_status'           => request('stock_adjustment_status'),
                'stock_adjustment_total_product'    => request('stock_adjustment_total_product'),
                'stock_adjustment_note'             => request('stock_adjustment_note'),
                'created_at'                        => now(),
            ]);

            foreach (request('items') as $product) {
                StockAdjustmentDetail::create([
                    'stock_adj_detail_sa_id'            => $data->stock_adjustment_id,
                    'stock_adj_detail_product_id'       => $product['product_id'],
                    'stock_adj_detail_in_stock'         => $product['stock_in'],
                    'stock_adj_detail_actual_stock'     => $product['stock_actual'],
                    'stock_adj_detail_diff_stock'       => $product['diff_stock'],
                    'stock_adj_detail_diff_stock_label' => $product['diff_stock_label'],
                    'stock_adj_detail_nominal'          => $product['nominal'],
                    'stock_adj_detail_nominal_label'    => $product['nominal_label'],
                    'stock_adj_detail_type'             => $product['type'],
                    'stock_adj_detail_note'             => $product['note'],
                    'created_at'                        => now(),
                ]);

                //update stock
                MasterProduct::where('product_id', $product['product_id'])
                ->update(['product_stock' => $product['stock_actual']]);
            }

            UserLog::create([
                'log_user_id'           => auth()->user()->user_id,
                'log_type'              => 'stock_adjustment',
                'log_note'              => 'Create stock adjustment number ' . $adjustment_number,
                'created_at'            => now(),
            ]);

            DB::commit();

            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'     => []
            ];

        } catch (Exception $e) {
            DB::rollBack();

            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

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
            $sort = request()->query('sort') ?? 'transaction_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $date = request()->query('date');
            // $start_date = request()->query('start_date');
            // $end_date = request()->query('end_date');
            $status = request()->query('status');

            $query = StockAdjustment::select(
                'stock_adjustment_id',
                'stock_adjustment_date',
                'stock_adjustment_user_id',
                'user_name as stock_adjustment_user_name',
                'stock_adjustment_code',
                'stock_adjustment_status',
                'stock_adjustment_total_product',
                'stock_adjustment_note',
                DB::raw("(SELECT COALESCE(SUM(stock_adj_detail_diff_stock), 0) FROM stock_adjustment_details WHERE stock_adj_detail_sa_id = stock_adjustment_id AND stock_adj_detail_type = 'plus') as total_add"),
                DB::raw("(SELECT COALESCE(SUM(stock_adj_detail_diff_stock), 0) FROM stock_adjustment_details WHERE stock_adj_detail_sa_id = stock_adjustment_id AND stock_adj_detail_type = 'minus') as total_out"),
                'stock_adjustments.created_at',
            )
            ->leftJoin('users', 'user_id', '=', 'stock_adjustment_user_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('stock_adjustment_code', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('stock_adjustment_status', $status);
            }

            if ($date && $date !== null) {
                $query->where('stock_adjustment_date', $date);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = StockAdjustment::select('1 as total');
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

    public function detail($id)
    {
        //find data by ID
        $data = StockAdjustment::select(
            'stock_adjustment_id',
            'stock_adjustment_date',
            'stock_adjustment_user_id',
            'user_name as stock_adjustment_user_name',
            'stock_adjustment_code',
            'stock_adjustment_status',
            'stock_adjustment_total_product',
            'stock_adjustment_note',
            'stock_adjustments.created_at',
        )
        ->leftJoin('users', 'user_id', '=', 'stock_adjustment_user_id')
        ->where('stock_adjustment_id', $id)
        ->get()->first();

        if ($data) {
            $detail = StockAdjustmentDetail::select(
                'stock_adj_detail_id',
                'stock_adj_detail_sa_id',
                'stock_adj_detail_product_id',
                'product_name as stock_adj_detail_product_name',
                'product_sku as stock_adj_detail_product_sku',
                'stock_adj_detail_in_stock',
                'stock_adj_detail_actual_stock',
                'stock_adj_detail_diff_stock',
                'stock_adj_detail_diff_stock_label',
                'stock_adj_detail_nominal',
                'stock_adj_detail_nominal_label',
                'stock_adj_detail_type',
                'stock_adj_detail_note',
            )
            ->leftjoin('master_products', 'product_id', '=', 'stock_adj_detail_product_id')
            ->where('stock_adj_detail_sa_id', $id)
            ->get();
        }

        $result = [
            'result' => $data,
            'detail' => $detail,
        ];

        if ($result['data']->count() > 0 && $result['detail']->count() > 0) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $result,
            ];
        } else {
            $output = [
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ];
        }

        return response()->json($output, 200);
    }

    function generate_number()
    {
        $sequence = DB::table('stock_adjustments')->count();

        $sequence = $sequence + 1;

        $number = "SA" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        return $number;
    }

    public function export_excel()
    {
        $date = date('ymd');
        $fileName = 'Stock-Adjustment' . $date . '.xlsx';
        Excel::store(new StockAdjustmentExport, $fileName, 'public');
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
        $date = request()->query('date');
        // $start_date = request()->query('start_date');
        // $end_date = request()->query('end_date');
        $status = request()->query('status');

        $query = StockAdjustment::select(
            'stock_adjustment_id',
            'stock_adjustment_date',
            'stock_adjustment_user_id',
            'user_name as stock_adjustment_user_name',
            'stock_adjustment_code',
            'stock_adjustment_status',
            'stock_adjustment_total_product',
            'stock_adjustment_note',
            DB::raw("(SELECT COALESCE(SUM(stock_adj_detail_diff_stock), 0) FROM stock_adjustment_details WHERE stock_adj_detail_sa_id = stock_adjustment_id AND stock_adj_detail_type = 'plus') as total_add"),
            DB::raw("(SELECT COALESCE(SUM(stock_adj_detail_diff_stock), 0) FROM stock_adjustment_details WHERE stock_adj_detail_sa_id = stock_adjustment_id AND stock_adj_detail_type = 'minus') as total_out"),
            'stock_adjustments.created_at',
        )
        ->leftJoin('users', 'user_id', '=', 'stock_adjustment_user_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('stock_adjustment_code', 'like', '%' . $search . '%');
            });
        }

        if ($status && $status !== null) {
            $query->where('stock_adjustment_status', $status);
        }

        if ($date && $date !== null) {
            $query->where('stock_adjustment_date', $date);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Stock Adjustment',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('stock_adjustment', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'stock-adjustment-' . $date . '.pdf';
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
