<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use App\Models\MasterPayroll;
use App\Models\UserPayroll;
use Illuminate\Support\Facades\DB;

class MyPayrollController extends Controller
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
            $sort = request()->query('sort') ?? 'user_payroll_id';
            $dir = request()->query('dir') ?? 'DESC';
            $year = request()->query('year');

            $query = UserPayroll::query()
                ->select(
                    'user_payroll_id',
                    DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%M %Y') as payroll_period"),
                    'user_payroll_value',
                    'user_payroll_total_accepted',
                    'user_payroll_sent_at',
                    'created_at',
                )
                ->where('user_payroll_status', 'sent')
                ->where('user_payroll_user_id', auth()->user()->user_id);
            
            if ($year && $year !== null) {
                $query->where('user_payroll_year', $year);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = UserPayroll::query()
            ->select('1 as total')
            ->where('user_payroll_status', 'sent')
            ->where('user_payroll_user_id', auth()->user()->user_id);
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

    public function detail($id)
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = UserPayroll::query()
            ->select(
                'user_payrolls.*',
            )
            ->where('user_payroll_id', $id)
            ->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => convertResponseJson($data),
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
}
