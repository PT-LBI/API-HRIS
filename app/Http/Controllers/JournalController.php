<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use App\Models\Journal;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JournalExport;
use Illuminate\Support\Facades\DB;
use PDF;

class JournalController extends Controller
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
            $sort = request()->query('sort') ?? 'journal_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $start_date = request()->query('start_date');
            $end_date = request()->query('end_date');

            $query = Journal::select(
                'journal_id',
                'journal_date',
                'journal_name',
                'journal_chart_account_id',
                'master_chart_account_code',
                'master_chart_account_name',
                'journal_debit',
                'journal_credit',
                'journals.created_at',
            )
            ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'journal_chart_account_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('journal_name', 'like', '%' . $search . '%')
                    ->orWhere('master_chart_account_name', 'like', '%' . $search . '%');
                });
            }

            // Add date range filter
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('journal_date', [$start_date, $end_date]);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = Journal::select('1 as total');
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

    public function export_excel()
    {
        $date = date('ymd');
        $fileName = 'Journal-' . $date . '.xlsx';
        Excel::store(new JournalExport, $fileName, 'public');
        $url = env('APP_URL'). '/storage/' . $fileName;

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil mengubah data',
            'result'     => $url
        ];

        return response()->json($output, 200);

    }

    public function export_pdf()
    {
        $start_date = request()->query('start_date');
        $end_date = request()->query('end_date');
        $search = request()->query('search');

        $query = Journal::select(
            DB::raw("DATE_FORMAT(journal_date, '%d-%m-%Y') as journal_date"),
            'journal_name',
            'master_chart_account_code',
            'master_chart_account_name',
            'journal_debit',
            'journal_credit',
        )
        ->leftJoin('master_chart_accounts', 'master_chart_account_id', '=', 'journal_chart_account_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('journal_name', 'like', '%' . $search . '%')
                ->orWhere('master_chart_account_name', 'like', '%' . $search . '%');
            });
        }

        // Add date range filter
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('journal_date', [$start_date, $end_date]);
        }
        $data = $query->get();

        $number = 1;
        foreach ($data as $value) {
            $value->number = $number;
            $number++;
        }

        $data = [
            'title' => 'Data Journal',
            'date' => date('d/m/Y'),
            'result' => $data
        ];

        $pdf = PDF::loadView('journal', $data)->setPaper('a4', 'landscape');

        $date = date('ymd');
        $fileName = 'journal' . $date . '.pdf';
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
