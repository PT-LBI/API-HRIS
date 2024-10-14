<?php

namespace App\Exports;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class LeaveExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $search = request()->query('search');
        $start_date = request()->query('start_date');
        $end_date = request()->query('end_date');
        $company_id = request()->query('company_id');
        $status = request()->query('status');
        
        $query = Leave::query()
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY leave_start_date) AS `index`'),
                'user_name',
                'user_code',
                'company_name',
                'division_name',
                'user_position',
                DB::raw("
                    CASE 
                        WHEN leave_type = 'leave' THEN 'cuti'
                        ELSE 'ijin'
                    END as leave_type
                "),
                'leave_start_date',
                'leave_end_date',
                'leave_desc',
                'leave_status',
            )
            ->where('leave.deleted_at', null)
            ->leftjoin('users', 'leave_user_id', '=', 'user_id')
            ->leftjoin('companies', 'user_company_id', '=', 'company_id')
            ->leftjoin('divisions', 'user_division_id', '=', 'division_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%')
                    ->orWhere('user_code', 'like', '%' . $search . '%');
            });
        }

        if ($status && $status !== null) {
            $query->where('leave_status', $status);
        }
        
        if ($company_id && $company_id !== null) {
            $query->where('company_id', $company_id);
        }

        if ($start_date && $start_date !== null) {
            if ($end_date && $end_date !== null) {
                $query->whereBetween('leave_start_date', [$start_date, $end_date]);
            } else{
                $query->where('leave_start_date', $start_date);
            }
        }

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
            'Jenis',
            'Tgl Mulai',
            'Tgl Selesai',
            'Keterangan',
            'Status',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 15,
            'D' => 24,
            'E' => 20,
            'F' => 20,
            'G' => 15,
            'H' => 20,
            'I' => 20,
            'J' => 30,
            'K' => 20,
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
