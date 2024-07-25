<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterBank;
use App\Models\BankLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class MasterBankController extends Controller
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

            $query = MasterBank::select('master_banks.*');

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('bank_name', 'like', '%' . $search . '%')
                        ->orWhere('bank_account_name', 'like', '%' . $search . '%')
                        ->orWhere('bank_account_number', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = MasterBank::select('1 as total');
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

    public function create()
    {
        $validator = Validator::make(request()->all(),[
            'bank_name' => 'required',
            'bank_account_name' => 'required',
            'bank_account_number' => 'required|unique:master_banks',
        ]);
        

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $data = MasterBank::create([
            'bank_name' => request('bank_name'),
            'bank_account_name' => request('bank_account_name'),
            'bank_account_number' => request('bank_account_number'),
            'bank_current_balance' => request('bank_current_balance'),
            'bank_first_balance' => request('bank_first_balance'),
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
        $page = request()->query('page');
        $limit = request()->query('limit') ?? 10;
        $sort = request()->query('sort') ?? 'log_id';
        $dir = request()->query('dir') ?? 'DESC';
        // $search = request()->query('search');

        $data = DB::table('master_banks')
            ->select('master_banks.*')
            ->where('bank_id', $id)
            ->first();

        if ($data) {
            $query = BankLog::select(
                'log_id',
                'log_type',
                'bank_logs.created_at',
                'log_bank_id',
                'master_banks.bank_name',
                'log_user_id',
                'users.user_name',
                'log_amount',
                'log_balance_before',
                'log_balance_after',
                'log_note',
            );

            $query->leftJoin('master_banks', 'master_banks.bank_id', '=', 'bank_logs.log_bank_id');
            $query->leftJoin('users', 'users.user_id', '=', 'bank_logs.log_user_id');
            $query->where('log_bank_id', $id);
            $query->where('log_po_id', 0);
            $query->where('log_transaction_id', 0);
            $query->where('log_expenses_id', 0);

            // if (!empty($search)) {
            //     $query->where(function ($query) use ($search) {
            //         $query->where('bank_name', 'like', '%' . $search . '%')
            //             ->orWhere('bank_account_name', 'like', '%' . $search . '%')
            //             ->orWhere('bank_account_number', 'like', '%' . $search . '%');
            //     });
            // }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);
            
            $history = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'list' => $res->items(),
            ];

            $data->history = $history;

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
        $check_data = MasterBank::find($id);

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
            'bank_name' => 'required',
            'bank_account_name' => 'required',
            'bank_account_number' => 'required|unique:master_banks,bank_account_number,' . $id . ',bank_id',
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
            'bank_name'             => $request->bank_name,
            'bank_account_name'     => $request->bank_account_name,
            'bank_account_number'   => $request->bank_account_number,
            'bank_current_balance'  => $request->bank_current_balance,
            'bank_first_balance'    => $request->bank_first_balance,
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
        $check_data = MasterBank::find($id);

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

    public function additional_balance(Request $request)
    {
        if ($request->type == 1) {
            //sumber lain
            $validator = Validator::make($request->all(),[
                'bank_id_to' => 'required|exists:master_banks,bank_id',
                'amount' => 'required',
            ]);
        } else {
            //antar bank
            $validator = Validator::make($request->all(),[
                'bank_id_from' => 'required|exists:master_banks,bank_id',
                'bank_id_to' => 'required|exists:master_banks,bank_id',
                'amount' => 'required',
            ]);
        }
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        DB::beginTransaction();
        try {
            if ($request->type == 1) {
                //sumber lain
                $check_data = MasterBank::find($request->bank_id_to);
                $check_data->update([
                    'bank_current_balance'  => $check_data->bank_current_balance + $request->amount,
                    'updated_at'            => now(),
                ]);

                BankLog::create([
                    'log_bank_id'   => $request->bank_id_to,
                    'log_user_id'   => auth()->user()->user_id,
                    'log_type'      => 'debit',
                    'log_amount'    => $request->amount,
                    'log_balance_before'    => $check_data->bank_current_balance,
                    'log_balance_after'     => $check_data->bank_current_balance + $request->amount,
                    'log_note'      => 'Penambahan saldo dari sumber lain (Rekening Silang: 11.01.08)',
                    'created_at'    => now(),
                ]);

            } else {
                //antar bank
                $check_data_from = MasterBank::find($request->bank_id_from);
                $check_data_to = MasterBank::find($request->bank_id_to);

                $update_from = $check_data_from->update([
                    'bank_current_balance'  => $check_data_from->bank_current_balance - $request->amount,
                    'updated_at'            => now(),
                ]);
                
                if ($update_from) {
                    BankLog::create([
                        'log_bank_id'   => $request->bank_id_from,
                        'log_user_id'   => auth()->user()->user_id,
                        'log_type'      => 'credit',
                        'log_amount'    => $request->amount,
                        'log_balance_before'    => $check_data_from->bank_current_balance,
                        'log_balance_after'     => $check_data_from->bank_current_balance - $request->amount,
                        'log_note'      => 'Pengurangan saldo ke rekening '.$check_data_to->bank_account_number.' (Rekening Silang: 11.01.08)',
                        'created_at'    => now(),
                    ]);
                }

                $update_to = $check_data_to->update([
                    'bank_current_balance'  => $check_data_to->bank_current_balance + $request->amount,
                    'updated_at'            => now(),
                ]);

                if ($update_to) {
                    BankLog::create([
                        'log_bank_id'   => $request->bank_id_to,
                        'log_user_id'   => auth()->user()->user_id,
                        'log_type'      => 'debit',
                        'log_amount'    => $request->amount,
                        'log_balance_before'    => $check_data_to->bank_current_balance,
                        'log_balance_after'     => $check_data_to->bank_current_balance + $request->amount,
                        'log_note'      => 'Penambahan saldo dari rekening '.$check_data_from->bank_account_number.' (Rekening Silang: 11.01.08)',
                        'created_at'    => now(),
                    ]);
                }
            }

            DB::commit();
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil transfer data',
                'result'     => ''
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }
}
