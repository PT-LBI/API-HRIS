<?php

namespace App\Exports;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromCollection;

class ReportPayrollExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sort = request()->query('sort') ?? 'company_name';
        $dir = request()->query('dir') ?? 'ASC';
        $year = request()->query('year') ?? date('Y');
        
        $query = Company::query()
        ->select(
            DB::raw('ROW_NUMBER() OVER (ORDER BY company_name) AS `index`'),
            'company_name',
            DB::raw("
                (SELECT COALESCE(CAST(COUNT(user_id) AS SIGNED), 0)
                FROM users
                WHERE user_company_id = company_id
                AND user_role NOT IN ('superadmin', 'owner')
                AND deleted_at IS NULL
                AND user_status = 'active'
                ) AS total_employee
            "),
            DB::raw("
                (SELECT COALESCE(CAST(SUM(user_payrolls.user_payroll_value) AS SIGNED), 0)
                FROM user_payrolls
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
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
                LEFT JOIN users ON users.user_id = user_payrolls.user_payroll_user_id
                WHERE users.user_company_id = company_id
                AND users.user_role NOT IN ('superadmin', 'owner')
                AND user_payrolls.user_payroll_status = 'sent'
                AND user_payrolls.user_payroll_year = $year
                AND users.deleted_at IS NULL
                AND users.user_status = 'active'
                AND user_payrolls.deleted_at IS NULL
                ) AS total_accepted
            "),
        )
        ->whereNull('companies.deleted_at');
        
        $query->orderBy($sort, $dir);

        $data = $query->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Perusahaan',
            'Total Karyawan',
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
            'C' => 25,
            'D' => 25,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 25,
            'N' => 25,
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
