<?php

namespace App\Exports;
use App\Models\MasterPayroll;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class PayrollExport implements FromCollection, WithHeadings,  WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sort = request()->query('sort') ?? 'payroll_id';
        $dir = request()->query('dir') ?? 'DESC';
        $search = request()->query('search');
        $status = request()->query('status');
        $company_id = request()->query('company_id');
        $division_id = request()->query('division_id');
        $this_year = date('Y');
        
        $query = MasterPayroll::query()
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY user_name) AS `index`'),
                'user_name',
                'user_code',
                'company_name',
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
        
        if ($company_id && $company_id !== null) {
            $query->where('user_company_id', $company_id);
        }

        if ($division_id && $division_id !== null) {
            $query->where('user_division_id', $division_id);
        }

        $query->orderBy($sort, $dir);

        $data = $query->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'NIP',
            'Perusahaan',
            'Divisi',
            'Jabatan',
            'Gaji Berjalan Tahun Ini',
            'Pengiriman Terakhir',
            'Stattus',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 15,
            'D' => 25,
            'E' => 20,
            'F' => 20,
            'G' => 25,
            'H' => 23,
            'I' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '6F97D5']
            ]],
        ];
    }
}
