<?php

namespace App\Exports;
use App\Models\UserPayroll;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromCollection;

class ReportPayrollUserExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $year = request()->query('year') ?? date('Y');
        $user_id = request()->query('user_id');
        
        $query = UserPayroll::query()
        ->select(
            DB::raw('ROW_NUMBER() OVER (ORDER BY user_payroll_month) AS `index`'),
            DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%M %Y') as payroll_period"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_value) AS SIGNED), 0) AS total_payroll_value"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_overtime_hour) AS SIGNED), 0) AS total_overtime_hours"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_transport) AS SIGNED), 0) AS total_transport"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_communication) AS SIGNED), 0) AS total_communication"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_meal_allowance) AS SIGNED), 0) AS total_meal_allowance"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_income_total) AS SIGNED), 0) AS total_income"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_absenteeism_cut) AS SIGNED), 0) AS total_absenteeism_cut"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_bpjs_kes) AS SIGNED), 0) AS total_bpjs_kes"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_bpjs_tk) AS SIGNED), 0) AS total_bpjs_tk"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_deduct_total) AS SIGNED), 0) AS total_deductions"),
            DB::raw("COALESCE(CAST(SUM(user_payroll_total_accepted) AS SIGNED), 0) AS total_accepted")
        )
        ->where('user_payroll_user_id', $user_id)
        ->where('user_payroll_year', $year)
        ->whereNull('user_payrolls.deleted_at')
        ->groupBy('user_payroll_year', 'user_payroll_month')
        ->orderBy(DB::raw("DATE_FORMAT(CONCAT(user_payroll_year, '-', user_payroll_month, '-01'), '%Y-%m')"), 'desc');

        $data = $query->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Bulan',
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
            'B' => 20,
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
