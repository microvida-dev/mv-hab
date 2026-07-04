# MV HAB Design System

**Versão:** 1.0

**Sprint:** 39 – Design System Core

**Estado:** Ativo

---

# Objetivo

O Design System MV HAB constitui a camada visual reutilizável da plataforma.

Todos os novos desenvolvimentos devem privilegiar componentes Blade reutilizáveis em detrimento de HTML/Tailwind repetido.

Objetivos:

- eliminar duplicação;
- uniformizar UX;
- reduzir dívida técnica;
- facilitar manutenção;
- aumentar consistência visual;
- simplificar futuras evoluções.

---

# Estrutura

```
Views

↓

Blade Components

↓

MV Design System

↓

Tailwind

↓

CSS Tokens
```

---

# Princípios

Todos os componentes devem:

- possuir responsabilidade única;
- ser reutilizáveis;
- ser compostos entre si;
- evitar lógica de negócio;
- privilegiar slots em vez de HTML repetido;
- manter compatibilidade retroativa sempre que possível.

---

# Componentes

## Layout

### section

Utilização:

```blade
<x-mv.section
    title="Título"
    description="Descrição">

    ...

</x-mv.section>
```

Responsável por:

- contentor principal
- superfície
- espaçamento
- cabeçalho opcional

---

### page-header

Utilização:

```blade
<x-mv.page-header
    title="Processo"
    description="Descrição">

    <x-slot:actions>
        ...
    </x-slot:actions>

</x-mv.page-header>
```

Responsável por:

- título
- descrição
- ações

---

# Informação

## badge

Representa estados curtos.

Props:

```
tone
size
icon
outline
pill
```

Tons disponíveis:

```
neutral
success
warning
danger
info
primary
```

Exemplo:

```blade
<x-mv.badge tone="success">
    Elegível
</x-mv.badge>
```

---

## alert

Mensagens ao utilizador.

Props:

```
tone
title
icon
bordered
```

Exemplo:

```blade
<x-mv.alert
    tone="warning"
    title="Prazo">
    O prazo termina amanhã.
</x-mv.alert>
```

---

## stat-card

Cartões de indicadores.

Props:

```
label
value
hint
icon
href
```

Utilização típica:

Dashboard.

---

# Formulários

## checkbox-card

Cartão clicável.

Utilização:

- preferências
- seleção
- opções

---

## file-input

Upload de ficheiros.

Suporta:

- multiple
- accept
- required

---

# Validação

## check-card

Apresentação de validações.

Estados:

- sucesso
- erro

---

# Description List

## description-list

Contentor.

Responsável por:

- grid
- colunas
- responsividade

Props:

```
columns
compact
```

---

## description-item

Representa um campo.

Props:

```
label
value
hint
icon
compact
```

---

# Empty States

## empty-state

Representa ausência de conteúdo.

Exemplo:

```
Sem documentos

Sem pagamentos

Sem reclamações
```

Suporta:

- ícone
- descrição
- ações

---

# Action System

## action-bar

Responsável pelo layout das ações.

Suporta:

```
start

center

between

end
```

---

## action-group

Agrupa:

- botões
- formulários
- dropdowns
- pesquisa

---

# Filter System

## filter-bar

Normaliza todos os filtros.

Suporta:

```
GET

action

columns

compact
```

Pode conter:

- inputs
- selects
- pesquisa
- action-bar

---

# Tables

## table

Contentor padrão.

Inclui:

- overflow
- responsividade
- superfície

---

## table-empty

Estado vazio para tabelas.

Evita repetição de:

```blade
<tr>
<td colspan="...">
Sem registos.
```

---

# Timeline

## timeline

Contentor principal.

---

## timeline-item

Evento.

---

## timeline-marker

Marcador visual.

Tons:

```
neutral
success
warning
danger
info
primary
```

---

## timeline-date

Uniformiza apresentação de datas.

---

# Ordem recomendada

Todas as páginas devem seguir:

```
page-header

↓

action-bar

↓

filter-bar (quando existir)

↓

section

↓

table

↓

empty-state

↓

timeline
```

---

# Boas práticas

## Fazer

✔ utilizar componentes MV

✔ reutilizar slots

✔ manter HTML reduzido

✔ manter lógica na ViewModel/Controller

✔ utilizar props tipificadas

---

## Evitar

✘ HTML repetido

✘ Tailwind duplicado

✘ blocos inline demasiado extensos

✘ componentes com múltiplas responsabilidades

---

# Roadmap

Sprint 40

Migração sistemática:

- tables
- filters
- actions
- description lists
- timelines

---

Sprint 41

Portal Público.

---

Sprint 42

Candidate Experience.

---

Sprint 43

Design System 100%.

---

# Objetivo Final

Todo o frontend do MV HAB deverá ser composto exclusivamente por componentes reutilizáveis do Design System, garantindo consistência visual, facilidade de manutenção e evolução incremental sem duplicação de código.