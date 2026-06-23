# Relatórios por Candidatura — Sprint 24

## Objetivo

Gerar relatório operacional por candidatura para apoio à análise municipal, mantendo cópia do payload usado e ficheiro em storage privado.

## Implementação

- Tabela: `application_reports`
- Model: `App\Models\ApplicationReport`
- Controller: `App\Http\Controllers\Backoffice\ApplicationReportController`
- Services: `ApplicationReportService`, `ApplicationReportPayloadBuilder`, `ApplicationReportExportService`
- Rota principal: `backoffice.applications.report.generate`

## Texto obrigatório

O relatório inclui a nota:

```text
Este documento foi gerado automaticamente com base nos dados registados na plataforma à data da emissão. A validação final compete aos serviços municipais.
```

## Segurança

- Storage privado no disk `local`.
- Download por controller autorizado.
- Dados nominais dependem da permissão `reports.view_sensitive`.

## Limites

PDF/XLSX usam fallback documentado quando não existe motor dedicado. Exportações reais formais devem ser validadas antes de produção.
