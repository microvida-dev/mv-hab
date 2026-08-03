# Sprint 52B — Separador Fogos e Ordenação Integral

## 1. Base auditada

- Branch: `sprint-52a-mandatory-communications-candidate-cleanup`
- HEAD: `6bbd7d4bf263c5922b7af07a302412490c04ad1e`
- Laravel: 13.12.0
- PHP: 8.4.21
- Rotas: 1170
- Working tree no momento da recolha: limpo e sincronizado com `origin`

A implementação parte da consolidação executada nas Sprints 50E e 50E.1.
`housing_preferences` permanece a fonte oficial. A tabela
`application_preferences` continua preservada exclusivamente como legado de
leitura e reconciliação controlada.

## 2. Achado principal

O fluxo existente permitia escolher apenas um subconjunto entre um mínimo e um
máximo configurados. A configuração municipal de demonstração utilizava o
intervalo 1–3, mesmo quando o motor devolvia mais fogos compatíveis.

Isto não satisfazia a regra municipal atual:

> todos os fogos ativos, publicados, disponíveis e compatíveis devem ser
> apresentados e ordenados integralmente, sem duplicações nem omissões.

O motor de compatibilidade, os locks, a invalidação, a fonte oficial e os
snapshots já estavam preparados. A alteração necessária incide sobretudo na
cardinalidade do conjunto, na validação exata e na experiência de ordenação.

## 3. Decisões de arquitetura

### 3.1 Fonte oficial

Não é criado qualquer terceiro sistema:

- escrita: `housing_preferences`;
- revalidação: `housing_preferences`;
- lock de submissão: `housing_preferences`;
- snapshot final: `housing_preferences`;
- atribuição: `housing_preferences`;
- legado preservado: `application_preferences`.

### 3.2 Cardinalidade dinâmica

O número de posições deixa de ser limitado pelo antigo
`maximum_preferences`. Passa a ser calculado em tempo real:

```text
N = número de ContestHousingUnit ativos e compatíveis devolvidos pelo motor
```

Quando as preferências são obrigatórias, o candidato tem de enviar exatamente
`N` posições. Em concursos onde a seleção continue opcional, mantém-se a
possibilidade de não enviar qualquer ordem; ao iniciar uma ordem, esta tem de
ser completa.

Os campos antigos `minimum_preferences` e `maximum_preferences` são mantidos na
base de dados para compatibilidade e auditoria. Não são removidos nem
reinterpretados como limite jurídico para o novo modo integral.

### 3.3 Conjunto exato

O servidor verifica cumulativamente:

- cardinalidade exatamente igual a `N`;
- posições consecutivas `1..N`;
- posições sem duplicações;
- fogos sem duplicações;
- igualdade exata entre os IDs submetidos e os IDs compatíveis atuais;
- ausência de fogos externos, inativos, indisponíveis ou incompatíveis.

A validação HTTP melhora o feedback, mas a decisão final permanece no service
de domínio.

### 3.4 Concorrência e submissão

Dentro da transação são bloqueados:

1. a candidatura;
2. todas as relações `contest_housing_units` do concurso;
3. as respetivas `housing_units`;
4. as preferências oficiais da candidatura.

Após os locks, o conjunto compatível é recalculado. Se um fogo tiver entrado ou
saído do conjunto desde a gravação, a submissão é recusada e o candidato deve
confirmar novamente a ordem completa.

Só depois são atualizados:

- estado de compatibilidade;
- snapshot explicável por preferência;
- `submitted_at`;
- `locked_at`;
- snapshot final imutável da candidatura.

### 3.5 UX e acessibilidade

A candidatura passa a ter navegação por secções:

- **Resumo**;
- **Fogos**;
- **Rever e submeter**.

O separador **Fogos** cria `N` listas de seleção, uma por posição. A seleção de
um fogo já usado troca automaticamente as duas posições, evitando uma ordem
incompleta no cliente.

A interação inclui:

- labels explícitas por posição;
- botões subir/descer;
- região `aria-live`;
- navegação route-based sem dependência de JavaScript;
- fallback integral no servidor;
- informação pública minimizada dos fogos;
- mensagem explícita de que a gravação não constitui reserva.

## 4. Ficheiros principais

### Produção

- `app/Services/Allocation/HousingPreferenceService.php`
- `app/Http/Requests/UpdateHousingPreferenceRequest.php`
- `app/Http/Controllers/Candidate/HousingPreferenceController.php`
- `app/Services/Applications/ApplicationValidationService.php`
- `app/Services/CandidateExperience/CandidateNavigationService.php`
- `resources/views/candidate/applications/partials/navigation.blade.php`
- `resources/views/candidate/applications/show.blade.php`
- `resources/views/candidate/applications/review.blade.php`
- `resources/views/candidate/housing-preferences/edit.blade.php`
- `resources/views/candidate/housing-preferences/index.blade.php`

### Testes

- `tests/Feature/Sprint52BCompleteHousingOrderingTest.php`
- `tests/Feature/Candidate/CompatibleHousingPreferenceTest.php`
- `tests/Feature/Candidate/CandidateNavigationEngineTest.php`

## 5. Compatibilidade

A alteração:

- não elimina tabelas ou colunas;
- não cria migrations desnecessárias;
- não altera scoring, elegibilidade, listas, sorteios ou contratos;
- não reserva fogos na seleção;
- não altera o número de rotas;
- mantém a rota legacy de confirmação de preferências;
- preserva snapshots e candidaturas já submetidas;
- mantém o fallback legacy apenas onde o resolver já o permitia.

## 6. Testes novos

A cobertura adicionada valida:

1. criação dinâmica de cinco posições quando existem cinco fogos compatíveis;
2. aceitação de uma ordem superior ao antigo máximo configurado de três;
3. rejeição de omissões;
4. rejeição de duplicações e ordens não consecutivas;
5. rejeição transacional quando surge um novo fogo compatível antes da submissão;
6. lock de todas as preferências na submissão;
7. snapshot final com todas as posições;
8. impossibilidade de edição após submissão;
9. presença do separador **Fogos** na candidatura e na navegação lateral.

## 7. Gates obrigatórios

Antes do commit:

```text
git diff --check
Pint integral
integridade de testes desde 6bbd7d4b
PHPStan
Composer validate/audit
build Vite
testes Sprint 52B
testes das Sprints 50E e 50E.1
suite PHPUnit integral
suite UX
access:audit-routes
route count 1170
migration status
```

## 8. Rollback

A implementação não inclui migration. O rollback consiste em reverter o commit
da Sprint 52B. Os dados `housing_preferences` existentes permanecem válidos e
não são transformados destrutivamente.

## 9. Classificação antes da execução local

```text
IMPLEMENTATION_READY_LOCAL_VALIDATION_REQUIRED
```

A classificação só deve evoluir para `REPOSITORY_PASS` após todos os gates
serem executados no repositório real.
