# Programa 53 - Manual do analista municipal

## Pré-condições de acesso

1. Conta e role municipais ativas.
2. Assignment ao Município do concurso.
3. Entitlement `applications.review`; para exportar,
   `applications.export`.
4. Permissions exatas da operação e MFA válido.
5. Feature municipal ativa e Policy favorável.

O template `analista-candidaturas-exportacao` combina análise e exportação não
sensível, mas não concede administração de acessos nem exportação sensível.

## Abrir a mesa de trabalho

1. Entrar no Backoffice e abrir o Espaço de Trabalho de Candidaturas.
2. Selecionar um concurso visível para o Município atual.
3. Confirmar a fase apresentada e os avisos de calendário incompleto.
4. Usar filtros por processo, candidatura, estado, analista e prontidão.

Um recurso ausente pode significar falta de acesso. Não procurar por ID direto
nem contornar a navegação; o scope municipal é fail-closed.

## Atribuição e análise progressiva

1. Selecionar processos dentro do limite apresentado.
2. Pré-visualizar a atribuição a técnico elegível.
3. Confirmar apenas se a lista e o fundamento estão corretos.
4. Colocar documentos em análise, validar ou rejeitar com fundamento.
5. Consultar a checklist; não marcar prontidão enquanto existirem documentos
   submetidos ou em análise.
6. Usar “Pronta para fecho” apenas como conclusão técnica reversível. Não é
   admissão, elegibilidade, pontuação ou atribuição.

Se a fonte mudar entre preview e confirmação, atualizar a página e repetir a
pré-visualização. Não repetir chamadas manualmente por URL.

## Selagem do lote

1. Abrir o concurso e confirmar que todos os processos estão incluídos.
2. Rever outcomes: completo, requer aperfeiçoamento, desistência ou não
   avaliado.
3. Inserir fundamento e gerar preview.
4. Confirmar contagens, blockers e ciclo (`initial_review` ou `revalidation`).
5. Selar. O lote e os itens tornam-se snapshots imutáveis.

Seleção parcial, documento em análise, hash divergente ou ciclo incompatível
bloqueiam a operação sem efeitos parciais.

## Publicação sincronizada

1. Abrir o lote selado e gerar preview de publicação.
2. Confirmar destinatários, resultados e próximo passo.
3. Publicar uma única vez. Todos os resultados recebem o mesmo
   `published_at` e o outbox é persistido na mesma transação.
4. Consultar o estado das entregas; falha de email não anula a publicação.

Não publicar para “testar”. A publicação é um ato persistente e auditado.

## Aperfeiçoamento

1. Pedidos canónicos são projetados apenas dos resultados
   `correction_required` publicados.
2. Confirmar prazo configurado no concurso; não introduzir prazo ad hoc.
3. Acompanhar itens, respostas, documentos substituídos e progresso.
4. Prorrogar apenas pelo fluxo autorizado, com fundamento e auditoria.
5. O candidato submete formalmente; o recibo congela respostas e versões.

Pedidos expirados sem resposta permanecem como tal. Não fabricar resposta nem
alterar manualmente timestamps.

## Revalidação diferencial

1. Abrir apenas pedidos com recibo formal submetido.
2. Rever alterações comparadas com o snapshot publicado e o recibo.
3. Classificar cada item como aceite, rejeitado, mais informação, decisão
   manual ou não aplicável, conforme as opções existentes.
4. Confirmar o agregado e gerar preview.
5. Selar o lote de revalidação e publicar o segundo resultado.

Versões posteriores ao recibo não entram na decisão. Não existe terceiro pedido
automático de aperfeiçoamento.

## Exportação temporal

1. Abrir Relatórios/Exportações com permissions e entitlement de exportação.
2. Escolher concurso, modo temporal e datasets estritamente necessários.
3. Manter o perfil não sensível por defeito.
4. Gerar preview e confirmar fonte, contagens e período.
5. Submeter. A fila `reports` gera o pacote privado.
6. Descarregar apenas quando `completed`, antes de `expires_at`.
7. Verificar manifesto e checksums quando o pacote integra um dossier oficial.

Um export `failed` deve ser analisado pelo código seguro de falha. Não alterar
ficheiros no storage nem reutilizar URLs expiradas.

## Auditoria e conflitos

- Consultar auditoria apenas com permission adequada.
- Registar o correlation/operation ID, nunca dados pessoais, ao pedir suporte.
- `stale_source` exige nova operação; não é retry automático.
- `authorization_revoked` exige regularização de acesso.
- `schema_invalid` exige correção técnica antes de novo pedido.
- Deadlock/storage indisponível são recuperados pelos retries limitados.

## Limites

- Sem exportação sensível por defeito.
- Sem documentos binários em dossiers enquanto o estado antivírus não for
  persistente e confiável.
- Candidate não acede ao backoffice; auditor é read-only.
- Nenhuma ação deste manual substitui decisão humana ou regulamento municipal.
