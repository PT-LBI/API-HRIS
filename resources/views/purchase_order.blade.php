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
                <th>No. PO</th>
                <th>Supplier</th>
                <th>PIC</th>
                <th>Total Product</th>
                <th>Tax (Rp)</th>
                <th>Total (Rp)</th>
                <th>Status</th>
                <th>Penerimaan</th>
                <th>Status Kapal</th>
                <th>Status Pembayaran</th>
                <th>Bank</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->po_date }}</td>
                    <td>{{ $value->po_number }}</td>
                    <td>{{ $value->po_supplier_name }}</td>
                    <td>{{ $value->po_pic_name }}</td>
                    <td>{{ $value->po_total_product }}</td>
                    <td class="amount">{{ number_format($value->po_tax , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->po_grandtotal , 0, ',', '.')}}</td>
                    <td>{{ $value->po_status }}</td>
                    <td>{{ $value->po_status_receiving }}</td>
                    <td>{{ $value->po_status_ship }}</td>
                    <td>{{ $value->po_payment_status }}</td>
                    <td>{{ $value->po_payment_bank_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
