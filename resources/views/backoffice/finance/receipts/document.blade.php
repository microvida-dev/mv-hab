<!doctype html>
<html lang="pt">
<head><meta charset="utf-8"><title>{{ $receipt->receipt_number }}</title></head>
<body>
    <h1>Comprovativo interno de pagamento</h1>
    <p><strong>Número:</strong> {{ $receipt->receipt_number }}</p>
    <p><strong>Emitido em:</strong> {{ $receipt->issued_at?->format('d/m/Y H:i') }}</p>
    <p><strong>Candidato:</strong> {{ $receipt->tenant?->name }}</p>
    <p><strong>Valor:</strong> {{ number_format((float) $receipt->total_amount, 2, ',', '.') }} {{ $receipt->currency }}</p>
    <p>Documento interno da plataforma municipal. Não substitui recibo fiscal oficial.</p>
</body>
</html>
