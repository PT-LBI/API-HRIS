<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Slip Gaji</title>
  <style>
    body {
      font-size: 14px;
      padding: 2px;
    }

    .watermark {
      position: absolute;
      top: 20%;
      left: 50%;
      transform: translate(-50%, 50%) rotate(-45deg);
      font-size: 5em;
      color: #215686;
      opacity: 0.2;
      white-space: nowrap;
      z-index: -10000;
    }

    img {
      width: 100%;
      height: 260px;
    }

    .content {
      border: 2px black solid;
      padding: 10px 14px;
    }

    .content-header {
      font-weight: 600;
      font-size: 14px;
      display: flex;
      width: 300px;
    }

    .content-child {
      font-weight: 400;
      font-size: 12px;
    }

    table {
      width: 100%;
      border: 2px black solid;
      border-collapse: collapse;
      margin-bottom: 5px;
    }

    thead>tr>th,
    td {
      text-align: center;
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
    
    table,
    th,
    td {
      border: 2px solid #000;
    }

    th,
    td {
      padding: 2px;
      padding-right: 10px;
      padding-left: 10px;
      text-align: left;
    }

    .footer {
      display: flex;
      justify-content: end;
      flex-direction: column;
      text-align: left;
    }

    .footer-content {
      margin-top: 30px;
      font-weight: 600;
      font-size: 14px;
    }

    .text-right {
        text-align: right;
    }

  </style>
</head>

<body>
  <div class="invoice-container">
    <div class="watermark">{{$result->company_name}}</div>
        <div class="header">
        <div>
            <p style="font-size: 20px">
            <strong>{{$result->company_name}}</strong>
            </p>
        </div>
        </div>

    <!-- content -->
    <!-- <div class="content">
      <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
        <div class="content-header" style="display: flex; align-items: center;">
          <p style="width: 50px; margin-right: 10px;">NAMA</p>
          <p style="width: 10px; margin-right: 10px;">:</p>
          <p>{{$result->user_name}}</p>
        </div>
        <div class="content-header" style="display: flex; align-items: center;">
          <p style="width: 50px; margin-right: 10px;">BULAN</p>
          <p style="width: 10px; margin-right: 10px;">:</p>
          <p>{{$result->user_payroll_month_name}}</p>
        </div>
      </div>
      <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
        <div class="content-header" style="display: flex; align-items: center;">
          <p style="width: 50px; margin-right: 10px;">JABATAN</p>
          <p style="width: 10px; margin-right: 10px;">:</p>
          <p>{{$result->user_position}}</p>
        </div>
        <div class="content-header" style="display: flex; align-items: center;">
          <p style="width: 50px; margin-right: 10px;">TAHUN</p>
          <p style="width: 10px; margin-right: 10px;">:</p>
          <p>{{$result->user_payroll_year}}</p>
        </div>
      </div>
    </div> -->

    <div class="details">
        <p><strong>NAMA :</strong> {{$result->user_name}}</p>
        <p><strong>BULAN :</strong> {{$result->user_payroll_month_name}}</p>
        <p><strong>JABATAN :</strong> {{$result->user_position}}</p>
        <p><strong>TAHUN :</strong> {{$result->user_payroll_year}}</p>
    </div>

    <div>
      <table>
        <thead>
          <tr>
            <th>DESKRIPSI</th>
            <th>JML HARI</th>
            <th>JUMLAH</th>
            <th>TTD</th>
          </tr>
        </thead>
        <tbody>
          <!-- Looping here -->
          <tr>
            <td>Gaji Pokok</td>
            number_format($value->transaction_detail_price_unit , 0, ',', '.')
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_value, 0, ',', '.') }}</td>
            <!-- jumlah rowspan sejumlah data +1 -->
            <td rowspan="9"></td>
          </tr>
          <tr>
            <td>Lembur ({{ number_format($result->user_payroll_overtime_hour_total, 0, ',', '.') }} Jam)</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_overtime_hour, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>Transport</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_transport, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>Komunikasi</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_communication, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>Konsumsi</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_meal_allowance, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>Potongan Absensi</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_absenteeism_cut, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>BPJS Ketenagaan Kerja</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_bpjs_tk, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td>BPJS Kesehatann</td>
            <td></td>
            <td class="text-right">Rp {{ number_format($result->user_payroll_bpjs_kes, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td colspan="2" style="text-align: center; font-weight: 700;">TOTAL GAJI YANG DITERIMA</td>
            <td class="text-right"><strong>Rp {{ number_format($result->user_payroll_total_accepted, 0, ',', '.') }}</strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="display: flex; justify-content: end;">
      <div class="footer">
        <p>Probolinggo, {{$result->formatted_sent_at}} </p>
        <span>Penerima</span>
        <div class="footer-content">
          <p>{{$result->user_name}}</p>
        </div>
      </div>
    </div>
  </div>
</body>

</html>