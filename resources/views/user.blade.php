<!DOCTYPE html>
<html>
<head>
    <title>Export PDF</title>
    <style>
        .amount {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Tanggal Export: {{ $date }}</p>

    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No</th>
                <th>Email</th>
                <th>Nama</th>
                <th>No. HP</th>
                <th>No. KTP</th>
                <th>NPWP</th>
                <th>Role</th>
                <th>Posisi</th>
                <th>Tgl Gabung</th>
                <th>Kota/Kab</th>
                <th>Provinsi</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->email }}</td>
                    <td>{{ $value->user_name }}</td>
                    <td>{{ $value->user_phone_number }}</td>
                    <td>{{ $value->user_identity_number }}</td>
                    <td>{{ $value->user_npwp }}</td>
                    <td>{{ $value->user_role }}</td>
                    <td>{{ $value->user_position }}</td>
                    <td>{{ $value->user_join_date }}</td>
                    <td>{{ $value->user_district_name }}</td>
                    <td>{{ $value->user_province_name }}</td>
                    <td>{{ $value->user_address }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
