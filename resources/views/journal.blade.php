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
                <th>Transaksi</th>
                <th>Akun</th>
                <th>Debit (Rp)</th>
                <th>Kredit (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->journal_date }}</td>
                    <td>{{ $value->journal_name }}</td>
                    <td>{{ $value->master_chart_account_code }} | {{ $value->master_chart_account_name }}</td>
                    <td class="amount">{{ number_format($value->journal_debit , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->journal_credit , 0, ',', '.')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
