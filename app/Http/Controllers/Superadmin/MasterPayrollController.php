<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\MasterPayroll;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MasterPayrollController extends Controller
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
            $sort = request()->query('sort') ?? 'payroll_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $status = request()->query('status');
            $company_id = request()->query('company_id');
            $division_id = request()->query('division_id');
            
            $query = MasterPayroll::query()
                ->select(
                    'payroll_id',
                    'payroll_user_id',
                    'user_name',
                    'user_code',
                    'user_company_id',
                    'company_name',
                    'user_division_id',
                    'division_name',
                    'payroll_value',
                    'payroll_overtime_hour',
                    'payroll_transport',
                    'payroll_communication',
                    'payroll_absenteeism_cut',
                    'payroll_bpjs',
                    'payroll_status',
                    'master_payrolls.created_at',
                )
                ->where('master_payrolls.deleted_at', null)
                ->leftJoin('users', 'master_payrolls.payroll_user_id', '=', 'users.user_id')
                ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
                ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%');
                    $query->orWhere('user_code', 'like', '%' . $search . '%');
                });
            }

            if ($status && $status !== null) {
                $query->where('payroll_status', $status);
            }
            
            if ($company_id && $company_id !== null) {
                $query->where('user_company_id', $company_id);
            }

            if ($division_id && $division_id !== null) {
                $query->where('user_division_id', $division_id);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('master_payrolls')
                ->select('1 as total')
                ->where('master_payrolls.deleted_at', null)
                ->leftJoin('users', 'master_payrolls.payroll_user_id', '=', 'users.user_id')
                ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
                ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id');
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => convertResponseArray($res->items()),
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
            'payroll_user_id' => 'required',
            'payroll_value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        $check_data = MasterPayroll::where('payroll_user_id', request('payroll_user_id'))->first();
        if ($check_data) {
            if ($check_data->deleted_at == null) {
                if ($check_data->payroll_status === 'inactive') {
                    return response()->json([
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Data sudah ada, silahkan aktifkan kembali',
                        'result' => []
                    ], 200);
                } else {
                    return response()->json([
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Data sudah ada',
                        'result' => []
                    ], 200);
                }
            }
        }

        $data = MasterPayroll::create([
            'payroll_user_id'       => request('payroll_user_id'),
            'payroll_value'         => request('payroll_value'),
            'payroll_overtime_hour' => request('payroll_overtime_hour'),
            'payroll_transport'     => request('payroll_transport'),
            'payroll_communication' => request('payroll_communication'),
            'payroll_absenteeism_cut'   => request('payroll_absenteeism_cut'),
            'payroll_bpjs'          => request('payroll_bpjs'),
            'payroll_status'        => 'active',
            'created_at'            => now()->addHours(7),
            'updated_at'            => null,
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
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = MasterPayroll::query()
            ->select(
                'payroll_id',
                'payroll_user_id',
                'user_name',
                'user_code',
                'user_company_id',
                'company_name',
                'user_division_id',
                'payroll_value',
                'payroll_overtime_hour',
                'payroll_transport',
                'payroll_communication',
                'payroll_absenteeism_cut',
                'payroll_bpjs',
                'payroll_status',
                'master_payrolls.created_at',
                'master_payrolls.updated_at',
            )
            ->where('payroll_id', $id)
            ->leftJoin('users', 'master_payrolls.payroll_user_id', '=', 'users.user_id')
            ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
            ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id')
            ->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => convertResponseSingle($data),
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
        $check_data = MasterPayroll::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        } else {
            if ($check_data->payroll_user_id != $request->payroll_user_id) {
                $check_data_available = MasterPayroll::where('payroll_user_id', $request->payroll_user_id)->first();
                if ($check_data_available) {
                    if ($check_data_available->deleted_at == null) {
                        if ($check_data_available->payroll_status === 'inactive') {
                            return response()->json([
                                'code' => 500,
                                'status' => 'error',
                                'message' => 'Data sudah ada, silahkan aktifkan kembali',
                                'result' => []
                            ], 200);
                        } else {
                            return response()->json([
                                'code' => 500,
                                'status' => 'error',
                                'message' => 'Data sudah ada',
                                'result' => []
                            ], 200);
                        }
                    }
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'payroll_user_id'   => 'required',
            'payroll_value'     => 'required',
            'payroll_status'    => 'required|in:active,inactive',
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
            'payroll_user_id'           => $request->payroll_user_id,
            'payroll_value'             => $request->payroll_value,
            'payroll_overtime_hour'     => $request->payroll_overtime_hour,
            'payroll_transport'         => $request->payroll_transport,
            'payroll_communication'     => $request->payroll_communication,
            'payroll_absenteeism_cut'   => $request->payroll_absenteeism_cut,
            'payroll_bpjs'              => $request->payroll_bpjs,
            'payroll_status'            => $request->payroll_status,
            'updated_at'                => now()->addHours(7),
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
        $check_data = MasterPayroll::find($id);

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
            'deleted_at'        => now()->addHours(7),
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
}
