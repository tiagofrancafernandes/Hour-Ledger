# Objetivo

Quero que você faça uma **auditoria arquitetural**, não uma documentação.

Sua missão é verificar se a implementação atual realmente segue uma das decisões arquiteturais mais importantes do projeto.

A hipótese a ser validada é:

> **Aluno e instrutor são identidades globais (Users). O relacionamento entre eles pertence ao domínio da aplicação (InstructorStudentLink/Invitation/etc.) e não ao modelo de identidade.**

Não quero que você simplesmente confirme essa hipótese.

Quero que você tente **provar que ela está errada**.

Se não conseguir encontrar violações, explique por que ela realmente foi respeitada.

---

# O que analisar

Analise todo o código do projeto.

Principalmente:

* Models
* Migrations
* Controllers
* Policies
* Services
* Traits
* Middlewares
* Requests
* Frontend
* Stores
* Composables
* Documentação arquitetural

---

# Verificações obrigatórias

## 1. Modelo de identidade

Verifique se:

* User representa apenas identidade global.
* User não contém regras específicas de instrutor.
* User não contém regras específicas de aluno.
* User pode existir sem ser instrutor nem aluno.
* A identidade permanece independente do domínio.

Explique se isso realmente acontece.

---

## 2. Relacionamento aluno × instrutor

Verifique se:

o relacionamento realmente é representado por entidades próprias, como:

* InstructorStudentLink
* Invitation
* outras relacionadas

e não por colunas como:

* instructor_id em users
* student_id em users
* owner_id em users
* parent_id em users

ou qualquer outro acoplamento semelhante.

---

## 3. Acoplamentos indevidos

Procure qualquer evidência de que User esteja assumindo responsabilidades de domínio.

Exemplos:

* lógica de instrutor dentro de User
* lógica de aluno dentro de User
* relacionamentos excessivos
* métodos que deveriam pertencer ao domínio
* regras específicas do Drive dentro da identidade global

Liste todos os casos encontrados.

---

## 4. Convites

Verifique se Invitation realmente representa um processo de domínio.

Ou se acabou assumindo responsabilidades que deveriam estar em User.

---

## 5. InstructorStudentLink

Verifique se essa entidade realmente representa o relacionamento.

Analise:

* responsabilidades
* atributos
* regras
* estados
* ciclo de vida

Avalie se ela está modelada corretamente.

---

## 6. Multi-tenancy

Verifique se essa decisão arquitetural continua válida quando existe multi-tenancy.

Responda:

* um usuário continua sendo global?
* o relacionamento continua pertencendo ao tenant?
* existe algum ponto onde identidade e tenant ficaram acoplados?

---

## 7. Wallet e Ledger

Verifique se Wallet continua independente da identidade.

Confirme se:

User
↓
participa de uma Wallet

ou

Wallet
↓
pertence ao domínio

e não à identidade.

---

## 8. Fronteiras do domínio

Verifique se os boundaries continuam coerentes.

Explique se existe alguma responsabilidade colocada no módulo errado.

---

## 9. Violações arquiteturais

Liste absolutamente tudo que encontrar.

Classifique cada item como:

* 🔴 Violação grave
* 🟡 Inconsistência
* 🔵 Oportunidade de melhoria
* ✅ Correto

---

## 10. Conclusão

Responda objetivamente:

* A arquitetura planejada foi realmente respeitada?
* Onde ela foi quebrada?
* Onde ela ficou melhor do que o planejado?
* Onde ainda existe acoplamento desnecessário?
* O domínio continua consistente?
* Há alguma decisão arquitetural que você mudaria hoje?

---

# Importante

Não presuma que a arquitetura está correta.

Considere que ela pode estar errada e tente encontrar evidências disso.

Sempre que afirmar que algo está correto, cite exatamente quais arquivos, classes, modelos ou migrations sustentam essa conclusão.

Prefira uma análise crítica em vez de uma análise descritiva.

Se a arquitetura realmente estiver consistente, explique por que ela resistiu à auditoria.
