# Roteirização Inteligente - Exemplos de teste

Os exemplos abaixo usam os endpoints do módulo de roteirização do SIGE.

Observação prática:
- Para teste determinístico sem depender do provider externo de geocoding, a API aceita `local_saida_latitude`, `local_saida_longitude`, `latitude` e `longitude` nas visitas.
- Em produção, o fluxo principal continua sendo informar endereços e deixar o backend resolver/coletar via cache de geocoding.

## 1. Mesmo bairro

Objetivo:
- validar agrupamento por proximidade
- validar economia contra ordem sequencial simples

POST /api/roteiros/sugerir

```json
{
  "lider_id": "SEU_LIDER_ID",
  "data_roteiro": "2026-05-10",
  "local_saida": "Comite central",
  "local_saida_latitude": -5.0892100,
  "local_saida_longitude": -42.8016000,
  "transporte": "carro",
  "raio_cluster_km": 3,
  "visitas": [
    {
      "nome": "Rua das Flores",
      "endereco": "Rua das Flores, Centro",
      "prioridade": "media",
      "latitude": -5.0911000,
      "longitude": -42.8032000
    },
    {
      "nome": "Mercado do Centro",
      "endereco": "Praca do Mercado, Centro",
      "prioridade": "alta",
      "latitude": -5.0923000,
      "longitude": -42.8046000
    },
    {
      "nome": "Esquina da Matriz",
      "endereco": "Rua da Matriz, Centro",
      "prioridade": "baixa",
      "latitude": -5.0904000,
      "longitude": -42.8009000
    }
  ]
}
```

Resultado esperado:
- 1 cluster
- prioridade alta puxando a ordem
- economia positiva em relação à sequência simples

## 2. Bairros distantes

Objetivo:
- validar múltiplos clusters
- validar ordenação entre grupos

POST /api/roteiros/sugerir

```json
{
  "lider_id": "SEU_LIDER_ID",
  "data_roteiro": "2026-05-10",
  "local_saida": "Base de campanha",
  "local_saida_latitude": -5.0892100,
  "local_saida_longitude": -42.8016000,
  "transporte": "carro",
  "raio_cluster_km": 3,
  "visitas": [
    {
      "nome": "Loteamento A",
      "endereco": "Bairro A",
      "prioridade": "alta",
      "latitude": -5.0820000,
      "longitude": -42.7900000
    },
    {
      "nome": "Conjunto A2",
      "endereco": "Bairro A",
      "prioridade": "media",
      "latitude": -5.0805000,
      "longitude": -42.7885000
    },
    {
      "nome": "Residencial B1",
      "endereco": "Bairro B",
      "prioridade": "baixa",
      "latitude": -5.1150000,
      "longitude": -42.8450000
    },
    {
      "nome": "Residencial B2",
      "endereco": "Bairro B",
      "prioridade": "media",
      "latitude": -5.1160000,
      "longitude": -42.8465000
    }
  ]
}
```

Resultado esperado:
- 2 clusters
- cluster com visita alta entrando antes, salvo conflito de horário

## 3. Conflito de horário

Objetivo:
- validar respeito a janelas
- validar logs de decisão

POST /api/roteiros/sugerir

```json
{
  "lider_id": "SEU_LIDER_ID",
  "data_roteiro": "2026-05-10",
  "local_saida": "Comite central",
  "local_saida_latitude": -5.0892100,
  "local_saida_longitude": -42.8016000,
  "transporte": "carro",
  "visitas": [
    {
      "nome": "Cafe da manha com liderancas",
      "endereco": "Centro",
      "prioridade": "media",
      "horario_inicio": "2026-05-10 08:30:00",
      "horario_fim": "2026-05-10 09:10:00",
      "latitude": -5.0913000,
      "longitude": -42.8041000
    },
    {
      "nome": "Visita urgente no bairro vizinho",
      "endereco": "Bairro vizinho",
      "prioridade": "alta",
      "horario_inicio": "2026-05-10 09:00:00",
      "horario_fim": "2026-05-10 09:30:00",
      "latitude": -5.0988000,
      "longitude": -42.8124000
    }
  ]
}
```

Resultado esperado:
- ordem pode contrariar a distância pura por causa da janela
- logs citando janela monitorada

## 4. Prioridades diferentes

Objetivo:
- validar alta > media > baixa quando não houver conflito crítico de horário

POST /api/roteiros/sugerir

```json
{
  "lider_id": "SEU_LIDER_ID",
  "data_roteiro": "2026-05-10",
  "local_saida": "Escritorio regional",
  "local_saida_latitude": -5.0892100,
  "local_saida_longitude": -42.8016000,
  "transporte": "moto",
  "visitas": [
    {
      "nome": "Parada baixa 1",
      "endereco": "Rua 1",
      "prioridade": "baixa",
      "latitude": -5.0900000,
      "longitude": -42.8020000
    },
    {
      "nome": "Parada alta 1",
      "endereco": "Rua 2",
      "prioridade": "alta",
      "latitude": -5.0910000,
      "longitude": -42.8030000
    },
    {
      "nome": "Parada media 1",
      "endereco": "Rua 3",
      "prioridade": "media",
      "latitude": -5.0920000,
      "longitude": -42.8040000
    }
  ]
}
```

Resultado esperado:
- a visita alta tende a ser puxada para frente se o custo extra não for grande

## 5. Desvio relevante

Objetivo:
- validar marcação de parada com aumento acima de 30%

POST /api/roteiros/sugerir

```json
{
  "lider_id": "SEU_LIDER_ID",
  "data_roteiro": "2026-05-10",
  "local_saida": "Base central",
  "local_saida_latitude": -5.0892100,
  "local_saida_longitude": -42.8016000,
  "transporte": "carro",
  "visitas": [
    {
      "nome": "Centro 1",
      "endereco": "Centro",
      "prioridade": "media",
      "latitude": -5.0901000,
      "longitude": -42.8021000
    },
    {
      "nome": "Centro 2",
      "endereco": "Centro",
      "prioridade": "media",
      "latitude": -5.0912000,
      "longitude": -42.8032000
    },
    {
      "nome": "Desvio longo",
      "endereco": "Zona rural distante",
      "prioridade": "baixa",
      "latitude": -5.1800000,
      "longitude": -42.9300000
    }
  ]
}
```

Resultado esperado:
- `desvio_relevante = true` na parada remota
- `motivo_desvio` explicando a elevação percentual

## 6. Reprocessamento dinâmico

Objetivo:
- validar remoção/adição e recálculo automático persistido

### Criar um roteiro

POST /api/roteiros

Use qualquer payload de sugestão anterior.

### Recalcular removendo uma visita

PUT /api/roteiros/{ID}/recalcular

```json
{
  "visitas": [
    {
      "nome": "Centro 1",
      "endereco": "Centro",
      "prioridade": "media",
      "latitude": -5.0901000,
      "longitude": -42.8021000
    },
    {
      "nome": "Centro 2",
      "endereco": "Centro",
      "prioridade": "alta",
      "latitude": -5.0912000,
      "longitude": -42.8032000
    }
  ]
}
```

### Recalcular adicionando uma visita

PUT /api/roteiros/{ID}/recalcular

```json
{
  "visitas": [
    {
      "nome": "Centro 1",
      "endereco": "Centro",
      "prioridade": "media",
      "latitude": -5.0901000,
      "longitude": -42.8021000
    },
    {
      "nome": "Centro 2",
      "endereco": "Centro",
      "prioridade": "alta",
      "latitude": -5.0912000,
      "longitude": -42.8032000
    },
    {
      "nome": "Nova parada inserida no dia",
      "endereco": "Avenida nova",
      "prioridade": "alta",
      "horario_inicio": "2026-05-10 11:30:00",
      "horario_fim": "2026-05-10 12:15:00",
      "latitude": -5.0945000,
      "longitude": -42.8069000
    }
  ]
}
```

Resultado esperado:
- nova ordem sugerida
- novos logs de decisão
- atualização de economia, distância e tempo

## 7. Melhor roteiro do dia

Objetivo:
- validar presença de `sugestao_melhor_roteiro`
- validar resumo textual operacional

Checagens esperadas na resposta:
- `sugestao_melhor_roteiro`
- `logs_decisao`
- `agrupamentos`
- `economia_km`
- `economia_percentual`

## 8. O que conferir em toda resposta

- `visitas` ordenadas por `ordem_sugerida`
- `agrupamentos` com `cluster_id`, `total_visitas` e `prioridade_dominante`
- `distancia_total_km`
- `tempo_total_min`
- `custo_estimado`
- `economia_km`
- `economia_percentual`
- `visitas_desvio`
- `logs_decisao`
- `sugestao_melhor_roteiro`