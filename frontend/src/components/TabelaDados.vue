<template>
  <div class="tabela-wrapper">
    <div v-if="carregando" class="tabela-carregando">Carregando...</div>

    <div v-else-if="!dados || dados.length === 0" class="tabela-vazia">
      Nenhum registro encontrado.
    </div>

    <table v-else class="tabela">
      <thead>
        <tr>
          <th v-for="col in colunas" :key="col.chave">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(linha, i) in dados" :key="linha.id || i">
          <td v-for="col in colunas" :key="col.chave">
            <slot :name="`celula-${col.chave}`" :valor="linha[col.chave]" :linha="linha">
              {{ linha[col.chave] ?? '—' }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  colunas:    { type: Array,   required: true },
  dados:      { type: Array,   default: () => [] },
  carregando: { type: Boolean, default: false },
})
</script>

<style scoped>
.tabela-wrapper { overflow-x: auto; margin-top: 1rem; }
.tabela { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.tabela th, .tabela td { padding: 0.75rem 1rem; text-align: left; font-size: 0.9rem; }
.tabela th { background: #1a2e4a; color: #fff; font-weight: 600; }
.tabela tbody tr:nth-child(even) { background: #f8f9fb; }
.tabela tbody tr:hover { background: #eef2f7; }
.tabela-carregando, .tabela-vazia { padding: 2rem; text-align: center; color: #888; background: #fff; border-radius: 8px; }
</style>
