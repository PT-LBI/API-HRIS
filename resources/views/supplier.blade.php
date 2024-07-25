<!DOCTYPE html>
<html>
<head>
    <title>Export PDF</title>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Tanggal Export: {{ $date }}</p>

    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No</th>
                <th>Supplier</th>
                <th>Tipe</th>
                <th>Nama Lain</th>
                <th>No. HP</th>
                <th>No. KTP</th>
                <th>NPWP</th>
                <th>Kota/Kab</th>
                <th>Provinsi</th>
                <th>Alamat</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->supplier_name }}</td>
                    <td>{{ $value->supplier_type }}</td>
                    <td>{{ $value->supplier_other_name }}</td>
                    <td>{{ $value->supplier_phone_number }}</td>
                    <td>{{ $value->supplier_identity_number }}</td>
                    <td>{{ $value->supplier_npwp }}</td>
                    <td>{{ $value->supplier_district_name }}</td>
                    <td>{{ $value->supplier_province_name }}</td>
                    <td>{{ $value->supplier_address }}</td>
                    <td>{{ $value->supplier_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
