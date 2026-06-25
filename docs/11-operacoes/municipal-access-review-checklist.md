# Municipal Access Review Checklist

## Objetivo

Checklist de revisao trimestral de acessos municipais para staging/piloto controlado.

## Cadencia

- revisao trimestral;
- revisao adicional antes de demonstracao municipal;
- revisao imediata apos saida de tecnico, mudanca de equipa ou incidente.

## Checklist

- [ ] Confirmar que existe pelo menos um administrator ativo.
- [ ] Confirmar que o ultimo administrator ativo nao pode ser desativado/removido.
- [ ] Confirmar MFA nos perfis sensiveis.
- [ ] Rever utilizadores inativos.
- [ ] Rever roles atribuidas.
- [ ] Rever membros de equipas municipais.
- [ ] Remover acessos de utilizadores que ja nao precisam.
- [ ] Confirmar que support_agent nao tem documentos sensiveis.
- [ ] Confirmar que auditor nao tem permissoes de mutacao.
- [ ] Confirmar que financial_manager nao altera scoring/listas.
- [ ] Confirmar que maintenance_manager nao consulta rendimentos/documentos por defeito.
- [ ] Confirmar que candidatos/inquilinos funcionais nao acedem a backoffice.
- [ ] Rever Work Tasks por equipa e perfis compativeis.
- [ ] Rever auditoria de role_assigned, role_removed e team_member_added.
- [ ] Registar decisao e riscos residuais.

## Evidencia

Guardar evidencia sanitizada em `storage/qa` ou ferramenta operacional municipal:

- data;
- responsavel pela revisao;
- roles alteradas;
- equipas alteradas;
- excecoes aceites;
- riscos e prazo de correcao.

Nunca incluir passwords, tokens, NIF, documentos privados ou dados pessoais desnecessarios.
