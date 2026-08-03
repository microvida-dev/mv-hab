# Perfil municipal — Técnico de Operações do Concurso

## Objetivo

O template `tecnico-operacoes-concurso` disponibiliza, para o deploy inicial, uma única matriz municipal comum a dois utilizadores distintos.

Os utilizadores mantêm identidade, MFA, sessões e auditoria individuais, mas recebem a mesma role municipal criada a partir do template versionado.

```text
2 utilizadores
        ↓
1 role municipal comum
        ↓
120 permissions exatas
```

Não são atribuídas permissions diretamente aos utilizadores.

## Âmbito funcional

O perfil permite:

- receber e atualizar candidaturas;
- registar candidato, agregado e rendimentos;
- receber, substituir, visualizar e validar documentos;
- usar a assistência de IA documental;
- executar análise e validação em lote;
- gerir aperfeiçoamentos e segunda análise;
- preparar e publicar resultados provisórios;
- tratar audiências prévias;
- utilizar templates de comunicação aprovados;
- configurar disponibilidades e horários de casa aberta;
- confirmar, concluir, cancelar e registar faltas em visitas;
- exportar dados normais, nominais e sensíveis do concurso.

## Entitlements

A aplicação do template não cria nem ativa entitlements.

O Município deve possuir:

```text
applications.intake
applications.review
applications.export
```

## Segurança

O template:

- não contém wildcards;
- exige MFA no backoffice;
- permanece limitado ao Município;
- não cria `PlatformOperatorAssignment`;
- não concede administração de utilizadores, roles ou equipas;
- não concede finanças, pagamentos, contratos, manutenção ou vistorias técnicas;
- não concede scoring, sorteios ou atribuições;
- não concede administração RGPD;
- é classificado como mutável para impedir combinação normal com auditor.

A exportação sensível continua dependente de permission, Policy, MFA, scope municipal, entitlement e confirmação explícita.

## Atribuição

A role deve ser criada uma única vez por Município através do fluxo de templates e atribuída aos dois utilizadores por `RoleAssignmentService`.

A atribuição preserva:

- bloqueio de self-promotion;
- bloqueio de candidate + role municipal;
- bloqueio de conta ou role inativa;
- bloqueio entre Municípios;
- bloqueio quando a role excede as permissions do ator;
- auditoria individual de cada assignment.

## Rollout

1. Publicar o código sem alterar dados.
2. Abrir o preview do template.
3. Confirmar as 120 permissions, os três entitlements e a exigência de MFA.
4. Aplicar o template ao Município.
5. Não reconciliar drift sem revisão explícita.
6. Atribuir a mesma role às duas contas municipais.
7. Confirmar MFA em ambas as contas.
8. Validar intake, revisão documental, lote, publicação, audiência, visitas e exportação.
9. Não executar `DatabaseSeeder`, `SystemAccessSeeder`, migrations ou bootstrap de operador.

## Rollback

- remover a role dos dois utilizadores;
- manter a role/template para auditoria, desativando-a se necessário;
- não eliminar assignments do operador global;
- não remover permissions diretamente;
- reverter a release pelo processo normal caso exista regressão de código.

## Limitação documental

A exportação integral inclui dados estruturados, resultados, metadata e índices documentais. A inclusão automática dos binários permanece bloqueada até existir uma fonte canónica persistida que prove que cada ficheiro foi analisado, não está infetado, não está em quarentena e está apto para exportação.
