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
                <th>Tanggal</th>
                <th>No. Adjustment</th>
                <th>PIC</th>
                <th>Status</th>
                <th>Total Product</th>
                <th>Total Masuk</th>
                <th>Total Keluar</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->stock_adjustment_date }}</td>
                    <td>{{ $value->stock_adjustment_code }}</td>
                    <td>{{ $value->stock_adjustment_user_name }}</td>
                    <td>{{ $value->stock_adjustment_status }}</td>
                    <td>{{ $value->stock_adjustment_total_product }}</td>
                    <td>{{ $value->total_add }}</td>
                    <td>{{ $value->total_out }}</td>
                    <td>{{ $value->stock_adjustment_note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
