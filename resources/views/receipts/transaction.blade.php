<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Pemesanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .total {
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Nota Pemesanan</h2>
        <p>{{ $transaction->stan->nama_stan }}</p>
    </div>
    
    <div>
        <p><strong>No. Transaksi:</strong> {{ $transaction->id }}</p>
        <p><strong>Tanggal:</strong> {{ $transaction->tanggal }}</p>
        <p><strong>Pembeli:</strong> {{ $transaction->siswa->nama_siswa }}</p>
        <p><strong>Status:</strong> {{ $transaction->status }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Menu</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->detailTransaksi as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->menu->nama_makanan }}</td>
                <td>Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                <td>{{ $item->qty }}</td>
                <td>Rp {{ number_format($item->harga_beli * $item->qty, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="total">
        <p>Total: Rp {{ number_format($total, 0, ',', '.') }}</p>
    </div>
</body>
</html>