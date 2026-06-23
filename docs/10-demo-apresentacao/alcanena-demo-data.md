# Inventario e Preparacao de Dados Demo — Alcanena

## Objetivo

Definir os dados ficticios necessarios para uma demonstracao coerente da MV HAB ao Municipio de Alcanena, sem introduzir dados pessoais reais.

## Dados base necessarios

### Programa

- Nome: Programa Municipal de Arrendamento Acessivel de Alcanena.
- Tipo: Arrendamento Municipal Acessivel.
- Regime: Renda acessivel.
- Municipio: Alcanena.
- Base legal: Regulamento Municipal de Arrendamento Acessivel — Edital n.o 1820/2024.
- Objeto: acesso ao arrendamento de habitacao a custos acessiveis, compativel com o rendimento dos agregados familiares.

### Concurso

- Titulo: Concurso n.o 01/2026 — Arrendamento Municipal Acessivel de Alcanena.
- Tipo: concurso por classificacao.
- Estado demo: publicado pelo seeder para demonstracao publica controlada.
- Canal: plataforma eletronica e atendimento municipal.
- Pre-condicao: registo de adesao antes da candidatura.
- Publicitacao: aviso/edital no site municipal e lugares de estilo.

### Regras de elegibilidade

Baseadas no artigo 8.o:

- Idade minima igual ou superior a 18 anos.
- Nacionalidade portuguesa ou titulo de residencia valido.
- Rendimento maximo nos termos da portaria aplicavel.
- Adultos emancipados e nao dependentes com rendimento mensal igual ou superior a RMMG.
- Tipologia adequada ao agregado.
- Taxa de esforco maxima de 35% do rendimento medio mensal.

### Impedimentos

Baseados no artigo 9.o:

- Propriedade, usufruto ou detencao de predio/fracao habitacional.
- Apoio publico habitacional ou habitacao publica.
- Situacao nao regularizada perante AT ou Seguranca Social.
- Divida ao municipio sem acordo.
- Acumulacao com outro apoio publico a habitacao.
- Falsas declaracoes ou meios fraudulentos.
- Cessacao anterior por despejo ou incumprimento contratual municipal.

### Documentos obrigatorios

Baseados no artigo 12.o:

- Documento de identificacao civil ou autorizacao de residencia.
- NIF, quando aplicavel.
- Cartao de Seguranca Social, quando aplicavel.
- Certidao de domicilio fiscal.
- IRS/nota de liquidacao ou comprovativos de rendimento.
- Certidao AT de nao propriedade de habitacao.
- Certidoes de situacao regularizada AT e ISS.
- Atestado multiusos, quando aplicavel.
- Declaracao medica de gravidez, quando aplicavel.

### Matriz de classificacao

Baseada no Anexo I:

- Nivel de qualificacao: 30%.
- Idade media dos elementos nao dependentes: 40%.
- Numero de dependentes: 20%.
- Deficiencia ou multideficiencia: 10%.

## Fogos ficticios recomendados para a demo

| Referencia | Freguesia/localidade | Tipologia | Renda demo | Objetivo |
| --- | --- | --- | --- | --- |
| ALC-DEMO-T1-CENTRO | Alcanena Centro | T1 | 320 EUR | Fogo pequeno para candidato isolado/casal |
| ALC-DEMO-T2-ALCANENA | Alcanena | T2 | 390 EUR | Fogo familiar padrao |
| ALC-DEMO-T3-MINDE | Minde | T3 | 470 EUR | Fogo para agregado maior |
| ALC-DEMO-T2-MONSANTO | Monsanto | T2 | 410 EUR | Validar filtros por freguesia e comparacao de oferta |

Estado Sprint 32: estes quatro fogos ficam preenchidos no `DemoAlcanenaAffordableRentSeeder` com campos publicos, SEO basico, renda, area, localizacao aproximada, estado publico e visibilidade publicada.

## Campos publicos a preencher por fogo

- Referencia publica.
- Titulo publico.
- Slug publico.
- Resumo publico.
- Descricao publica.
- Freguesia.
- Localidade.
- Tipologia.
- Area util e area bruta.
- Renda minima/maxima ou renda acessivel.
- Estado publico.
- Visibilidade publica.
- Localizacao aproximada.
- Indicar se a morada completa pode ou nao ser visivel.
- Galeria ficticia ou placeholder seguro.
- Brochura HTML imprimivel atraves da ficha publica do fogo.
- SEO title/description.

## Candidaturas ficticias recomendadas

| Cenario | Objetivo | Estado recomendado |
| --- | --- | --- |
| Candidato elegivel completo | Demonstrar fluxo positivo | Submetida/admitida |
| Candidato com documento em falta | Demonstrar aperfeicoamento | Em aperfeicoamento |
| Candidato com divergencia documental | Demonstrar apoio IA/tecnico | Revisao manual |
| Candidato com impedimento | Demonstrar exclusao fundamentada | Proposta de indeferimento |
| Candidato em audiencia | Demonstrar resposta administrativa | Audiencia/reclamacao |

## Utilizadores demo recomendados

- Administrador municipal: `admin-demo@exemplo.pt`.
- Tecnico municipal: `tecnico-demo@exemplo.pt`.
- Juri: `juri-demo@exemplo.pt`.
- Candidato: `candidato-demo@exemplo.pt`.
- Password temporaria comum em ambiente local/demo: `password`.
- Gestor financeiro, gestor de manutencao e auditor podem ser adicionados depois se a demonstracao exigir esses perfis.

## Regras de seguranca dos dados demo

- Usar apenas nomes ficticios.
- Usar emails em dominio reservado, por exemplo `example.test`.
- Nao usar NIFs reais.
- Nao usar documentos reais.
- Nao incluir moradas completas reais de pessoas.
- Usar moradas ou referencias ficticias claramente identificadas.
- Nao carregar ficheiros com dados pessoais reais.

## Estado apos Sprint 32

- Seeder demo contem quatro fogos ficticios, incluindo T2 Monsanto.
- Campos publicos essenciais dos fogos foram enriquecidos para demo.
- Programa e concurso ficam publicados pelo seeder para demonstracao controlada.
- Filtros publicos por freguesia, tipologia, renda e estado estao expostos na UI.
- Ficha publica do imovel inclui area bruta, CTA de simulacao, area reservada e brochura.
- Brochura simples foi implementada como HTML imprimivel.
- Galeria/imagens ficticias continuam opcionais e podem ser adicionadas depois se o Municipio quiser uma demo mais visual.
- Candidaturas ficticias completas e listas demo nao foram criadas nesta sprint para evitar aprofundar o ciclo processual alem da preparacao de demonstracao.
