# Decisões de permissions — Sprint 47B

## Âmbito

Este registo resolve as 34 lacunas ou propostas semanticamente insuficientes
do manifesto imutável da Sprint 47B. O lote abrange candidaturas, intake,
agregados, simulador interno, documentos privados, IA documental, pedidos de
aperfeiçoamento e processos administrativos.

As restantes 68 rotas do manifesto já tinham uma permission candidata
adequada e foram migradas sem alargar o respetivo poder.

## Regras comuns aprovadas

- Todas as rotas exigem `auth`, `active.backoffice`, `mfa.backoffice` e
  `log.backoffice`.
- O manifesto propunha entitlement em 85 endpoints. A resolução do domínio
  retirou `applications.review` das cinco rotas financeiras de atualização
  documental anual; ficam 80 endpoints com entitlement e 22 sem entitlement
  candidatural artificial.
- A permission é validada antes da Policy; a Policy confirma o Município e o
  scope do registo.
- O `administrator` recebe as ações pelo catálogo estrutural existente.
- O `municipal_technician` recebe as ações operacionais de análise e
  tratamento documental.
- `jury` e `legal_manager` recebem apenas consulta e decisão estritamente
  previstas; `financial_manager` recebe apenas o subconjunto documental
  financeiro.
- Os modelos municipais `operador-recolha` e `analista-candidaturas` foram
  atualizados segundo menor privilégio.
- `auditor` mantém-se read-only; `candidate` não recebe nenhuma destas rotas
  backoffice.
- Não foram atribuídas permissions diretamente a utilizadores nem
  introduzidos wildcards.
- Todas as ações novas são sensíveis a MFA no catálogo.

## Evidência de testes

| Código | Teste |
| --- | --- |
| `RTE` | `ApplicationsDocumentsPermissionRoutesTest` |
| `BND` | `ApplicationsDocumentsMunicipalBoundaryTest` |
| `ADM` | `AdministrativeProcessBackofficePolicyTest` e `AdministrativeProcessBackofficeRouteAccessTest` |
| `DAI` | testes de dashboard, extração, validação e RGPD de IA documental |
| `LEG` | `Sprint9AdministrativeWorkflowTest`, `Sprint14FinanceTest` e `Sprint24BackofficeOperationalTest` |

## Matriz de decisão

| # | Rota | Permission final | Razão da decisão | Templates autorizados | Ability | Testes |
| ---: | --- | --- | --- | --- | --- | --- |
| 1 | `backoffice.administrative-tasks.cancel` | `administrative_processes.cancel` | Cancelar é uma transição, não uma atualização genérica. | administrator, municipal_technician, analista-candidaturas | `cancelBackoffice` | RTE, ADM |
| 2 | `backoffice.administrative-tasks.complete` | `administrative_processes.complete` | A conclusão altera o estado final da tarefa. | administrator, municipal_technician, analista-candidaturas | `completeBackoffice` | RTE, ADM |
| 3 | `backoffice.administrative-tasks.store` | `administrative_processes.assign` | Criar uma tarefa atribuída exerce poder de distribuição de trabalho. | administrator, municipal_technician, analista-candidaturas | `assignBackoffice` | RTE, ADM |
| 4 | `backoffice.application-inconsistencies.resolve` | `administrative_processes.decide` | Resolver uma inconsistência é uma decisão processual. | administrator, municipal_technician, jury, legal_manager, analista-candidaturas | `decideBackoffice` | RTE, BND |
| 5 | `backoffice.correction-requests.cancel` | `administrative_processes.cancel` | Cancelamento separado de edição. | administrator, municipal_technician, analista-candidaturas | `cancelBackoffice` | RTE, LEG |
| 6 | `backoffice.correction-requests.close` | `administrative_processes.complete` | Fecho é transição conclusiva. | administrator, municipal_technician, analista-candidaturas | `completeBackoffice` | RTE, LEG |
| 7 | `backoffice.correction-requests.issue` | `administrative_processes.issue` | A emissão produz efeito externo no processo. | administrator, municipal_technician, analista-candidaturas | `issueBackoffice` | RTE, LEG |
| 8 | `backoffice.correction-requests.mark-overdue` | `administrative_processes.mark_overdue` | Marcar vencimento é transição operacional própria. | administrator, municipal_technician, analista-candidaturas | `markOverdueBackoffice` | RTE, LEG |
| 9 | `backoffice.correction-responses.accept` | `administrative_processes.decide` | Aceitação é decisão, não leitura. | administrator, municipal_technician, jury, legal_manager, analista-candidaturas | `decideBackoffice` | RTE, LEG |
| 10 | `backoffice.correction-responses.reject` | `administrative_processes.decide` | Rejeição usa o mesmo poder decisório, mantendo ability e auditoria próprias. | administrator, municipal_technician, jury, legal_manager, analista-candidaturas | `decideBackoffice` | RTE, LEG |
| 11 | `backoffice.correction-responses.request-more-information` | `administrative_processes.decide` | Pedir nova informação decide a continuação da instrução. | administrator, municipal_technician, jury, legal_manager, analista-candidaturas | `decideBackoffice` | RTE, LEG |
| 12 | `backoffice.simulator.configuration.edit` | `simulator.view` | O GET apenas consulta o formulário; a mutação continua em `simulator.update`. | perfis backoffice com consulta do simulador | `viewBackoffice` | RTE, BND |
| 13 | `backoffice.contracts.documents.download` | `documents.download` | Download privado não pode ser inferido de leitura. | administrator, municipal_technician, jury, legal_manager, operador-recolha, analista-candidaturas | `downloadBackoffice` | RTE, BND |
| 14 | `backoffice.contracts.documents.generate` | `documents.generate` | Geração documental é ação própria e auditável. | administrator, municipal_technician | `generateBackoffice` | RTE, LEG |
| 15 | `backoffice.document-ai.assistant.recalculate` | `documents.analyze` | Recalcular inicia análise automática. | administrator, municipal_technician, analista-candidaturas | `analyzeBackoffice` | RTE, DAI |
| 16 | `backoffice.document-ai.assistant.suggestions.accept` | `documents.review_ai` | Aceitar sugestão é revisão humana de IA. | administrator, municipal_technician, analista-candidaturas | `reviewBackoffice` | RTE, DAI |
| 17 | `backoffice.document-ai.assistant.suggestions.dismiss` | `documents.review_ai` | Descartar sugestão exige o mesmo poder de revisão humana. | administrator, municipal_technician, analista-candidaturas | `reviewBackoffice` | RTE, DAI |
| 18 | `backoffice.document-ai.assistant.suggestions.update` | `documents.review_ai` | Corrigir sugestão não deve herdar atualização documental geral. | administrator, municipal_technician, analista-candidaturas | `reviewBackoffice` | RTE, DAI |
| 19 | `backoffice.document-ai.extractions.index` | `documents.audit` | Extrações podem conter dados derivados sensíveis. | administrator, legal_manager, auditor, analista-candidaturas | `auditAnyBackoffice` | RTE, DAI |
| 20 | `backoffice.document-ai.extractions.show` | `documents.audit` | Consulta detalhada de extração é atividade de auditoria. | administrator, legal_manager, auditor, analista-candidaturas | `auditBackoffice` | RTE, DAI |
| 21 | `backoffice.document-ai.fields.review` | `documents.review_ai` | Revisão de campo extraído é decisão humana sobre IA. | administrator, municipal_technician, analista-candidaturas | `reviewBackoffice` | RTE, DAI |
| 22 | `backoffice.document-ai.validations.manual-review` | `documents.review_ai` | Revisão manual é distinta de atualizar o documento. | administrator, municipal_technician, analista-candidaturas | `reviewBackoffice` | RTE, DAI |
| 23 | `backoffice.document-ai.validations.rerun` | `documents.analyze` | Reexecução inicia processamento de análise. | administrator, municipal_technician, analista-candidaturas | `analyzeDocumentsBackoffice` | RTE, DAI |
| 24 | `backoffice.document-template-versions.activate` | `documents.activate` | Ativação de versão é transição publicável própria. | administrator, municipal_technician | `activateBackoffice` | RTE, BND |
| 25 | `backoffice.document-templates.archive` | `documents.archive` | Arquivo altera disponibilidade sem equivaler a edição. | administrator, municipal_technician | `archiveBackoffice` | RTE, BND |
| 26 | `backoffice.document-templates.preview` | `documents.preview` | Pré-visualização pode renderizar conteúdo privado. | administrator, municipal_technician, jury, legal_manager | `previewBackoffice` | RTE, BND |
| 27 | `backoffice.finance.annual-document-updates.accept` | `finance.approve` | A atualização anual pertence ao processo financeiro e não deve conceder acesso documental geral. | administrator, financial_manager | `approveBackoffice` | RTE, LEG |
| 28 | `backoffice.generated-documents.download` | `documents.download` | Artefacto gerado permanece privado. | administrator, municipal_technician, jury, legal_manager, operador-recolha, analista-candidaturas | `downloadBackoffice` | RTE, BND |
| 29 | `backoffice.generated-documents.issue` | `documents.issue` | Emitir produz versão oficial e auditável. | administrator, municipal_technician | `issueBackoffice` | RTE, LEG |
| 30 | `backoffice.official-documents.cancel` | `documents.cancel` | Cancelamento de documento oficial é transição distinta. | administrator, municipal_technician | `cancelBackoffice` | RTE, LEG |
| 31 | `backoffice.official-documents.download` | `documents.download` | Download de documento oficial privado exige permission própria. | administrator, municipal_technician, jury, legal_manager, operador-recolha, analista-candidaturas | `downloadBackoffice` | RTE, BND |
| 32 | `backoffice.official-documents.generate` | `documents.generate` | Geração não pode ser autorizada por create genérico. | administrator, municipal_technician | `createBackoffice` | RTE, LEG |
| 33 | `backoffice.official-documents.issue` | `documents.issue` | Emissão é ação administrativa própria. | administrator, municipal_technician | `issueBackoffice` | RTE, LEG |
| 34 | `backoffice.procedure-templates.documents.generate` | `documents.generate` | Renderização de documento processual exige poder de geração. | administrator, municipal_technician | `generateBackoffice` | RTE, LEG |

## Correção de ability durante a implementação

O manifesto de entrada manteve, de forma imutável, a ability inicialmente
planeada `updateBackoffice` para
`backoffice.document-template-versions.store`. A revisão em profundidade
detetou incompatibilidade com a permission final `documents.create`.

Foi criada a ability específica `createVersionBackoffice`, usada pelo
controller e pelo `StoreDocumentTemplateVersionRequest`. A ability exige
`documents.create`, bloqueia `candidate` e `auditor` e conserva o scope
municipal/plataforma do template. O teste `BND` prova criação local e recusa
cross-municipality sem efeitos.

## Correção de domínio financeiro

A suite completa revelou que usar `documents.*` nas atualizações documentais
anuais obrigava a conceder ao `financial_manager` acesso ao índice documental
geral. Isso violava o limite RGPD já coberto por
`SensitiveBackofficeAccessTest`.

Foram aprovadas estas correções face ao manifesto de entrada:

| Rota | Permission implementada | Feature implementada |
| --- | --- | --- |
| `backoffice.finance.annual-document-updates.index` | `finance.view` | nenhuma |
| `backoffice.finance.annual-document-updates.show` | `finance.view` | nenhuma |
| `backoffice.finance.annual-document-updates.store` | `finance.create` | nenhuma |
| `backoffice.finance.annual-document-updates.accept` | `finance.approve` | nenhuma |
| `backoffice.finance.annual-document-updates.reject` | `finance.reject` | nenhuma |

As Policies mantêm o scope municipal sobre o contrato/conta financeira e
bloqueiam `candidate` e `auditor` nas mutações. O `financial_manager` deixou de
receber `documents.view/create/approve/reject`; conserva apenas as permissions
financeiras que já delimitam o seu domínio. Esta correção concretiza a regra
do programa segundo a qual rotas mixed-context resolvem primeiro o domínio.

## Sensibilidade MFA

As ações novas deixaram de ser classificadas sensíveis apenas pelo sufixo
global (`complete`, `cancel`, entre outros). A sensibilidade passou a usar as
permissions completas de `applications`, `documents` e
`administrative_processes`. Assim, `work_tasks.complete` não torna
automaticamente o perfil de apoio sensível, enquanto todas as ações da 47B
continuam protegidas por `mfa.backoffice`.

## Decisão

As 34 lacunas iniciais e as cinco correções mixed-context foram resolvidas
sem role fixa, permission direta ou wildcard novo. As permissions distinguem
leitura, análise, revisão humana, geração, emissão, decisão e transições
processuais. O catálogo, os perfis estruturais, as Policies, os Form Requests
e os testes permanecem alinhados.
