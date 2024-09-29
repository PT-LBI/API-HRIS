<?php

namespace App\Exports;
use App\Models\Presence;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresenceExport implements FromCollection, WithHeadings,  WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $search = request()->query('search');
        $date = request()->query('date');
        $company_id = request()->query('schedule_date');
        $status = request()->query('status');
        
        $query = Presence::query()
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY schedule_date) AS `index`'),
                'user_name',
                'user_code',
                'company_name',
                'division_name',
                'user_position',
                'schedule_date',
                DB::raw("CONCAT(shifts.shift_start_time, ' - ', shifts.shift_finish_time) as working_hours"),
                'presence_in_time',
                'presence_in_note',
                'presence_out_time',
                'presence_out_note',
                'presence.presence_extra_time',
                'schedules.schedule_status',
                DB::raw("
                    CASE 
                        WHEN presence.presence_status IN ('in', 'out') THEN 'reguler'
                        ELSE 'overtime'
                    END as presence_status
                "),
            )
            ->leftjoin('users', 'presence_user_id', '=', 'user_id')
            ->leftjoin('divisions', 'user_division_id', '=', 'division_id')
            ->leftjoin('companies', 'user_company_id', '=', 'company_id')
            ->leftjoin('schedules', 'schedule_id', '=', 'presence_schedule_id')
            ->leftjoin('shifts', 'shift_id', '=', 'schedule_shift_id')
            ->whereNull('presence.deleted_at');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%');
            });
        }

        // Status condition
        if ($status && $status !== null) {
            $query->where(function ($query) use ($status) {
                if ($status == 'regular') {
                    $query->whereIn('presence.presence_status', ['in', 'out']);
                } elseif ($status == 'overtime') {
                    $query->whereIn('presence.presence_status', ['ovt_in', 'ovt_out']);
                }
            });
        }

        if ($date && $date !== null) {
            $query->where('schedule_date', $date);
        }
        
        if ($company_id && $company_id !== null) {
            $query->where('company_id', $company_id);
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
            'Tanggal',
            'Jam Kerja',
            'Absen In',
            'Keterangan Absen In',
            'Absen Out',
            'Keterangan Absen Out',
            'Extra Time',
            'Hari Kerja',
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
            'L' => 30,
            'M' => 15,
            'N' => 15,
            'O' => 15,
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
