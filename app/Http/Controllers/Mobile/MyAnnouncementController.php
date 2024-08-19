<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Announcement;

class MyAnnouncementController extends Controller
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
            $sort = request()->query('sort') ?? 'announcement_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            
            $query = Announcement::query()
                ->select(
                    'announcement_id',
                    'announcement_title',
                    'announcement_image',
                    'announcement_status',
                )
                ->where('deleted_at', null)
                ->where('announcement_status', 'active');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('announcement_name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);
            
            //get total data
            $queryTotal = Announcement::query()
                ->select('1 as total')
                ->where('deleted_at', null)
                ->where('announcement_status', 'active');
            $total_all = $queryTotal->count();

            $response = [
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
                'data' => convertResponseArray($res->items()),
            ];

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $response,
            ];
            
        } catch (Exception $e) {
            $output['code'] = 500;
            $output['message'] = $output['message'];
        }

        return response()->json($output, 200);
    }

    public function detail($id)
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = Announcement::query()
            ->select('announcements.*')
            ->where('announcement_id', $id)
            ->first();

        if ($data) {

            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => convertResponseSingle($data),
            ];
        } else {
            $output = [
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ];
        }

        return response()->json($output, 200);
    }
}
