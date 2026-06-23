# MASTER PROMPT — AUDITORIA EXAUSTIVA MV HAB v2.0 (PÓS-SPRINTS E PÓS-PHPSTAN)

## Objetivo

Executar uma auditoria **integral, técnica, funcional e arquitetural** da plataforma **MV HAB**, assumindo que:

- O projeto está desenvolvido em **Laravel 13.8 + PHP 8.4**.
- **PHPStan já se encontra instalado e operacional**.
- Existe uma auditoria anterior e respetivo relatório, devendo **validar se todas as conclusões anteriores permanecem válidas ou foram entretanto resolvidas**.
- A plataforma evoluiu segundo o roadmap revisto, privilegiando primeiro a funcionalidade core e adiando integrações externas dependentes dos municípios.
- O objetivo é identificar **apenas lacunas reais**, evitando falsos positivos ou recomendações redundantes.

---

# 1. Preparação

## 1.1 Ler integralmente

- Código-fonte.
- Migrations.
- Models.
- Services.
- Policies.
- Form Requests.
- Jobs.
- Events.
- Observers.
- Commands.
- Seeders.
- Factories.
- Tests.
- Blade.
- Tailwind.
- Alpine.
- Config.
- Rotas.
- Documentação interna.
- Relatório da auditoria anterior.
- Requisitos funcionais do projeto.
- Regulamentos municipais carregados.
- Manual do Utilizador e FAQ de referência inspirados na plataforma HABITAR Lisboa.
- Documento “Requisitos da Plataforma Digital”, usando-o como baseline de validação funcional.

---

# 2. Assunções obrigatórias

## NÃO considerar como falhas:

- ausência de Autenticação.gov;
- ausência de Chave Móvel Digital;
- ausência de integração AT;
- ausência de integração Segurança Social;
- ausência de integração IRN;
- ausência de OCR;
- ausência de gateways bancários;
- ausência de SMS;
- ausência de assinatura digital qualificada;
- ausência de integrações com ERP municipais;
- ausência de webservices externos.

Estas funcionalidades pertencem ao **Sprint de Integrações Externas**, deliberadamente adiado por depender de protocolos e credenciais fornecidos pelos municípios.

Contudo, verificar se:

- existe arquitetura desacoplada;
- existem interfaces ou pontos de extensão;
- existem feature flags ou configuração por município;
- a sua futura integração não exigirá refatorações profundas.

---

# 3. Verificações técnicas obrigatórias

## Executar

```bash
composer validate

composer install

php artisan optimize:clear

php artisan migrate --pretend

php artisan test

vendor/bin/pint --test

vendor/bin/phpstan analyse
```

Registar:

- erros;
- warnings;
- deprecações;
- problemas de tipagem;
- problemas de arquitetura;
- código morto;
- inconsistências.

---

# 4. Auditoria arquitetural

Avaliar:

- SOLID;
- DRY;
- separação de responsabilidades;
- utilização de Services;
- utilização de Form Requests;
- utilização de Policies;
- Events;
- Listeners;
- Jobs;
- Queues;
- Actions;
- DTOs (quando aplicável);
- Transactions;
- Cache;
- Eager Loading;
- Índices;
- Performance;
- Auditoria;
- RGPD;
- Logging;
- Tratamento de exceções;
- Segurança.

Classificar cada domínio de:

- Excelente
- Bom
- Aceitável
- Fraco
- Crítico

---

# 5. Auditoria funcional

Validar exaustivamente:

## Portal Público

- Homepage
- Concursos
- Pesquisa
- Filtros
- Mapa
- Empreendimentos
- Fogos
- SEO
- OpenGraph
- Schema
- WCAG

## Registo

- Utilizador
- Agregado
- Rendimentos
- Habitação
- Atualizações
- Renovação

## Simulador

- Elegibilidade
- Hard gate
- Motivos de exclusão
- Tipologia
- Pré-validação

## Candidaturas

- Wizard
- Upload
- Documentos
- Snapshots
- Submissão
- Desistência
- Reutilização

## Workflow administrativo

- Estados
- Audiência prévia
- Reclamações
- Recursos
- Listas provisórias
- Listas definitivas
- Publicações

## Backoffice

- Concursos
- Editais
- Minutas
- Gestão documental
- Dashboards
- KPIs
- Relatórios
- Exportações
- Auditoria

## Classificação

- Elegibilidade
- Pontuação
- Ranking
- Sorteios
- Critérios
- Ata

## Contratos

- Geração
- PDFs
- Minutas
- Histórico

## Área do Inquilino

- Contratos
- Rendas
- Pagamentos registados
- Manutenção
- Vistorias
- Comunicações
- Agenda

## Scheduler

- Cron
- Jobs
- Caducidades
- Alertas
- Limpezas
- RGPD

---

# 6. Comparação obrigatória com requisitos

Construir tabela:

| EXPERIÊNCIA | PONTO | DETALHE | IMPLEMENTAÇÃO MV HAB | CUMPRE |
| ----------- | ----- | ------- | -------------------- | ------ |

Usar como referência o documento de requisitos da plataforma.

Estados permitidos:

- ✅ Sim
- ⚠️ Parcial
- ❌ Não

Não sugerir funcionalidades fora desse âmbito.

---

# 7. Comparação obrigatória com HABITAR Lisboa

Comparar a experiência do utilizador com:

- Registo de Adesão;
- Simulador;
- Candidaturas;
- Gestão documental;
- Notificações;
- Renovação;
- Área pessoal;
- Fluxo processual.

Usar como benchmark o Manual do Utilizador e as FAQ disponibilizados.

---

# 8. Rever conclusões da auditoria anterior

Para cada problema anteriormente identificado:

- ✅ Resolvido
- ⚠️ Parcialmente resolvido
- ❌ Continua presente

Assinalar explicitamente regressões ou novas incidências. Basear a comparação no relatório de auditoria anterior.

---

# 9. Testes obrigatórios

Executar e validar:

- testes unitários;
- testes funcionais;
- testes de permissões;
- testes de políticas;
- testes de workflow;
- testes de documentos;
- testes de elegibilidade;
- testes de pontuação;
- testes de contratos;
- testes de renda;
- testes de manutenção;
- testes de vistorias;
- testes de notificações;
- testes de upload;
- testes de regressão.

---

# 10. Segurança

Auditar:

- autorização;
- autenticação;
- MFA;
- CSRF;
- XSS;
- Mass Assignment;
- SQL Injection;
- IDOR;
- Storage privado;
- acesso a documentos;
- auditoria de ações;
- proteção RGPD.

---

# 11. Performance

Verificar:

- N+1;
- eager loading;
- índices;
- paginação;
- cache;
- consultas pesadas;
- dashboards;
- exportações;
- relatórios;
- consumo de memória.

---

# 12. Código

Identificar:

- duplicação;
- métodos demasiado extensos;
- classes demasiado grandes;
- código morto;
- comentários obsoletos;
- TODO/FIXME;
- acoplamento excessivo;
- dívida técnica.

---

# 13. Relatório final

Produzir obrigatoriamente:

## Resumo Executivo

## Inventário Funcional

## Estado Arquitetural

## Comparação com Auditoria Anterior

## Comparação com Requisitos

## Comparação com HABITAR Lisboa

## Segurança

## Performance

## Qualidade do Código

## Testes

## PHPStan

Indicar:

- número de erros;
- número de warnings;
- ficheiros afetados;
- prioridade de correção.

## Conclusão

Classificar a plataforma em:

- Pronta para Produção;
- Pronta para Beta;
- Necessita Ajustes Menores;
- Necessita Ajustes Relevantes;
- Não Recomendada para Produção.

---

## Regras finais

- Não propor integrações externas como falhas obrigatórias.
- Não recomendar breaking changes desnecessárias.
- Priorizar estabilidade e compatibilidade com produção.
- Basear todas as conclusões em evidências verificáveis do código e da documentação.
- Sempre que possível, apontar ficheiros, classes ou testes concretos que suportem cada conclusão.
