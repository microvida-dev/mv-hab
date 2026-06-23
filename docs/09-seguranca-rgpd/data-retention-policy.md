# Política de Retenção de Dados

## Estado Sprint 18

A retenção foi implementada com `retention_policies` e `retention_executions`.

Controlos:

- políticas por entidade/model;
- período em meses;
- ação prevista: manter, arquivar, restringir, anonimizar, pseudonimizar, eliminar ou revisão manual;
- simulação sem alteração de dados;
- execução real apenas após aprovação quando exigida;
- execução conservadora nesta sprint, sem eliminação automática massiva.

Pendências:

- validar prazos legais por tipo documental/processual;
- definir responsáveis e evidência de aprovação;
- testar restore antes de qualquer eliminação real;
- aprovar plano com DPO e serviços municipais.
