<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class MyScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');

        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = Schedule::query()
        ->select(
            'schedule_id',
            'schedule_shift_id',
            'shift_name',
            'shift_start_time',
            'shift_finish_time',
            'schedule_user_id',
            'schedule_date',
            'schedule_note',
            'schedule_status',
            'schedule_leave_id',
            'leave_type',
            'leave_desc',
            'schedules.created_at',
        )
        ->where('schedule_date', $date)
        ->leftJoin('shifts', 'shift_id', '=', 'schedule_shift_id')
        ->leftJoin('leave', 'leave_id', '=', 'schedule_leave_id')
        ->first();

        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => convertResponseSingle($data),
        ];

        return response()->json($output, 200);
    }
}