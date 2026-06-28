# Teste end-to-end da plataforma MV HAB

Este guia define um percurso manual limpo para validar a MV HAB sem usar o utilizador administrador para todas as operações. O objetivo é reduzir entropia no backoffice e testar cada etapa com a função municipal correta.

## 1. Preparação

Executar primeiro os comandos base:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --except-vendor
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
npm run build
```

Opcionalmente preparar utilizadores funcionais fictícios para teste local:

```bash
php artisan db:seed --class=MunicipalEndToEndWorkflowSeeder
```

Se for necessário autenticar diretamente com esses utilizadores no ambiente local, definir previamente uma password apenas no ambiente local:

```bash
MVHAB_E2E_USER_PASSWORD="SUBSTITUIR_LOCALMENTE" php artisan db:seed --class=MunicipalEndToEndWorkflowSeeder
```

Nunca versionar a password em `.env`, documentação, screenshots ou artefactos.

## 2. Utilizadores de teste

| Fase | Utilizador fictício | Role | Equipa | Uso recomendado |
| --- | --- | --- | --- | --- |
| Administração mínima | `e2e.admin@example.test` | `administrator` | Auditoria | Apenas configuração, desbloqueio e verificação global. |
| Candidato | `e2e.candidato@example.test` | `candidate` | Sem equipa | Registo, simulador, candidatura, documentos, reclamações e área do inquilino. |
| Atendimento | `e2e.atendimento@example.test` | `support_agent` | Atendimento | Tickets, visitas, FAQ e apoio ao candidato. |
| Técnico municipal | `e2e.tecnico@example.test` | `municipal_technician` | Gabinete Técnico | Receção, documentos, elegibilidade, processos e execução de classificação. |
| Júri | `e2e.juri@example.test` | `jury` | Gabinete Técnico / Jurídico | Revisão de pontuação, reclamações, listas e decisões colegiais. |
| Jurídico | `e2e.juridico@example.test` | `legal_manager` | Gabinete Jurídico | Audiência, reclamações, contratos e validação jurídica. |
| Habitação | `e2e.habitacao@example.test` | `housing_manager` | Gabinete de Habitação | Fogos, visitas, atribuição, contratos operacionais e transição para inquilino. |
| Financeiro | `e2e.financeiro@example.test` | `financial_manager` | Gabinete Financeiro | Rendas e registos financeiros manuais. |
| Manutenção | `e2e.manutencao@example.test` | `maintenance_manager` | Manutenção | Pedidos e intervenções. |
| Vistorias | `e2e.vistorias@example.test` | `inspection_manager` | Vistorias | Vistorias preventivas e autos. |
| Auditor/RGPD | `e2e.auditor@example.test` | `auditor` | Auditoria | Auditoria, acessos sensíveis e RGPD em leitura. |

## 3. Portal Público

Utilizador: visitante.

Percurso:

1. Abrir `/`.
2. Abrir `/programas`.
3. Abrir `/oferta-habitacional/concursos`.
4. Abrir um concurso publicado em `/oferta-habitacional/concursos/{slug}`.
5. Abrir `/oferta-habitacional/imoveis`.
6. Abrir `/oferta-habitacional/mapa`.
7. Abrir `/perguntas-frequentes`.
8. Abrir `/simulador`.

Validar:

- só aparecem concursos publicados;
- filtros e mapa carregam;
- ficha do imóvel abre;
- documentos públicos descarregam;
- simulador público calcula sem autenticação indevida.

Rotas principais:

| Método | Caminho | Objetivo |
| --- | --- | --- |
| `GET` | `/` | Portal público. |
| `GET` | `/oferta-habitacional/concursos` | Concursos públicos. |
| `GET` | `/oferta-habitacional/imoveis` | Oferta habitacional. |
| `GET` | `/oferta-habitacional/mapa` | Mapa/filtros. |
| `GET` | `/simulador` | Simulador. |
| `POST` | `/simulador` | Submeter simulação. |

## 4. Registo, adesão e dados base

Utilizador: candidato.

Percurso:

1. Entrar em `/area-candidato`.
2. Abrir `Registo` em `/area-candidato/registo`.
3. Criar ou editar registo em `/area-candidato/registo/criar` ou `/area-candidato/registo/editar`.
4. Aceitar consentimentos RGPD.
5. Preencher agregado em `/area-candidato/agregado`.
6. Preencher rendimentos em `/area-candidato/rendimentos`.
7. Preencher habitação atual em `/area-candidato/habitacao-atual`.
8. Finalizar registo de adesão.

Validar:

- sem registo finalizado não deve avançar para candidatura completa;
- dados do agregado e rendimentos ficam guardados;
- fonte de rendimento mostra opções;
- o candidato vê o estado do processo.

## 5. Simulador e criação de candidatura

Utilizador: candidato.

Percurso:

1. Abrir `/area-candidato/elegibilidade`.
2. Executar pré-verificação.
3. Abrir concurso em `/area-candidato/candidaturas/criar/{contest}`.
4. Confirmar dados importados do registo/agregado.
5. Selecionar preferências/fogo quando aplicável.
6. Guardar rascunho.
7. Abrir `/area-candidato/candidaturas/{application}/rever`.
8. Confirmar que não submete incompleta.

Validar:

- rascunho edita sem perder dados;
- candidatura fora de prazo é bloqueada;
- candidato só vê candidaturas próprias.

## 6. Upload documental

Utilizador: candidato.

Percurso:

1. Abrir `/area-candidato/candidaturas/{application}/rever`.
2. Na checklist, clicar `Submeter` para o documento obrigatório.
3. Usar `/area-candidato/documentos/submeter` quando a submissão for geral.
4. Substituir documento em `/area-candidato/documentos/{documentSubmission}/substituir`.
5. Submeter candidatura em `/area-candidato/candidaturas/{application}/submeter`.

Documentos mínimos a testar:

- identificação civil;
- NIF/Segurança Social, quando exigido;
- domicílio fiscal;
- IRS/rendimentos;
- certidão predial negativa;
- certidões AT/Segurança Social;
- atestado multiúso, se aplicável;
- declaração médica de gravidez, se aplicável.

Validar:

- documentos ficam privados;
- checklist atualiza;
- obrigatórios bloqueiam submissão quando em falta;
- após submissão a candidatura fica bloqueada para edição crítica.

## 7. Receção backoffice

Utilizador: técnico municipal.

Percurso recomendado:

1. Entrar em `/dashboard`.
2. Abrir espaço `Atendimento` ou `Concursos`.
3. Abrir `/backoffice/administrative-processes`.
4. Abrir o processo da candidatura.
5. Confirmar número de processo e cronologia.
6. Atribuir técnico, se necessário.
7. Clicar `Iniciar triagem`.

Rotas críticas:

| Método | Caminho | Ação |
| --- | --- | --- |
| `GET` | `/backoffice/administrative-processes` | Lista de processos. |
| `GET` | `/backoffice/administrative-processes/{administrativeProcess}` | Detalhe do processo. |
| `POST` | `/backoffice/administrative-processes/{administrativeProcess}/assign` | Atribuir técnico. |
| `POST` | `/backoffice/administrative-processes/{administrativeProcess}/start-preliminary-review` | Iniciar triagem. |

## 8. Validação documental e IA documental

Utilizador: técnico municipal.

Percurso:

1. Abrir `/admin/document-reviews`.
2. Abrir documento submetido.
3. Clicar `Executar IA documental`, quando aplicável.
4. Confirmar OCR/classificação no assistente IA.
5. Marcar documento como `Em análise`, `Válido` ou `Rejeitado`.
6. Se faltar informação, voltar ao processo e criar pedido de aperfeiçoamento.

Rotas críticas:

| Método | Caminho | Ação |
| --- | --- | --- |
| `GET` | `/admin/document-reviews` | Revisão documental. |
| `POST` | `/admin/document-reviews/{documentSubmission}/document-ai` | Executar IA documental. |
| `POST` | `/admin/document-reviews/{documentSubmission}/validate` | Validar documento. |
| `POST` | `/admin/document-reviews/{documentSubmission}/reject` | Rejeitar documento. |
| `GET` | `/backoffice/administrative-processes/{administrativeProcess}/correction-requests/create` | Criar aperfeiçoamento. |

Validar:

- estado documental muda;
- documento antigo mantém histórico;
- pedido de aperfeiçoamento fica associado ao processo;
- candidato recebe tarefa/notificação quando configurado.

## 9. Resposta do candidato ao aperfeiçoamento

Utilizador: candidato.

Percurso:

1. Abrir `/area-candidato/pedidos-aperfeicoamento`.
2. Abrir pedido.
3. Responder em `/area-candidato/pedidos-aperfeicoamento/{correctionRequest}/responder`.
4. Submeter novos documentos.
5. Submeter resposta.

Validar:

- resposta fica no processo;
- técnico volta a ver a candidatura em análise;
- documentos anteriores não desaparecem.

## 10. Elegibilidade técnica

Utilizador: técnico municipal.

Este passo é obrigatório antes da pontuação.

Percurso:

1. Abrir `/backoffice/administrative-processes/{administrativeProcess}`.
2. Clicar `Análise documental`, se ainda não foi iniciada.
3. Clicar `Análise de requisitos`.
4. Abrir a candidatura em `Ver candidatura`.
5. Executar validação de elegibilidade pela rota de backoffice.
6. Confirmar resultado como `Elegível`.

Rotas críticas:

| Método | Caminho | Ação |
| --- | --- | --- |
| `POST` | `/backoffice/administrative-processes/{administrativeProcess}/start-document-review` | Iniciar análise documental. |
| `POST` | `/backoffice/administrative-processes/{administrativeProcess}/start-eligibility-review` | Iniciar análise de requisitos. |
| `POST` | `/backoffice/eligibility/applications/{application}/run` | Executar verificação de elegibilidade. |
| `GET` | `/backoffice/eligibility/checks` | Consultar verificações. |

Validar:

- requisitos ficam calculados;
- impedimentos ficam visíveis;
- última verificação da candidatura deve ser `Elegível` para entrar na pontuação.

## 11. Admissão para classificação

Utilizador: técnico municipal ou júri, conforme permissões.

Este é o passo que desbloqueia a pontuação/ranking.

Percurso:

1. Abrir `/backoffice/administrative-processes/{administrativeProcess}`.
2. Em `Ações processuais`, clicar `Propor admissão`.
3. Preencher resumo e fundamentação.
4. Clicar `Registar decisão`.
5. Abrir a decisão criada.
6. Marcar `Confirmo a aprovação desta decisão administrativa`.
7. Clicar `Aprovar decisão`.

Rotas críticas:

| Método | Caminho | Ação |
| --- | --- | --- |
| `GET` | `/backoffice/administrative-processes/{administrativeProcess}/decisions/create-admission` | Formulário de admissão. |
| `POST` | `/backoffice/administrative-processes/{administrativeProcess}/decisions/admission` | Registar decisão. |
| `GET` | `/backoffice/administrative-decisions/{administrativeDecision}` | Consultar decisão. |
| `POST` | `/backoffice/administrative-decisions/{administrativeDecision}/approve` | Aprovar e aplicar decisão. |

Validação técnica:

```php
App\Models\AdministrativeProcess::query()
    ->latest()
    ->first(['id', 'status', 'admitted_for_scoring_at']);
```

Esperado:

- `status = admitted_for_scoring`;
- `admitted_for_scoring_at` preenchido.

## 12. Pontuação e ranking

Utilizador: técnico municipal ou júri.

Percurso:

1. Abrir `/backoffice/scoring/rule-sets`.
2. Confirmar matriz ativa do concurso.
3. Abrir `/backoffice/scoring/runs/create`.
4. Selecionar programa/concurso/matriz.
5. Clicar `Executar classificação`.
6. Abrir `/backoffice/scoring/application-scores`.
7. Abrir `/backoffice/scoring/ranking-snapshots`.
8. Confirmar snapshot com entradas.

Rotas críticas:

| Método | Caminho | Ação |
| --- | --- | --- |
| `GET` | `/backoffice/scoring/rule-sets` | Matrizes. |
| `GET` | `/backoffice/scoring/runs/create` | Nova execução. |
| `POST` | `/backoffice/scoring/runs` | Executar classificação. |
| `GET` | `/backoffice/scoring/application-scores` | Pontuações calculadas. |
| `GET` | `/backoffice/scoring/ranking-snapshots` | Snapshots de ranking. |

Condições obrigatórias para uma candidatura entrar no ranking:

1. candidatura no concurso correto;
2. candidatura com estado compatível: `submitted`, `under_review`, `correction_submitted` ou `eligible`;
3. processo administrativo em `admitted_for_scoring`;
4. última elegibilidade com resultado `eligible`.

Diagnóstico rápido:

```php
App\Models\ScoringRun::query()->latest()->first([
    'id',
    'status',
    'total_applications',
    'scored_applications',
    'excluded_applications',
]);
```

Se `total_applications = 0`, o problema está antes da pontuação: processo ainda não admitido para classificação ou elegibilidade não elegível.

## 13. Júri, lista provisória e reclamações

Utilizador: júri e jurídico.

Percurso:

1. Júri abre `/backoffice/scoring/application-scores`.
2. Revê pontuações e casos com revisão manual.
3. Abre `/backoffice/scoring/ranking-snapshots`.
4. Gera lista provisória a partir do snapshot disponível.
5. Publica lista provisória.
6. Candidato abre `/area-candidato/audiencias` ou `/area-candidato/reclamacoes`.
7. Candidato submete pronúncia/reclamação.
8. Jurídico/júri analisam e decidem.

Validar:

- lista não expõe dados pessoais indevidos;
- candidato vê estado atualizado;
- reclamação fica associada ao processo;
- alteração, se existir, reflete numa nova execução/ranking.

## 14. Lista definitiva e atribuição

Utilizadores: júri e gestor de habitação.

Percurso:

1. Fechar audiência/reclamações.
2. Gerar lista definitiva.
3. Publicar lista definitiva.
4. Abrir fluxo de atribuição.
5. Selecionar candidato classificado.
6. Atribuir fogo.
7. Registar aceitação ou desistência.
8. Em caso de desistência, chamar o seguinte classificado.

Validar:

- atribuição segue ranking final;
- desistência fica auditada;
- candidato recebe comunicação;
- fogo fica reservado/associado.

## 15. Contrato e transição para inquilino

Utilizadores: jurídico, habitação e financeiro.

Percurso:

1. Abrir módulo de contratos.
2. Gerar ou preparar contrato a partir da atribuição.
3. Confirmar município, arrendatário, imóvel, prazo, renda e caução.
4. Validar juridicamente.
5. Registar assinatura/validação manual.
6. Ativar contrato.
7. Confirmar transição para área do inquilino.

Validar:

- assinatura digital continua fora do âmbito;
- contrato nasce de atribuição aprovada;
- candidato passa a inquilino;
- dados financeiros só aparecem a perfis autorizados.

## 16. Entrega de chaves e área do inquilino

Utilizador: gestor de habitação e inquilino.

Percurso:

1. Agendar entrega de chaves.
2. Registar data e checklist.
3. Inquilino entra na área pessoal.
4. Consultar contrato, documentos, rendas/comprovativos e comunicações.
5. Criar pedido de manutenção.

Validar:

- evento aparece no processo;
- imóvel fica ocupado;
- inquilino só vê os seus dados;
- pedido de manutenção cria tarefa operacional.

## 17. Manutenção

Utilizador: gestor de manutenção.

Percurso:

1. Abrir `/backoffice/maintenance/dashboard`.
2. Abrir `/backoffice/maintenance/requests`.
3. Abrir pedido.
4. Classificar prioridade.
5. Atribuir técnico/equipa.
6. Agendar intervenção.
7. Registar relatório.
8. Fechar pedido.

Validar:

- inquilino é notificado quando configurado;
- estado muda corretamente;
- relatório fica associado ao imóvel/contrato.

## 18. Vistorias

Utilizador: gestor de vistorias.

Percurso:

1. Criar vistoria associada ao imóvel/fogo.
2. Agendar data.
3. Notificar inquilino com antecedência operacional.
4. Registar auto/relatório.
5. Identificar obras, se aplicável.
6. Fechar vistoria.

Validar:

- vistoria fica no histórico do imóvel;
- relatório fica associado;
- ações futuras ficam registadas;
- assinatura digital continua fora do âmbito.

## 19. Visitas de candidatos

Utilizadores: habitação, atendimento e candidato.

Percurso:

1. Habitação cria disponibilidade em `/backoffice/visit-availabilities`.
2. Cria slots de visita.
3. Candidato agenda visita pela área do candidato.
4. Técnico confirma.
5. Regista presença ou no-show.
6. Candidato continua ou atualiza candidatura.

Validar:

- visita aparece no dashboard;
- candidato recebe confirmação;
- visita fica associada ao concurso/fogo.

## 20. Tickets, FAQ e comunicações

Utilizador: atendimento.

Percurso:

1. Candidato abre `/area-candidato/apoio`.
2. Cria ticket.
3. Atendimento abre `/backoffice/support-tickets`.
4. Atribui/responde/resolve.
5. Rever FAQ contextual.

Validar:

- notas internas não aparecem ao candidato;
- anexos são privados;
- ticket cria tarefa quando aplicável;
- comunicação fica no histórico.

## 21. Auditoria e RGPD

Utilizador: auditor.

Percurso:

1. Abrir `/backoffice/security/audit/events`.
2. Abrir `/backoffice/security/audit/sensitive-logs`.
3. Abrir `/backoffice/security/privacy/requests`.
4. Simular pedido RGPD.
5. Gerar exportação autorizada.
6. Confirmar bloqueio de acessos indevidos.

Validar:

- ações críticas geram auditoria;
- documentos não ficam públicos;
- exports exigem permissão;
- dados pessoais são minimizados.

## 22. Resultado esperado

O processo completo deve seguir esta sequência:

Visitante -> Registo -> Simulador -> Candidatura -> Documentos -> Submissão -> Receção -> Validação documental -> Aperfeiçoamento -> Resposta do candidato -> Elegibilidade -> Admissão para classificação -> Pontuação -> Júri -> Lista provisória -> Audiência/Reclamação -> Lista definitiva -> Atribuição -> Contrato -> Inquilino -> Rendas manuais -> Manutenção -> Vistorias.

## 23. Checklist de bloqueios comuns

| Sintoma | Causa provável | Correção operacional |
| --- | --- | --- |
| Snapshot de ranking sem entradas | Processo não está `admitted_for_scoring` ou elegibilidade não está `eligible`. | Executar análise de requisitos, gerar decisão de admissão e aprovar decisão. |
| Documento não entra na IA | IA não foi executada no documento ou OCR local não configurado. | Usar `Executar IA documental` em revisão documental e verificar configuração local. |
| Candidato não consegue submeter candidatura | Documentos obrigatórios em falta ou registo incompleto. | Rever checklist em `/area-candidato/candidaturas/{application}/rever`. |
| Técnico não vê ações | Utilizador tem role demasiado restrita ou está fora da equipa. | Usar persona E2E correta ou rever equipas/permissões. |
| Administrador vê opções em excesso | Está esperado. Administrador é para configuração global, não para execução de fluxo. | Testar com as personas funcionais do seeder. |
