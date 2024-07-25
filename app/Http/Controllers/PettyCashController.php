<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PettyCash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PettyCashController extends Controller
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

            $query = PettyCash::select('master_petty_cash.*');

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('petty_cash_name', 'like', '%' . $search . '%')
                        ->orWhere('petty_cash_account_name', 'like', '%' . $search . '%')
                        ->orWhere('petty_cash_account_number', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = PettyCash::select('1 as total');
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

    public function create()
    {
        $validator = Validator::make(request()->all(),[
            'petty_cash_name' => 'required',
            'petty_cash_account_name' => 'required',
            'petty_cash_account_number' => 'required|unique:master_petty_cash',
        ]);
        

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $data = PettyCash::create([
            'petty_cash_name' => request('petty_cash_name'),
            'petty_cash_account_name' => request('petty_cash_account_name'),
            'petty_cash_account_number' => request('petty_cash_account_number'),
            'petty_cash_current_balance' => request('petty_cash_current_balance'),
            'petty_cash_first_balance' => request('petty_cash_first_balance'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
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
        $data = PettyCash::select('master_petty_cash.*')
            ->where('petty_cash_id', $id)
            ->first();

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

    public function update(Request $request, $id)
    {
        $check_data = PettyCash::find($id);

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
            'petty_cash_name' => 'required',
            'petty_cash_account_name' => 'required',
            'petty_cash_account_number' => 'required|unique:master_petty_cash,petty_cash_account_number,' . $id . ',petty_cash_id',
        ]);
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $res = $check_data->update([
            'petty_cash_name'             => $request->petty_cash_name,
            'petty_cash_account_name'     => $request->petty_cash_account_name,
            'petty_cash_account_number'   => $request->petty_cash_account_number,
            'petty_cash_current_balance'  => $request->petty_cash_current_balance,
            'petty_cash_first_balance'    => $request->petty_cash_first_balance,
            'updated_at'            => date('Y-m-d H:i:s'),
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
        $check_data = PettyCash::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $res = $check_data->delete();

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
}
