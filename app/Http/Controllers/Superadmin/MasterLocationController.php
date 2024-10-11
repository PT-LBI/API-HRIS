<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\Http\Request;
use App\Models\MasterLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MasterLocationController extends Controller
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
            $sort = request()->query('sort') ?? 'location_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $company_id = request()->query('company_id');
            
            $query = MasterLocation::query()
                ->select(
                    'location_id',
                    'location_name',
                    'location_longitude',
                    'location_latitude',
                    'location_company_id',
                    'company_name',
                    'location_radius',
                    'master_locations.created_at',
                    DB::raw('(SELECT count(user_id) FROM users WHERE user_location_id = master_locations.location_id) as total_user')
                )
                ->leftJoin('companies', 'company_id', '=', 'location_company_id')
                ->where('master_locations.deleted_at', null);
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('location_name', 'like', '%' . $search . '%');
                });
            }

            // role manager and admin only can see their company data
            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $query->where('location_company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $query->where('location_company_id', $company_id);
                }
            }

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = MasterLocation::query()
                ->select('1 as total')
                ->leftJoin('companies', 'company_id', '=', 'location_company_id')
                ->where('master_locations.deleted_at', null);

            // role manager and admin only can see their company data
            if (auth()->user()->user_role == 'manager' || auth()->user()->user_role == 'admin') {
                $queryTotal->where('location_company_id', auth()->user()->user_company_id);
            } else {
                if ($company_id && $company_id !== null) {
                    $queryTotal->where('location_company_id', $company_id);
                }
            }
            
            $total_all = $queryTotal->count();

            $data = [
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
            'location_name' => 'required',
            'location_longitude' => 'required',
            'location_latitude' => 'required',
            'location_company_id' => 'required',
            'location_radius' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        $data = MasterLocation::create([
            'location_name'         => request('location_name'),
            'location_longitude'    => request('location_longitude'),
            'location_latitude'     => request('location_latitude'),
            'location_company_id'   => request('location_company_id'),
            'location_radius'       => request('location_radius'),
            'created_at'            => now()->addHours(7),
            'updated_at'            => null,
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

    public function detail($id)
    {
        $output = [
            'code'      => 400,
			'status'    => 'error',
			'message'   => 'Bad Request',
            'result'     => []
        ];
			
        $data = MasterLocation::query()
        ->select(
            'location_id',
            'location_name',
            'location_longitude',
            'location_latitude',
            'location_company_id',
            'company_name',
            'location_radius',
            'master_locations.created_at',
        )
        ->leftJoin('companies', 'company_id', '=', 'location_company_id')
        ->where('location_id', $id)
        ->first();
        
        $output = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data ditemukan',
            'result' => $data ? convertResponseSingle($data) : new \stdClass(),
        ];

        return response()->json($output, 200);
    }

    public function update(Request $request, $id)
    {
        $check_data = MasterLocation::find($id);
        
        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'location_name'         => 'required',
            'location_longitude'    => 'required',
            'location_latitude'     => 'required',
            'location_company_id'   => 'required',
            'location_radius'       => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }
       
        $res = $check_data->update([
            'location_name'         => $request->location_name,
            'location_longitude'    => $request->location_longitude,
            'location_latitude'     => $request->location_latitude,
            'location_company_id'   => $request->location_company_id,
            'location_radius'       => $request->location_radius,
            'updated_at'            => now()->addHours(7),
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => $check_data
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
        $check_data = MasterLocation::find($id);

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
}
