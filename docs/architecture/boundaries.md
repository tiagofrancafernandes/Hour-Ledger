# Boundaries do Hour Ledger

## Regra principal

O HL Core não conhece produtos específicos.

## Dependências permitidas

```txt
HL Drive -> Core
HL Drive -> Ledger
HL Drive -> Tenancy
HL Drive -> Invitations

HL Consulting -> Core
HL Consulting -> Ledger
HL Consulting -> Tenancy
```

## Dependências proibidas

```txt
Core -> Drive
Ledger -> Drive
Tenancy -> Drive
Core -> Consulting
Ledger -> Consulting
```

## Exemplo correto

Aula finalizada no HL Drive consome saldo chamando uma operação genérica do Ledger.

```txt
Drive LessonCompleted -> Ledger DebitWallet
```

## Exemplo incorreto

Ledger conhecer o conceito de aula.

```txt
Ledger DebitDrivingLessonHour
```
