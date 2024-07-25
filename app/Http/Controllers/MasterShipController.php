<?php

namespace App\Http\Controllers;

use App\Models\MasterShip;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MasterShipController extends Controller
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
            $sort = request()->query('sort') ?? 'ship_id';
            $dir = request()->query('dir') ?? 'DESC';
            $search = request()->query('search');
            $ship_type_id = request()->query('ship_type_id');

            $query = DB::table('master_ships')
                ->select(
                    'ship_id',
                    'ship_name',
                    'ship_number',
                    'ship_captain',
                    'ship_main_machine',
                    'ship_propeler',
                    'ship_gt',
                    'master_ship_types.ship_type_id',
                    'ship_type_name',
                    'ship_last_dock',
                    'ship_compressor',
                    'ship_compressor_driving_machine',
                    'ship_skat',
                    'ship_sipi',
                    'ship_eligibility_letter',
                    'ship_status',
                    'ship_image',
                    'master_ships.created_at',
                    'master_ships.updated_at',
                )
                ->where('ship_is_deleted', 0)
                ->leftJoin('master_ship_types', 'master_ships.ship_type_id', '=', 'master_ship_types.ship_type_id');
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('ship_name', 'like', '%' . $search . '%')
                        ->orWhere('ship_number', 'like', '%' . $search . '%')
                        ->orWhere('ship_captain', 'like', '%' . $search . '%');
                });
            }

            if (!empty($ship_type_id)) {
                // Decode JSON array if necessary
                if (is_string($ship_type_id)) {
                    $ship_type_id = json_decode($ship_type_id, true);
                }
            
                if (is_array($ship_type_id) && count($ship_type_id) > 0) {
                    $query->whereIn('master_ship_types.ship_type_id', $ship_type_id);
                }
            }

            // dd($query->toSql());

            $query->orderBy($sort, $dir);
            $res = $query->paginate($limit, ['*'], 'page', $page);

            //get total data
            $queryTotal = DB::table('master_ships')
                ->select('1 as total')
                ->where('ship_is_deleted', 0);
            $total_all = $queryTotal->count();

            $data = [
                'result' => $res->items(),
                'current_page' => $res->currentPage(),
                'from' => $res->firstItem(),
                'last_page' => $res->lastPage(),
                'per_page' => $res->perPage(),
                'total' => $res->total(),
                'total_all' => $total_all,
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
            'ship_name' => 'required|unique:master_ships',
            'ship_number' => 'required|unique:master_ships',
            'ship_captain' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if (request()->file('image')) {
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/ships', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;
        }

        $data = MasterShip::create([
            'ship_name'         => request('ship_name'),
            'ship_number'       => request('ship_number'),
            'ship_captain'      => request('ship_captain'),
            'ship_main_machine' => request('ship_main_machine'),
            'ship_propeler'     => request('ship_propeler'),
            'ship_gt'           => request('ship_gt'),
            'ship_type_id'      => request('ship_type_id'),
            'ship_last_dock'    => request('ship_last_dock'),
            'ship_compressor'   => request('ship_compressor'),
            'ship_compressor_driving_machine'   => request('ship_compressor_driving_machine'),
            'ship_skat'         => request('ship_skat'),
            'ship_sipi'         => request('ship_sipi'),
            'ship_eligibility_letter'   => request('ship_eligibility_letter'),
            'ship_status'       => 'active',
            'ship_image'        => isset($image_url) ? $image_url : null,
            'created_at'        => date('Y-m-d H:i:s'),
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
        //find data by ID
        $data = DB::table('master_ships')
            ->select(
                'ship_id',
                'ship_name',
                'ship_number',
                'ship_captain',
                'ship_main_machine',
                'ship_propeler',
                'ship_gt',
                'master_ship_types.ship_type_id',
                'ship_type_name',
                'ship_last_dock',
                'ship_compressor',
                'ship_compressor_driving_machine',
                'ship_skat',
                'ship_sipi',
                'ship_eligibility_letter',
                'ship_image',
                'ship_status',
                'master_ships.created_at',
                'master_ships.updated_at',
            )
            ->where('ship_id', $id)
            ->leftJoin('master_ship_types', 'master_ships.ship_type_id', '=', 'master_ship_types.ship_type_id')
            ->get()->first();

        if ($data) {
            $output = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data ditemukan',
                'result' => $data,
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

    public function update(Request $request, $id)
    {
        $check_data = MasterShip::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 200);
        }

        //define validation rules
        if ($check_data->ship_image !== request()->file('image')) {
            $validator = Validator::make($request->all(), [
                'ship_name' => 'required|unique:master_ships,ship_name,' . $id . ',ship_id',
                'ship_number' => 'required|unique:master_ships,ship_number,' . $id . ',ship_id',
                'ship_captain' => 'required',
                'ship_status' => 'required|in:active,inactive',
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'ship_name' => 'required|unique:master_ships,ship_name,' . $id . ',ship_id',
                'ship_number' => 'required|unique:master_ships,ship_number,' . $id . ',ship_id',
                'ship_captain' => 'required',
                'ship_status' => 'required|in:active,inactive',
            ]);
        }
        
        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 200);
        }

        if ($request->file('image')) {
            //upload image
            $image = request()->file('image');
            $image_name = time() . '-' . $image->getClientOriginalName();
            $image_path = $image->storeAs('images/ships', $image_name, 'public');
            $image_url = env('APP_URL'). '/storage/' . $image_path;

            // Delete old image if it exists
            if ($check_data && $check_data->ship_image) {
                // Extract the relative path of the old image from the URL
                $old_image_path = str_replace(env('APP_URL') . '/storage/', '', $check_data->ship_image);

                // Delete the old image
                Storage::disk('public')->delete($old_image_path);
            }
        }

        $res = $check_data->update([
            'ship_name'         => $request->ship_name,
            'ship_number'       => $request->ship_number,
            'ship_captain'      => $request->ship_captain,
            'ship_main_machine' => $request->ship_main_machine,
            'ship_propeler'     => $request->ship_propeler,
            'ship_gt'           => $request->ship_gt,
            'ship_type_id'      => $request->ship_type_id,
            'ship_last_dock'    => $request->ship_last_dock,
            'ship_compressor'   => $request->ship_compressor,
            'ship_compressor_driving_machine'   => $request->ship_compressor_driving_machine,
            'ship_skat'         => $request->ship_skat,
            'ship_sipi'         => $request->ship_sipi,
            'ship_eligibility_letter'   => $request->ship_eligibility_letter,
            'ship_status'       => $request->ship_status,
            'ship_image'        => isset($image_url) ? $image_url : $check_data->ship_image,
            'updated_at'        => date('Y-m-d H:i:s'),
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
        $check_data = MasterShip::find($id);

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
            'ship_is_deleted'   => 1,
            'updated_at'        => date('Y-m-d H:i:s'),
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
