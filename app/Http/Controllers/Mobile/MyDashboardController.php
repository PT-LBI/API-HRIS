<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Presence;

class MyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date') ?? date('Y-m-d');

        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];

        $data_user = User::query()
            ->select(
                'user_id',
                'user_name',
                'user_company_id',
                'company_name',
                'user_division_id',
                'division_name',
                'user_position',
                'user_profile_url',
            )
            ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
            ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id')
            ->where('user_id', auth()->user()->user_id)
            ->first();
			
        $data_schedule = Schedule::query()
            ->select(
                'schedule_id',
                'schedule_shift_id',
                'shift_name',
                'shift_start_time',
                'shift_finish_time',
                'schedule_date',
                'schedule_leave_id',
                'leave_type',
                'leave_desc',
            )
            ->where('schedule_date', $date)
            ->leftJoin('shifts', 'shift_id', '=', 'schedule_shift_id')
            ->leftJoin('leave', 'leave_id', '=', 'schedule_leave_id')
            ->first();

        if ($data_schedule) {
            $data_presence = Presence::query()
                ->select(
                    'presence_id',
                    'presence_schedule_id',
                    'presence_in_time',
                    'presence_out_time',
                    'presence_status',
                )
                ->where('deleted_at', null)
                ->where('presence_user_id', auth()->user()->user_id)
                ->where(function ($query) {
                    $query->where('presence_status', 'in')
                            ->orWhere('presence_status', 'out');
                })
                ->where('presence_schedule_id', $data_schedule['schedule_id'])
                ->orderBy('presence_id', 'DESC')
                ->first();

            if ($data_schedule['leave_type'] == 'leave') {
                $title = 'Hari ini anda sedang cuti';
            } elseif ($data_schedule['leave_type'] == 'permission') {
                $title = 'Hari ini anda sedang izin';
            } else {
                if ($data_presence) {
                    if ($data_presence['presence_status'] == 'in' || $data_presence['presence_status'] == 'ovt_in') {
                        $title = 'Tetap semangat dalam bekerja';
                    } elseif ($data_presence['presence_status'] == 'out' || $data_presence['presence_status'] == 'ovt_out') {
                        $title = 'Selamat beristirahat';
                    } else {
                        $title = 'Kamu belum absensi hari ini';
                    }
                } else {
                    $title = 'Kamu belum absensi hari ini';
                }
            }
        } else {
            $data_presence = null;
            $title = 'Kamu tidak ada jadwal hari ini';
        }


        $data = [
            'user' => convertResponseSingle($data_user),
            'schedule' => $data_schedule ? convertResponseSingle($data_schedule) : '',
            'presence' => $data_presence ? convertResponseSingle($data_presence) : '',
            'title' => $title,
        ];

        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data,
        ];

        return response()->json($output, 200);
    }
}
