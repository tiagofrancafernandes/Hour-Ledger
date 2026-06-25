# Hour Ledger

## Visão Geral

Hour Ledger é uma plataforma de gestão transacional baseada em Ledger.

O primeiro produto é o HL Drive.

Hour Ledger não nasceu para ser apenas um sistema de gestão de horas.

Seu propósito é fornecer uma plataforma para gerenciamento de recursos transacionais representados por unidades mensuráveis e auditáveis, utilizando um modelo baseado em Ledger.

Esses recursos podem representar horas, créditos, bônus, consumo, ajustes ou qualquer outro ativo controlado por movimentações.

O primeiro produto dessa plataforma é o **Hour Ledger Drive**.

Todo o desenvolvimento atual tem como objetivo validar esse domínio através de um produto real.

---

# O problema que estamos resolvendo

Instrutores autônomos normalmente controlam seus alunos utilizando uma combinação de:

* planilhas
* aplicativos de agenda
* anotações
* mensagens
* controle manual de pagamentos
* controle manual das horas restantes

Esse processo possui diversos problemas:

* falta de rastreabilidade
* perda de histórico
* erros de cálculo
* pouca transparência para o aluno
* dificuldade para controlar créditos
* dificuldade para auditar movimentações

O Hour Ledger Drive busca resolver esse problema utilizando um modelo transacional baseado em Ledger.

---

# O Hour Ledger Drive

O HL Drive é o primeiro produto do ecossistema Hour Ledger.

Seu objetivo é atender inicialmente instrutores autônomos.

A primeira versão deve resolver completamente o fluxo operacional de um único instrutor antes de qualquer expansão para organizações maiores ou novos produtos.

O Drive não existe para validar escalabilidade.

Ele existe para validar o domínio.

---

# Objetivo da V1

A primeira versão deve permitir que um instrutor consiga operar seu negócio diariamente utilizando exclusivamente o sistema.

Isso significa que o sistema precisa oferecer suporte completo aos seguintes fluxos:

* autenticação
* cadastro de alunos
* convite entre aluno e instrutor
* aceite do convite
* gerenciamento dos vínculos
* criação de pacotes
* aquisição de créditos
* consumo de horas
* agendamento de aulas
* histórico das movimentações
* auditoria das operações
* gerenciamento da carteira de horas
* gerenciamento básico da agenda

Se esses fluxos estiverem completos, a V1 cumpriu seu objetivo.

---

# O que NÃO faz parte da V1

A primeira versão não pretende resolver todos os problemas do mercado.

Ela não deve ser otimizada para:

* franquias
* autoescolas
* múltiplas empresas
* marketplace
* milhares de usuários simultâneos
* integrações complexas
* microsserviços
* alta distribuição
* arquitetura orientada a eventos

Nada disso deve influenciar decisões atuais.

Toda decisão deve favorecer a entrega de um produto excelente para o primeiro cliente.

---

# O papel do HL Drive

O Drive é muito mais do que um produto.

Ele é o primeiro consumidor da plataforma.

Todas as abstrações existentes precisam ser justificadas pelo Drive.

Se o Drive ainda não precisou reutilizar determinado comportamento, provavelmente esse comportamento ainda não pertence ao Core.

---

# O HL Core

O HL Core representa o conjunto de capacidades reutilizáveis entre diferentes produtos do ecossistema Hour Ledger.

O Core não conhece regras específicas do domínio de direção.

Ele fornece apenas recursos genéricos.

Exemplos:

* autenticação
* autorização
* identidade global
* multi-tenancy
* internacionalização
* notificações
* preferências do usuário
* auditoria
* Wallet
* Ledger
* políticas de carteira
* movimentações
* controle transacional
* controle de saldo derivado
* convites genéricos
* gerenciamento de contexto
* logs de atividades

O Core nunca deve depender do Drive.

O Drive sempre depende do Core.

---

# Princípios Arquiteturais

Toda evolução do projeto deve respeitar os seguintes princípios.

## O domínio vem antes da arquitetura.

A arquitetura existe para servir o domínio.

Nunca o contrário.

---

## O Core nasce da reutilização.

Não criamos módulos genéricos esperando reutilização futura.

Eles somente passam a existir quando dois consumidores reais justificarem sua existência.

---

## Simplicidade é prioridade.

Sempre preferimos uma solução simples, compreensível e evolutiva.

Arquiteturas sofisticadas somente serão adotadas quando resolverem problemas reais.

---

## O Ledger é a fonte da verdade.

O saldo nunca representa a verdade do sistema.

O saldo é apenas uma projeção das movimentações registradas no Ledger.

---

## Identidade é diferente de domínio.

Usuários representam identidades globais.

Relacionamentos, permissões e regras de negócio pertencem ao domínio.

---

## Produtos validam o Core.

O HL Drive existe para validar a modelagem do Core.

O Core não deve antecipar necessidades de produtos que ainda não existem.

---

# Estado Atual do Projeto

Neste momento o projeto encontra-se em fase de validação arquitetural.

O objetivo principal não é expandir funcionalidades indiscriminadamente.

O objetivo é confirmar que a arquitetura atual suporta um produto real sem necessidade de novas abstrações.

Por esse motivo o projeto encontra-se em **Architecture Freeze**.

Durante essa fase:

* novos produtos não serão iniciados;
* novos packages não serão criados;
* grandes reorganizações arquiteturais não serão realizadas;
* abstrações preventivas não serão introduzidas.

O foco é estabilizar o domínio e concluir o HL Drive.

---

# O futuro

Somente após a conclusão da primeira versão do HL Drive será avaliada a extração definitiva do HL Core como plataforma reutilizável.

Novos produtos somente deverão ser iniciados quando a reutilização deixar de ser uma hipótese e passar a ser uma necessidade comprovada pelo uso do primeiro produto.
