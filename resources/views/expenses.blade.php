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
                <th>No. Expenses</th>
                <th>No. Akun</th>
                <th>Kode Akun</th>
                <th>Jumlah (Rp)</th>
                <th>Status</th>
                <th>Bank</th>
                <th>PIC</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->expenses_date }}</td>
                    <td>{{ $value->expenses_number }}</td>
                    <td>{{ $value->master_chart_account_name }}</td>
                    <td>{{ $value->master_chart_account_code }}</td>
                    <td class="amount">{{ number_format($value->expenses_amount , 0, ',', '.')}}</td>
                    <td>{{ $value->expenses_status }}</td>
                    <td>{{ $value->expenses_bank_name }}</td>
                    <td>{{ $value->expenses_pic_name }}</td>
                    <td>{{ $value->expenses_note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
