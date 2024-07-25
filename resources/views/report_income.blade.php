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
                <th>Tanggal</th>
                <th>Total Penjualan (Rp)</th>
                <th>Total Pembelian (Rp)</th>
                <th>Total Pengeluaran (Rp)</th>
                <th>Total Pajak (Rp)</th>
                <th>Penghasilan Bersih (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value['month'] }}</td>
                    <td class="amount">{{ number_format($value['total_sales'] , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value['total_purchase'] , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value['total_expenses'] , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value['total_tax'] , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value['total_income'] , 0, ',', '.')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
