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
                <th>Nama Produk</th>
                <th>SKU</th>
                <th>Kategori</th>
                <th>Stok Masuk</th>
                <th>Stok Keluar</th>
                <th>Total Adjustment</th>
                <th>Total Nominal (Rp)</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->product_name }}</td>
                    <td>{{ $value->product_sku }}</td>
                    <td>{{ $value->category_name }}</td>
                    <td>{{ $value->card_stock_in }}</td>
                    <td>{{ $value->card_stock_out }}</td>
                    <td>{{ $value->card_stock_adjustment_total }}</td>
                    <td class="amount">{{ number_format($value->card_stock_nominal , 0, ',', '.')}}</td>
                    <td>{{ $value->card_stock_type }}</td>
                    <td>{{ $value->card_stock_status }}</td>
                    <td>{{ $value->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
