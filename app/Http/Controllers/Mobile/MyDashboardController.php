<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Presence;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date') ?? Carbon::now()->addHours(7)->format('Y-m-d');
        $previous_date = Carbon::parse($date)->subDay()->format('Y-m-d');

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
			
        //shifts previous
        $data_schedule_previous = $this->get_schedule($previous_date);

        if (!empty($data_schedule_previous && $data_schedule_previous['shift_is_different_day'] == 1)) {
 
            $previous_presence = $this->check_previous_presence($previous_date );
            
            if ($previous_presence['schedule'] == 'previous') {
                //replace schedule date with previous date
                $data_presence = $previous_presence;

                $data_schedule_previous['shift_start_time'] = $data_schedule_previous['schedule_date'] . ' ' . $data_schedule_previous['shift_start_time'];
                $data_schedule_previous['shift_finish_time'] = Carbon::parse($data_schedule_previous['schedule_date'])->addDay()->format('Y-m-d') . ' ' . $data_schedule_previous['shift_finish_time'];
                $data_schedule = $data_schedule_previous;

                $title = $this->get_title($previous_presence['status']);
            } else {
                $data_schedule = $this->get_schedule($date);

                if ($data_schedule['shift_is_different_day'] == 1) {
                    $data_schedule['shift_start_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_start_time'];
                    $data_schedule['shift_finish_time'] = Carbon::parse($data_schedule['schedule_date'])->addDay()->format('Y-m-d') . ' ' . $data_schedule['shift_finish_time'];
                } else {
                    $data_schedule['shift_start_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_start_time'];
                    $data_schedule['shift_finish_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_finish_time'];
                }

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
                    // ->whereDate('presence_in_time', $date)
                    ->where('presence_schedule_id', $data_schedule['schedule_id'])
                    ->orderBy('presence_id', 'DESC')
                    ->first();

                if (!empty($data_presence)) {
                    $title = $this->get_title($data_presence['presence_status']);
                } else {
                    $title = 'Kamu belum absensi hari ini';
                }
            }
        } else {
            $data_schedule = $this->get_schedule($date);

            if ($data_schedule['shift_is_different_day'] == 1) {
                $data_schedule['shift_start_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_start_time'];
                $data_schedule['shift_finish_time'] = Carbon::parse($data_schedule['schedule_date'])->addDay()->format('Y-m-d') . ' ' . $data_schedule['shift_finish_time'];
            } else {
                $data_schedule['shift_start_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_start_time'];
                $data_schedule['shift_finish_time'] = $data_schedule['schedule_date'] . ' ' . $data_schedule['shift_finish_time'];
            }

            if (!empty($data_schedule)){
                if ($data_schedule['leave_type'] == 'leave') {
                    $title = 'Hari ini anda sedang cuti';
                } elseif ($data_schedule['leave_type'] == 'permission') {
                    $title = 'Hari ini anda sedang izin';
                } else {
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
                        ->where('presence_schedule_id', $data_schedule['schedule_id'])
                        ->orderBy('presence_id', 'DESC')
                        ->first();
    
                    if ($data_presence) {
                        $title = $this->get_title($data_presence['presence_status']);
                    } else {
                        $title = 'Kamu belum absensi hari ini';
                    }
                }
            } else {
                $data_presence = null;
                $title = 'Kamu tidak ada jadwal hari ini';
            }
        }

        //get difference time
        if ($data_schedule['shift_is_different_day'] == 1) {
            if ($data_presence) {
                $shift_duration = Carbon::parse($data_presence['presence_out_time'])->diff(Carbon::parse($data_presence['presence_in_time']))->format('%H:%I:%S');
            } else {
                $shift_duration = '00:00:00';
            }
        } else {
            if ($data_presence) {
                if (!empty($data_presence['presence_in_time']) && !empty($data_presence['presence_out_time'])) {
                    $shift_duration = Carbon::parse($data_presence['presence_out_time'])->diff(Carbon::parse($data_presence['presence_in_time']))->format('%H:%I:%S');
                } else if (!empty($data_presence['presence_in_time'])) {
                    $shift_duration = Carbon::parse($data_presence['presence_in_time'])->diff(Carbon::now()->addHours(7))->format('%H:%I:%S');
                } else {
                    $shift_duration = '00:00:00';
                }
            }
        }

        $data_schedule['shift_duration'] = isset($shift_duration) ? $shift_duration : '00:00:00';

        $count_notif = DB::table('log_notif')
            ->where('log_notif_user_id', auth()->user()->user_id)
            ->where('log_notif_is_read', 0)
            ->count();

        $data = [
            'user' => convertResponseSingle($data_user),
            'schedule' => $data_schedule ? convertResponseSingle($data_schedule) : new \stdClass(),
            'presence' => $data_presence ? convertResponseSingle($data_presence) : new \stdClass(),
            'title' => $title,
            'notif' => $count_notif
        ];

        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data,
        ];

        return response()->json($output, 200);
    }

    private function get_schedule($date) {
        $data = Schedule::query()
            ->select(
                'schedule_id',
                'schedule_shift_id',
                'shift_name',
                'shift_start_time',
                'shift_finish_time',
                'shift_is_different_day',
                // DB::raw('TIMEDIFF(shifts.shift_finish_time, shifts.shift_start_time) as shift_duration'),
                'schedule_date',
                'schedule_leave_id',
                'leave_type',
                'leave_desc',
            )
            ->where('schedule_date', $date)
            ->where('schedules.deleted_at', NULL)
            ->where('schedule_user_id', auth()->user()->user_id)
            ->leftJoin('shifts', 'shift_id', '=', 'schedule_shift_id')
            ->leftJoin('leave', 'leave_id', '=', 'schedule_leave_id')
            ->first();

        return $data;
    }

    private function get_title($status) {
        if ($status == 'in' || $status == 'ovt_in') {
            $title = 'Tetap semangat dalam bekerja';
        } elseif ($status == 'out' || $status == 'ovt_out') {
            $title = 'Selamat beristirahat';
        } else {
            $title = 'Kamu belum absensi hari ini';
        }

        return $title;
    }

    private function check_previous_presence($date) {
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
            ->whereDate('presence_in_time', $date)
            // ->where('presence_schedule_id', $data_schedule['schedule_id'])
            ->orderBy('presence_id', 'DESC')
            ->first();

        if (!empty($data_presence)) {
            if ($data_presence['presence_status'] == 'out' || $data_presence['presence_status'] == 'ovt_out') {
                $data_presence['schedule'] = 'now';
            } else if ($data_presence['presence_status'] == 'in' || $data_presence['presence_status'] == 'ovt_in') {
                $data_presence['schedule'] = 'previous';
            } else {
                $data_presence['schedule'] = 'now';
            }
            $data_presence['status'] = $data_presence['presence_status'];
        } else {
            $data_presence['schedule'] = 'now';
            $data_presence['status'] = 'none';
        }

        return $data_presence;

    }
}
