# Domain Boundaries

## Objetivo

Definir guardrails arquiteturais para manter a plataforma MV HAB modular, testavel e segura apos o fecho de PHPStan global.

## Principio base

Cada camada deve ter uma responsabilidade clara:

| Camada | Responsabilidade | Nao deve fazer |
| --- | --- | --- |
| Controllers | Receber request, autorizar, delegar, devolver response. | Calcular elegibilidade, scoring, ranking ou regras regulamentares. |
| Form Requests | Validar payload HTTP e preparar dados de entrada. | Executar workflows, alterar estado ou consultar dominios complexos. |
| Policies/Gates | Decidir autorizacao e ownership. | Alterar estado, criar auditoria de negocio ou substituir services. |
| Services | Orquestrar regras de dominio e transacoes. | Depender diretamente de `Request` HTTP ou misturar dominios sem fronteira clara. |
| Models | Relacoes, casts, scopes simples, atributos derivados simples. | Workflows complexos, calculos regulamentares ou autorizacao. |
| Jobs | Processamento assicrono idempotente. | Assumir estado mutavel sem lock, auditoria ou retry seguro. |
| Events | Comunicar factos de dominio com payload minimo. | Transportar snapshots extensos ou dados pessoais desnecessarios. |

## Fronteiras por dominio

| Dominio | Responsabilidade | Guardrail |
| --- | --- | --- |
| Portal publico | Consulta publica de programas, concursos e oferta habitacional. | Nao expor dados pessoais, documentos privados ou identificadores internos sensiveis. |
| Registo de adesao | Dados base do candidato e consentimentos. | Alteracoes devem respeitar ownership e auditoria. |
| Agregado, rendimentos e habitacao | Dados declarativos do candidato. | Validacao deve ser explicita e protegida contra mass assignment. |
| Documentos | Submissao, storage privado, revisao e downloads autorizados. | Downloads passam sempre por controller/policy e devem ser auditados quando sensiveis. |
| Elegibilidade | Regras automaticas de acesso e impedimentos. | Alteracoes exigem testes deterministicos e nao devem excluir sem rastreabilidade. |
| Scoring e ranking | Pontuacao, criterios, desempates e snapshots. | Ordenacao deve ser deterministica e auditavel. |
| Listas e audiencia | Publicacoes, reclamacoes, decisoes e historico. | Publicacao deve preservar snapshots e rastreabilidade administrativa. |
| Atribuicao e sorteios | Resultados, ofertas, reservas e fecho de concurso. | Sorteios devem ser auditaveis e reproduziveis quando aplicavel. |
| Contratos e rendas | Minutas, calculos, revisoes, depositos e encargos. | Calculos financeiros exigem testes e nao devem depender de formatacao de UI. |
| Area do inquilino | Pos-atribuicao, contratos, pagamentos, manutencao e vistorias. | Inquilino so acede aos seus proprios dados. |
| RGPD | Consentimentos, pedidos de titular, exportacao, anonimizacao e retencao. | Nenhuma acao RGPD critica sem auditoria e policy. |
| Auditoria | Eventos criticos e acessos sensiveis. | Logs nao devem conter dados pessoais desnecessarios. |
| Document Intelligence | OCR, classificacao, extracao, validacao e flags. | IA nunca toma decisao administrativa final automaticamente. |

## Regras para controllers

- Controllers devem chamar Form Requests quando houver payload mutavel.
- Controllers devem chamar Policies/Gates antes de dados protegidos.
- Controllers devem delegar regras de negocio em services.
- Controllers nao devem montar queries complexas com multiplas relacoes quando existir service/query object.
- Controllers nao devem manipular estados criticos diretamente.

## Regras para services

- Services devem ter responsabilidade unica ou fronteira de dominio clara.
- Services que alteram multiplos modelos devem usar transacao quando a consistencia exigir.
- Services devem devolver DTO, array documentado ou model persistido conforme padrao local.
- Services nao devem depender diretamente de `Illuminate\Http\Request`.
- Services devem lancar excecoes de dominio claras quando uma operacao nao for permitida.

## Regras para policies

- Policies devem verificar permissao, ownership e escopo municipal.
- Policies nao devem gravar dados.
- Policies nao devem disparar jobs, events ou auditoria de negocio.
- Alargamento de permissao exige teste de autorizacao.

## Regras para models

- Models podem conter relacoes, casts, scopes simples e atributos derivados simples.
- Workflows complexos devem sair para services.
- Calculos regulamentares devem ser testaveis fora do model.
- Mass assignment deve ser explicito e campos criticos devem ser protegidos.

## Regras para jobs e events

- Jobs devem ser idempotentes ou protegidos por lock/chave de deduplicacao.
- Jobs devem registar falhas relevantes e respeitar retries.
- Events devem transportar identificadores e payload minimo.
- Dados pessoais em payloads de eventos devem ser minimizados.

## Sinais de alerta arquitetural

- Controller com calculo de elegibilidade, scoring, ranking ou renda.
- Policy com alteracao de estado.
- Model com workflow administrativo completo.
- Job que cria duplicados ao ser reprocessado.
- Export sem paginacao/chunking.
- Dashboard com queries sem eager loading.
- Service a aceitar `Request` HTTP em vez de dados validados.
