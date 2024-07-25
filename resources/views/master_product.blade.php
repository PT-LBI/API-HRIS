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
                <th>HPP (Rp)</th>
                <th>Harga Jual (Rp)</th>
                <th>Unit</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Tgl Update Harga</th>
                <th>Nilai Stok (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $value)
                <tr>
                    <td>{{ $value->number }}</td>
                    <td>{{ $value->product_name }}</td>
                    <td>{{ $value->product_sku }}</td>
                    <td>{{ $value->product_category_name }}</td>
                    <td class="amount">{{ number_format($value->product_hpp , 0, ',', '.')}}</td>
                    <td class="amount">{{ number_format($value->product_sell_price , 0, ',', '.')}}</td>
                    <td>{{ $value->product_unit }}</td>
                    <td>{{ $value->product_stock }}</td>
                    <td>{{ $value->product_status }}</td>
                    <td>{{ $value->product_price_updated_at }}</td>
                    <td class="amount">{{ number_format($value->product_stock_value , 0, ',', '.')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
