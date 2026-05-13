# WalletPolicy

## Definição

WalletPolicy define capacidades e restrições de uma wallet.

## Políticas previstas

```txt
allow_purchase
allow_transfer
allow_negative_balance
allow_expiration
allow_bonus
allow_manual_credit
```

## Regras

- Políticas pertencem à wallet.
- Políticas devem ser avaliadas antes de movimentações.
- Produtos podem restringir uso conforme contexto.
- O core não deve conhecer regra específica do produto.

## Exemplo

HL Drive pode decidir que uma carteira de aula prática não permite transferência.

Mas a regra genérica `allow_transfer` pertence ao core.
