<template>
  <div class="alerta" :class="`alerta-${tipo}`">
    <span class="alerta-icone">{{ icone }}</span>
    <span class="alerta-mensagem">{{ mensagem }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tipo:     { type: String, default: 'info' },
  mensagem: { type: String, required: true },
})

const icone = computed(() => ({
  sucesso: '✓', erro: '✕', aviso: '⚠', info: 'ℹ',
}[props.tipo] || 'ℹ'))
</script>

<style scoped>
.alerta { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
.alerta-sucesso { background: #d4edda; color: #155724; }
.alerta-erro    { background: #f8d7da; color: #721c24; }
.alerta-aviso   { background: #fff3cd; color: #856404; }
.alerta-info    { background: #d1ecf1; color: #0c5460; }
.alerta-icone   { font-weight: 700; }
</style>
