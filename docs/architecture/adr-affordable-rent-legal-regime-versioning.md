# ADR — Versionamento do regime jurídico de renda acessível

## Estado

Aceite para a Sprint 50A.

## Contexto

A plataforma executa atualmente regras de elegibilidade, tipologia e renda
através de rule sets independentes. O programa demo de Alcanena representa o
regime PAA, mas vários parâmetros ainda estão repetidos em código e seeders.

A transição temporal aprovada é:

```text
até 31/08/2026, inclusive: PAA legacy
desde 01/09/2026, inclusive: RSAA
```

A fonte oficial necessária aos limites de renda RSAA não está configurada no
repositório. Reutilizar limites PAA seria juridicamente inseguro. Ao mesmo
tempo, procedimentos e contratos PAA devem continuar a ser interpretados pelo
regime originalmente aplicado.

## Decisão

### Modelo

Criar `AffordableRentRegulatoryProfile` como catálogo regulamentar versionado.
Um perfil pode ser:

- nacional, sem `municipality_id`;
- overlay municipal, com `municipality_id` e `parent_profile_id` para o perfil
  nacional que restringe.

O perfil guarda em colunas tipadas os parâmetros estáveis necessários à
validação: regime, versão, vigência, estado, completude, taxa máxima de esforço,
limites de rendimento, rendimento mínimo, duração contratual e disponibilidade
da tabela de renda. Metadados auxiliares podem usar JSON, mas os rule sets
continuam nas suas entidades próprias.

`EligibilityRuleSet`, `RentRuleSet`, `TypologyAdequacyRule` e
`AllocationRuleSet` podem referenciar o perfil aplicável.

### Regime tipado

Usar `AffordableRentLegalRegime` com:

- `paa_legacy_2019`;
- `rsaa_2026`.

O enum contém labels e a fronteira temporal estável, mas não contém valores
monetários nem executa cálculos.

### Datas por contexto

Não existe uma única data global:

| Contexto | Data de referência |
| --- | --- |
| Publicação de programa | `published_at`; em pré-publicação, data explícita fornecida ao serviço ou `starts_at` |
| Publicação de concurso | `published_at`; em pré-publicação, data explícita fornecida ao serviço ou `opens_at` |
| Submissão da candidatura | `submitted_at`; antes da submissão, regime fixado do concurso |
| Elegibilidade | `executed_at`; antes da execução, snapshot/regime da candidatura |
| Cálculo de renda | `calculated_at`; regime fixado da atribuição/candidatura |
| Contrato | snapshot do cálculo e data contratual autoritativa |

O resolver recebe sempre uma data explícita quando infere um perfil. A data é
normalizada para `Europe/Lisbon` antes de aplicar a fronteira.

### Precedência

1. Um `RegulatorySnapshot` bloqueado prevalece.
2. Um perfil explicitamente fixado no registo prevalece.
3. Para um rascunho novo, resolver pelo Município e pela data explícita.
4. Um histórico ambíguo fica `requires_manual_review`; não se usa
   `created_at`, `updated_at`, estado ou ausência de valor para adivinhar.

### Snapshots

Criar `RegulatorySnapshot` como prova imutável, sem PII, com:

- contexto e entidade de origem;
- Município, regime, perfil e versão;
- data de referência;
- base legal e fonte;
- IDs/versões dos rule sets;
- limites e parâmetros efetivamente aplicados;
- overlay municipal;
- checksum SHA-256 determinístico;
- ator, criação e `locked_at`.

Programa, concurso, candidatura, check de elegibilidade, cálculo de renda e
contrato podem referenciar um snapshot. Um snapshot bloqueado não pode ser
atualizado nem eliminado pela aplicação. As foreign keys usam `restrict` ou
`nullOnDelete` apenas onde a preservação histórica não fica comprometida; não
há cascade de um domínio operacional para snapshots.

### Regras nacionais e municipais

O perfil nacional define limites obrigatórios. O overlay municipal pode:

- reduzir um máximo;
- aumentar um mínimo;
- exigir prazo contratual superior ao mínimo nacional;
- adicionar condições e documentação.

Não pode:

- aumentar um máximo nacional;
- reduzir um mínimo nacional;
- desativar requisito obrigatório;
- declarar completa uma tabela nacional incompleta;
- alterar o regime do perfil pai.

O resultado combinado é validado antes de publicação e entra no snapshot.

### Limites de renda

Usar `RentLimitProviderInterface` com providers separados:

- PAA consome rule set/tabela PAA versionada;
- RSAA consome exclusivamente configuração RSAA com fonte e versão.

O resultado é tipado como `configured`, `incomplete`, `not_applicable` ou
`requires_manual_review`. Na ausência da tabela oficial RSAA, o estado é
`incomplete`; não existe fallback para PAA.

### Publicação

`RegulatoryPublicationReadinessService` integra os serviços reais de publicação
de `Program` e `Contest`.

Um registo novo só pode ser publicado quando:

- tem perfil ativo e vigente;
- o perfil pertence ao mesmo Município;
- a base legal está preenchida;
- a configuração nacional e municipal é coerente;
- os rule sets exigidos estão associados/configurados;
- o provider de renda confirma configuração aplicável;
- é possível criar e bloquear o snapshot.

RSAA sem tabela oficial é recusado através de `ValidationException`, com
mensagem em pt-PT e sem erro 500.

Registos já publicados antes da migration não são despublicados nem
reclassificados automaticamente.

### Contratos legacy

Novos contratos copiam o snapshot do cálculo de renda. Contratos anteriores
podem ser classificados apenas quando existe cadeia inequívoca:

```text
contrato
→ cálculo de renda
→ candidatura/atribuição
→ concurso/programa
→ snapshot ou perfil fixado
```

Sem essa cadeia, o contrato permanece para revisão manual. A Sprint 50A não
executa backfill destrutivo em ambientes externos. Um eventual comando futuro
será dry-run por defeito, idempotente, sem PII e baseado num manifesto externo.

### Multi-Município

Um overlay municipal só é resolvido para o seu `municipality_id`. Um utilizador
municipal não ganha acesso global quando tem `municipality_id = null`. A
resolução regulamentar não substitui Policies nem o scope municipal
permission-first.

## Alternativas rejeitadas

### Um JSON de configuração no programa

Rejeitado porque mistura catálogo jurídico, regra executável e snapshot,
impede integridade referencial e favorece alterações retroativas.

### Inferir sempre pela data corrente

Rejeitado porque reclassifica procedimentos e contratos históricos.

### Converter tudo para RSAA em 01/09/2026

Rejeitado por destruir a base jurídica de procedimentos PAA em curso.

### Reutilizar limites PAA quando RSAA está incompleto

Rejeitado. A publicação deve falhar de forma fechada.

### Alterar as tabelas históricas diretamente

Rejeitado. A solução é incremental e preserva dados existentes.

## Migrations e rollback

A migration:

- cria tabelas novas;
- adiciona apenas colunas nullable e índices/FKs compatíveis;
- não classifica nem altera dados históricos;
- reverte primeiro foreign keys/colunas e depois tabelas;
- não elimina tabelas ou dados de negócio existentes.

O rollback operacional de uma release deve ocorrer antes de criar dependências
em produção. Depois de snapshots legais terem sido emitidos, a reversão de
schema exige retenção/exportação desses snapshots e decisão explícita; não se
deve apagar prova administrativa.

## Consequências

- PAA e RSAA podem coexistir sem reinterpretar histórico;
- Alcanena recebe um overlay versionado;
- a falta de tabela RSAA é visível e bloqueante;
- cálculo, elegibilidade, candidatura e contrato passam a conservar o regime;
- a Sprint 50E pode consumir parâmetros e snapshots sem hardcodes;
- haverá mais configuração obrigatória antes de publicar, por desenho.

## Critérios de revisão

Rever esta ADR quando:

- a fonte oficial de rendas RSAA estiver disponível;
- existir integração IHRU;
- forem adicionados novos regimes ou transições;
- for necessário classificar contratos legacy com manifesto;
- o catálogo regulamentar ganhar UI própria de gestão.
