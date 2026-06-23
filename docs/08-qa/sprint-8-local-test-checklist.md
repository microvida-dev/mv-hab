# Checklist de teste local — Sprint 8

Esta checklist valida o fluxo de candidatura formal na máquina local através de:

```text
http://127.0.0.1:8001
```

Usar apenas dados fictícios, emails com domínio `.test` e documentos sem dados pessoais reais.

## 1. Preparação

- [ ] Abrir um terminal na raiz do projeto.
- [ ] Confirmar PHP e Laravel:

```bash
php -v
php artisan --version
```

- [ ] Instalar dependências apenas se ainda não existirem:

```bash
composer install
npm install
```

- [ ] Confirmar a ligação à base de dados configurada localmente:

```bash
php artisan migrate:status
```

- [ ] Aplicar migrations incrementais pendentes:

```bash
php artisan migrate
```

- [ ] Carregar dados de demonstração apenas num ambiente local preparado para isso:

```bash
php artisan db:seed
```

O seeder cria um programa e um concurso público de demonstração. Também cria a conta administrativa local definida em `database/seeders/UserSeeder.php`. Essa conta nunca deve ser usada em produção.

- [ ] Compilar os assets:

```bash
npm run build
```

## 2. Arranque na porta 8001

- [ ] Confirmar que a porta está livre:

```bash
lsof -nP -iTCP:8001 -sTCP:LISTEN
```

- [ ] Se o comando não devolver processos, iniciar a aplicação:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

- [ ] Manter o terminal aberto.
- [ ] Abrir no browser:

```text
http://127.0.0.1:8001
```

Se os estilos não aparecerem, executar novamente `npm run build` e atualizar a página sem cache.

## 3. Portal público

- [ ] A homepage abre sem autenticação.
- [ ] `/programas` apresenta apenas programas publicados.
- [ ] `/concursos` apresenta o concurso de demonstração.
- [ ] O detalhe do concurso mostra estado e prazo.
- [ ] Um concurso aberto mostra “Criar conta para candidatar-me” e “Já tenho conta”.
- [ ] Nenhuma página pública apresenta dados de candidatos.

## 4. Criar candidato fictício

- [ ] Se o portal mostrar “Backoffice”, existe uma sessão administrativa ativa.
- [ ] Selecionar “Sair” ou “Terminar sessão”.
- [ ] Confirmar que o portal passa a mostrar “Entrar” e “Criar conta”.
- [ ] Aceder a `/register`.
- [ ] Criar uma conta com dados fictícios:

```text
Nome: Candidato Teste Local
Email: candidato.local@example.test
Password: uma password temporária exclusiva deste ambiente
```

- [ ] Confirmar que o utilizador entra na Área do Candidato.
- [ ] Confirmar que não consegue abrir `/backoffice/applications`.

Não atribuir a role `candidate` à conta administrativa. Administrador e candidato devem ser contas diferentes para validar corretamente o isolamento de acessos.

## 5. Registo de Adesão

- [ ] Abrir “O meu registo”.
- [ ] Preencher os campos obrigatórios com dados fictícios.
- [ ] Usar data de nascimento correspondente a uma pessoa com pelo menos 18 anos.
- [ ] Aceitar os termos e a informação de tratamento.
- [ ] Guardar o rascunho.
- [ ] Finalizar o Registo de Adesão.
- [ ] Confirmar o estado “Finalizado”.

## 6. Agregado, rendimentos e habitação

- [ ] Abrir “Agregado”.
- [ ] Confirmar a existência do membro requerente.
- [ ] Adicionar membros fictícios, caso seja necessário ao cenário.
- [ ] Em “Rendimentos”, escolher um dos caminhos:
  - adicionar pelo menos um rendimento; ou
  - declarar ausência de rendimentos para cada membro.
- [ ] Abrir “Habitação atual”.
- [ ] Preencher a situação habitacional.
- [ ] Confirmar que o dashboard apresenta 100% nas áreas preparatórias.

## 7. Checklist documental

- [ ] Abrir “Documentos” e depois “Checklist documental”.
- [ ] Confirmar que a lista muda de acordo com agregado, rendimentos e habitação.
- [ ] Criar ficheiros PDF fictícios sem dados pessoais reais.
- [ ] Submeter cada documento obrigatório.
- [ ] Confirmar que o progresso documental chega a 100%.
- [ ] Confirmar que a interface não apresenta `storage_path`, checksum ou caminhos de servidor.

Documentos com estado `submitted`, `under_review` ou `validated` permitem a submissão formal. Documentos em falta, rejeitados, expirados ou cancelados devem bloqueá-la.

## 8. Criar candidatura

- [ ] Voltar ao portal e abrir um concurso com candidaturas abertas.
- [ ] Selecionar “Iniciar candidatura”.
- [ ] Confirmar as verificações:
  - concurso aberto;
  - adesão finalizada;
  - agregado e requerente;
  - rendimentos completos;
  - situação habitacional;
  - inexistência de candidatura ativa duplicada.
- [ ] Criar o rascunho.
- [ ] Confirmar que aparece em “As minhas candidaturas” com estado “Rascunho”.
- [ ] Tentar iniciar outra candidatura ao mesmo concurso.
- [ ] Confirmar que a candidatura ativa duplicada é bloqueada.

## 9. Rever e submeter

- [ ] Abrir o rascunho e selecionar “Rever e submeter”.
- [ ] Confirmar o resumo de dados pessoais, agregado, rendimentos e documentos.
- [ ] Confirmar que é apresentada a mensagem de que o motor de elegibilidade ainda não está implementado.
- [ ] Tentar submeter sem aceitar as declarações.
- [ ] Confirmar os erros de validação.
- [ ] Aceitar as cinco declarações obrigatórias.
- [ ] Submeter a candidatura.

## 10. Resultado da submissão

- [ ] Confirmar o estado “Submetida”.
- [ ] Confirmar número semelhante a:

```text
CAND-2026-CODIGO-CONCURSO-000001
```

- [ ] Confirmar data e hora de submissão.
- [ ] Confirmar o comprovativo HTML.
- [ ] Abrir a versão para impressão.
- [ ] Confirmar que a edição direta deixou de estar disponível.
- [ ] Confirmar que o comprovativo não apresenta paths internos.

## 11. Teste de isolamento

- [ ] Criar uma segunda conta candidata fictícia.
- [ ] Copiar o URL da candidatura da primeira conta.
- [ ] Tentar abrir esse URL com a segunda conta.
- [ ] Confirmar resposta `403 Forbidden`.
- [ ] Confirmar que a segunda conta não vê a candidatura na sua listagem.

## 12. Backoffice

- [ ] Entrar com uma conta administrativa local.
- [ ] Abrir:

```text
http://127.0.0.1:8001/backoffice/applications
```

- [ ] Confirmar que a candidatura submetida aparece na lista.
- [ ] Testar filtros por estado, concurso, programa e número.
- [ ] Abrir o detalhe administrativo.
- [ ] Confirmar dados, documentos, declarações, snapshots e histórico.
- [ ] Confirmar que esta sprint disponibiliza apenas consulta administrativa.

## 13. Desistência

- [ ] Voltar à conta candidata.
- [ ] Abrir uma candidatura em rascunho ou submetida.
- [ ] Registar a desistência com motivo fictício.
- [ ] Confirmar estado “Desistida”.
- [ ] Confirmar que a candidatura deixa de poder ser editada ou submetida.

## 14. Validação automatizada

- [ ] Executar:

```bash
php artisan route:list
php artisan test
npm run build
./vendor/bin/pint
```

Resultado de referência da Sprint 8:

```text
170 rotas
78 testes
455 asserções
Build Vite aprovado
Pint aprovado
```

## 15. Responsividade e acessibilidade base

- [ ] Testar desktop, aproximadamente `1280x720`.
- [ ] Testar mobile, aproximadamente `390x844`.
- [ ] Confirmar ausência de scroll horizontal incoerente.
- [ ] Confirmar que botões, links e checkboxes são utilizáveis por teclado.
- [ ] Confirmar labels nos campos.
- [ ] Confirmar mensagens de erro junto dos formulários.
- [ ] Confirmar que textos não ficam sobrepostos.

## 16. Critério final

A validação local fica aprovada quando:

- [ ] candidatura válida pode ser criada, revista e submetida;
- [ ] número e comprovativo são gerados;
- [ ] documentos em falta ou rejeitados bloqueiam a submissão;
- [ ] edição posterior à submissão é bloqueada;
- [ ] candidatos não acedem a processos alheios;
- [ ] backoffice autorizado consulta o processo;
- [ ] não existem erros inesperados no browser ou no terminal;
- [ ] todos os testes automatizados passam.

## 17. Encerramento

- [ ] No terminal do servidor, pressionar `Ctrl+C`.
- [ ] Remover contas e documentos fictícios se a base local for reutilizada.
- [ ] Não transportar dados de demonstração para staging ou produção.
