# x-mv.file-input

## Objetivo

Normalizar campos de upload de ficheiros.

## Quando utilizar

- Upload documental
- Substituição de documentos
- Anexos de processos
- Ficheiros submetidos por candidatos ou técnicos

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| id | string | Sim | ID do input |
| name | string | Sim | Nome do campo |
| required | bool | Não | Campo obrigatório |
| accept | string | Não | Tipos aceites |
| multiple | bool | Não | Permite múltiplos ficheiros |

## Exemplo

Usar sempre que existir upload no sistema.

## Boas práticas

- Definir `accept` quando possível.
- Validar sempre no backend através de Form Request.

## Não utilizar

- Inputs `type="file"` diretos fora do Design System.
