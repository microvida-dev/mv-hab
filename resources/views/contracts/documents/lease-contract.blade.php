<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->contract_number }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; line-height: 1.55; margin: 32px; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        h2 { font-size: 18px; margin-top: 28px; }
        h3 { font-size: 15px; margin-top: 20px; }
        .muted { color: #6b7280; font-size: 12px; }
        .box { border: 1px solid #d1d5db; padding: 16px; margin: 16px 0; }
        @media print { button { display: none; } body { margin: 16mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimir</button>
    <p class="muted">Documento HTML gerado pela Sprint 13. PDF não configurado nesta instalação.</p>
    <h1>Contrato {{ $contract->contract_number }}</h1>
    <div class="box">
        <strong>Arrendatário:</strong> {{ $contract->tenant_name }}<br>
        <strong>Habitação:</strong> {{ $contract->housing_address }}<br>
        <strong>Prazo:</strong> {{ $contract->start_date?->format('d/m/Y') }} a {{ $contract->end_date?->format('d/m/Y') }}<br>
        <strong>Renda:</strong> {{ number_format((float) $contract->monthly_rent, 2, ',', ' ') }} EUR<br>
        <strong>Caução:</strong> {{ number_format((float) ($contract->deposit?->amount ?? $contract->deposit_amount), 2, ',', ' ') }} EUR
    </div>
    {!! $body !!}
    <h2>Assinaturas / validações</h2>
    <p>Arrendatário: ________________________________</p>
    <p>Representante municipal: ______________________</p>
</body>
</html>
