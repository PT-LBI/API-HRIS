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
                <th>No. Transaksi</th>
                <th>Customer</th>
                <th>PIC</th>
                <th>Total Product</th>
                <th>Subtotal (Rp)</th>
                <th>Tax (Rp)</th>
                <th>Tax Ppn(Rp)</th>
                <th>Total (Rp)</th>
                <th>Status</th>
                <th>Pengiriman</th>
                <th>Bank</th>
                <th>Status Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->transaction_date }}</td>
                    <td>{{ $value->transaction_number }}</td>
                    <td>{{ $value->user_name }}</td>
                    <td>{{ $value->transaction_pic_name }}</td>
                    <td>{{ $value->transaction_total_product }}</td>
                    <td class="amount">{{ number_format($value->transaction_subtotal , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->transaction_tax , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->transaction_tax_ppn , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->transaction_grandtotal , 0, ',', '.')}}</td>
                    <td>{{ $value->transaction_status }}</td>
                    <td>{{ $value->transaction_status_delivery }}</td>
                    <td>{{ $value->transaction_payment_bank_name }}</td>
                    <td>{{ $value->transaction_payment_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
