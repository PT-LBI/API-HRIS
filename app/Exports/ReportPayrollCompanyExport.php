<?php

namespace App\Exports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportPayrollCompanyExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sort = request()->query('sort') ?? 'user_name';
            $dir = request()->query('dir') ?? 'ASC';
            $search = request()->query('search');
            $year = request()->query('year') ?? date('Y');
            $company_id = request()->query('company_id') ?? 1;
            
            $query = User::query()
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY user_name) AS `index`'),
                'users.user_name',
                'users.user_code',
                'divisions.division_name',
                'users.user_position',
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_value) AS SIGNED), 0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_payroll_value
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_overtime_hour) AS SIGNED), 0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_overtime_hours
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_transport) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_transport
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_communication) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_communication
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_meal_allowance) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_meal_allowance
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_income_total) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_income
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_absenteeism_cut) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_absenteeism_cut
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_kes) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_kes
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_bpjs_tk) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_bpjs_tk
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_deduct_total) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_deductions
                "),
                DB::raw("
                    (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_total_accepted) AS SIGNED),0)
                    FROM user_payrolls
                    WHERE user_payrolls.user_payroll_user_id = users.user_id
                    AND users.user_id = user_id
                    AND users.user_role NOT IN ('superadmin', 'owner')
                    AND user_payrolls.user_payroll_status = 'sent'
                    AND user_payrolls.user_payroll_year = $year
                    AND users.deleted_at IS NULL
                    AND users.user_status = 'active'
                    AND user_payrolls.deleted_at IS NULL
                    ) AS total_accepted
                "),
            )
            ->leftjoin('divisions', 'user_division_id', 'division_id')
            ->leftJoin('companies', 'user_company_id', 'company_id')
            ->whereNull('users.deleted_at')
            ->whereNotIn('user_role', ['superadmin', 'owner'])
            ->where('user_status', 'active')
            ->where('user_company_id', $company_id);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%')
                        ->orWhere('user_code', 'like', '%' . $search . '%');
                });
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
            'Divisi',
            'Jabatan',
            'Total Gaji Pokok',
            'Total Lembur',
            'Total Transport',
            'Total Komunikasi',
            'Total Konsumsi',
            'Total Pendapatan Lain',
            'Total Pot Absensi',
            'Total Pot BPJS Kes',
            'Total Pot BPJS TK',
            'Total Pot Lain',
            'Total Gaji Diterima',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 25,
            'N' => 25,
            'O' => 25,
            'P' => 25,
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
