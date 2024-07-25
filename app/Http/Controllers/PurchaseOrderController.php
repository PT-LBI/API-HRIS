<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderPayment;
use App\Models\CardStock;
use App\Models\UserLog;
use App\Models\BankLog;
use App\Models\Journal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchaseOrderExport;
use PDF;

class PurchaseOrderController extends Controller
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
            $sort = request()->query('sort') ?? 'po_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $po_status_receiving = request()->query('po_status_receiving');
            $po_payment_status = request()->query('po_payment_status');
            $po_status = request()->query('po_status');
            $po_supplier_id = request()->query('po_supplier_id');
            $po_type = request()->query('po_type');
            $date = request()->query('date');

            $query = PurchaseOrder::select(
                'po_id',
                'po_supplier_id',
                'supplier_name as po_supplier_name',
                'po_number',
                'po_pic_id',
                'user_name as po_pic_name',
                'po_date',
                'po_tax',
                'po_tax_ppn',
                'po_total_product',
                'po_total_product_qty',
                'po_subtotal',
                'po_grandtotal',
                'po_status',
                'po_ship_id',
                'ship_name',
                'po_type',
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
            ->leftJoin('master_ships', 'ship_id', '=', 'po_ship_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('po_number', 'like', '%' . $search . '%')
                    ->orWhere('supplier_name', 'like', '%' . $search . '%');
                });
            }

            if ($po_status && $po_status !== null) {
                $query->where('po_status', $po_status);
            }
            
            if ($po_status_receiving && $po_status_receiving !== null) {
                $query->where('po_status_receiving', $po_status_receiving);
            }

            if ($po_payment_status && $po_payment_status !== null) {
                $query->where('po_payment_status', $po_payment_status);
            }
            
            if ($po_supplier_id && $po_supplier_id !== null) {
                $query->where('po_supplier_id', $po_supplier_id);
            }
           
            if ($po_type && $po_type !== null) {
                $query->where('po_type', $po_type);
            }

            if (!empty($date)) {
                $query->where('po_date', 'like', '%'. $date .'%' );
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = PurchaseOrder::select('1 as total')
            ->leftJoin('suppliers', 'supplier_id', '=', 'po_supplier_id')
            ->leftJoin('users', 'user_id', '=', 'po_pic_id');
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
            'po_supplier_id' => 'required',
            'po_date' => 'required|date',
            // 'po_payment_method' => 'required|in:cash,transfer',
            'po_payment_bank_id' => 'required|integer',
            // 'po_status_ship' => 'required|in:kapal pulang,titip kapal angkut',
            'items' => 'required|array',
            'items.*.product_id'           => 'required|integer',
            'items.*.product_name'         => 'required|string',
            'items.*.product_sku'          => 'required|string',
            'items.*.product_category_id'  => 'required|integer',
            'items.*.product_category_name'=> 'required|string',
            'items.*.qty'                  => 'required|numeric',
            'items.*.product_purchase_price' => 'required|numeric',
            'items.*.subtotal'             => 'required|numeric',
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
            $generate_po_number = $this->generate_number();

            $data = PurchaseOrder::create([
                'po_supplier_id'        => request('po_supplier_id'),
                'po_number'             => $generate_po_number,
                'po_pic_id'             => auth()->user()->user_id,
                'po_date'               => request('po_date'),
                'po_total_product'      => request('po_total_product'),
                'po_total_product_qty'  => request('po_total_product_qty'),
                'po_subtotal'           => request('po_subtotal'),
                'po_grandtotal'         => request('po_grandtotal'),
                'po_status_ship'        => request('po_status_ship'),
                'po_tax'                => request('po_tax'),
                'po_tax_ppn'            => request('po_tax_ppn'),
                'po_config_tax'         => request('po_config_tax'),
                'po_config_tax_ppn'     => request('po_config_tax_ppn'),
                'po_ship_id'            => request('po_ship_id'),
                'po_type'               => request('po_type'),
                // 'po_payment_method'     => request('po_payment_method'),
                'po_payment_bank_id'    => request('po_payment_bank_id'),
                'po_status'             => 'waiting',
                'po_status_receiving'   => 'waiting',
                'po_payment_status'     => 'waiting',
                'po_note'               => request('po_note') ?? null,
                'po_chart_account_id'   => 23, //11.05.00
                'created_at'            => now(),
            ]);

            foreach (request('items') as $product) {
                PurchaseOrderDetail::create([
                    'po_detail_po_id'           => $data->po_id,
                    'po_detail_product_id'      => $product['product_id'],
                    'po_detail_product_name'    => $product['product_name'],
                    'po_detail_product_sku'     => $product['product_sku'],
                    'po_detail_product_category_id'     => $product['product_category_id'],
                    'po_detail_product_category_name'   => $product['product_category_name'],
                    'po_detail_qty'             => $product['qty'],
                    'po_detail_product_purchase_price'  => $product['product_purchase_price'],
                    'po_detail_subtotal'        => $product['subtotal'],
                    'created_at'                => now(),
                ]);
            }

            UserLog::create([
                'log_user_id'   => auth()->user()->user_id,
                'log_po_id'     => $data->po_id,
                'log_type'      => 'purchase_order',
                'log_note'      => 'Create purchase order No. ' . $generate_po_number,
                'created_at'    => now(),
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

    public function update(Request $request, $po_id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = PurchaseOrder::find($po_id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        if ($check_data->po_status !== 'waiting'
            || $check_data->po_status_receiving !== 'waiting'
            || $check_data->po_payment_status !== 'waiting')
        {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'PO sudah diproses',
                'result' => [],
            ], 200);
        }

        $validator = Validator::make($request->all(),[
            'po_supplier_id' => 'required',
            'po_date' => 'required|date',
            'po_payment_bank_id' => 'required|integer',
            // 'po_status_ship' => 'required|in:kapal pulang,titip kapal angkut',
            'items' => 'required|array',
            'items.*.product_id'           => 'required|integer',
            'items.*.product_name'         => 'required|string',
            'items.*.product_sku'          => 'required|string',
            'items.*.product_category_id'  => 'required|integer',
            'items.*.product_category_name'=> 'required|string',
            'items.*.qty'                  => 'required|numeric',
            'items.*.product_purchase_price' => 'required|numeric',
            'items.*.subtotal'             => 'required|numeric',
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
                'po_supplier_id'        => $request->po_supplier_id,
                'po_pic_id'             => auth()->user()->user_id,
                'po_date'               => $request->po_date,
                'po_total_product'      => $request->po_total_product,
                'po_total_product_qty'  => $request->po_total_product_qty,
                'po_subtotal'           => $request->po_subtotal,
                'po_grandtotal'         => $request->po_grandtotal,
                'po_status_ship'        => $request->po_status_ship,
                'po_tax'                => $request->po_tax,
                'po_tax_ppn'            => $request->po_tax_ppn,
                'po_config_tax'         => $request->po_config_tax,
                'po_config_tax_ppn'     => $request->po_config_tax_ppn,
                'po_ship_id'            => $request->po_ship_id,
                'po_type'               => $request->po_type,
                'po_payment_bank_id'    => $request->po_payment_bank_id,
                'po_note'               => $request->po_note,
                'updated_at'            => now(),
            ]);

            $data_update    = [];
            $data_add       = [];
            $update_id      = [];
            $data_delete    = [];

            if ($res) {
                foreach ($request->items as $product) {
                    if ($product['po_detail_id'] == null){
                        $data_add[] = [
                            'po_detail_po_id'           => $po_id,
                            'po_detail_product_id'      => $product['product_id'],
                            'po_detail_product_name'    => $product['product_name'],
                            'po_detail_product_sku'     => $product['product_sku'],
                            'po_detail_product_category_id'     => $product['product_category_id'],
                            'po_detail_product_category_name'   => $product['product_category_name'],
                            'po_detail_qty'             => $product['qty'],
                            'po_detail_product_purchase_price'  => $product['product_purchase_price'],
                            'po_detail_subtotal'        => $product['subtotal'],
                            'created_at'                => now(),
                        ];
                    } else {
                        $data_update[] = [
                            'po_detail_id'              => $product['po_detail_id'],
                            'po_detail_po_id'           => $po_id,
                            'po_detail_product_id'      => $product['product_id'],
                            'po_detail_product_name'    => $product['product_name'],
                            'po_detail_product_sku'     => $product['product_sku'],
                            'po_detail_product_category_id'     => $product['product_category_id'],
                            'po_detail_product_category_name'   => $product['product_category_name'],
                            'po_detail_qty'             => $product['qty'],
                            'po_detail_product_purchase_price'  => $product['product_purchase_price'],
                            'po_detail_subtotal'        => $product['subtotal'],
                            'updated_at'                => now(), 
                        ];

                        $update_id[] = $product['po_detail_id'];
                    }
                }

                $get_detail = PurchaseOrderDetail::select('po_detail_id')
                    ->where('po_detail_po_id', $po_id)
                    ->get();

                foreach ($get_detail as $product) {
                    if (!in_array($product['po_detail_id'], $update_id)) {
                        $data_delete[] = $product['po_detail_id'];
                    }
                }

                // Perform bulk operations
                if (!empty($data_add)) {
                    DB::table('po_details')->insert($data_add);
                }

                foreach ($data_update as $update) {
                    DB::table('po_details')
                        ->where('po_detail_id', $update['po_detail_id'])
                        ->update($update);
                }

                if (!empty($data_delete)) {
                    DB::table('po_details')
                        ->whereIn('po_detail_id', $data_delete)
                        ->delete();
                }
            }

            UserLog::create([
                'log_user_id'   => auth()->user()->user_id,
                'log_po_id'     => $po_id,
                'log_type'      => 'purchase_order',
                'log_note'      => 'Update purchase order No. ' . $check_data->po_number,
                'created_at'    => now(),
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
        $data = PurchaseOrder::select(
            'po_id',
            'po_supplier_id',
            'supplier_name as po_supplier_name',
            'supplier_address',
            'supplier_phone_number',
            'supplier_npwp',
            'po_number',
            'po_pic_id',
            'user_name as po_pic_name',
            'po_date',
            'po_total_product',
            'po_total_product_qty',
            'po_subtotal',
            'po_tax',
            'po_tax_ppn',
            'po_config_tax',
            'po_config_tax_ppn',
            'po_ship_id',
            'ship_name',
            'po_type',
            'po_grandtotal',
            'po_status',
            'po_status_receiving',
            'po_status_ship',
            'po_payment_status',
            'po_payment_bank_id',
            'bank_name as po_payment_bank_name',
            'po_note',
            'pos.created_at',
            'pos.updated_at',
        )
        ->leftJoin('suppliers', 'supplier_id', '=', 'po_supplier_id')
        ->leftJoin('users', 'user_id', '=', 'po_pic_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'po_payment_bank_id')
        ->leftJoin('master_ships', 'ship_id', '=', 'po_ship_id')
        ->where('po_id', $id)
        ->get()->first();

        if ($data) {
            $detail = PurchaseOrderDetail::select(
                'po_detail_id',
                'po_detail_po_id',
                'po_detail_product_id',
                'po_detail_product_name',
                'po_detail_product_sku',
                'po_detail_product_category_id',
                'po_detail_product_category_name',
                'po_detail_qty',
                'po_detail_product_purchase_price',
                'po_detail_subtotal',
            )
            ->where('po_detail_po_id', $id)
            ->get();

            $payment = PurchaseOrderPayment::select(
                'po_payment_id',
                'po_payment_po_id',
                'po_payment_bank_id',
                'bank_name as po_payment_bank_name',
                'po_payment_status',
                'po_payment_amount',
                'po_payment_note',
                'po_payment_installment',
                'po_payment_remaining',
                'po_payments.created_at',
            )
            ->where('po_payment_po_id', $id)
            ->leftJoin('master_banks', 'bank_id', '=', 'po_payment_bank_id')
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

    public function updateStatus(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        //define validation rules
        $validator = Validator::make($request->all(), [
            'po_status' => 'required|in:cancelled',
        ]);

         //check if validation fails
         if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $check_data = PurchaseOrder::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        // if ($request->po_status == 'completed') {
        //     $check_data2 = PurchaseOrder::find($id)
        //         ->where('po_status', '=', 'processing')
        //         ->where('po_payment_status', '=', 'paid')
        //         ->first();

        //     if (!$check_data2) {
        //         return response()->json([
        //             'code' => 500,
        //             'status' => 'error',
        //             'message' => 'PO belum di proses atau belum dibayar',
        //             'result' => [],
        //         ], 200);
        //     }
        // }

        try {
            DB::beginTransaction();
            $res = $check_data->update([
                'po_status'     => $request->po_status,
                'updated_at'    => now(),
            ]);

            if ($check_data->po_payment_status == 'paid' || $check_data->po_payment_status == 'partial') {
                $get_payment = PurchaseOrderPayment::select(
                    DB::raw('SUM(po_payment_installment) as total_payment')
                )
                ->where('po_payment_po_id', $id)
                ->first();
                $get_payment = $get_payment->total_payment ?? 0;

                $check_bank = DB::table('master_banks')
                    ->where('bank_id', $check_data->po_payment_bank_id)
                    ->get()->first();
                
                if ($check_bank) {
                    $new_saldo = $check_bank->bank_current_balance + $get_payment;

                    DB::table('master_banks')
                    ->where('bank_id', $check_data->po_payment_bank_id)
                    ->update([
                        'bank_current_balance' => $new_saldo,
                        'updated_at' => now(),
                    ]);
                }

                //ubah jadi unpaid
                $check_data->update([
                    'po_payment_status' => 'unpaid',
                    'updated_at'    => now(),
                ]);

                DB::table('journals')->where('journal_po_id', $id)->delete();
                DB::table('bank_logs')->where('log_po_id', $id)->delete();
            }

            UserLog::create([
                'log_user_id'   => auth()->user()->user_id,
                'log_po_id'     => $id,
                'log_type'      => 'purchase_order',
                'log_note'      => 'Cancelled purchase order No. ' . $check_data->po_number,
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

    public function updateReceiving(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = PurchaseOrder::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $check_paid = PurchaseOrder::find($id)
            // ->where('po_status', '=', 'completed')
            // ->where('po_payment_status', '=', 'paid')
            ->get()->first();
        
        if (!$check_paid) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'PO belum diselesaikan atau belum dibayar',
                'result' => [],
            ], 200);
        }

        //define validation rules
        $validator = Validator::make($request->all(), [
            'po_status_receiving' => 'required|in:receiving',
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
            $data_update = [];
            if ($check_data->po_payment_status == 'paid') {
                $data_update = [
                    'po_status_receiving'   => $request->po_status_receiving,
                    'po_status'             => 'completed',
                    'updated_at'            => now(),
                ];

            } else {
                $data_update = [
                    'po_status_receiving'   => $request->po_status_receiving,
                    'po_status'             => 'processing',
                    'updated_at'            => now(),
                ];
            }

            $res = $check_data->update($data_update);

            if ($res) {
                $get_product = PurchaseOrderDetail::select(
                    'po_detail_product_id',
                    'po_detail_qty',
                    'po_detail_product_purchase_price',
                )
                ->where('po_detail_po_id', $id)
                ->get();

                if ($get_product->count() > 0) {
                    foreach ($get_product as $product) {
                        // add stock in master product
                        $check_product = DB::table('master_products')
                        ->where('product_id', $product->po_detail_product_id)
                        ->get()->first();

                        if ($check_product) {
                            $new_stock = $check_product->product_stock + $product->po_detail_qty;
                            $new_purchase_price = $product->po_detail_product_purchase_price;

                            DB::table('master_products')
                            ->where('product_id', $product->po_detail_product_id)
                            ->update([
                                'product_stock' => $new_stock,
                                'product_hpp' => $new_purchase_price,
                                'product_price_updated_at' => now(),
                            ]);
                        }

                        //create card stock
                        CardStock::create([
                            'card_stock_product_id' => $product->po_detail_product_id,
                            'card_stock_in'         => $product->po_detail_qty,
                            'card_stock_out'        => 0,
                            'card_stock_nominal'    => $product->po_detail_product_purchase_price,
                            'card_stock_type'       => 'plus',
                            'card_stock_status'     => 'in',
                            'card_stock_note'       => 'Stock from PO ' . $check_data->po_number,
                            'created_at'            => now(),
                        ]);
                    }
                }
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

        $check_data = PurchaseOrder::find($id);
        
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
            'po_payment_status' => 'required|in:paid,partial',
            'po_payment_amount' => 'required|numeric',
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
                ->where('bank_id', $check_data->po_payment_bank_id)
                ->get()->first();
            
            if ($check_bank) {
                $new_saldo = $check_bank->bank_current_balance - $request->po_payment_amount;

                DB::table('master_banks')
                ->where('bank_id', $check_data->po_payment_bank_id)
                ->update([
                    'bank_current_balance' => $new_saldo,
                    'updated_at' => now(),
                ]);
            }

            $status = $request->po_payment_status == 'paid' && $check_data->po_status_receiving == 'receiving' ? 'completed' : 'processing';

            $res = $check_data->update([
                'po_payment_status'     => $request->po_payment_status,
                'po_status'             => $status,
                'updated_at'            => now(),
            ]);

            if ($res) {
                $note = $request->po_payment_status == 'paid' ? 'Pembayaran lunas' : 'Pembayaran sebagian';
                $get_payment = PurchaseOrderPayment::select(
                    DB::raw('SUM(po_payment_installment) as total_payment')
                )
                ->where('po_payment_po_id', $id)
                ->first();
                $get_payment = $get_payment->total_payment ?? 0;

                PurchaseOrderPayment::create([
                    'po_payment_po_id'          => $id,
                    'po_payment_bank_id'        => $check_data['po_payment_bank_id'],
                    'po_payment_status'         => $request->po_payment_status,
                    'po_payment_amount'         => $check_data->po_grandtotal,
                    'po_payment_installment'    => $request->po_payment_amount,
                    'po_payment_remaining'      => $check_data->po_grandtotal - ($get_payment + $request->po_payment_amount),
                    'po_payment_note'           => $note,
                    'created_at'                => now(),
                ]);

                if ($request->po_payment_status == 'paid') {
                    $note = 'Pembayaran pembelian (lunas) No. '. $check_data->po_number;
                } else {
                    $note = 'Pembayaran pembelian (sebagian) No. '. $check_data->po_number;
                }
                
                Journal::create([
                    'journal_date'          => $check_data->po_date,
                    'journal_name'          => $note,
                    'journal_chart_account_id'  => $check_data->po_chart_account_id,
                    'journal_po_id'         => $id,
                    'journal_debit'         => 0,
                    'journal_credit'        => $request->po_payment_amount,
                    'created_at'            => now(),
                ]);
    
                BankLog::create([
                    'log_bank_id'   => $check_data->po_payment_bank_id,
                    'log_po_id'     => $id,
                    'log_amount'    => $request->po_payment_amount,
                    'log_note'      => $note,
                    'log_type'      => 'credit',
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
        $fileName = 'Purchase-Order-' . $date . '.xlsx';
        Excel::store(new PurchaseOrderExport, $fileName, 'public');
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
        $sequence = DB::table('pos')->count();

        $sequence = $sequence + 1;

        $number = "PO" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        return $number;
    }

    public function export_pdf()
    {
        $search = request()->query('search');
        $po_status_receiving = request()->query('po_status_receiving');
        $po_payment_status = request()->query('po_payment_status');
        $po_status = request()->query('po_status');
        $po_supplier_id = request()->query('po_supplier_id');
        $date = request()->query('date');

        $query = PurchaseOrder::select(
            'po_id',
            'po_supplier_id',
            'supplier_name as po_supplier_name',
            'po_number',
            'po_pic_id',
            'user_name as po_pic_name',
            'po_date',
            'po_tax',
            'po_tax_ppn',
            'po_config_tax',
            'po_config_tax_ppn',
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
        ->leftJoin('master_banks', 'bank_id', '=', 'po_payment_bank_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('po_number', 'like', '%' . $search . '%')
                ->orWhere('supplier_name', 'like', '%' . $search . '%');
            });
        }

        if ($po_status && $po_status !== null) {
            $query->where('po_status', $po_status);
        }
        
        if ($po_status_receiving && $po_status_receiving !== null) {
            $query->where('po_status_receiving', $po_status_receiving);
        }

        if ($po_payment_status && $po_payment_status !== null) {
            $query->where('po_payment_status', $po_payment_status);
        }
        
        if ($po_supplier_id && $po_supplier_id !== null) {
            $query->where('po_supplier_id', $po_supplier_id);
        }

        if (!empty($date)) {
            $query->where('po_date', 'like', '%'. $date .'%' );
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Purchase Order',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('purchase_order', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'purchase_order-' . $date . '.pdf';
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
        $data = PurchaseOrder::select(
            'po_id',
            'po_supplier_id',
            'supplier_name as po_supplier_name',
            'supplier_address',
            'supplier_phone_number',
            'supplier_npwp',
            'po_number',
            'po_pic_id',
            'user_name as po_pic_name',
            'po_date',
            DB::raw("DATE_FORMAT(po_date, '%d-%m-%Y') as po_date"),
            'po_total_product',
            'po_total_product_qty',
            'po_subtotal',
            'po_tax',
            'po_tax_ppn',
            'po_config_tax',
            'po_config_tax_ppn',
            'po_grandtotal',
            'po_status',
            'po_status_receiving',
            'po_status_ship',
            'po_payment_status',
            'po_payment_bank_id',
            'bank_name as po_payment_bank_name',
            'po_note',
            'pos.created_at',
            'pos.updated_at',
        )
        ->leftJoin('suppliers', 'supplier_id', '=', 'po_supplier_id')
        ->leftJoin('users', 'user_id', '=', 'po_pic_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'po_payment_bank_id')
        ->where('po_id', $id)
        ->get()->first();

        if ($data) {
            $detail = PurchaseOrderDetail::select(
                'po_detail_id',
                'po_detail_po_id',
                'po_detail_product_name',
                'po_detail_product_sku',
                'po_detail_product_category_id',
                'po_detail_product_category_name',
                'po_detail_qty',
                'po_detail_product_purchase_price',
                'po_detail_subtotal',
            )
            ->where('po_detail_po_id', $id)
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

        $pdf = PDF::loadView('purchase_invoice', ['result' => $result]);
        $fileName = $data['po_number'] . '.pdf';
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
