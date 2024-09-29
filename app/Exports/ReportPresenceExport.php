<?php

namespace App\Exports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportPresenceExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sort = request()->query('sort') ?? 'user_name';
        $dir = request()->query('dir') ?? 'ASC';
        $search = request()->query('search');
        $company_id = request()->query('company_id');
        $month = request()->query('month') ?? date('m');
        $year = request()->query('year') ?? date('Y');
        
        $date = $year . '-' . $month;
        
        $query = User::query()
        ->select(
            DB::raw('ROW_NUMBER() OVER (ORDER BY user_name) AS `index`'),
            'user_name',
            'user_code',
            'division_name',
            'user_position',
            DB::raw("
                (SELECT COUNT(schedule_id) 
                FROM schedules 
                WHERE schedule_user_id = users.user_id 
                AND schedule_date LIKE '%$date%' 
                AND schedule_status = 'in' 
                AND deleted_at IS NULL) as total_schedule
            "),
            DB::raw("
                (SELECT COUNT(presence_id) 
                FROM presence 
                WHERE presence_user_id = users.user_id 
                AND presence_in_time LIKE '%$date%' 
                AND presence_status IN ('in', 'out') 
                AND deleted_at IS NULL) as total_presence
            "),
            DB::raw("
                (SELECT COUNT(schedule_id) 
                    FROM schedules 
                    WHERE schedule_user_id = users.user_id 
                    AND schedule_date LIKE '%$date%' 
                    AND schedule_status = 'in' 
                    AND deleted_at IS NULL) - 
                (SELECT COUNT(presence_id) 
                    FROM presence 
                    WHERE presence_user_id = users.user_id 
                    AND presence_in_time LIKE '%$date%' 
                    AND presence_status IN ('in', 'out') 
                    AND deleted_at IS NULL
                ) as total_absence
            "),
            DB::raw("
                (SELECT COUNT(schedule_id) 
                    FROM schedules 
                    WHERE schedule_user_id = users.user_id 
                    AND schedule_date LIKE '%$date%' 
                    AND schedule_status IN ('leave', 'permission') 
                    AND deleted_at IS NULL
                ) as total_leave
            "),
            DB::raw("
                (SELECT COUNT(CASE 
                    WHEN TIME(presence_in_time) > TIME(shifts.shift_start_time) THEN 1 
                    ELSE NULL 
                    END)
                FROM presence 
                LEFT JOIN schedules ON schedule_id = presence.presence_schedule_id
                LEFT JOIN shifts ON shift_id = schedules.schedule_shift_id
                WHERE presence_user_id = users.user_id 
                AND presence_in_time LIKE '%$date%' 
                AND presence_status IN ('in', 'out') 
                AND presence.deleted_at IS NULL) as total_presence_late
            ")
        )
            ->whereNotIn('user_role', ['superadmin', 'owner'])
            ->where('users.deleted_at', null)
            ->leftJoin('divisions', 'user_division_id', '=', 'division_id');
        
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%')
                    ->orWhere('user_code', 'like', '%' . $search . '%');
            });
        }

        if ($company_id && $company_id !== null) {
            $query->where('user_company_id', $company_id);
        }
        
        $query->orderBy($sort, $dir);

        $data = $query->get();
        foreach ($data as $key => $value) {
            $total_percentage = $value->total_presence / $value->total_schedule * 100;
            // $toal_percentage_new =  number_format((float)$total_percentage, 2, '.', '');
            $data[$key]->total_percentage = $total_percentage;
        }
        

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
            'Total Jadwal',
            'Kehadiran',
            'Tidak Hadir',
            'Ijin/Cuti',
            'Terlambat',
            'Presentase',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 15,
            'D' => 25,
            'E' => 25,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
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
