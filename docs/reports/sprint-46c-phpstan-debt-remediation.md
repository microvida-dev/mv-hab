# Sprint 46C — Liquidação controlada da dívida PHPStan

## Identificação

- ID: `TECH-QUALITY-002`
- Branch: `sprint-46c-phpstan-debt-remediation`
- Commit-base: `fe8372e76b8313fb45f50565b828310ad1f30f4a`
- Objetivo: eliminar a dívida PHPStan herdada sem baseline, supressões ou alterações
  funcionais incompatíveis.

## Baseline

A baseline foi reproduzida no HEAD publicado da Sprint 46B:

- 156 diagnósticos;
- 40 ficheiros;
- nível 8;
- 0 baselines ou supressões prévias adicionadas pela sprint.

O wrapper sem `-v` apresentava corretamente o total de 156, mas truncava os detalhes aos
primeiros 30 diagnósticos. A execução verbosa num worktree isolado permitiu confirmar a
distribuição integral, documentada em
`docs/quality/tech-quality-002-phpstan-inventory.md`.

## Commits

- `9493c9f9` — `fix(types): corrigir análise estática da revisão documental`
- `37e2a91b` — `fix(types): tipar comando de auditoria de rotas`
- `a500e0fd` — `fix(types): definir contrato do evento de timeline`
- `b0ef9365` — `fix(types): validar utilizadores em fluxos backoffice`
- `35ec924b` — `fix(types): preservar agregado opcional no simulador`
- `4efb28ca` — `fix(types): normalizar lista de favoritos ordenados`
- `91133851` — `fix(documents): corrigir autorização de requisitos documentais`
- `a4f0f09c` — `fix(agenda): tornar filtros e ordenação determinísticos`
- `e9cd3aef` — `fix(dashboard): tipar composição das operações municipais`
- `16e7d014` — `fix(timeline): alinhar eventos com estados processuais`
- `0e0dc31b` — `fix(quality): corrigir contratos de timeline e navegação`
- `decce153` — `fix(timeline): corrigir eventos documentais e audiências`
- `fddeb936` — `fix(timeline): alinhar eventos operacionais com o domínio`
- `91006b15` — `fix(timeline): tipar eventos financeiros e do inquilino`
- `b5accdb8` — `fix(documents): simplificar estado documental obrigatório`
- relatório e inventário final: neste commit.

## Ficheiros alterados

Foram alterados 74 ficheiros técnicos antes da documentação final:

| Grupo | Quantidade | Âmbito |
|---|---:|---|
| Controllers, Requests e comando | 8 | autenticação, validação, auditoria e revisão documental |
| Data | 1 | contrato do evento da Timeline |
| Models | 20 | contratos de casts, enums, datas e relações |
| Services | 32 | Agenda, Dashboard, Timeline, documentos, navegação e minutas |
| Seeder | 1 | remoção de método privado morto |
| Testes | 12 | 3 novos e 9 reforçados |

Os principais ficheiros de produção abrangem:

- `app/Console/Commands/AuditAccessRoutes.php`;
- `app/Data/Dashboard/TimelineEvent.php`;
- controllers de revisão documental, Agenda, minutas, simulador e favoritos;
- requests do simulador e documentos obrigatórios;
- models de documentos, workflow processual, operações, rendas e inquilinos;
- services de Agenda, Dashboard, Timeline, documentos, favoritos e renderização de atas;
- `database/seeders/AlcanenaProcedureTemplateSeeder.php`.

Não foram alterados:

- migrations;
- rotas;
- Policies;
- middleware;
- permissions;
- APIs públicas;
- schema de base de dados.

## Decisões arquiteturais

### Tipos reais em vez de supressões

- Collections receberam generics e arrays receberam shapes.
- Resultados autenticados são validados como `User` nos limites HTTP.
- Casts Eloquent foram documentados com enums e `Carbon`.
- Relações opcionais são tratadas defensivamente.
- Não foi reduzido o nível PHPStan nem criado baseline.

### Correções funcionais encontradas pela análise

O PHPStan revelou problemas reais que foram corrigidos de forma incremental:

- chamada `ccan()` inexistente no request de documentos obrigatórios;
- ordenação incorreta da Agenda por uso inadequado de callbacks em `sortBy()`;
- estados processuais inexistentes nos providers da Timeline;
- propriedades inexistentes em documentos, pronúncias, programas, entregas de chaves,
  intervenções, pedidos RGPD e habitações;
- comparações enum/string que nunca podiam ser verdadeiras;
- uso inconsistente de datas convertidas por casts;
- fallbacks mortos em estados documentais obrigatórios.

As correções preservam as rotas, permissões, filtros, auditoria, limites de consulta e
payloads autorizados.

### Timeline

Os providers passaram a usar:

- valores atuais dos enums;
- referências reais do domínio;
- eager loading nas relações usadas para descrições;
- datas `Carbon`;
- metadata previsível;
- fallbacks sem inventar dados ou schema.

## Testes criados

- `tests/Unit/Dashboard/ProcessTimelineProviderTest.php`
- `tests/Unit/Dashboard/HearingTimelineProviderTest.php`
- `tests/Unit/Dashboard/OperationalTimelineProviderTest.php`

Cobertura nova:

- estados processuais válidos;
- relações de reclamações e correções;
- audiências abertas e pronúncias;
- vistorias, alertas, entregas de chaves e manutenção;
- pedidos RGPD e visitas;
- omissão de campos inexistentes;
- referências reais em metadata.

## Testes existentes alterados

- autorização de configuração/revisão documental;
- favoritos;
- filtros, serviço e repositório da Agenda;
- sorteios;
- rendas;
- operações do inquilino.

As alterações acrescentam cenários de recusa, ordem determinística, eventos sem data,
metadata enum e referências reais. Não foram removidas garantias de segurança.

O gate de integridade apresentou três avisos de comparação estrita removida. Todos foram
revistos:

- em `AgendaServiceTest`, a comparação foi precedida por
  `assertInstanceOf(TimelineEvent::class, ...)`;
- em `AgendaTimelineRepositoryTest`, as expectativas foram reordenadas para refletir a
  ordenação cronológica corrigida e foi acrescentada uma asserção global de ordenação.

Resultado: 0 violações críticas; os três avisos são falsos positivos revistos.

## Validação incremental

Foram executados, por bloco:

- PHPStan dirigido sobre os ficheiros alterados;
- testes de revisão documental, segurança e permissões;
- testes de Agenda e Dashboard;
- testes de Timeline processual, operacional, financeira e do inquilino;
- Pint dirigido;
- `git diff --check`.

Resultados dirigidos relevantes:

- documentos e audiências: 8 testes / 33 asserções;
- providers operacionais e sorteios: 9 testes / 33 asserções;
- rendas e operações do inquilino: 13 testes / 54 asserções;
- gestão e segurança documental: 27 testes / 235 asserções.

## Gates finais

| Validação | Resultado |
|---|---|
| PHPStan global configurado | PASS — 0 erros |
| PHPUnit completo | PASS — 1 048 testes / 7 395 asserções |
| PHPUnit UX | PASS — 130 testes / 645 asserções |
| Pint incremental | PASS — 74 ficheiros |
| Pint global | PASS |
| Integridade de testes | PASS — 0 violações / 3 avisos revistos |
| `composer validate --strict` | PASS |
| `npm run build` | PASS |
| `php artisan optimize:clear` | PASS |
| `git diff --check` | PASS |

## Auditoria de rotas

Não houve alteração de rotas.

- rotas totais: 1 165;
- rotas sem vendor: 1 162;
- fixed role: 926;
- backoffice fixed role: 706;
- candidate fixed role: 220;
- permission middleware: 195;
- backoffice fixed sem `active.backoffice`: 594;
- backoffice fixed sem `mfa.backoffice`: 594;
- backoffice fixed sem `log.backoffice`: 594.

Os valores coincidem com a Sprint 46B.

## PHPStan

```text
Antes: 156 erros / 40 ficheiros
Depois:  0 erros /  0 ficheiros
Delta: -156 erros
```

Não existem:

- baseline;
- `ignoreErrors`;
- `@phpstan-ignore`;
- wildcard de supressão;
- casts falsos;
- redução do nível;
- erros residuais aprovados.

## Segurança e RGPD

- Candidate permanece bloqueado no backoffice.
- A autorização documental passou a chamar o método real `can()`.
- Não foram enfraquecidas permissions, Policies, MFA ou entitlements.
- Os providers continuam condicionados pelas permissions existentes.
- Não foram adicionados documentos, paths internos ou novos dados pessoais aos payloads.
- A auditoria documental mantém ator e transição de estado.

## Riscos

- Os tipos PHPDoc dos models devem continuar alinhados com casts e schema em alterações
  futuras.
- O PHPStan global configurado analisa `app`, `config`, `database` e `routes`; testes
  continuam fora dos paths globais por configuração histórica.
- Providers de Timeline dependem de enums partilhados. Novos estados devem atualizar
  explicitamente queries e testes, evitando strings livres.

## Backlog

- Avaliar separadamente a inclusão gradual de `tests/` no PHPStan, sem misturar esse
  aumento de âmbito com a dívida de produção já liquidada.
- Manter os novos testes de providers como caracterização obrigatória ao alterar estados
  processuais.
- Prosseguir para a Sprint 46D sobre este HEAD publicado e limpo.

## Classificação final

**PASS**

A dívida PHPStan configurada foi eliminada integralmente, a suite completa e UX estão
verdes, o Pint permanece globalmente limpo, as rotas não mudaram e não foi criada qualquer
supressão ou baseline.
