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
                <th>Bulan</th>
                <th>Jumlah Transaksi</th>
                <th>Jumlah Supplier</th>
                <th>Cash (Rp)</th>
                <th>Transfer (Rp)</th>
                <th>Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->date }}</td>
                    <td>{{ $value->total_po }}</td>
                    <td>{{ $value->total_supplier }}</td>
                    <td class="amount">{{ number_format($value->total_cash , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->total_transfer , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->total , 0, ',', '.')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
