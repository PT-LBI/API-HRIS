<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1.0" />
		<title>Sales Invoice</title>
		<style>
			body {
				font-size: 14px;
			}
			.watermark {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, 50%) rotate(-45deg);
				font-size: 5em;
				color: #215686;
				opacity: 0.2;
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
		</style>
	</head>
	<body>
		<div class="invoice-container">
			<div class="watermark">PT Lautan Berlian Indah</div>
			<img
				src="https://lautanberlianindah.com/salesHeader.jpg"
				alt="salesHeader" />
			<div class="header">
				<div class="header-date">
					<p>Date : {{$result['data']->transaction_date}}</p>
				</div>
				<div>
					<p>Yth,</p>
					<p style="font-size: 20px"><strong>{{$result['data']->user_name}}</strong></p>
				</div>
			</div>
			<div class="details">
				<p><strong>NPWP :</strong> {{$result['data']->user_npwp}}</p>
				<p><strong>Alamat :</strong> {{$result['data']->user_address}}</p>
				<p><strong>No Kendaraan :</strong> ..................</p>
				<p><strong>No Count :</strong> ..................</p>
				<p><strong>No Seal :</strong> ..................</p>
				<p><strong>No Kwitansi :</strong> {{$result['data']->transaction_number}}</p>
			</div>
			<table>
				<thead style="background-color: #93c5fd">
					<tr>
						<th>No</th>
						<th>Sak</th>
						<th>Description</th>
						<th>Qty</th>
						<th>Price</th>
						<th>Amount</th>
					</tr>
				</thead>
				<tbody>
                    @foreach ($result['detail'] as $value)
					<tr>
						<td>{{ $value->number }}</td>
						<td></td>
						<td>{{ $value->transaction_detail_product_name }}</td>
						<td>{{ $value->transaction_detail_qty }}</td>
						<td style="text-align: right">Rp {{ number_format($value->transaction_detail_price_unit , 0, ',', '.')}}</td>
						<td style="text-align: right">Rp {{ number_format($value->transaction_detail_total_price , 0, ',', '.')}}</td>
					</tr>
                    @endforeach
					<!-- space after data -->
					<tr>
						<td>.</td>
						<td>.</td>
						<td>.</td>
						<td>.</td>
						<td>.</td>
						<td>.</td>
					</tr>
					<tr>
						<td colspan="3">Total</td>
						<td>{{$result['data']->transaction_total_product_qty}}</td>
						<td></td>
						<td style="text-align: right">Rp {{ number_format($result['data']->transaction_subtotal , 0, ',', '.')}}</td>
					</tr>
					<tr>
						<td colspan="3">PPH 22</td>
						<td
							style="text-align: right"
							colspan="2">
							{{$result['data']->transaction_config_tax}}
						</td>
						<td style="text-align: right">Rp {{ number_format($result['data']->transaction_tax , 0, ',', '.')}}</td>
					</tr>
					<tr>
						<td colspan="3">PPn</td>
						<td
							colspan="2"
							style="text-align: right">
							{{$result['data']->transaction_config_tax_ppn}}
						</td>
						<td style="text-align: right">Rp {{ number_format($result['data']->transaction_tax_ppn , 0, ',', '.')}}</td>
					</tr>
					<tr>
						<td colspan="3"><strong>Total Pembayaran</strong></td>
						<td colspan="2"></td>
						<td style="text-align: right"><strong>Rp {{ number_format($result['data']->transaction_grandtotal , 0, ',', '.')}}</strong></td>
					</tr>
				</tbody>
			</table>
			<div class="footer">
				<div>
					<p>Pembayaran Bank :</p>
					<p>{{$result['data']->bank_name}} {{$result['data']->bank_account_name}} {{$result['data']->bank_account_number}}</p>
				</div>
			</div>
			<div class="footer">
				<div style="float: left; width: 50%">
					<p>Diterima Oleh:</p>
                    <p></p>
					<p>________</p>
				</div>
				<div style="float: left; width: 50%">
					<p>Hormat Kami:</p>
                    <p></p>
					<p>________</p>
				</div>
			</div>
		</div>
	</body>
</html>