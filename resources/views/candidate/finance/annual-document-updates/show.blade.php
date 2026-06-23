<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $annualDocumentUpdateRequest->request_number }}</h1></x-slot>
    <div class="mv-card space-y-4"><p>Estado: {{ $annualDocumentUpdateRequest->status->label() }}</p><form method="POST" action="{{ route('candidate.finance.annual-document-updates.submit', $annualDocumentUpdateRequest) }}" class="grid gap-3">@csrf<select class="mv-input" name="document_submission_ids[]" multiple>@foreach ($documents as $document)<option value="{{ $document->id }}">{{ $document->title }}</option>@endforeach</select><textarea class="mv-input" name="notes" placeholder="Notas"></textarea><button class="mv-button-primary">Submeter documentos</button></form></div>
</x-app-layout>
