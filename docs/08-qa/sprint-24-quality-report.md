# Sprint 24 — Relatório de Qualidade

## Cobertura Implementada

- Dashboards operacionais e executivos protegidos contra guest/candidato.
- Relatório por candidatura gerado em storage privado.
- Dossier documental gerado em storage privado.
- Minutas de procedimento criadas, publicadas e usadas para documento gerado.
- Documento de procedimento aprovado por backoffice.
- Ata gerada e aprovada.
- Alerta interno resolvido.
- Execução de automação de lista aprovada com revisão humana.
- Confirmação de processo gerada e marcada como enviada.

## Testes

Ficheiro principal:

```text
tests/Feature/Sprint24BackofficeOperationalTest.php
```

Resultado inicial do teste específico:

```text
5 testes / 47 asserções: OK
```

## Segurança Validada

- Guest redirecionado para login.
- Candidate bloqueado em rotas backoffice.
- Ficheiros gerados em `storage/app/private` via disk `local`.
- Downloads passam por controller autorizado.
- Fluxos de listas e atas exigem validação humana.

## Pendências

- Validar KPI final com município.
- Definir scheduler de alertas.
- Validar juridicamente minutas e textos finais.
- Confirmar formato oficial de número de processo.
