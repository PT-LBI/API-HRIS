<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use App\Models\Presence;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\MasterLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MyPresenceController extends Controller
{
    public function index(Request $request)
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        $schedule_id = $request->query('schedule_id');

        try {
            $query_normal = Presence::query()
                ->select(
                    'presence_id',
                    'presence_user_id',
                    'presence_schedule_id',
                    'presence_in_time',
                    'presence_in_photo',
                    'presence_out_time',
                    'presence_out_photo',
                    'presence_extra_time',
                    'presence_status',
                    'presence_in_longitude',
                    'presence_in_latitude',
                    'presence_out_longitude',
                    'presence_out_latitude',
                    'presence_in_note',
                    'presence_out_note',
                    'created_at',
                    'updated_at',
                )
                ->where('deleted_at', null)
                ->where('presence_user_id', auth()->user()->user_id)
                ->where(function ($query) {
                    $query->where('presence_status', 'in')
                          ->orWhere('presence_status', 'out');
                })
                ->where('presence_schedule_id', $schedule_id)
                ->first();

            $query_ovt = Presence::query()
                ->select(
                    'presence_id',
                    'presence_user_id',
                    'presence_schedule_id',
                    'presence_in_time',
                    'presence_in_photo',
                    'presence_out_time',
                    'presence_out_photo',
                    'presence_status',
                    'presence_in_longitude',
                    'presence_in_latitude',
                    'presence_out_longitude',
                    'presence_out_latitude',
                    'presence_in_note',
                    'presence_out_note',
                    'created_at',
                    'updated_at',
                )
                ->where('deleted_at', null)
                ->where('presence_user_id', auth()->user()->user_id)
                ->where(function ($query) {
                    $query->where('presence_status', 'ovt_in')
                          ->orWhere('presence_status', 'ovt_out');
                })
                ->where('presence_schedule_id', $schedule_id)
                ->first();

            $data = [
                'normal' => $query_normal ? convertResponseSingle($query_normal) : new \stdClass(),
                'overtime' => $query_ovt ? convertResponseSingle($query_ovt) : new \stdClass(),
            ];
            
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function create()
    {
        $validator = Validator::make(request()->all(),[
            'type'          => 'required|in:in,out,ovt_in,ovt_out',
            'schedule_id'   => 'required',
            'date'          => 'required',
            'longitude'     => 'required',
            'latitude'      => 'required',
            'presence_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        if (request('date') < now()->toDateString()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => 'Tanggal tidak boleh kurang dari hari ini',
                'result' => []
            ], 422);
        }

        $check_schedule = $this->checkSchedule(request('schedule_id'));
        if ($check_schedule === false) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Jadwal tidak ditemukan',
                'result' => []
            ], 404);
        }
        
        $validTransitions = [
            'in' => 'out',
            'out' => 'ovt_in',
            'ovt_in' => 'ovt_out',
        ];
        
        $lastStatus = $this->getLastPresenceStatus(request('schedule_id'));
        
        // Validasi transisi status
        if ($lastStatus) {
            $expectedNextStatus = $validTransitions[$lastStatus] ?? null;
            if ($expectedNextStatus !== request('type')) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => "Status absensi tidak sesuai urutan. Harusnya $expectedNextStatus setelah $lastStatus.",
                    'result' => []
                ], 422);
            }
        } elseif (request('type') !== 'in') {
            // Jika tidak ada status sebelumnya, hanya status 'in' yang diperbolehkan
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => 'Status pertama harus "in".',
                'result' => []
            ], 422);
        }
        
        $check_presence = $this->checkPresence(request('schedule_id'), request('type'));

        $typeMessages = [
            'in' => 'Anda sudah melakukan absen masuk',
            'out' => 'Anda sudah melakukan absen keluar',
            'ovt_in' => 'Anda sudah melakukan absen lembur masuk',
            'ovt_out' => 'Anda sudah melakukan absen lembur keluar',
        ];
        
        if ($check_presence && isset($typeMessages[request('type')]) && $check_presence['presence_status'] == request('type')) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $typeMessages[request('type')],
                'result' => []
            ], 422);
        }

        if (auth()->user()->user_location_id || auth()->user()->user_location_id != null) {
            $get_location = MasterLocation::where('location_id', auth()->user()->user_location_id)
                ->first();
            $user_location_latitude = $get_location->location_latitude;
            $user_location_longitude = $get_location->location_longitude;
            $user_location_radius = $get_location->location_radius;

            $distance = getDistanceBetweenPoints($user_location_latitude, $user_location_longitude, request('latitude'), request('longitude'));
            if ($distance > $user_location_radius) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Anda berada diluar jangkauan lokasi kerja',
                    'result' => []
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            if (request()->file('presence_photo')) {
                $image = request()->file('presence_photo');
                $image_name = time() . '-' . $image->getClientOriginalName();
                $image_path = $image->storeAs('images/presence', $image_name, 'public');
                $image_url = env('APP_URL'). '/storage/' . $image_path;
            }

            if (request('type') == 'in') {
                Presence::create([
                    'presence_user_id'      => auth()->user()->user_id,
                    'presence_schedule_id'  => request('schedule_id'),
                    'presence_in_time'      => request('date'),
                    'presence_in_photo'     => isset($image_url) ? $image_url : null,
                    'presence_in_longitude' => request('longitude'),
                    'presence_in_latitude'  => request('latitude'),
                    'presence_in_note'      => request('note'),
                    'presence_status'       => request('type'),
                    'created_at'            => now()->addHours(7),
                    'updated_at'            => null,
                ]);
            } elseif (request('type') == 'out') {
                $get_shift = Shift::where('shift_id', $check_schedule->schedule_shift_id)
                    ->first();

                $requestDate = Carbon::parse(request('date'));
                $shiftFinishTime = Carbon::parse($get_shift->shift_finish_time);

                $shiftFinishDateTime = $requestDate->copy()->setTimeFrom($shiftFinishTime);

                if ($requestDate->greaterThan($shiftFinishDateTime)) {
                    // Calculate the difference in seconds
                    $extra_seconds = $shiftFinishTime->diffInSeconds($requestDate, false);
                    
                    // Convert seconds to HH:MM:SS format
                    $extra_time = gmdate('H:i:s', abs($extra_seconds));
                } else {
                    $extra_time = '00:00:00';
                }

                $get_presence = Presence::where('presence_schedule_id', request('schedule_id'))
                    ->where('presence_user_id', auth()->user()->user_id)
                    ->where('presence_status', 'in')
                    ->first();

                if (!$get_presence) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'error',
                        'message' => 'Anda belum melakukan absen masuk',
                        'result' => []
                    ], 422);
                }

                Presence::where('presence_schedule_id', request('schedule_id'))
                    ->where('presence_user_id', auth()->user()->user_id)
                    ->where('presence_status', 'in')
                    ->update([
                        'presence_out_time'      => request('date'),
                        'presence_out_photo'     => isset($image_url) ? $image_url : null,
                        'presence_out_longitude' => request('longitude'),
                        'presence_out_latitude'  => request('latitude'),
                        'presence_out_note'      => request('note'),
                        'presence_extra_time'    => $extra_time,
                        'presence_status'        => request('type'),
                        'updated_at'             => now()->addHours(7),
                    ]);
            } elseif (request('type') == 'ovt_in') {
                $get_presence = Presence::where('presence_schedule_id', request('schedule_id'))
                    ->where('presence_user_id', auth()->user()->user_id)
                    ->where('presence_status', 'out')
                    ->first();

                if (!$get_presence) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'error',
                        'message' => 'Anda belum melakukan absen keluar',
                        'result' => []
                    ], 422);
                }

                Presence::create([
                    'presence_user_id'      => auth()->user()->user_id,
                    'presence_schedule_id'  => request('schedule_id'),
                    'presence_in_time'      => request('date'),
                    'presence_in_photo'     => isset($image_url) ? $image_url : null,
                    'presence_in_longitude' => request('longitude'),
                    'presence_in_latitude'  => request('latitude'),
                    'presence_in_note'      => request('note'),
                    'presence_status'       => request('type'),
                    'created_at'            => now()->addHours(7),
                    'updated_at'            => null,
                ]);
            } elseif (request('type') == 'ovt_out') {
                $get_presence = Presence::where('presence_schedule_id', request('schedule_id'))
                    ->where('presence_user_id', auth()->user()->user_id)
                    ->where('presence_status', 'ovt_in')
                    ->first();

                if (!$get_presence) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'error',
                        'message' => 'Anda belum melakukan absen masuk lembur',
                        'result' => []
                    ], 422);
                }

                Presence::where('presence_schedule_id', request('schedule_id'))
                    ->where('presence_user_id', auth()->user()->user_id)
                    ->where('presence_status', 'ovt_in')
                    ->update([
                        'presence_out_time'      => request('date'),
                        'presence_out_photo'     => isset($image_url) ? $image_url : null,
                        'presence_out_longitude' => request('longitude'),
                        'presence_out_latitude'  => request('latitude'),
                        'presence_out_note'      => request('note'),
                        'presence_status'        => request('type'),
                        'updated_at'             => now()->addHours(7),
                    ]);
            }

            DB::commit();
        
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil melakukan absen',
                'result'     => []
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }

    public function checkSchedule($schedule_id) {
        $check_schedule = Schedule::where('schedule_id', $schedule_id)
            ->where('schedule_user_id', auth()->user()->user_id)
            ->where('deleted_at', null)
            ->first();

        return $check_schedule ? $check_schedule : false;
    }

    public function checkPresence($schedule_id, $type) {
        $statusGroup = in_array($type, ['in', 'out']) 
            ? ['in', 'out'] 
            : ['ovt_in', 'ovt_out'];

        return Presence::where('presence_schedule_id', $schedule_id)
            ->where('presence_user_id', auth()->user()->user_id)
            ->where('deleted_at', null)
            ->whereIn('presence_status', $statusGroup)
            ->first();

    }

    public function getLastPresenceStatus($schedule_id) {
        return Presence::where('presence_schedule_id', $schedule_id)
            ->where('presence_user_id', auth()->user()->user_id)
            ->where('deleted_at', null)
            ->orderBy('presence_id', 'desc') // Asumsi id adalah kolom auto-increment
            ->value('presence_status'); // Hanya ambil status terakhir
    }

    //for testing purpose
    public function delete($id)
    {
        $check_data = Presence::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
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

    public function history()
    {
        $output = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Bad Request',
            'result' => []
        ];

        try {
            $page = request()->query('page');
            $limit = request()->query('limit') ?? 10;
            $sort = request()->query('sort') ?? 'schedule_date';
            $dir = request()->query('dir') ?? 'DESC';

            $query = Presence::query()
                ->select(
                    'presence_id',
                    'presence_user_id',
                    'presence_schedule_id',
                    'schedule_date',
                    'shift_name',
                    'presence_in_time',
                    'presence_out_time',
                    'presence_in_longitude',
                    'presence_in_latitude',
                    'presence_out_longitude',
                    'presence_out_latitude',
                    'presence_in_photo',
                    'presence_out_photo',
                    'presence_status',
                    DB::raw("
                        CASE 
                            WHEN presence.presence_status IN ('in', 'out') THEN 'reguler'
                            ELSE 'overtime'
                        END as presence_type
                    "),
                    DB::raw("TIMEDIFF(presence_out_time, presence_in_time) as working_hours"),
                )
                ->leftjoin('schedules', 'presence_schedule_id', '=', 'schedule_id')
                ->leftjoin('shifts', 'schedule_shift_id', '=', 'shift_id')
                ->where('presence.deleted_at', null)
                ->where('presence_user_id', auth()->user()->user_id);
                // ->whereIn('presence_status', ['in', 'out']);

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            $queryTotal = Presence::query()
                ->select('1 as total')
                ->leftjoin('schedules', 'presence_schedule_id', '=', 'schedule_id')
                ->leftjoin('shifts', 'schedule_shift_id', '=', 'shift_id')
                ->where('presence.deleted_at', null)
                ->where('presence_user_id', auth()->user()->user_id);
                // ->whereIn('presence_status', ['in', 'out']);
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                // 'data' => $res->items(),
                'data' => convertResponseArray($res->items()),
            ];

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
            // $output['message'] = $e->getMessage();
        }

        return response()->json($output, 200);
    }
}
