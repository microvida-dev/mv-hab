<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovativo {{ $application->application_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; margin: 32px; line-height: 1.5; }
        header { border-bottom: 2px solid #145c52; padding-bottom: 16px; margin-bottom: 24px; }
        h1 { font-size: 24px; margin: 0; }
        h2 { font-size: 17px; margin: 24px 0 10px; }
        dl { display: grid; grid-template-columns: 180px 1fr; gap: 8px 16px; }
        dt { color: #5e6778; }
        dd { margin: 0; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #dfe3e8; text-align: left; padding: 8px 0; }
        .notice { background: #eef8f5; padding: 14px; margin-top: 24px; }
        @media print { .print-button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <header>
        <p>MV HAB · Plataforma municipal</p>
        <h1>Comprovativo de Submissão de Candidatura</h1>
    </header>

    <dl>
        <dt>Número</dt><dd>{{ $application->application_number }}</dd>
        <dt>Candidato</dt><dd>{{ $application->adhesionRegistration->full_name }}</dd>
        <dt>Concurso</dt><dd>{{ $application->contest->title }}</dd>
        <dt>Programa</dt><dd>{{ $application->program->name }}</dd>
        <dt>Submetida em</dt><dd>{{ $application->submitted_at->format('d/m/Y H:i') }}</dd>
        <dt>Estado</dt><dd>{{ $application->status->label() }}</dd>
    </dl>

    <h2>Resumo</h2>
    <dl>
        <dt>Membros do agregado</dt><dd>{{ $summary['member_count'] ?? $application->household->members->count() }}</dd>
        <dt>Rendimento mensal</dt><dd>{{ number_format($summary['monthly_income'] ?? 0, 2, ',', '.') }} €</dd>
        <dt>Documentos associados</dt><dd>{{ $application->applicationDocuments->count() }}</dd>
    </dl>

    <h2>Documentos</h2>
    <table>
        <thead><tr><th>Tipo</th><th>Estado na submissão</th></tr></thead>
        <tbody>
            @foreach ($application->applicationDocuments as $document)
                <tr><td>{{ $document->documentType->name }}</td><td>{{ $document->status_at_submission->label() }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <p class="notice">A candidatura foi submetida com sucesso. Este comprovativo não constitui decisão de elegibilidade.</p>
    <button type="button" class="print-button" onclick="window.print()">Imprimir</button>
</body>
</html>
