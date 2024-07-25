<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\UserLog;
use App\Models\BankLog;
use App\Models\Expenses;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Journal;
use PDF;

class ExpensesController extends Controller
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
            'expenses_date' => 'required',
            'expenses_chart_account_id' => 'required',
            'expenses_amount' => 'required',
            'expenses_type' => 'required|in:normal,ship',
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
            $image_path = $image->storeAs('images/expenses', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }

        $expenses_bank_id = request('expenses_bank_id') == 'undefined' ? 0 : request('expenses_bank_id');
        $expenses_ship_id = request('expenses_ship_id') == 'undefined' ? 0 : request('expenses_ship_id');

        try {
            DB::beginTransaction();
            $expense_number = $this->generate_number();

            $insert = Expenses::create([
                'expenses_chart_account_id' => request('expenses_chart_account_id'),
                'expenses_date'             => request('expenses_date'),
                'expenses_number'           => $expense_number,
                'expenses_amount'           => request('expenses_amount'),
                'expenses_status'           => 'waiting',
                'expenses_type'             => request('expenses_type'),
                'expenses_ship_id'          => $expenses_ship_id,
                'expenses_bank_id'          => $expenses_bank_id,
                'expenses_note'             => request('expenses_note'),
                'expenses_tax_ppn'          => request('expenses_tax_ppn'),
                'expenses_config_tax_ppn'   => request('expenses_config_tax_ppn'),
                'expenses_image'            => isset($image_url) ? $image_url : null,
                'expenses_pic_id'           => auth()->user()->user_id,
                'created_at'                => now(),
            ]);

            UserLog::create([
                'log_user_id'           => auth()->user()->user_id,
                'log_expenses_id'       => $insert->expenses_id,
                'log_type'              => 'expenses',
                'log_note'              => 'Create pengeluaran No. ' . $expense_number,
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
            $sort = request()->query('sort') ?? 'expenses_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $chart_account_id = request()->query('chart_account_id');
            $status = request()->query('status');
            $type = request()->query('type');

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
                'expenses_type',
                'expenses_ship_id',
                'ship_name',
                'bank_name as expenses_bank_name',
                'expenses_image',
                'expenses_tax_ppn',
                'expenses_config_tax_ppn',
                'expenses_note',
                'expenses_pic_id',
                'user_name as expenses_pic_name',
                'expenses.created_at',
            )
            ->leftJoin('users', 'user_id', '=', 'expenses_pic_id')
            ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'expenses_chart_account_id')
            ->leftJoin('master_banks', 'bank_id', '=', 'expenses_bank_id')
            ->leftJoin('master_ships', 'ship_id', '=', 'expenses_ship_id')
            ->where('expenses_is_deleted', 0);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('expenses_number', 'like', '%' . $search . '%')
                    ->orWhere('master_chart_account_name', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('expenses_status', $status);
            }
            
            if ($type && $type !== null) {
                $query->where('expenses_type', $type);
            }

            if ($chart_account_id && $chart_account_id !== null) {
                $query->where('master_chart_account_id', $chart_account_id);
            }
            
            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Expenses::select('1 as total')->where('expenses_is_deleted', 0);
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
        $data = Expenses::select(
            'expenses_id',
            'expenses_number',
            'expenses_date',
            'expenses_chart_account_id',
            'master_chart_account_name',
            'master_chart_account_code',
            'expenses_amount',
            'expenses_status',
            'expenses_image',
            'expenses_type',
            'expenses_ship_id',
            'ship_name',
            'expenses_bank_id',
            'bank_name as expenses_bank_name',
            'expenses_note',
            'expenses_tax_ppn',
            'expenses_config_tax_ppn',
            'expenses_pic_id',
            'user_name as expenses_pic_name',
            'expenses.created_at',
        )
        ->leftJoin('users', 'user_id', '=', 'expenses_pic_id')
        ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'expenses_chart_account_id')
        ->leftJoin('master_banks', 'bank_id', '=', 'expenses_bank_id')
        ->leftJoin('master_ships', 'ship_id', '=', 'expenses_ship_id')
        ->where('expenses_id', $id)
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

    public function summary()
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m');

        $this_month = DB::table('expenses')
            ->where('expenses_date', 'like', "$currentMonth%")
            ->where('expenses_status', 'approved')
            ->sum('expenses_amount');
      
        $this_year = DB::table('expenses')
            ->where('expenses_date', 'like', "$currentYear%")
            ->where('expenses_status', 'approved')
            ->sum('expenses_amount');


        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => [
                'this_month' => $this_month ? intval($this_month) : 0,
                'this_year' => $this_year ? intval($this_year) : 0,
            ],
        ];
    

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

        $check_data = Expenses::find($id);
        
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
            'expenses_status' => 'required|in:approved,cancelled',
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
            $data_update = [
                'expenses_status'   => $request->expenses_status,
                'updated_at'        => now(),
            ];

            $res = $check_data->update($data_update);

            if ($request->expenses_status == 'approved') {
                //update saldo
                $check_bank = DB::table('master_banks')
                    ->where('bank_id', $check_data->expenses_bank_id)
                    ->get()->first();
                
                if ($check_bank) {
                    $new_saldo = $check_bank->bank_current_balance - $check_data->expenses_amount;
    
                    DB::table('master_banks')
                    ->where('bank_id', $check_data->expenses_bank_id)
                    ->update([
                        'bank_current_balance' => $new_saldo,
                        'updated_at' => now(),
                    ]);
                }

                Journal::create([
                    'journal_date'          => $check_data->expenses_date,
                    'journal_expenses_id'   => $id,
                    'journal_name'          => 'Pembayaran pengeluaran No. ' . $check_data->expenses_number,
                    'journal_chart_account_id'  => $check_data->expenses_chart_account_id,
                    'journal_debit'         => 0,
                    'journal_credit'        => $check_data->expenses_amount,
                    'created_at'            => now(),
                ]);

                BankLog::create([
                    'log_bank_id'   => $check_data->expenses_bank_id,
                    'log_expenses_id'   => $id,
                    'log_amount'    => $check_data->expenses_amount,
                    'log_note'      => 'Pembayaran pengeluaran No. ' . $check_data->expenses_number,
                    'log_type'      => 'credit',
                    'created_at'    => now(),
                ]);
            }

            DB::commit();

            if ($res) {

                UserLog::create([
                    'log_user_id'           => auth()->user()->user_id,
                    'log_expenses_id'       => $id,
                    'log_type'              => 'expenses',
                    'log_note'              => 'Approve pengeluaran No. ' . $check_data->expenses_number,
                    'created_at'            => now(),
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

    public function update(Request $request, $id)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $check_data = Expenses::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        if ($check_data->expenses_status == 'approved') {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak dapat diubah karena status sudah approved',
                'result' => [],
            ], 200);
        }

        $rules = [
            'expenses_chart_account_id' => 'required|integer',
            'expenses_amount' => 'required|integer',
            'expenses_date' => 'required',
            'expenses_type' => 'required|in:normal,ship',
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

        if ($request->file('image')) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/expenses', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->expenses_image) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->expenses_image);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }
        }

        $expenses_ship_id = $request->expenses_ship_id == 'undefined' ? 0 : $request->expenses_ship_id;
        $expenses_bank_id = $request->expenses_bank_id == 'undefined' ? 0 : $request->expenses_bank_id;

        try {
            $data_update = [
                'expenses_date'                 => $request->expenses_date,
                'expenses_chart_account_id'     => $request->expenses_chart_account_id,
                'expenses_amount'               => $request->expenses_amount,
                'expenses_type'                 => $request->expenses_type,
                'expenses_ship_id'              => $expenses_ship_id,
                'expenses_bank_id'              => $expenses_bank_id,
                'expenses_tax_ppn'              => $request->expenses_tax_ppn,
                'expenses_config_tax_ppn'       => $request->expenses_config_tax_ppn,
                'expenses_note'                 => $request->expenses_note,
                'expenses_image'                => isset($image_url) ? $image_url : $check_data->expenses_image,
                'updated_at'                    => now(),
            ];

            $res = $check_data->update($data_update);

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

    public function delete($id)
    {
        $check_data = Expenses::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        $data_update = [
            'expenses_is_deleted'   => 1,
            'updated_at'            => now(),
        ];
        $res = $check_data->update($data_update);

        if ($check_data->expenses_status == 'approved') {
            //update saldo
            $check_bank = DB::table('master_banks')
            ->where('bank_id', $check_data->expenses_bank_id)
            ->get()->first();
            
            if ($check_bank) {
                $new_saldo = $check_bank->bank_current_balance + $check_data->expenses_amount;

                DB::table('master_banks')
                ->where('bank_id', $check_data->expenses_bank_id)
                ->update([
                    'bank_current_balance' => $new_saldo,
                    'updated_at' => now(),
                ]);
            }

            // DB::table('jurnals')->where('id', $id)->delete();
        }

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

    function generate_number()
    {
        $sequence = DB::table('expenses')->count();

        $sequence = $sequence + 1;

        $number = "EX" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        return $number;
    }

    public function get_chart_account()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $query = Expenses::select(
                'master_chart_account_id',
                'master_chart_account_name',
            )
            ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'expenses_chart_account_id')
            ->where('expenses_is_deleted', 0)
            ->groupBy('expenses_chart_account_id');
            
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
        $fileName = 'Expenses-' . $date . '.xlsx';
        Excel::store(new ExpensesExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'     => $url
        ];

        return response()->json($output, 200);

        return Excel::download(new ExpensesExport, "tes-{$date}.xlsx");
    }

    public function export_pdf()
    {
        $search = request()->query('search');
        $chart_account_id = request()->query('chart_account_id');
        $status = request()->query('status');
        $type = request()->query('type');

        $query = Expenses::select(
            'expenses_id',
            'expenses_number',
            'expenses_date',
            'expenses_chart_account_id',
            'master_chart_account_name',
            'master_chart_account_code',
            'expenses_amount',
            'expenses_type',
            'expenses_ship_id',
            'ship_name',
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
        ->leftJoin('master_ships', 'ship_id', '=', 'expenses_ship_id')
        ->where('expenses_is_deleted', 0);
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('expenses_number', 'like', '%' . $search . '%')
                ->orWhere('master_chart_account_name', 'like', '%' . $search . '%');
            });
        }

        if ($status && $status !== null) {
            $query->where('expenses_status', $status);
        }
      
        if ($type && $type !== null) {
            $query->where('expenses_type', $type);
        }

        if ($chart_account_id && $chart_account_id !== null) {
            $query->where('master_chart_account_id', $chart_account_id);
        }

        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Expenses',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('expenses', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'expenses-' . $date . '.pdf';
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
