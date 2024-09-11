<?php

namespace App\Http\Controllers\Mobile;

use App\Models\LogNotif;

class MyNotifController extends Controller
{
    public function index()
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
            $sort = request()->query('sort') ?? 'created_at';
            $dir = request()->query('dir') ?? 'DESC';
            $is_read = request()->query('is_read');
            
            $query = LogNotif::query()
                ->select(
                    'log_notif_id',
                    'log_notif_user_id',
                    'log_notif_data_json',
                    'log_notif_is_read',
                    'created_at',
                )
                ->where('log_notif_user_id', auth()->user()->user_id);
            
            if ($is_read && $is_read !== null) {
                $query->where('log_notif_is_read', $is_read);
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            $decodedItems = $res->map(function ($item) {
                $item->log_notif_data_json = json_decode($item->log_notif_data_json, true);
                return $item;
            });

            //get total data
            $queryTotal = LogNotif::query()
                ->select('1 as total')
                ->where('log_notif_user_id', auth()->user()->user_id);
            $total_all = $queryTotal->count();

            $data = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => convertResponseArray($decodedItems),
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
