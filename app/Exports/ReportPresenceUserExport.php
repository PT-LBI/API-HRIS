<?php

namespace App\Exports;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Carbon\Carbon;

class ReportPresenceUserExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user_id = request()->query('user_id');
        $month = request()->query('month') ?? date('m');
        $year = request()->query('year') ?? date('Y');
        
        $dates = $this->getDatesOfMonth($year, $month);

        // Fetch all schedules and presences in a single query
        $schedules = DB::table('schedules')
            ->select('schedule_date', 'schedule_status', 'shift_start_time', 'shift_finish_time')
            ->leftJoin('shifts', 'schedule_shift_id', '=', 'shift_id')
            ->where('schedule_user_id', $user_id)
            ->where('schedule_date', 'like', "$year-$month%")
            ->whereNull('schedules.deleted_at')
            ->get()
            ->keyBy('schedule_date'); // Index by schedule_date for faster lookup

        $presences = DB::table('presence')
            ->select(
                DB::raw('DATE(presence_in_time) as presence_date'), 
                DB::raw('DATE_FORMAT(presence_in_time, "%H:%i") as presence_in_time'), 
                DB::raw('DATE_FORMAT(presence_out_time, "%H:%i") as presence_out_time'),
                'presence_status'
            )
            ->where('presence_user_id', $user_id)
            ->where('presence_in_time', 'like', "$year-$month%")
            ->whereIn('presence_status', ['in', 'out'])
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('presence_date'); // Index by presence_date for faster lookup

        $results = [];
        $absence = 0;
        $alpha = 0;
        $leave = 0;
        $vacation = 0;

        foreach($dates as $day) {
            $schedule = $schedules->get($day);
            $presence = $presences->get($day);

            if ($schedule) {
                // Check for leave or permission
                if ($schedule->schedule_status == 'leave' || $schedule->schedule_status == 'permission') {
                    $status = 'Ijin/Cuti';
                    $remark = '';
                    $leave++;
                } else {
                    // If presence exists, check the time
                    if ($presence) {
                        $inTime = $presence->presence_in_time;
                        $shiftStart = date('H:i:s', strtotime($schedule->shift_start_time));

                        if ($inTime > $shiftStart) {
                            $status = $presence->presence_in_time . ' - ' . $presence->presence_out_time;
                            $remark = 'Late';
                        } else {
                            $status = $presence->presence_in_time . ' - ' . $presence->presence_out_time;
                            $remark = 'On Time';
                        }
                        $absence++;
                    } else {
                        $status = 'Alpha'; // No presence data, considered absent
                        $remark = '';
                        $alpha++;
                    }
                }
            } else {
                $status = 'Libur'; // No schedule for this day
                $remark = '';
                $vacation++;
            }

            // Save the result for this day
            $results[] = [
                'no' => count($results) + 1,
                'date' => $day,
                'status' => $status,
                'remark' => $remark
            ];

        }
        return collect($results);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Status',
            'Keterangan',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 25,
            'D' => 30,
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

    private function getDatesOfMonth($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dates = [];
        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->copy()->toDateString();
            $startDate->addDay();
        }

        return $dates;
    }
}
