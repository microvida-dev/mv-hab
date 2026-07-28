# Demonstração Municipal de Candidaturas MV-HAB

## Finalidade

Este cenário cria dados integralmente fictícios para demonstração técnica e comercial do ciclo municipal de candidatura a arrendamento acessível.

Não produz decisões administrativas, notificações reais, contratos, pagamentos ou qualquer efeito jurídico.

## Proteções obrigatórias

A execução é recusada quando:

- `APP_ENV` não é `demo`, `local` ou `testing`;
- `MVHAB_REGULATORY_DEMO_MODE` não está ativo;
- `MVHAB_MUNICIPAL_APPLICATION_DEMO` não está ativo;
- a password demo não possui pelo menos 12 caracteres;
- já existe outra execução do comando em curso.

Nunca ativar estas opções em produção.

## Configuração

```dotenv
APP_ENV=demo
MVHAB_REGULATORY_DEMO_MODE=true
MVHAB_MUNICIPAL_APPLICATION_DEMO=true
MVHAB_DEMO_REFERENCE_DATE=2026-07-27
MVHAB_DEMO_USER_PASSWORD="definir-uma-password-forte"
```

Depois de alterar variáveis de ambiente:

```bash
php artisan optimize:clear
```

## Criar ou atualizar o cenário

Execução interativa:

```bash
php artisan mvhab:demo:municipal-application
```

Execução não interativa:

```bash
php artisan mvhab:demo:municipal-application --force
```

Saída JSON para automação:

```bash
php artisan mvhab:demo:municipal-application \
  --force \
  --format=json
```

## Verificar sem alterar

```bash
php artisan mvhab:demo:municipal-application \
  --verify-only
```

Em JSON:

```bash
php artisan mvhab:demo:municipal-application \
  --verify-only \
  --format=json
```

O comando termina com código diferente de zero quando o cenário está incompleto ou incompatível.

## Contas fictícias

| Perfil | Email |
|---|---|
| Operador de recolha | `operador.recolha.demo@mvhab.local` |
| Analista de candidaturas | `analista.candidaturas.demo@mvhab.local` |
| Gestor de visitas | `gestor.visitas.demo@mvhab.local` |
| Exportador de candidaturas | `exportador.candidaturas.demo@mvhab.local` |
| Candidato | `joao.ferreira.demo@mvhab.local` |

Todas usam a password definida em `MVHAB_DEMO_USER_PASSWORD`. O comando nunca apresenta essa password na consola nem na saída JSON.

## Cenário final verificado

| Indicador | Total |
|---|---:|
| Município demo | 1 |
| Perfis municipais de privilégio mínimo | 4 |
| Contas fictícias | 5 |
| Fogos T2 | 3 |
| Candidaturas submetidas | 1 |
| Preferências oficiais bloqueadas | 3 |
| Snapshots finais imutáveis | 8 |
| Documentos associados à submissão | 15 |
| Submissões documentais | 15 |
| Versões documentais | 16 |
| Revisões da candidatura | 2 |
| Pedidos de aperfeiçoamento | 1 |
| Respostas ao aperfeiçoamento | 1 |
| Disponibilidades de visita | 1 |
| Slots de visita | 4 |
| Visitas concluídas | 1 |
| Relatórios municipais HTML/CSV | 2 |
| Dossiers documentais | 1 |
| Itens do dossier | 15 |
| Notificações oficiais fictícias | 6 |
| Análises documentais por IA | 0 |

## Fluxo demonstrado

```text
adesão e agregado
→ simulador elegível
→ candidatura em rascunho
→ preferências oficiais
→ checklist documental privado
→ submissão formal
→ snapshots imutáveis
→ revisão documental
→ rejeição de um recibo
→ pedido de aperfeiçoamento
→ substituição com histórico de versões
→ reanálise e aceitação
→ disponibilidade e slots de visita
→ agendamento, confirmação e reagendamento
→ conclusão da visita
→ relatório HTML
→ relatório CSV
→ dossier documental municipal
```

## Armazenamento e privacidade

- Os documentos do candidato permanecem privados.
- Os relatórios e o dossier são gravados no disco `local`.
- Os ficheiros exportados são artefactos de demonstração.
- O payload municipal utiliza dados fictícios e apresentação controlada.
- A execução mantém auditoria dos serviços de domínio utilizados.
- A IA documental permanece desativada durante a criação dos documentos fictícios.

## Reexecução

O cenário é idempotente. A reexecução preserva identificadores, snapshots finais, histórico documental e artefactos já consolidados.

Não editar ou eliminar parcialmente os registos demo. Para reinicializar uma base exclusivamente local ou de demonstração, usar o procedimento normal de reconstrução da base de dados definido para esse ambiente.

`migrate:fresh` é destrutivo e nunca deve ser usado numa base partilhada ou de produção.
