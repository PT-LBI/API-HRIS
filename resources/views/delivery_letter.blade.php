<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1.0" />
		<title>Delivery Letter</title>
		<style>
			body {
				font-size: 14px;
			}
			.watermark {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%) rotate(-45deg);
				font-size: 3.5em;
				color: blue;
				opacity: 0.1;
				white-space: nowrap;
				z-index: -10000;
			}
			.invoice-container {
				position: relative;
				padding: 1.5rem;
			}
			.invoice-container h1 {
				text-align: center;
				margin-bottom: 15px;
			}
			img {
				width: 100%;
				height: 260px;
			}
			.header-date {
				text-align: right;
			}
			.details {
				margin-bottom: 15px;
			}
			.details p {
				margin: 0;
			}
			.details p strong {
				display: inline-block;
				width: 150px;
			}
			table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 5px;
			}
			table,
			th,
			td {
				border: 1px solid #000;
			}
			th,
			td {
				padding: 2px;
				padding-right: 10px;
				padding-left: 10px;
				text-align: center;
			}
			.total {
				text-align: right;
				margin-right: 20px;
				margin-top: 20px;
			}
			.another-table {
				width: auto; /* Override width */
				border: none; /* Override border */
				border-collapse: separate; /* Override border-collapse */
				margin-bottom: 0; /* Override margin */
			}
			.another-table td {
				border: none; /* Override border */
				padding: 0; /* Override padding */
				text-align: left; /* Override text-align */
			}
		</style>
	</head>
	<body>
		<div class="invoice-container">
			<div class="watermark">PT Lautan Berlian Indah</div>
			<h1>Delivery Letter</h1>
			<div class="header">
				<div class="header-date">
					<p>{{ $result['data']->transaction_date }}</p>
				</div>
			</div>
			<div>
				<table
					class="another-table"
					style="float: right; width: 50%; margin-bottom: 10px">
					<tr>
						<td style="text-decoration: underline">Toko</td>
						<td rowspan="2">.......................</td>
					</tr>
					<tr>
						<td>Tuan</td>
					</tr>
				</table>
				<table
					class="another-table"
					style="float: left; width: 50%; margin-bottom: 10px">
					<tr>
						<td style="text-decoration: underline">Surat Pengantar</td>
						<td rowspan="2">No. ........................</td>
					</tr>
					<tr>
						<td>Faktur Menyusul</td>
					</tr>
				</table>
			</div>
			<div>
				<p>
					Bersama ini kendaraan ............... No. ............. kami ada kirim barang-barang tersebut
					dibawah ini
				</p>
			</div>
			<table>
				<thead style="background-color: #93c5fd">
					<tr>
						<th style="width: 25%">Banyaknya</th>
						<th>Nama Barang</th>
					</tr>
				</thead>
				<tbody>
          @foreach ($result['detail'] as $value)
					<tr>
						<td>{{ $value->transaction_detail_qty }}</td>
						<td>{{ $value->transaction_detail_product_name}}</td>
					</tr>
          @endforeach
					<!-- space after data -->
					<tr>
						<td>.</td>
						<td>.</td>
					</tr>
					<tr>
						<td
							style="text-align: center"
							colspan="2">
							Total: 10 kg / ......sak
						</td>
					</tr>
				</tbody>
			</table>
			<div class="footer">
				<div style="float: left; width: 50%">
					<p>Tanda tangan si penerima</p>
					<p>________</p>
				</div>
				<div style="float: left; width: 50%">
					<p>Hormat Kami:</p>
					<p>________</p>
				</div>
			</div>
		</div>
	</body>
</html>