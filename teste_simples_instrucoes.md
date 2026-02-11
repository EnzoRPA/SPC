# Testando se CPF_CNPJ é detectado

O sistema detecta CPF se a coluna tiver:
```
CPF
CNPJ
CPF_CNPJ
CPF/CNPJ
```

Na sua planilha você tem:
- `CPF_CNPJ` ✓ DEVERIA ser detectado
- `CPF_CNPJ_PL_CONTRATANTE` ✓ DEVERIA ser detectado

Ambas têm "CPF" no nome, então DEVERIAM funcionar.

## Próximo passo

Preciso que você faça um teste simples:

1. Crie uma planilha nova com APENAS 3 colunas:

```
CONTRATO | NOME         | CPF
1987     | João Silva   | 12345678900
```

2. Salve como `teste_simples.xlsx`

3. Importe via item 6

4. Me diga se funcionou

Se funcionar, o problema está em alguma coisa específica da sua planilha original (formato de dados, colunas extras, etc.)
