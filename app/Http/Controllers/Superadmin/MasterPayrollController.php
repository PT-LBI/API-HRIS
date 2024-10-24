<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\MasterPayroll;
use App\Models\UserPayroll;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;

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
            $this_year = date('Y');
           
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
                    'user_position',
                    DB::raw('CAST(COALESCE((SELECT SUM(user_payroll_total_accepted)
                        FROM user_payrolls
                        WHERE user_payroll_user_id = master_payrolls.payroll_user_id
                        AND user_payroll_status = "sent"
                        AND user_payroll_year = ' . $this_year . '
                    ), 0) AS UNSIGNED) as total_accepted_year'),
                    DB::raw('(SELECT user_payroll_sent_at
                        FROM user_payrolls
                        WHERE user_payroll_user_id = master_payrolls.payroll_user_id
                        AND user_payroll_status = "sent"
                        ORDER BY user_payroll_sent_at DESC
                        LIMIT 1
                    ) as user_payroll_sent_at'),
                    'payroll_status'
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
            
            // role manager and admin only can see their company data
            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $query->where('company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $query->where('company_id', $company_id);
                }
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

            // role manager and admin only can see their company data
            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $queryTotal->where('company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $queryTotal->where('company_id', $company_id);
                }
            }

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
            'payroll_overtime_hour' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        $check_data = MasterPayroll::where('payroll_user_id', request('payroll_user_id'))->first();
        if ($check_data) {
            if ($check_data->deleted_at == null) {
                if ($check_data->payroll_status === 'inactive') {
                    return response()->json([
                        'code' => 422,
                        'status' => 'error',
                        'message' => 'Data sudah ada, silahkan aktifkan kembali',
                        'result' => []
                    ], 422);
                } else {
                    return response()->json([
                        'code' => 422,
                        'status' => 'error',
                        'message' => 'Data sudah ada',
                        'result' => []
                    ], 422);
                }
            }
        }

        $data = MasterPayroll::create([
            'payroll_user_id'       => request('payroll_user_id'),
            'payroll_value'         => request('payroll_value'),
            'payroll_overtime_hour' => request('payroll_overtime_hour'),
            // 'payroll_transport'     => request('payroll_transport'),
            // 'payroll_communication' => request('payroll_communication'),
            // 'payroll_absenteeism_cut'   => request('payroll_absenteeism_cut'),
            // 'payroll_bpjs'          => request('payroll_bpjs'),
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

    public function monthly_payroll()
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        try {
            $page = request()->query('page');
            $limit = request()->query('limit') ?? 10;
            $sort = request()->query('sort') ?? 'user_payroll_id';
            $dir = request()->query('dir') ?? 'DESC';
            $status = request()->query('status');
            $year = request()->query('year');
            $user_id = request()->query('user_id');

            $query = UserPayroll::query()
                ->select(
                    'user_payroll_id',
                    'user_payroll_user_id',
                    DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%M %Y') as payroll_period"),
                    'user_payroll_value',
                    DB::raw('CAST((user_payroll_overtime_hour + user_payroll_transport + user_payroll_communication + user_payroll_meal_allowance + user_payroll_income_total) - (user_payroll_absenteeism_cut + user_payroll_bpjs_tk + user_payroll_bpjs_kes + user_payroll_deduct_total) AS SIGNED) as other_component'),
                    'user_payroll_total_accepted',
                    'user_payroll_status',
                    'user_payrolls.created_at',
                )
                ->where('user_payroll_user_id', $user_id);
            
            if ($status && $status !== null) {
                $query->where('user_payroll_status', $status);
            }

            if ($year && $year !== null) {
                $query->where('user_payroll_year', $year);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = UserPayroll::query()
            ->select('1 as total')
            ->where('user_payroll_user_id', $user_id);
            $total_all = $queryTotal->count();

            $user = User::query()
                ->select(
                    'user_id',
                    'user_code',
                    'email',
                    'user_name',
                    'user_phone_number',
                    DB::raw('DATE(user_join_date) as user_join_date'),
                    'user_bank_name',
                    'user_account_name',
                    'user_account_number',
                    'user_company_id',
                    'company_name',
                    'user_division_id',
                    'division_name',
                    'user_position',
                    'user_role',
                    'user_type',
                    'user_profile_url'
                )
                ->leftJoin('companies', 'user_company_id', '=', 'company_id')
                ->leftJoin('divisions', 'user_division_id', '=', 'division_id')
                ->where('user_id', $user_id)
                ->first();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'header' => convertResponseSingle($user),
                'data' => convertResponseArray($res->items()),
            ];

            $check_schedule_payroll = $this->check_schedule_payroll();

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
                'code' => 404,
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
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        } else {
            if ($check_data->payroll_user_id !== $request->payroll_user_id) {
                $check_data_available = MasterPayroll::where('payroll_user_id', $request->payroll_user_id)->first();
                if ($check_data_available) {
                    if ($check_data_available->deleted_at == null) {
                        if ($check_data_available->payroll_status === 'inactive') {
                            return response()->json([
                                'code' => 422,
                                'status' => 'error',
                                'message' => 'Data sudah ada, silahkan aktifkan kembali',
                                'result' => []
                            ], 422);
                        } else {
                            return response()->json([
                                'code' => 422,
                                'status' => 'error',
                                'message' => 'Data sudah ada',
                                'result' => []
                            ], 422);
                        }
                    }
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'payroll_value'             => 'required',
            'payroll_overtime_hour'     => 'required',
            // 'payroll_status'    => 'required|in:active,inactive',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }
       
        $res = $check_data->update([
            // 'payroll_user_id'           => $request->payroll_user_id,
            'payroll_value'             => $request->payroll_value,
            'payroll_overtime_hour'     => $request->payroll_overtime_hour,
            // 'payroll_transport'         => $request->payroll_transport,
            // 'payroll_communication'     => $request->payroll_communication,
            // 'payroll_absenteeism_cut'   => $request->payroll_absenteeism_cut,
            // 'payroll_bpjs'              => $request->payroll_bpjs,
            // 'payroll_status'            => $request->payroll_status,
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
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
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

    public function export_excel()
    {
        $date = date('ymdhm');
        $fileName = 'Payroll-' . $date . '.xlsx';
        Excel::store(new PayrollExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mendapatkan data',
            'result'    => $url
        ];

        return response()->json($output, 200);
    }

    public function check_schedule_payroll()
    {
        $month = date('m');
        $year = date('Y');

        $get_payroll = UserPayroll::query()
            ->select('user_payroll_id')
            ->where('user_payroll_month', $month)
            ->where('user_payroll_year', $year)
            ->whereNull('deleted_at')
            ->first();

        if (empty($get_payroll)) {
            $get_master_payroll = MasterPayroll::query()
                ->select('payroll_id', 'payroll_user_id', 'payroll_value', 'payroll_overtime_hour')
                ->whereNull('deleted_at')
                ->get();

            if (!empty($get_master_payroll)) {
                $payroll = [];
                foreach ($get_master_payroll as $master) {
                    $payroll[] = [
                        'user_payroll_month' => $month,
                        'user_payroll_year' => $year,
                        'user_payroll_user_id' => $master->payroll_user_id,
                        'user_payroll_payroll_id' => $master->payroll_id,
                        'user_payroll_value' => $master->payroll_value,
                        'user_payroll_overtime_hour' => $master->payroll_overtime_hour,
                        'user_payroll_total_accepted' => $master->payroll_value,
                        'user_payroll_status' => 'waiting',
                        'created_at' => now()->addHours(7),
                        'updated_at' => null,
                    ];
                }

                if (!empty($payroll)) {
                    UserPayroll::insert($payroll);
                }
            }
        }
    }
}
