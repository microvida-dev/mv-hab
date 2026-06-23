# Plano de Tarefas Pre-Sprint 32 — Apresentacao Municipio de Alcanena

## Objetivo

Preparar a execucao da Sprint 32 com risco controlado, sem criar codigo nesta fase. Este plano transforma a analise do backlog, requisitos da plataforma e documentacao legal de Alcanena numa lista objetiva de tarefas a executar antes e durante a Sprint 32.

## Principios da apresentacao

- Apresentar a MV HAB como plataforma municipal ja forte para demonstracao operacional e piloto controlado.
- Ser transparente sobre o que esta completo, parcial ou previsto em roadmap.
- Evitar prometer integracoes externas que dependem de protocolos, credenciais ou decisoes municipais.
- Usar apenas dados ficticios, sem dados pessoais reais.
- Demonstrar valor municipal: backoffice, candidatura, documentos, elegibilidade, classificacao, listas, auditoria, RGPD e rastreabilidade processual.

## Tarefas P0 — obrigatorias antes de demonstrar

### 1. Confirmar escopo honesto da demonstracao

**Resultado esperado:** mensagem de abertura validada para o Municipio.

- Separar claramente `ja demonstravel`, `parcial mas visivel`, `roadmap`.
- Nao apresentar como concluido:
  - assinatura digital qualificada;
  - gateways de pagamento reais;
  - integracoes AT, Seguranca Social, IRN ou Autenticacao.gov;
  - SMS;
  - ERP municipal;
  - OCR/IA documental como decisor administrativo autonomo.
- Posicionar integracoes externas como extensoes futuras.

### 2. Validar dados demo de Alcanena

**Resultado esperado:** checklist de dados para seeder demo completo.

- Confirmar programa municipal de Alcanena.
- Confirmar concurso n.o 01/2026 em modo rascunho/simulacao/publicavel.
- Confirmar regras de elegibilidade do artigo 8.o.
- Confirmar impedimentos do artigo 9.o.
- Confirmar checklist documental do artigo 12.o.
- Confirmar matriz de classificacao do Anexo I.
- Confirmar habitações ficticias suficientes para demo:
  - T1 Alcanena Centro;
  - T2 Alcanena;
  - T3 Minde;
  - T2 Monsanto.
- Garantir que todos os dados sao ficticios e seguros para demonstracao.

### 3. Validar portal publico de oferta habitacional

**Resultado esperado:** percurso publico demonstravel sem quebras.

- Confirmar pagina publica de concursos.
- Confirmar detalhe de concurso.
- Confirmar listagem publica de fogos.
- Confirmar detalhe publico de fogo.
- Confirmar que dados sensiveis ou internos nao aparecem publicamente.
- Confirmar mapa publico ou fallback sem depender de servicos externos.
- Confirmar downloads publicos apenas para documentos publicos.

### 4. Validar gaps visiveis do portal publico

**Resultado esperado:** lista curta de ajustes antes da demo.

Pontos identificados para a Sprint 32:

- A pesquisa publica ja suporta filtros tecnicos por renda minima/maxima e estado publico, mas a interface visivel deve expor melhor esses filtros se forem demonstrados.
- A plataforma suporta documentos publicos por fogo, incluindo tipo `brochure`, mas deve ser decidido se a demo precisa de uma rota/CTA dedicado "Descarregar brochura".
- O seeder atual deve ser revisto para preencher campos publicos ricos dos fogos, imagens/documentos ficticios e pelo menos uma unidade T2 em Monsanto.

### 5. Preparar narrativa juridica e processual

**Resultado esperado:** roteiro alinhado com o Regulamento Municipal.

- Enquadrar o objeto do programa: arrendamento municipal acessivel compativel com rendimento do agregado.
- Explicar adesao obrigatoria antes de candidatura.
- Mostrar candidatura a uma ou varias habitacoes, quando permitido.
- Mostrar validacao documental e audiencia previa quando aplicavel.
- Mostrar classificacao por criterios do Anexo I.
- Mostrar listas provisoria/definitiva e rastreabilidade.

## Tarefas P1 — recomendadas para uma demo convincente

### 6. Preparar percurso do cidadao

**Resultado esperado:** uma historia demo com inicio, meio e fim.

- Consultar oferta publica.
- Abrir detalhe do concurso.
- Entrar na area reservada.
- Rever registo de adesao.
- Submeter ou consultar documentos.
- Iniciar candidatura.
- Consultar estado inicial e comprovativo.

### 7. Preparar percurso do tecnico municipal

**Resultado esperado:** demonstracao backoffice com alto valor operacional.

- Abrir concurso.
- Consultar candidatura.
- Rever documentos.
- Consultar elegibilidade.
- Consultar pontuacao/ranking.
- Gerir aperfeicoamento ou audiencia.
- Publicar/listar resultados em ambiente demo.
- Mostrar auditoria e historico.

### 8. Preparar demonstracao RGPD e seguranca

**Resultado esperado:** argumentos objetivos para decisores municipais.

- Mostrar perfis e permissoes.
- Mostrar que candidatos veem apenas os seus dados.
- Mostrar documentos privados protegidos.
- Mostrar logs/auditoria de decisao e alteracao.
- Explicar retencao, consentimentos e direitos dos titulares.

### 9. Preparar cenarios de candidatura

**Resultado esperado:** 3 a 5 candidaturas ficticias com estados diferentes.

Cenarios recomendados:

- Candidatura elegivel e completa.
- Candidatura com documento em falta.
- Candidatura com divergencia documental.
- Candidatura excluida por impedimento.
- Candidatura em audiencia/reclamacao.

## Tarefas P2 — materiais de apresentacao

### 10. Criar matriz requisitos vs plataforma

**Resultado esperado:** tabela objetiva para uso interno e apresentacao executiva.

- Marcar cada requisito como `Sim`, `Parcial` ou `Nao`.
- Justificar parciais sem dramatizar.
- Separar funcionalidades dependentes de integracoes externas.

### 11. Criar roteiro da apresentacao

**Resultado esperado:** guiao para uma demo de 30 a 45 minutos.

- Abertura institucional.
- Portal publico.
- Area do candidato.
- Backoffice tecnico.
- Workflow administrativo.
- Auditoria, RGPD e relatorios.
- Roadmap honesto.
- Perguntas e respostas.

### 12. Criar readiness checklist

**Resultado esperado:** decisao objetiva de avancar ou nao para demo.

- Ambiente local/preview funcional.
- Seeder demo executavel.
- Dados ficticios coerentes.
- Rotas principais acessiveis.
- Fluxos principais demonstraveis.
- Sem dependencias pagas.
- Sem dados reais.

## Nao objetivos desta preparacao

- Nao criar novas funcionalidades aplicacionais.
- Nao alterar regras de negocio.
- Nao criar migrations, models, controllers, views ou rotas.
- Nao instalar dependencias.
- Nao executar integracoes externas.
- Nao alterar `.env`.

## Criterios de entrada para executar a Sprint 32

A Sprint 32 pode ser iniciada quando:

- A matriz de requisitos estiver aceite como baseline.
- O roteiro da demonstracao estiver aceite.
- Os gaps publicos forem priorizados.
- O conjunto minimo de dados demo estiver definido.
- For confirmado que a Sprint 32 pode criar os ajustes aplicacionais necessarios.

## Recomendacao

Avancar para Sprint 32 apos validacao deste plano. A plataforma tem base suficiente para uma demonstracao forte, mas a Sprint 32 deve focar acabamento, dados demo, narrativa e alinhamento com requisitos de Alcanena, nao novas areas funcionais profundas.
