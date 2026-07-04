# MV HAB Design System

Biblioteca visual reutilizável da plataforma MV HAB.

## Objetivo

Garantir consistência visual, reduzir duplicação Blade e acelerar a criação de novas interfaces sem comprometer acessibilidade, manutenção ou segurança.

## Componentes disponíveis

- `x-mv.page-header`
- `x-mv.section`
- `x-mv.stat-card`
- `x-mv.check-card`
- `x-mv.checkbox-card`
- `x-mv.file-input`
- `x-mv.badge`
- `x-mv.alert`

## Regras gerais

- Não criar novas páginas com blocos visuais duplicados quando existir componente `x-mv`.
- Usar `x-mv.page-header` em cabeçalhos de página.
- Usar `x-mv.section` para blocos de conteúdo.
- Usar `x-mv.badge` para estados simples.
- Usar `x-mv.alert` para mensagens contextuais.
- Usar `x-mv.file-input` para uploads.
- Preservar sempre rotas, policies e validações existentes durante refatorações visuais.

## Validação

Antes de commit:

```bash
php -l resources/views/components/mv/*.blade.php
npm run build
