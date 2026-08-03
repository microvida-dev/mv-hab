# ADR — Acesso a Reporting, Comunicações, Notificações e Configuração

## Estado

**Aceite para implementação**

## Contexto

A Sprint 47H encerra a migração permission-first do backoffice. O
universo imutável contém 123 rotas:

- reporting: 38;
- communications: 36;
- notifications: 41;
- configuration: 8.

No commit de origem, todas conservavam middleware de role fixa. A
autorização era ainda parcialmente baseada em grupos históricos, com
queries sem scope municipal uniforme e operações de ciclo de vida
agregadas em permissions genéricas.

## Decisão

### 1. Modelo de autorização

Cada rota aplica cumulativamente:

```text
permission exata
&& Policy/ability semântica
&& scope municipal
&& estado/transição válida
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
```

O middleware `role:*` deixa de participar na autorização efetiva das
rotas do backoffice.

### 2. Origem municipal

O scope é derivado de relações autoritativas:

```text
relatório/execução -> definição + actor/contexto municipal
comunicação -> recurso de origem/destinatário municipal
ticket -> candidatura/concurso/programa ou utilizador municipal
notificação -> destinatário/candidatura municipal
tarefa -> municipality_id e relações operacionais coerentes
configuração -> programa/concurso/municipality_id
ata -> concurso/programa/candidatura
```

O destinatário isolado não redefine o Município de uma comunicação.
Relações nulas, históricas ou contraditórias falham fechado.

### 3. Catálogos globais e híbridos

Templates e variáveis sem origem municipal explícita são tratados como
catálogo global. Um utilizador municipal pode lê-los apenas quando a
Policy o permite, mas não os pode alterar. Mutação global requer
assignment explícito de operador da plataforma.

Configurações municipais exigem `municipality_id` ou uma relação
autoritativa a programa/concurso. Um valor nulo não concede acesso
global implícito.

### 4. Reporting

O scope é aplicado antes de filtros, agregações, paginação, execução ou
exportação. Runs e exports preservam actor, definição e contexto
municipal. Ficheiros são privados e URLs assinadas não substituem a
Policy.

As rotas de export já permission-first permanecem inalteradas. A Sprint
47H adiciona a permission semântica `reports.run` às execuções.

### 5. Comunicações e notificações

Criar, enviar/reprocessar, cancelar, arquivar, aprovar e ativar são
operações distintas. Services críticos voltam a validar permission,
scope e estado antes de efeitos laterais.

Preferências pessoais continuam limitadas ao owner. A listagem
administrativa exige permission específica e scope municipal.

### 6. Configuração

Configuração municipal, configuração global e catálogo híbrido não são
misturados:

- municipal: origem municipal obrigatória;
- global: operador global explicitamente atribuído;
- híbrido: leitura do system quando prevista, mutação apenas no âmbito
  autorizado.

### 7. Entitlements

Não são introduzidos novos `FeatureKey`. As features existentes de
candidaturas só podem ser usadas onde a operação é efetivamente de
candidaturas. Não são aplicadas a reporting genérico, comunicações,
notificações ou configuração.

### 8. Auditor

O auditor recebe apenas operações read-only/audit. Não pode criar,
editar, eliminar, enviar, repetir, cancelar, resolver, ativar,
desativar ou publicar.

## Consequências

- os perfis municipais passam a ser extensíveis por permissions;
- desaparece a dependência de listas de roles nas rotas de backoffice;
- recursos estrangeiros ou sem origem municipal deixam de aparecer;
- novas operações exigem permission, ability e decisão de scope
  explícitas;
- a expansão comercial de entitlements fica reservada ao Programa 48.

## Alternativas rejeitadas

1. **Manter middleware de role fixa**: impede perfis municipais
   personalizados e duplica a matriz de RBAC.
2. **Usar `reports.manage`/`notifications.update` em todas as mutações**:
   não distingue operações críticas.
3. **Tratar `municipality_id = null` como global**: cria bypass de
   isolamento.
4. **Reutilizar `applications.export` para reporting genérico**:
   semanticamente incorreto.
