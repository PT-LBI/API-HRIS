<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionPayment;
use App\Models\UserLog;
use App\Models\CardStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionExport;
use App\Models\Journal;
use App\Models\BankLog;
use function Spatie\LaravelPdf\Support\pdf;
use PDF;


class TransactionController extends Controller
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
            $sort = request()->query('sort') ?? 'transaction_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $transaction_status_delivery = request()->query('transaction_status_delivery');
            $transaction_payment_status = request()->query('transaction_payment_status');
            $transaction_status = request()->query('transaction_status');
            $date = request()->query('date');
            $transaction_type = request()->query('transaction_type');

            $query = Transaction::select(
                'transaction_id',
                'transaction_date',
                'transaction_number',
                'transaction_customer_id',
                'user_name',
                'user_desc',
                'transaction_pic_id',
                'transaction_pic_name',
                'transaction_total_product',
                'transaction_total_product_qty',
                'transaction_type',
                'transaction_config_tax',
                'transaction_tax',
                'transaction_config_tax_ppn',
                'transaction_tax_ppn',
                'transaction_grandtotal',
                'transaction_status',
                'transaction_status_delivery',
                'transaction_payment_bank_id',
                'bank_name as transaction_payment_bank_name',
                'transaction_payment_status',
                'transactions.created_at',
            )
            ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id');
            
            // Define a closure to apply the filters
            $applyFilters = function ($query) use ($search, $transaction_status, $transaction_status_delivery, $transaction_payment_status, $date, $transaction_type) {
                if (!empty($search)) {
                    $query->where('transaction_number', 'like', '%' . $search . '%');
                }

                if (!empty($transaction_status)) {
                    $query->where('transaction_status', $transaction_status);
                }

                if (!empty($transaction_status_delivery)) {
                    $query->where('transaction_status_delivery', $transaction_status_delivery);
                }

                if (!empty($transaction_payment_status)) {
                    $query->where('transaction_payment_status', $transaction_payment_status);
                }

                if (!empty($transaction_type)) {
                    $query->where('transaction_type', $transaction_type);
                }

                if (!empty($date)) {
                    $query->where('transaction_date', 'like', '%' . $date . '%');
                }
            };

            // Apply the filters to the main query
            $applyFilters($query);

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Transaction::select('1 as total')
                ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
                ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id');
            // Apply the same filters to the total count query
            $applyFilters($queryTotal);
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'result' => $res->items(),
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

    public function create()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $validator = Validator::make(request()->all(),[
            'transaction_customer_id' => 'required',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:1,2',
            'transaction_payment_bank_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.product_id'           => 'required|integer',
            'items.*.product_name'         => 'required|string',
            'items.*.product_sku'          => 'required|string',
            'items.*.qty'                  => 'required|numeric',
            'items.*.price_unit'           => 'required|numeric',
            'items.*.total_price'          => 'required|numeric',
            // 'items.*.adjust_price'         => 'required|numeric',
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
            $check_stock = $this->check_stock(request('items'));

            if (!$check_stock) {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Stock tidak mencukupi',
                    'result' => []
                ], 200);
            } else {
                $generate_number = $this->generate_number();

                if (request('transaction_type') == 1) {
                    //penjualan ikan
                    $chart_account_id = 137;
                } else {
                    //pengangkutan
                    $chart_account_id = 136;
                }
    
                $data = Transaction::create([
                    'transaction_customer_id'       => request('transaction_customer_id'),
                    'transaction_number'            => $generate_number,
                    'transaction_pic_id'            => auth()->user()->user_id,
                    'transaction_pic_name'          => auth()->user()->user_name,
                    'transaction_date'              => request('transaction_date'),
                    'transaction_total_product'     => request('transaction_total_product'),
                    'transaction_total_product_qty' => request('transaction_total_product_qty'),
                    'transaction_subtotal'          => request('transaction_subtotal'),
                    'transaction_type'              => request('transaction_type'),
                    'transaction_tax'               => request('transaction_tax') === null ? 0 : request('transaction_tax'),
                    'transaction_tax_ppn'           => request('transaction_tax_ppn') === null ? 0 : request('transaction_tax_ppn'),
                    'transaction_config_tax'        => request('transaction_config_tax') === null ? 0 : request('transaction_config_tax'),
                    'transaction_config_tax_ppn'    => request('transaction_config_tax_ppn') === null ? 0 : request('transaction_config_tax_ppn'),
                    'transaction_disc_type'         => request('transaction_disc_type'),
                    'transaction_disc_percent'      => request('transaction_disc_percent') === null ? 0 : request('transaction_disc_percent'),
                    'transaction_disc_nominal'      => request('transaction_disc_nominal') === null ? 0 : request('transaction_disc_nominal'),
                    'transaction_shipping_cost'     => request('transaction_shipping_cost') === null ? 0 : request('transaction_shipping_cost'),
                    'transaction_grandtotal'        => request('transaction_grandtotal'),
                    'transaction_status'            => 'waiting',
                    'transaction_status_delivery'   => 'waiting',
                    'transaction_payment_bank_id'   => request('transaction_payment_bank_id'),
                    'transaction_payment_status'    => 'waiting',
                    'transaction_chart_account_id'  => $chart_account_id,
                    'transaction_note'              => request('transaction_note') ?? null,
                    'created_at'                    => now(),
                ]);
    
                foreach (request('items') as $product) {
                    TransactionDetail::create([
                        'transaction_detail_transaction_id'  => $data->transaction_id,
                        'transaction_detail_product_id'      => $product['product_id'],
                        'transaction_detail_product_name'    => $product['product_name'],
                        'transaction_detail_product_sku'     => $product['product_sku'],
                        'transaction_detail_qty'             => $product['qty'],
                        'transaction_detail_price_unit'      => $product['price_unit'],
                        'transaction_detail_total_price'     => $product['total_price'],
                        'transaction_detail_adjust_price'    => $product['adjust_price'] == null ? 0 : $product['adjust_price'],
                        'created_at'                         => now(),
                    ]);
                }
    
                UserLog::create([
                    'log_user_id'           => auth()->user()->user_id,
                    'log_transaction_id'    => $data->transaction_id,
                    'log_type'              => 'transaction',
                    'log_note'              => 'Create transaction No. ' . $generate_number,
                    'created_at'            => now(),
                ]);

                //update stock
                $this->update_stock(request('items'), 'create');
                
                DB::commit();
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil menambahkan data',
                    'result'     => []
                ];
                   
            }

        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    function check_stock($items)
    {
        $status = false;

        foreach ($items as $item) {
            $product = DB::table('master_products')
            ->where('product_id', $item['product_id'])
            ->get()->first();

            if ($product) {
                if ($product->product_stock < $item['qty']) {
                    $status = false;
                } else {
                    $status = true;
                
                }
            }
        }

        return $status;
    }

    function check_unit_stock($items, $type)
    {
        $status = false;

        if ($type == 'create') {
            $product = DB::table('master_products')
            ->where('product_id', $items['product_id'])
            ->get()->first();
    
            if ($product) {
                if ($product->product_stock < $items['qty']) {
                    $status = false;
                } else {
                    $status = true;
                }
            }
        } else {
            $product = DB::table('master_products')
                ->where('product_id', $items['product_id'])
                ->get()->first();

            $product_detail = DB::table('transaction_details')
                ->where('transaction_detail_id', $items['transaction_detail_id'])
                ->get()->first();

            $difference = $items['qty'] - $product_detail->transaction_detail_qty;
    
            if ($product) {
                if ($product->product_stock < $difference) {
                    $status = false;
                } else {
                    $status = true;
                }
            }
        }


        return $status;
    }

    function update_stock($items, $type)
    {
        $status = true;

        if ($type == 'create') {
            foreach ($items as $item) {
                $product = DB::table('master_products')
                    ->where('product_id', $item['product_id'])
                    ->get()->first();
    
                if ($product) {
                    $new_stock = $product->product_stock - $item['qty'];
    
                    DB::table('master_products')
                    ->where('product_id', $item['product_id'])
                    ->update([
                        'product_stock' => $new_stock,
                    ]);
                }
            }
        } else if ($type == 'update') {
            foreach ($items as $item) {
                $product = DB::table('master_products')
                    ->where('product_id', $item['transaction_detail_product_id'])
                    ->get()->first();
                
                if ($product) {
                    $new_stock = $product->product_stock + $item['transaction_detail_qty_old'] - $item['transaction_detail_qty'];
    
                    DB::table('master_products')
                    ->where('product_id', $item['transaction_detail_product_id'])
                    ->update([
                        'product_stock' => $new_stock,
                    ]);
                }
            }
        } else if ($type == 'cancel') {
            foreach ($items as $item) {
                $product = DB::table('master_products')
                    ->where('product_id', $item['transaction_detail_product_id'])
                    ->get()->first();
    
                if ($product) {
                    $new_stock = $product->product_stock + $item['transaction_detail_qty'];
    
                    DB::table('master_products')
                    ->where('product_id', $item['transaction_detail_product_id'])
                    ->update([
                        'product_stock' => $new_stock,
                    ]);
                }
            }
        }

        return $status;
    }

    public function update(Request $request, $transaction_id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Transaction::find($transaction_id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        if ($check_data->transaction_status !== 'waiting'
            || $check_data->transaction_status_delivery !== 'waiting'
            || $check_data->transaction_payment_status !== 'waiting')
        {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Transaksi sudah diproses',
                'result' => [],
            ], 200);
        }

        $validator = Validator::make($request->all(),[
            'transaction_customer_id' => 'required',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:1,2',
            'transaction_payment_bank_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.product_id'           => 'required|integer',
            'items.*.product_name'         => 'required|string',
            'items.*.product_sku'          => 'required|string',
            'items.*.qty'                  => 'required|numeric',
            'items.*.price_unit'            => 'required|numeric',
            'items.*.total_price'           => 'required|numeric',
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
            $res = $check_data->update([
                'transaction_customer_id'       => $request->transaction_customer_id,
                'transaction_pic_id'            => auth()->user()->user_id,
                'transaction_pic_name'          => auth()->user()->user_name,
                'transaction_date'              => $request->transaction_date,
                'transaction_total_product'     => $request->transaction_total_product,
                'transaction_total_product_qty' => $request->transaction_total_product_qty,
                'transaction_subtotal'          => $request->transaction_subtotal,
                'transaction_type'              => $request->transaction_type,
                'transaction_tax'               => $request->transaction_tax,
                'transaction_tax_ppn'           => $request->transaction_tax_ppn,
                'transaction_config_tax'        => $request->transaction_config_tax,
                'transaction_config_tax_ppn'    => $request->transaction_config_tax_ppn,
                'transaction_disc_type'         => $request->transaction_disc_type,
                'transaction_disc_percent'      => $request->transaction_disc_percent,
                'transaction_disc_nominal'      => $request->transaction_disc_nominal,
                'transaction_shipping_cost'     => $request->transaction_shipping_cost,
                'transaction_grandtotal'        => $request->transaction_grandtotal,
                'transaction_payment_bank_id'   => $request->transaction_payment_bank_id,
                'transaction_note'              => $request->transaction_note,
                'updated_at'                    => now(),
            ]);

            $data_update        = [];
            $data_update_stock  = [];
            $data_add           = [];
            $data_add_stock     = [];
            $update_id          = [];
            $data_delete        = [];
            $data_cancel_stock  = [];

            if ($res) {
                foreach ($request->items as $product) {
                    if ($product['transaction_detail_id'] == null){
                        $check_stock = $this->check_unit_stock($product, 'create');

                        if (!$check_stock) {
                            return response()->json([
                                'code' => 500,
                                'status' => 'error',
                                'message' => 'Stock '.$product['product_name'].' tidak mencukupi',
                                'result' => []
                            ], 200);
                        } else {
                            $data_add[] = [
                                'transaction_detail_transaction_id' => $transaction_id,
                                'transaction_detail_product_id'     => $product['product_id'],
                                'transaction_detail_product_name'   => $product['product_name'],
                                'transaction_detail_product_sku'    => $product['product_sku'],
                                'transaction_detail_qty'            => $product['qty'],
                                'transaction_detail_price_unit'     => $product['price_unit'],
                                'transaction_detail_total_price'    => $product['total_price'],
                                'transaction_detail_adjust_price'   => $product['adjust_price'] == null ? 0 : $product['adjust_price'],
                                'created_at'                        => now(),
                            ];

                            $data_add_stock[] = [
                                'product_id'    => $product['product_id'],
                                'qty'           => $product['qty'],
                            ];
                        }

                    } else {
                        $check_stock = $this->check_unit_stock($product, 'update');

                        if (!$check_stock) {
                            return response()->json([
                                'code' => 500,
                                'status' => 'error',
                                'message' => 'Stock '.$product['product_name'].' tidak mencukupi',
                                'result' => []
                            ], 200);
                        } else {
                            $product_detail = DB::table('transaction_details')
                                ->where('transaction_detail_id', $product['transaction_detail_id'])
                                ->get()->first();

                            $data_update[] = [
                                'transaction_detail_id'             => $product['transaction_detail_id'],
                                'transaction_detail_transaction_id' => $transaction_id,
                                'transaction_detail_product_id'     => $product['product_id'],
                                'transaction_detail_product_name'   => $product['product_name'],
                                'transaction_detail_product_sku'    => $product['product_sku'],
                                'transaction_detail_qty'            => $product['qty'],
                                'transaction_detail_price_unit'     => $product['price_unit'],
                                'transaction_detail_total_price'    => $product['total_price'],
                                'transaction_detail_adjust_price'   => $product['adjust_price'] == null ? 0 : $product['adjust_price'],
                                'updated_at'                        => now(),
                            ];

                            $data_update_stock[] = [
                                'transaction_detail_id'             => $product['transaction_detail_id'],
                                'transaction_detail_product_id'     => $product['product_id'],
                                'transaction_detail_qty_old'        => $product_detail->transaction_detail_qty,
                                'transaction_detail_qty'            => $product['qty'],
                            ];
                        }

                        $update_id[] = $product['transaction_detail_id'];
                    }
                }

                $get_detail = TransactionDetail::select('transaction_detail_id')
                    ->where('transaction_detail_transaction_id', $transaction_id)
                    ->get();

                foreach ($get_detail as $product) {
                    if (!in_array($product['transaction_detail_id'], $update_id)) {
                        $data_delete[] = $product['transaction_detail_id'];

                        $data_cancel_stock[] = [
                            'transaction_detail_product_id' => $product['transaction_detail_product_id'],
                            'transaction_detail_qty'        => $product['transaction_detail_qty'],
                        ];
                    }
                }

                // Perform bulk operations
                if (!empty($data_add)) {
                    DB::table('transaction_details')->insert($data_add);
                    
                    //update stock
                    $this->update_stock($data_add_stock, 'create');
                }

                foreach ($data_update as $update) {
                    DB::table('transaction_details')
                        ->where('transaction_detail_id', $update['transaction_detail_id'])
                        ->update($update);
                }

                //update stock
                $this->update_stock($data_update_stock, 'update');

                if (!empty($data_delete)) {
                    DB::table('transaction_details')
                        ->whereIn('transaction_detail_id', $data_delete)
                        ->delete();

                    if (!empty($data_cancel_stock)) {
                        //return stock
                        $this->update_stock($data_cancel_stock, 'cancel');
                    }
                }

            }

            UserLog::create([
                'log_user_id'           => auth()->user()->user_id,
                'log_transaction_id'    => $transaction_id,
                'log_type'              => 'transaction',
                'log_note'              => 'Update transaction No. ' . $check_data->transaction_number,
                'created_at'            => now(),
            ]);

            DB::commit();
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
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


    public function detail($id)
    {
        //find data by ID
        $data = Transaction::select(
            'transaction_id',
            'transaction_date',
            'transaction_number',
            'transaction_customer_id',
            'transaction_pic_id',
            'transaction_pic_name',
            'transaction_total_product',
            'transaction_total_product_qty',
            'transaction_subtotal',
            'transaction_type',
            'transaction_config_tax',
            'transaction_tax',
            'transaction_config_tax_ppn',
            'transaction_tax_ppn',
            'transaction_disc_type',
            'transaction_disc_percent',
            'transaction_disc_nominal',
            'transaction_shipping_cost',
            'transaction_config_tax',
            'transaction_grandtotal',
            'transaction_status',
            'transaction_status_delivery',
            'transaction_payment_bank_id',
            'bank_name as transaction_payment_bank_name',
            'transaction_payment_status',
            'transaction_note',
            "transaction_travel_doc",
            'transactions.created_at',
            'user_name',
            'user_phone_number',
            'user_desc',
            'user_npwp',
            'user_address',
            'user_identity_number',
        )
        ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id')
        ->where('transaction_id', $id)
        ->get()->first();

        if ($data) {
            $detail = TransactionDetail::select(
                'transaction_detail_id',
                'transaction_detail_transaction_id',
                'transaction_detail_product_id',
                'transaction_detail_product_sku',
                'transaction_detail_product_name',
                'transaction_detail_qty',
                'transaction_detail_price_unit',
                'transaction_detail_total_price',
                'transaction_detail_adjust_price',
                'product_unit',
            )
            ->where('transaction_detail_transaction_id', $id)
            ->leftJoin('master_products', 'product_id', '=', 'transaction_detail_product_id')
            ->get();

            $payment = TransactionPayment::select(
                'transaction_payment_id',
                'transaction_payment_transaction_id',
                'transaction_payment_bank_id',
                'bank_name as transaction_payment_bank_name',
                'transaction_payment_status',
                'transaction_payment_amount',
                'transaction_payment_note',
                'transaction_payment_installment',
                'transaction_payment_remaining',
                'transaction_payments.created_at',
            )
            ->where('transaction_payment_transaction_id', $id)
            ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id')
            ->orderBy('created_at', 'DESC')
            ->get();
        }

        $result = [
            'result' => $data,
            'detail' => $detail,
            'payment' => $payment,
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

    public function printTravelDoc($id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Transaction::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        try {
            $res = $check_data->update([
                'transaction_travel_doc'    => 1,
                'transaction_status'        => 'processing',
                'transaction_status_delivery' => 'delivered',
                'updated_at'                => now(),
            ]);

            if ($res) {
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil mengubah data',
                    'result'     => $check_data
                ];
            } else {
                $output = [
                    'code'      => 500,
                    'status'    => 'error',
                    'message'   => 'Gagal mengubah data',
                    'result'     => []
                ];
            }
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Transaction::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //define validation rules
        $validator = Validator::make($request->all(), [
            'transaction_status' => 'required|in:cancelled',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        try {
            DB::beginTransaction();
            $res = $check_data->update([
                'transaction_status'    => $request->transaction_status,
                'updated_at'            => now(),
            ]);

            $get_detail = TransactionDetail::select(
                'transaction_detail_product_id',
                'transaction_detail_qty',
            )
            ->where('transaction_detail_transaction_id', $id)
            ->get();

            $this->update_stock($get_detail, 'cancel');

            if ($check_data->transaction_payment_status == 'paid' || $check_data->transaction_payment_status == 'partial') {
                $get_payment = TransactionPayment::select(
                    DB::raw('SUM(transaction_payment_installment) as total_payment')
                )
                ->where('transaction_payment_transaction_id', $id)
                ->first();
                $get_payment = $get_payment->total_payment ?? 0;

                $check_bank = DB::table('master_banks')
                    ->where('bank_id', $check_data->transaction_payment_bank_id)
                    ->get()->first();
                
                if ($check_bank) {
                    $new_saldo = $check_bank->bank_current_balance - $get_payment;

                    DB::table('master_banks')
                    ->where('bank_id', $check_data->transaction_payment_bank_id)
                    ->update([
                        'bank_current_balance' => $new_saldo,
                        'updated_at' => now(),
                    ]);
                }

                //ubah jadi unpaid
                $res = $check_data->update([
                    'transaction_payment_status'    => 'unpaid',
                    'updated_at'                    => now(),
                ]);

                DB::table('journals')->where('journal_transaction_id', $id)->delete();
                DB::table('bank_logs')->where('log_transaction_id', $id)->delete();
            }

            UserLog::create([
                'log_user_id'   => auth()->user()->user_id,
                'log_transaction_id'    => $id,
                'log_type'      => 'transaction',
                'log_note'      => 'Cancelled transaction No. ' . $check_data->transaction_number,
                'created_at'    => now(),
            ]);

            if ($res) {
                DB::commit();
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil mengubah data',
                    'result'     => $check_data
                ];
            } else {
                DB::rollBack();
                $output = [
                    'code'      => 500,
                    'status'    => 'error',
                    'message'   => 'Gagal mengubah data',
                    'result'     => []
                ];
            }
        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function updateDelivery(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Transaction::find($id);
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }
        
        $check_payment = Transaction::where('transaction_id', $id)
        ->where('transaction_payment_status', 'paid')
        ->get()->first();
        
        if (!$check_payment) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Transaction belum dibayar / belum lunas',
                'result' => [],
            ], 200);
        }
        
        $check_delivery = Transaction::where('transaction_id', $id)
            ->where('transaction_status_delivery', 'delivered')
            ->first();
        
        if ($check_delivery) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Transaction sudah dikirim',
                'result' => [],
            ], 200);
        }

        //define validation rules
        $validator = Validator::make($request->all(), [
            'transaction_status_delivery' => 'required|in:delivered',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        DB::beginTransaction();
        try {
            $res = $check_data->update([
                'transaction_status'            => 'completed',
                'transaction_status_delivery'   => $request->transaction_status_delivery,
                'updated_at'                    => now(),
            ]);

            if ($res) {
                $get_product = TransactionDetail::select(
                    'transaction_detail_product_id',
                    'transaction_detail_qty',
                    'transaction_detail_price_unit',
                )
                ->where('transaction_detail_transaction_id', $id)
                ->get();
                
                if ($get_product->count() > 0) {
                    foreach ($get_product as $product) {
                        // $check_product = DB::table('master_products')
                        // ->where('product_id', $product->transaction_detail_product_id)
                        // ->get()->first();

                        // if ($check_product) {
                        //     $new_stock = $check_product->product_stock - $product->transaction_detail_qty;
                        //     // $new_purchase_price = $product->transaction_detail_product_purchase_price;

                        //     DB::table('master_products')
                        //     ->where('product_id', $product->transaction_detail_qty)
                        //     ->update([
                        //         'product_stock' => $new_stock,
                        //     ]);
                        // }

                        //create card stock
                        CardStock::create([
                            'card_stock_product_id' => $product->transaction_detail_product_id,
                            'card_stock_in'         => 0,
                            'card_stock_out'        => $product->transaction_detail_qty,
                            'card_stock_nominal'    => $product->transaction_detail_price_unit,
                            'card_stock_type'       => 'minus',
                            'card_stock_status'     => 'out',
                            'card_stock_note'       => 'Stock from Transaksi '. $check_data->transaction_number,
                            'created_at'            => now(),
                        ]);
                    }
                }

                // Journal::create([
                //     'journal_date'          => $check_data->transaction_date,
                //     'journal_name'          => 'Pembayaran Sales ' . $check_data->transaction_number,
                //     'journal_chart_account_id'  => 135, //penjualan
                //     'journal_debit'         => $check_data->transaction_grandtotal,
                //     'journal_credit'        => 0,
                //     'created_at'            => now(),
                // ]);
            }

            DB::commit();
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => $check_data
            ];
        
        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function updatePayment(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Transaction::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //define validation rules
        $validator = Validator::make($request->all(), [
            'transaction_payment_status' => 'required|in:paid,partial',
            'transaction_payment_amount' => 'required|numeric',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        try {
            //update saldo
            $check_bank = DB::table('master_banks')
                ->where('bank_id', $check_data->transaction_payment_bank_id)
                ->get()->first();
            
            if ($check_bank) {
                $new_saldo = $check_bank->bank_current_balance + $request->transaction_payment_amount;

                DB::table('master_banks')
                ->where('bank_id', $check_data->transaction_payment_bank_id)
                ->update([
                    'bank_current_balance' => $new_saldo,
                    'updated_at' => now(),
                ]);
            }

            $status = $request->transaction_payment_status == 'paid' && $check_data->transaction_status_delivery == 'delivered' ? 'completed' : 'processing';
            $res = $check_data->update([
                'transaction_payment_status'    => $request->transaction_payment_status,
                'transaction_status'            => $status,
                'updated_at'                    => now(),
            ]);
            
            if ($res) {
                $note = $request->transaction_payment_status == 'paid' ? 'Pembayaran lunas' : 'Pembayaran sebagian';
                $get_payment = TransactionPayment::select(
                    DB::raw('SUM(transaction_payment_installment) as total_payment')
                )
                ->where('transaction_payment_transaction_id', $id)
                ->first();
                $get_payment = $get_payment->total_payment ?? 0;

                TransactionPayment::create([
                    'transaction_payment_transaction_id'    => $id,
                    'transaction_payment_bank_id'           => $check_data['transaction_payment_bank_id'],
                    'transaction_payment_status'            => $request->transaction_payment_status,
                    'transaction_payment_amount'            => $check_data->transaction_grandtotal,
                    'transaction_payment_installment'       => $request->transaction_payment_amount,
                    'transaction_payment_remaining'         => $check_data->transaction_grandtotal - ($get_payment + $request->transaction_payment_amount),
                    'transaction_payment_note'              => $note,
                    'created_at'                            => now(),
                ]);

                if ($request->transaction_payment_status == 'paid') {
                    $note = 'Pembayaran penjualan (lunas) No. '. $check_data->transaction_number;
                } else {
                    $note = 'Pembayaran penjualan (sebagian) No. '. $check_data->transaction_number;
                }
                
                Journal::create([
                    'journal_date'          => $check_data->transaction_date,
                    'journal_name'          => $note,
                    'journal_chart_account_id'  => $check_data->transaction_chart_account_id,
                    'journal_transaction_id'    => $id,
                    'journal_debit'         => $request->transaction_payment_amount,
                    'journal_credit'        => 0,
                    'created_at'            => now(),
                ]);
    
                BankLog::create([
                    'log_bank_id'   => $check_data->transaction_payment_bank_id,
                    'log_transaction_id' => $id,
                    'log_amount'    => $request->transaction_payment_amount,
                    'log_note'      => $note,
                    'log_type'      => 'debit',
                    'created_at'    => now(),
                ]);

                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil mengubah data',
                    'result'     => $check_data
                ];
            } else {
                $output = [
                    'code'      => 500,
                    'status'    => 'error',
                    'message'   => 'Gagal mengubah data',
                    'result'     => []
                ];
            }
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
        $fileName = 'Transaction-' . $date . '.xlsx';
        Excel::store(new TransactionExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);

    }

    function generate_number()
    {
        $sequence = DB::table('transactions')->count();

        $sequence = $sequence + 1;

        $number = "TR" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        return $number;
    }

    public function export_pdf()
    {
        $search = request()->query('search');
        $transaction_status_delivery = request()->query('transaction_status_delivery');
        $transaction_payment_status = request()->query('transaction_payment_status');
        $transaction_status = request()->query('transaction_status');
        $date = request()->query('date');
        $transaction_type = request()->query('transaction_type');

        $query = Transaction::select(
            'transaction_id',
            DB::raw("DATE_FORMAT(transaction_date, '%d-%m-%Y') as transaction_date"),
            'transaction_number',
            'transaction_customer_id',
            'user_name',
            'user_desc',
            'transaction_pic_id',
            'transaction_pic_name',
            'transaction_total_product',
            'transaction_total_product_qty',
            DB::raw("IF(transaction_type = 1, 'Penjualan Ikan', 'Pengangkutan') AS transaction_type"),
            'transaction_subtotal',
            'transaction_tax',
            'transaction_tax_ppn',
            'transaction_grandtotal',
            'transaction_status',
            'transaction_status_delivery',
            'transaction_payment_bank_id',
            'bank_name as transaction_payment_bank_name',
            'transaction_payment_status',
            'transactions.created_at',
        )
        ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', '%' . $search . '%');
            });
        }

        if ($transaction_status && $transaction_status !== null) {
            $query->where('transaction_status', $transaction_status);
        }
        
        if ($transaction_status_delivery && $transaction_status_delivery !== null) {
            $query->where('transaction_status_delivery', $transaction_status_delivery);
        }

        if ($transaction_payment_status && $transaction_payment_status !== null) {
            $query->where('transaction_payment_status', $transaction_payment_status);
        }
        
        if ($transaction_type && $transaction_type !== null) {
            $query->where('transaction_type', $transaction_type);
        }

        if (!empty($date)) {
            $query->where('transaction_date', 'like', '%'. $date .'%' );
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $type = $transaction_type == 1 ? 'Penjualan Ikan' : 'Pengangkutan';

        $data = [
            'title' => 'Data Transaksi ' . $type,
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('transaction', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'transaction-' . $date . '.pdf';
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

    public function invoice($id)
    {
        $data = Transaction::select(
            DB::raw("DATE_FORMAT(transaction_date, '%d-%m-%Y') as transaction_date"),
            'transaction_number',
            'transaction_pic_name',
            'transaction_total_product',
            'transaction_total_product_qty',
            'transaction_subtotal',
            'transaction_type',
            'transaction_tax',
            'transaction_tax_ppn',
            'transaction_config_tax',
            'transaction_config_tax_ppn',
            'transaction_disc_type',
            'transaction_disc_percent',
            'transaction_disc_nominal',
            'transaction_shipping_cost',
            'transaction_grandtotal',
            'transaction_payment_bank_id',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'transaction_payment_status',
            'transaction_note',
            "transaction_travel_doc",
            'user_name',
            'user_phone_number',
            'user_desc',
            'user_npwp',
            'user_address',
        )
        ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id')
        ->where('transaction_id', $id)
        ->get()->first();

        if ($data) {
            $detail = TransactionDetail::select(
                'transaction_detail_product_name',
                'transaction_detail_qty',
                'transaction_detail_price_unit',
                'transaction_detail_total_price',
                'transaction_detail_adjust_price',
            )
            ->where('transaction_detail_transaction_id', $id)
            ->get();

              // Add sequential number to each detail
            foreach ($detail as $index => $value) {
                $value->number = $index + 1;
            }

        }

        $result = [
            'result' => $data,
            'detail' => $detail,
        ];

        $pdf = PDF::loadView('invoice', ['result' => $result]);
        $fileName = $data['transaction_number'] . '.pdf';
        $pdf->save(storage_path('app/public/' . $fileName));
        $url = env('APP_URL'). '/storage/' . $fileName;

        // $pdf = pdf()->view('invoice', ['result' => $result]);
        // $fileName = 'invoice-new' . '.pdf';
        // $pdf->save(storage_path('app/public/' . $fileName));
        // $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => [
                'name'  => $fileName,
                'url' => $url
            ]
        ];

        return response()->json($output, 200);


        // return pdf()
        // ->view('invoice', [
        //     'result' => $result,
        // ])
        // // ->name('invoice.pdf')
        // ->disk('public')
        // ->save('invoice.pdf');
        
    }

    public function delivery_letter($id)
    {
        $data = Transaction::select(
            DB::raw("DATE_FORMAT(transaction_date, '%d-%m-%Y') as transaction_date"),
            'transaction_number',
            'transaction_pic_name',
            'transaction_total_product',
            'transaction_total_product_qty',
            'transaction_subtotal',
            'transaction_tax',
            'transaction_disc_type',
            'transaction_disc_percent',
            'transaction_disc_nominal',
            'transaction_shipping_cost',
            DB::raw('1.5 / 100 * transactions.transaction_subtotal as transaction_ppn'),
            'transaction_grandtotal',
            'transaction_payment_bank_id',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'transaction_payment_status',
            'transaction_note',
            "transaction_travel_doc",
            'user_name',
            'user_phone_number',
            'user_desc',
            'user_npwp',
            'user_address',
        )
        ->leftJoin('users', 'user_id', '=', 'transaction_customer_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'transaction_payment_bank_id')
        ->where('transaction_id', $id)
        ->get()->first();

        if ($data) {
            $detail = TransactionDetail::select(
                'transaction_detail_product_sku',
                'transaction_detail_product_name',
                'transaction_detail_qty',
                'transaction_detail_price_unit',
                'transaction_detail_total_price',
                'transaction_detail_adjust_price',
            )
            ->where('transaction_detail_transaction_id', $id)
            ->get();

        }

        $result = [
            'result' => $data,
            'detail' => $detail,
        ];

        $date = date('ymd');
        $pdf = PDF::loadView('delivery_letter', ['result' => $result]);
        $fileName = 'delivery-letter-'. $date . '.pdf';
        $pdf->save(storage_path('app/public/' . $fileName));
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => [
                'name'  => $fileName,
                'url' => $url
            ]
        ];

        return response()->json($output, 200);

       
    }
}
