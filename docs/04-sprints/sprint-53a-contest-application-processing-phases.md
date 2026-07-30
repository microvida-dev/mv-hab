# Sprint 53A — Motor de fases processuais do concurso

## Objetivo

Centralizar a resolução dos períodos de submissão, análise inicial, aperfeiçoamento e revalidação sem criar uma segunda estrutura temporal concorrente com `contest_deadlines`.

## Decisão arquitetural

`contest_deadlines` permanece a fonte temporal processual. Os campos `contests.opens_at` e `contests.closes_at` continuam disponíveis como contrato de compatibilidade para o portal público, pesquisas e integrações existentes.

Sempre que um concurso é criado ou atualizado, o prazo `applications` é sincronizado automaticamente com esses dois campos.

## Tipos processuais

- `applications` — submissão de candidaturas;
- `review` — análise inicial pelo backoffice;
- `corrections` — aperfeiçoamento pelo candidato;
- `revalidation` — validação das alterações submetidas.

Os prazos de reclamações, audiência e outros atos continuam independentes.

## Componentes

- `ContestApplicationTimelineService`: normalização, ordenação e validação da sequência;
- `ContestApplicationPhaseService`: resolução da fase atual, próxima fase e compatibilidade pública;
- `ContestApplicationPhase`: enum do estado processual resolvido;
- `ValidatesContestApplicationTimeline`: validação partilhada pelos Form Requests.

## Compatibilidade

Concursos legacy sem linha `applications` continuam a usar `opens_at` e `closes_at`. Concursos que já têm apenas o prazo de aperfeiçoamento continuam válidos, mas são identificados no backoffice como calendário processual incompleto.

A completude exige os quatro períodos processuais. Nesta sprint, a ausência de fases posteriores é apresentada como aviso e não bloqueia concursos existentes.

## Regras

- só existe uma fase de cada tipo processual;
- todas as fases exigem início e fim;
- o fim deve ser posterior ao início;
- uma fase não pode começar no mesmo instante nem antes do fim da fase anterior;
- linhas totalmente vazias do formulário são ignoradas;
- alterações aos prazos são incluídas no payload de auditoria do concurso;
- as fronteiras temporais são inclusivas e usam o timezone da aplicação.

## Fora de âmbito

- lotes imutáveis de resultados;
- publicação sincronizada;
- abertura automática de pedidos de aperfeiçoamento;
- exportação temporal;
- eventos persistidos de transição de fase.

Estes componentes pertencem às Sprints 53B–53I.
