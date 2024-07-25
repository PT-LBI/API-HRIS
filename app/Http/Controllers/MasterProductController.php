<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\PurchaseOrderDetail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\MasterProductExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

use function Symfony\Component\Translation\DataCollector\sanitizeString;

class MasterProductController extends Controller
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
            $sort = request()->query('sort') ?? 'product_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $product_category_id = request()->query('product_category_id');
            $product_status = request()->query('product_status');

            $query = MasterProduct::select(
                'product_id',
                'product_name',
                'product_sku',
                'product_category_id',
                'category_name as product_category_name',
                'product_hpp',
                'product_show_hpp',
                'product_sell_price',
                'product_unit',
                'product_stock',
                'product_image',
                'product_status',
                'master_products.created_at',
                'master_products.updated_at',
                'product_price_updated_at',
                DB::raw('product_sell_price * product_stock as product_stock_value'),
            )
            ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
            ->where('deleted_at', null);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('product_name', 'like', '%' . $search . '%')
                        ->orWhere('product_sku', 'like', '%' . $search . '%')
                        ->orWhere('category_name', 'like', '%' . $search . '%');
                });
            }

            if ($product_category_id && $product_category_id !== null) {
                $query->where('product_category_id', $product_category_id);
            }
            if ($product_status && $product_status !== null) {
                $query->where('product_status', $product_status);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = MasterProduct::select('1 as total')
                ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
                ->where('deleted_at', null);
            $total_all = $queryTotal->count();

            //get total low stock
            $queryTotal = MasterProduct::select('1 as total')
                ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
                ->where('deleted_at', null)
                ->where('product_stock', '<', 10);
            $total_low_stock = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'total_low_stock' => $total_low_stock,
                'data' => $res->items(),
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
        $validator = Validator::make(request()->all(),[
            'product_name' => 'required|unique:master_products',
            'product_sku' => 'required|unique:master_products',
            'product_category_id' => 'required',
            'product_sell_price' => 'required',
            'product_unit' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('image')) {
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/products', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }

        $data = MasterProduct::create([
            'product_name'          => request('product_name'),
            'product_sku'           => request('product_sku'),
            'product_category_id'   => request('product_category_id'),
            'product_sell_price'    => request('product_sell_price'),
            'product_unit'          => request('product_unit'),
            'product_show_hpp'      => request('product_show_hpp'),
            'product_status'        => 'active',
            'product_image'         => isset($image_url) ? $image_url : null,
            'created_at'            => now(),
        ]);

        if ($data) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menambahkan data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }

    public function detail($id)
    {
        //find data by ID
        $data = MasterProduct::select(
            'product_id',
            'product_name',
            'product_sku',
            'product_category_id',
            'category_name as product_category_name',
            'product_hpp',
            'product_show_hpp',
            'product_sell_price',
            'product_unit',
            'product_stock',
            'product_image',
            'product_status',
            'master_products.created_at',
            'master_products.updated_at',
            'product_price_updated_at',
            DB::raw('product_sell_price * product_stock as product_stock_value'),
        )
        ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
        ->where('product_id', $id)
        ->get()->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
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

    public function get_fifo()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $product_id = request()->query('product_id');
            $page = request()->query('page');
            $limit = request()->query('limit') ?? 10;
            $sort = request()->query('sort') ?? 'po_detail_id';
            $dir = request()->query('dir') ?? 'DESC';

            $query = PurchaseOrderDetail::select(
                'po_detail_product_id',
                'po_detail_product_purchase_price',
                'po_detail_qty',
                'po_detail_subtotal',
                'product_unit',
                'po_details.created_at',
            )
            ->leftJoin('master_products', 'po_detail_product_id', '=', 'product_id')
            ->leftJoin('pos', 'po_detail_po_id', '=', 'po_id')
            ->where('po_detail_product_id', $product_id)
            ->where('po_status', 'completed');
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = PurchaseOrderDetail::select('1 as total')
                ->leftJoin('master_products', 'po_detail_product_id', '=', 'product_id')
                ->leftJoin('pos', 'po_detail_po_id', '=', 'po_id')
                ->where('po_detail_product_id', $product_id)
                ->where('po_status', 'completed');
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => $res->items(),
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

    public function update(Request $request, $id)
    {
        $check_data = MasterProduct::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $rules = [
            'product_name' => 'required|unique:master_products,product_name,' . $id . ',product_id',
            'product_sku' => 'required|unique:master_products,product_sku,' . $id . ',product_id',
            'product_category_id' => 'required',
            'product_sell_price' => 'required',
            'product_status' => 'required|in:active,inactive',
        ];

        if ($request->hasFile('image')) {
            $rules['image'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if ($request->file('image') && $request->file('image') !== null) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/products', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->product_image) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->product_image);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }

        }

        $res = $check_data->update([
            'product_name'          => $request->product_name,
            'product_sku'           => $request->product_sku,
            'product_category_id'   => $request->product_category_id,
            'product_sell_price'    => $request->product_sell_price,
            'product_unit'          => $request->product_unit,
            'product_show_hpp'      => $request->product_show_hpp,
            'product_status'        => $request->product_status,
            'product_image'         => isset($image_url) ? $image_url : $check_data->product_image,
            'updated_at'            => now(),
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

        return response()->json($output, 200);
    }

    public function delete($id)
    {
        $check_data = MasterProduct::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //soft delete post
        $res = $check_data->update([
            'deleted_at' => now(),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menghapus data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menghapus data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);

    }

    public function get_category()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $query = MasterProduct::select(
                'category_id',
                'category_name',
            )
            ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
            ->where('deleted_at', null)
            ->groupBy('category_id');
            
            $res = $query->get();

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $res,
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
        $fileName = 'Master-Product-' . $date . '.xlsx';
        Excel::store(new MasterProductExport, $fileName, 'public');
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
        $product_category_id = request()->query('product_category_id');
        $product_status = request()->query('product_status');

        $query = MasterProduct::select(
            'product_name',
            'product_sku',
            'category_name as product_category_name',
            'product_hpp',
            'product_show_hpp',
            'product_sell_price',
            'product_unit',
            'product_stock',
            'product_status',
            'master_products.created_at',
            'master_products.updated_at',
            'product_price_updated_at',
            DB::raw('product_sell_price * product_stock as product_stock_value'),
        )
        ->leftJoin('product_categories', 'product_category_id', '=', 'category_id')
        ->where('deleted_at', null);
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%')
                    ->orWhere('product_sku', 'like', '%' . $search . '%')
                    ->orWhere('category_name', 'like', '%' . $search . '%');
            });
        }

        if ($product_category_id && $product_category_id !== null) {
            $query->where('product_category_id', $product_category_id);
        }
        if ($product_status && $product_status !== null) {
            $query->where('product_status', $product_status);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Master Product',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('master_product', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'master-product-' . $date . '.pdf';
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
