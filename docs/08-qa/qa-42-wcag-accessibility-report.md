# QA-42 WCAG Accessibility Report

## Sumario executivo

QA-42 reforcou acessibilidade pragmatica no portal publico, login e area autenticada, com skip link, landmark principal e testes de labels/fallbacks.

## Ficheiros analisados

- `resources/views/components/public-layout.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/public/housing-offer/index.blade.php`
- `resources/views/candidate/documents/create.blade.php`
- `resources/css/app.css`

## Alteracoes implementadas

- Skip link para conteudo principal nos layouts publico, autenticado e guest.
- `<main id="conteudo-principal" tabindex="-1">` nos layouts principais.
- Checklist operacional WCAG em `docs/11-operacoes/wcag-candidate-public-checklist.md`.

## Testes criados

- `tests/Feature/QA42WcagCandidatePublicAccessibilityTest.php`
- `tests/Feature/PublicPortal/PublicMapAccessibilityFallbackTest.php`
- `tests/Feature/Candidate/DocumentUploadAccessibilityTest.php`
- `tests/Feature/Candidate/CandidateAccessibilityTest.php`

## Validacoes

- Portal publico tem skip link, main landmark e h1.
- Login tem main landmark.
- Area do candidato tem skip link e h1.
- Upload documental tem labels, instrucoes e erros.
- Mapa publico tem fallback textual.

## Riscos residuais

- Validacao WCAG completa com leitor de ecra/axe/browser real fica recomendada antes de producao plena.

## Resultado

Bloco QA-42 validado por testes automatizados pragmaticos.
