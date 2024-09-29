<?php

namespace App\Exports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class EmployeeExport implements FromCollection, WithHeadings,  WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sort = request()->query('sort') ?? 'user_id';
        $dir = request()->query('dir') ?? 'DESC';
        $search = request()->query('search');
        $status = request()->query('status');
        $company_id = request()->query('company_id');
        $division_id = request()->query('division_id');
        $start_date = request()->query('start_date');
        $end_date = request()->query('end_date');
        $user_role = request()->query('user_role');

        $query = User::query()
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY ' . $sort . ') AS `index`'),
                'user_name',
                'user_code',
                'company_name',
                'division_name',
                'user_position',
                'email',
                'user_address',
                'user_driving_license',
                'user_identity_number',
                'user_npwp',
                'user_bpjs_kes',
                'user_bpjs_tk',
                'user_date_birth',
                'user_place_birth',
                'user_last_education',
                'user_marital_status',
                'user_number_children',
                'user_phone_number',
                'user_emergency_contact',
                'user_gender',
                'user_blood_type',
                'user_bank_name',
                'user_account_name',
                'user_account_number',
                DB::raw('DATE(user_join_date) as user_join_date'),
                'user_role',
                'user_type',
                'user_status',
            )
            ->leftJoin('companies', 'user_company_id', '=', 'company_id')
            ->leftJoin('divisions', 'user_division_id', '=', 'division_id')
            ->where('user_role', '!=', 'superadmin')
            ->where('users.deleted_at', null);

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('user_code', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('user_name', 'like', '%' . $search . '%');
            });
        }

        if ($start_date && $start_date !== null) {
            if ($end_date && $end_date !== null) {
                $query->whereBetween('user_join_date', [$start_date, $end_date]);
            } else{
                $query->where('user_join_date', $start_date);
            }
        }

        if ($status && $status !== null) {
            $query->where('user_status', $status);
        }

        if ($division_id && $division_id !== null) {
            $query->where('user_division_id', $division_id);
        }

        if ($company_id && $company_id !== null) {
            $query->where('user_company_id', $company_id);
        }

        if ($user_role && $user_role !== null) {
            $query->where('user_role', $user_role);
        }

        $query->orderBy($sort, $dir);
        
        $data = $query->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'NIP',
            'Perusahaan',
            'Divisi',
            'Jabatan',
            'Email',
            'Alamat',
            'SIM',
            'NIK',
            'NPWP',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'Tgl Lahir',
            'Tempat Lahir',
            'Pendidikan Terakhir',
            'Status Pernikahan',
            'Jumlah Anak',
            'No. HP',
            'Kontak Darurat',
            'Jenis Kelamin',
            'Golongan Darah',
            'Nama Bank',
            'Nama Rekening',
            'No. Rekening',
            'Tgl Bergabung',
            'Role',
            'Tipe',
            'Status',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 15,
            'D' => 25,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 30,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 20,
            'M' => 20,
            'N' => 15,
            'O' => 20,
            'P' => 15,
            'Q' => 20,
            'R' => 15,
            'S' => 15,
            'T' => 15,
            'U' => 15,
            'V' => 15,
            'W' => 15,
            'X' => 25,
            'Y' => 15,
            'Z' => 15,
            'AA' => 15,
            'AB' => 15,
            'AC' => 15,
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
}
