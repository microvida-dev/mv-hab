# WCAG Candidate and Public Checklist

## Objetivo

Checklist pragmatica de acessibilidade para o portal publico e area do candidato antes de demonstracao municipal.

## Navegacao e estrutura

- [ ] Cada pagina tem um unico `<main>` com `id="conteudo-principal"`.
- [ ] Existe link "Saltar para o conteudo principal".
- [ ] Existe `h1` visivel e coerente.
- [ ] Headings seguem hierarquia previsivel.
- [ ] Breadcrumbs publicos usam `aria-label="Breadcrumb"`.
- [ ] Navegacao publica usa `aria-label`.

## Formularios

- [ ] Campos de pesquisa tem label textual.
- [ ] Upload documental tem label para ficheiro.
- [ ] Campos obrigatorios mostram erro compreensivel.
- [ ] Formatos/tamanho maximo de upload sao indicados.
- [ ] Mensagens nao dependem apenas de cor.

## Foco e teclado

- [ ] Links, botoes, inputs, selects e textareas tem foco visivel.
- [ ] O skip link fica visivel ao receber foco.
- [ ] A navegacao principal e acessivel por teclado.
- [ ] Nao ha armadilhas de foco em paginas base.

## Mapa e conteudo visual

- [ ] Mapa publico tem alternativa textual/lista.
- [ ] Estado sem coordenadas tem mensagem clara.
- [ ] Imagens publicas tem `alt` quando relevantes.
- [ ] Conteudo privado nao aparece no mapa nem nas fichas.

## RGPD

- [ ] Paginas publicas nao mostram dados pessoais.
- [ ] Documentos privados nao aparecem como links publicos.
- [ ] Paths internos nao aparecem em HTML/responses.

## Evidencia

Validado por testes:

- `QA42WcagCandidatePublicAccessibilityTest`
- `PublicMapAccessibilityFallbackTest`
- `CandidateAccessibilityTest`
- `DocumentUploadAccessibilityTest`
- `PublicPortalAccessibilitySmokeTest`
