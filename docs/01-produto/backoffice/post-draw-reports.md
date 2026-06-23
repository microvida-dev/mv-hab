# Relatórios pós-sorteio

## Objetivo

Gerar relatório HTML privado com método, participantes, presenças, resultados, vencedor e hashes.

## Segurança

- Ficheiro guardado no disk `local`, que aponta para `storage/app/private`.
- Download passa por controller autorizado.
- Download gera registo de auditoria.

## PDF

PDF real não foi implementado nesta sprint. O fallback documentado é HTML privado descarregável.
