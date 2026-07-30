<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Confirmação da visita</title>
</head>
<body>
    <h1>Visita marcada</h1>

    <p>A sua visita ao fogo <strong>{{ $booking->housingUnit?->displayTitle() }}</strong> ficou registada.</p>

    <p>
        Data e hora:
        <strong>{{ $booking->slot?->starts_at?->format('d/m/Y H:i') }}</strong><br>
        Participantes: <strong>{{ $booking->guest_count }}</strong><br>
        Referência: <strong>{{ $booking->booking_reference }}</strong>
    </p>

    @if ($booking->slot?->meeting_point)
        <p>Ponto de encontro: {{ $booking->slot->meeting_point }}</p>
    @endif

    <p>
        Para cancelar dentro do prazo permitido, utilize este endereço:<br>
        <a href="{{ $cancellationUrl }}">Cancelar marcação</a>
    </p>

    <p>Não partilhe o endereço de cancelamento.</p>
</body>
</html>
