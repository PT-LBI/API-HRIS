<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $page = request()->query('page');
        $limit = request()->query('limit') ?? 10;
        $sort = request()->query('sort') ?? 'user_id';
        $dir = request()->query('dir') ?? 'DESC';
        $division_id = request()->query('division_id');
        $company_id = request()->query('company_id');
        $year = request()->query('year') ?? now()->year;
        $month = request()->query('month') ?? now()->month;

        $dates = $this->getDatesOfMonth($year, $month);
        $query = $this->getUserQuery($division_id, $company_id, $sort, $dir);

        $res = $query->paginate($limit, ['*'], 'page', $page);
        $users = $res->items();

        $schedulesData = $this->getSchedulesData($users, $dates);

        $response = $this->formatResponse($res, $schedulesData);

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $response,
        ]);
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

    private function getUserQuery($division_id, $company_id, $sort, $dir)
    {
        $query = User::where('user_status', 'active')
            ->whereNull('deleted_at');

        if ($division_id) {
            $query->where('division_id', $division_id);
        }

        if ($company_id) {
            $query->where('company_id', $company_id);
        }

        return $query->orderBy($sort, $dir);
    }

    private function getSchedulesData($users, $dates)
    {
        return collect($users)->map(function ($user) use ($dates) {
            return [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'schedules' => $this->getUserScheduleForDates($user->user_id, $dates)
            ];
        })->toArray();
    }

    private function getUserScheduleForDates($userId, $dates)
    {
        return collect($dates)->map(function ($date) use ($userId) {
            $schedule = Schedule::where('schedule_user_id', $userId)
                ->whereDate('schedule_date', $date)
                ->leftjoin('shifts', 'shift_id', '=', 'schedule_shift_id')
                ->leftjoin('leave', 'leave_id', '=', 'schedule_leave_id')
                ->select('schedules.*', 'shift_name', 'leave_type', 'leave_desc')
                ->first();

            return [
                'schedule_id' => $schedule ? $schedule->schedule_id : 0,
                'schedule_date' => $date,
                'shift_id' => $schedule ? $schedule->schedule_shift_id : 0,
                'shift_name' => $schedule ? $schedule->shift_name : 'Pilih jadwal',
                'leave_id' => $schedule ? $schedule->schedule_leave_id : 0,
                'leave_type' => $schedule ? $schedule->leave_type : '',
                'leave_desc' => $schedule ? $schedule->leave_desc : '',
            ];
        })->toArray();
    }

    private function formatResponse($res, $schedulesData)
    {
        return [
            'current_page' => $res->currentPage(),
            'from' => $res->firstItem(),
            'last_page' => $res->lastPage(),
            'per_page' => $res->perPage(),
            'total' => $res->total(),
            'total_all' => User::where('deleted_at', null)->where('user_status', 'active')->count(),
            'data' => convertResponseArray($schedulesData),
        ];
    }

    public function create()
    {
        $validator = Validator::make(request()->all(),[
            // 'schedule_shift_id' => 'required',
            'schedule_date' => 'required',
            'schedule_user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request('schedule_date') < now()->toDateString()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Tanggal tidak boleh kurang dari hari ini',
                'result' => []
            ], 200);
        }

        $check_schedule = $this->checkSchedule(request('schedule_user_id'), request('schedule_date'));

        if ($check_schedule === false) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Jadwal sudah ada',
                'result' => []
            ], 200);
        }

        $data = Schedule::create([
            'schedule_user_id'  => request('schedule_user_id'),
            'schedule_shift_id' => !empty(request('schedule_shift_id')) ? request('schedule_shift_id') : 0,
            'schedule_date'     => request('schedule_date'),
            'schedule_note'     => !empty(request('schedule_note')) ? request('schedule_note') : null,
            'schedule_status'   => 'in',
            'created_at'        => now()->addHours(7),
            'updated_at'        => null,
        ]);

        if ($data) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menambahkan data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menambahkan data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }

    public function detail($user_id)
    {
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
            'user_code',
            'user_company_id',
            'company_name',
            'user_division_id',
            'division_name',
        )
        ->where('user_id', $user_id)
        ->leftJoin('companies', 'users.user_company_id', '=', 'companies.company_id')
        ->leftJoin('divisions', 'users.user_division_id', '=', 'divisions.division_id')
        ->first();
        
        $data_schedule = Schedule::query()
        ->select(
            'schedule_id',
            'schedule_shift_id',
            'shift_name',
            'shift_start_time',
            'shift_finish_time',
            'schedule_user_id',
            'schedule_date',
            'schedule_note',
            'schedule_leave_id',
            'leave_type',
            'leave_desc',
            'schedule_status',
            'schedules.created_at',
            'schedules.updated_at',
        )
        ->where('schedule_user_id', $user_id)
        ->leftJoin('shifts', 'shift_id', '=', 'schedule_shift_id')
        ->leftJoin('leave', 'leave_id', '=', 'schedule_leave_id')
        ->get()->toArray();

        $data = [
            'user' => $data_user ? convertResponseSingle($data_user) : '',
            'schedule' => convertResponseArray($data_schedule),
        ];

        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data,
        ];
        

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = Schedule::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        } else {
            if ($check_data->schedule_date < now()->toDateString()) {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Jadwal yang sudah berlalu tidak bisa diubah',
                    'result' => []
                ], 200);
            }
        }

        $validator = Validator::make($request->all(), [
            'schedule_shift_id' => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }
       
        $res = $check_data->update([
            'schedule_shift_id' => $request->schedule_shift_id,
            // 'schedule_note'     => $request->schedule_note,
            'updated_at'        => now()->addHours(7),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => convertResponseSingle($check_data)
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal mengubah data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }

    public function delete($id)
    {
        $check_data = Schedule::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //soft delete post
        $res = $check_data->update([
            'deleted_at'    => now()->addHours(7),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil menghapus data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal menghapus data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);

    }

    public function checkSchedule($user_id, $date) {
        $check_schedule = Schedule::where('schedule_user_id', $user_id)
            ->where('schedule_date', $date)
            ->first();

        if ($check_schedule) {
            return false;
        }
    }
}
