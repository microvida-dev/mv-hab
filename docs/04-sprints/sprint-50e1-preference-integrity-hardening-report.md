# Sprint 50E.1 — Preference Integrity Hardening

## 1. Resumo

A Sprint 50E.1 reforçou a integridade das preferências habitacionais sem criar
um terceiro sistema, sem apagar dados legacy e sem alterar regras de
elegibilidade, scoring, listas, sorteios ou atribuição.

O trabalho concluiu:

- substituição repetida de preferências de rascunho sem colisões com os
  índices únicos de MySQL/MariaDB;
- estado estrutural e auditável da origem das preferências;
- resolução central fail-closed entre a fonte oficial e a fonte legacy;
- criação do snapshot final apenas na submissão;
- unicidade, imutabilidade, idempotência e concorrência dos snapshots;
- autorização centralizada na candidatura e ownership;
- invalidação transversal por eventos de domínio;
- atribuição limitada às preferências oficiais finais;
- reforço de acessibilidade na página **Habitações pretendidas**;
- migração incremental e reversível;
- testes SQLite, MySQL/MariaDB e browser real.

As fontes regulamentares PAA, RSAA e fiscais em falta não foram inventadas.
Dados demo continuam marcados como `demo_only` e não são válidos para efeitos
administrativos reais.

Classificação: `REPOSITORY_PASS_DEPLOYMENT_GATED`.

## 2. Branch, base e commits

- Branch: `sprint-50e1-preference-integrity-hardening`
- Commit-base remoto da Sprint 50A.1:
  `1dc512b3d45a0dfc8cbe36d30f45a76caa222a96`
- Commit funcional e de testes validado:
  `e5b91cdc6318a0dc9627df19704a76ad4a1e1145`
- Branch `main`: não alterada
- Findings:
  `docs/quality/sprint-50-hardening-findings.md`

Commits funcionais:

1. `15d3850c` — auditoria de integridade da Sprint 50E;
2. `df2a9c1d` — substituição segura e origem oficial;
3. `479738b9` — ciclo final dos snapshots;
4. `ab21eb1b` — atribuição dependente de preferências finais oficiais;
5. `e0e7094e` — autorização centralizada;
6. `7af2a4b2` — invalidação por eventos de domínio;
7. `f653696e` — acessibilidade da página de preferências;
8. `e5b91cdc` — cobertura de legado, snapshots, índices e invalidação.

O commit documental de fecho sucede ao commit funcional acima. A igualdade
local/remota é confirmada pelos comandos Git posteriores à publicação, porque
um commit não pode conter o seu próprio SHA.

## 3. Findings e decisões

| Finding | Estado final | Decisão |
| --- | --- | --- |
| SoftDeletes versus índices únicos | `CONFIRMED` e corrigido | Rascunhos desbloqueados são substituídos sob transação e lock; as linhas mutáveis, incluindo soft-deleted, são eliminadas definitivamente antes da recriação. |
| Fallback legacy após origem oficial | `CONFIRMED` e corrigido | A origem fica persistida; uma coleção oficial vazia nunca reativa legacy. |
| Snapshot final criado prematuramente | `CONFIRMED` e corrigido | O service exige candidatura submetida e bloqueada; previews permanecem transitórios. |
| Unicidade estrutural do snapshot | `ALREADY_SAFE` no schema; service reforçado | Mantida a unique por candidatura/tipo e acrescentado tratamento idempotente de concorrência. |
| Policy dependente de role fixa | `CONFIRMED` e corrigido | A policy delega na `ApplicationPolicy` e combina ownership, estado, lock e atribuição. |
| Invalidação incompleta | `CONFIRMED` e corrigido | Evento de domínio e listener síncrono cobrem writers candidate, backoffice, correções, renovações e atualização anual. |
| Atribuição sem prova de fonte/snapshot final | `CONFIRMED` e corrigido | A atribuição exige origem oficial/reconciliada, snapshot final e snapshot regulamentar coincidente. |
| N+1 nos readers | `NOT_REPRODUCED`; prevenção reforçada | Relações seletivas e eager loading são usados nos readers e no payload de atas. |

## 4. SoftDeletes e substituição segura

Os índices reais de `housing_preferences` são:

- `(application_id, preference_order)`;
- `(application_id, contest_housing_unit_id)`.

Como `deleted_at` não integra estas chaves, uma linha soft-deleted continua a
ocupar a identidade única em MySQL/MariaDB.

A solução adotada preserva o contrato do schema e evita recursos específicos
de PostgreSQL:

1. abre transação;
2. bloqueia a candidatura com `lockForUpdate`;
3. revalida estado, lock, snapshot e atribuição dentro da transação;
4. elimina definitivamente apenas preferências mutáveis de rascunho;
5. cria a nova ordenação consecutiva;
6. mantém o histórico administrativo final no snapshot e na auditoria.

Não são eliminadas preferências bloqueadas, snapshots ou dados
`application_preferences`. O ciclo guardar/remover/reutilizar/reordenar foi
repetido em MySQL sem `Duplicate entry`.

## 5. Origem oficial e legacy

Foi criado `ApplicationPreferenceSource` com:

- `uninitialized`;
- `legacy`;
- `official`;
- `reconciled`;
- `requires_manual_review`.

A candidatura conserva:

- a origem atual;
- a data de inicialização da fonte oficial;
- a data de reconciliação legacy.

`ApplicationHousingPreferenceSourceResolver` é o ponto único de leitura para
elegibilidade, snapshots, comprovativo, dossier e atas.

Regras fail-closed:

- `uninitialized` pode resolver legacy apenas quando o estado e os dados o
  permitem;
- `legacy` continua legível para compatibilidade;
- `official` usa exclusivamente `housing_preferences`, mesmo com zero linhas;
- `reconciled` usa a fonte oficial reconciliada;
- `requires_manual_review` não escolhe silenciosamente uma fonte;
- uma candidatura que entrou no fluxo oficial nunca regressa
  automaticamente a legacy.

O comando de reconciliação continua dry-run por defeito. Correspondências
ambíguas, incluindo execução com `--apply`, ficam em
`requires_manual_review`.

## 6. Ciclo de snapshots

O ciclo final fica definido assim:

```text
rascunho
→ preferências mutáveis
→ preview transitório
→ sem snapshot final

submissão
→ revalidação
→ lock da candidatura
→ snapshot final único
→ snapshot imutável
```

`ApplicationSnapshotService`:

- exige candidatura submetida e bloqueada;
- usa transação e `lockForUpdate`;
- não usa `firstOrCreate()` para cristalizar previews;
- trata repetição como operação idempotente;
- trata corrida apoiado na constraint estrutural;
- não atualiza nem elimina snapshots emitidos.

`ApplicationSnapshot` rejeita update e delete. Comprovativo e dossier usam o
snapshot final quando a candidatura já foi submetida.

## 7. Autorização e fronteiras de acesso

`HousingPreferencePolicy` deixou de usar uma role fixa como única prova. A
autorização reutiliza `ApplicationPolicy` e confirma:

- ownership da candidatura;
- ability existente;
- candidatura editável;
- ausência de lock;
- ausência de atribuição.

O middleware candidate das rotas foi preservado. Não foram alteradas rotas,
permissions ou fronteiras do backoffice. Candidatos com roles múltiplas não
ganham acesso adicional e um operador não entra acidentalmente no fluxo
candidate.

## 8. Invalidação transversal

Foram introduzidos:

- `HousingPreferenceInputsChanged`;
- `InvalidateHousingPreferences`.

Os writers validados disparam o evento após mutação efetiva em:

- agregado e membros;
- rendimentos;
- situação habitacional;
- registo de adesão;
- correções administrativas;
- renovação de registo;
- atualização documental anual;
- alterações backoffice relevantes.

O listener:

- é síncrono e idempotente;
- não apaga preferências;
- marca apenas escolhas mutáveis como necessitando revalidação;
- não toca em snapshots finais;
- não afeta candidaturas não relacionadas;
- usa metadata minimizada.

Um teste de arquitetura mantém uma matriz explícita dos writers obrigatórios
para reduzir o risco de novos caminhos sem invalidação.

## 9. Atribuição

`PreferenceAllocationService` só considera uma candidatura quando:

- a origem é `official` ou `reconciled`;
- existe snapshot final do tipo `housing_preferences`;
- a preferência consta do snapshot com a mesma unidade e ordem;
- o `regulatory_snapshot_id` coincide;
- a escolha está válida e bloqueada;
- a unidade continua disponível.

A ordem continua estritamente:

```text
1.ª preferência
→ 2.ª preferência
→ 3.ª preferência
→ reserva
```

Não existe fallback para unidade não escolhida. Uma preferência legacy,
invalidada, sem lock ou com snapshot regulamentar divergente é recusada.

## 10. UX, acessibilidade e browser

A página **Habitações pretendidas** foi reforçada com:

- `aria-live` para alterações de seleção e ordem;
- reordenação por `Alt` + setas;
- foco explícito após seleção/remoção;
- estado vazio compreensível;
- ausência de IDs técnicos visíveis;
- layout sem overflow em desktop e tablet.

Validação real no browser local:

- autenticação candidate;
- candidatura em rascunho;
- configuração regulamentar incompleta apresentada em modo fail-closed;
- zero opções quando não existe fonte válida;
- heading e botão de gravação acessíveis;
- uma região `aria-live`;
- viewport de tablet com 768 px sem overflow horizontal;
- zero erros de consola.

Drag-and-drop permanece melhoria progressiva; os botões e o teclado continuam
o mecanismo acessível principal.

## 11. Migration e compatibilidade

Migration criada:

- `2026_07_27_000044_add_application_preference_source_state.php`

A migration:

- é incremental e reversível;
- adiciona apenas estado e timestamps estruturais;
- não elimina preferências nem snapshots;
- evita backfill ambíguo;
- suporta MySQL/MariaDB e SQLite;
- preserva dados existentes.

Validação MySQL/MariaDB numa base temporária isolada:

1. migração integral — PASS;
2. rollback da migration 44 — PASS;
3. reaplicação — PASS;
4. testes de migration/índices — 4 testes, 23 asserções, PASS;
5. inserção concorrente real do snapshot por dois processos — exatamente uma
   linha final.

Na base local da aplicação, a migration ficou aplicada no batch 17.

## 12. Performance e query count

- readers usam `select()` e eager loading seletivo;
- o resolver central aceita relações previamente carregadas;
- o payload de atas carrega snapshots antecipadamente;
- não existem queries em Blade;
- a matriz de compatibilidade compara 1 e 20 cards e exige crescimento máximo
  de duas queries;
- o teste passou com 20 opções, demonstrando contagem limitada e independente
  do número de cards;
- os testes focados não detetaram N+1.

O payload continua proporcional às opções autorizadas e não inclui dados
privados adicionais.

## 13. Testes e quality gates

| Gate | Resultado |
| --- | --- |
| Testes focados 50E.1 | PASS, 48 testes / 218 asserções |
| Testes MySQL de migration e índices | PASS, 4 / 23 |
| Concorrência MySQL do snapshot | PASS, uma linha final |
| PHPUnit integral | PASS, 1 356 / 20 276 |
| PHPUnit UX | PASS, 130 / 645 |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| Integridade desde o commit-base | PASS, 0 violações / 0 avisos |
| Pint integral | PASS |
| Pint incremental | PASS, 36 ficheiros PHP |
| PHPStan | PASS, 0 erros |
| Migration apply/rollback/reapply | PASS |
| `php artisan migrate:status` | PASS, migration 44 `Ran` |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| Artefactos proibidos | PASS |
| Browser real | PASS |

O scan de artefactos encontrou apenas `.gitignore` e `.gitkeep` legítimos em
`storage/framework` e `storage/logs`.

## 14. Rotas e contrato HTTP

- rotas antes: 1 167;
- rotas depois: 1 167;
- comparação: nome, URI, métodos e middleware;
- diff: vazio;
- SHA-256 antes:
  `8ffba3746d3dc77fb68ca7a5ef8815e30acfa30449f8816002c83c7c9444d87b`;
- SHA-256 depois:
  `8ffba3746d3dc77fb68ca7a5ef8815e30acfa30449f8816002c83c7c9444d87b`.

Não foram alteradas rotas.

## 15. Trabalho concluído

- infraestrutura estrutural da origem das preferências;
- resolver único fail-closed;
- substituição segura em MySQL/MariaDB;
- reconciliação ambígua para revisão manual;
- snapshot final único, imutável e idempotente;
- atribuição dependente do snapshot final;
- autorização por candidatura e ownership;
- invalidação transversal;
- UX e acessibilidade;
- migration, testes e documentação.

## 16. Gates regulamentares ainda fechados

Continuam fechados:

1. PAA sem tabela nacional oficial completa e validada;
2. PAA/RSAA sem limite superior oficial do 6.º escalão do IRS, ano fiscal e
   vigência;
3. RSAA sem tabela oficial de rendas;
4. qualquer manifesto sem checksum, cobertura e validação verificáveis;
5. contratos legacy ambíguos;
6. utilização de dados `demo_only` fora de ambiente demo explícito.

Nestes casos o sistema devolve `configuration_incomplete` ou exige revisão
manual. Este comportamento é intencional.

## 17. Fontes oficiais necessárias

Para desbloquear produção são necessárias:

- tabela PAA aplicável, com cobertura nacional/municipal e tipologias;
- documento, referência, versão e vigência da tabela PAA;
- limite superior oficial do 6.º escalão do IRS;
- ano fiscal e período de vigência desse limite;
- tabela e fonte oficial RSAA aplicável;
- validação jurídica e técnica das fontes;
- validação formal de eventuais overlays municipais.

Nenhum destes valores foi inferido a partir de dados demo ou textos
descritivos.

## 18. Procedimento futuro de importação e validação

1. receber os documentos oficiais por canal controlado;
2. preservar os originais e calcular os respetivos checksums;
3. confirmar referência, versão, ano fiscal e vigência;
4. obter validação jurídica;
5. importar valores para staging;
6. normalizar apenas identificadores e tipologias, sem alterar montantes;
7. criar manifesto com contagem e cobertura esperadas;
8. calcular checksum determinístico das linhas;
9. executar `regulatory:audit-rent-limit-tables`;
10. resolver lacunas e divergências;
11. validar por utilizador autorizado;
12. executar publicação e snapshots em staging;
13. repetir testes integrais, rollback e concorrência;
14. promover apenas o catálogo validado, nunca um manifesto demo.

Qualquer divergência mantém o gate fechado.

## 19. Funcionalidades exclusivamente demo

Só podem ser usadas com modo demo explicitamente ativo:

- perfil fictício de Alcanena;
- teto fiscal fictício;
- rendas fictícias do cenário municipal;
- manifesto restrito ao cenário demo;
- seleção, publicação, snapshots e atribuição demonstrativas.

Os fixtures demo declaram explicitamente a origem oficial dentro do cenário de
teste para exercitar o fluxo. Isso não converte esses valores em fonte oficial
de produção.

## 20. Riscos residuais e deployment gates

Riscos residuais:

- fontes regulamentares oficiais continuam ausentes;
- contratos legacy de outros ambientes ainda têm de ser inventariados;
- a concorrência deve ser repetida no MySQL/MariaDB do ambiente de staging;
- candidaturas ambíguas exigem revisão humana;
- novos writers futuros devem integrar o evento de invalidação;
- volumes muito elevados de opções devem ser monitorizados.

Antes de ativar num ambiente:

1. aplicar a migration 44 e confirmar constraints;
2. executar reconciliação legacy em dry-run;
3. rever todos os casos `requires_manual_review`;
4. instalar e validar fontes regulamentares oficiais;
5. confirmar perfil, snapshot e rule set do concurso;
6. testar submissão, comprovativo, dossier e atribuição;
7. testar concorrência e rollback no motor MySQL/MariaDB de destino;
8. validar accessibility e browser no deployment;
9. confirmar auditoria e monitorização;
10. manter demo mode desativado.

## 21. Evidência Git

Antes do commit documental:

- working tree: limpa;
- HEAD funcional: `e5b91cdc6318a0dc9627df19704a76ad4a1e1145`;
- branch: `sprint-50e1-preference-integrity-hardening`;
- base: `1dc512b3d45a0dfc8cbe36d30f45a76caa222a96`;
- diferença funcional: 37 ficheiros, 1 710 inserções e 138 remoções;
- remoto de origem da branch antes da publicação:
  `origin/sprint-50a1-regulatory-hardening`;
- branch `main`: não alterada.

Depois da publicação é obrigatório confirmar:

```bash
git status --short --branch
git rev-parse HEAD
git rev-parse origin/sprint-50e1-preference-integrity-hardening
git rev-list --left-right --count \
    HEAD...origin/sprint-50e1-preference-integrity-hardening
```

Critério: working tree limpa, HEAD local igual ao remoto e `0 0`.

## 22. Decisão final

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O repositório passou os gates técnicos, funcionais, de segurança, migração,
concorrência, acessibilidade e regressão da Sprint 50E.1. O deployment com
efeitos administrativos reais continua bloqueado até instalação e validação
das fontes oficiais identificadas.
