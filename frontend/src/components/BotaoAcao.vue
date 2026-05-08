<template>
  <button
    class="botao"
    :class="`botao-${variante}`"
    :type="tipo"
    :disabled="desabilitado"
    @click="$emit('click')"
  >
    {{ label }}
  </button>
</template>

<script setup>
defineProps({
  label:       { type: String,  required: true },
  variante:    { type: String,  default: 'primario' },
  tipo:        { type: String,  default: 'button' },
  desabilitado: { type: Boolean, default: false },
})
defineEmits(['click'])
</script>

<style scoped>
.botao { padding: 0.45rem 1rem; border: none; border-radius: 5px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: opacity .2s; }
.botao:disabled { opacity: .5; cursor: not-allowed; }
.botao-primario   { background: #1a2e4a; color: #fff; }
.botao-secundario { background: #6c757d; color: #fff; }
.botao-aviso      { background: #f0a500; color: #fff; }
.botao-perigo     { background: #dc3545; color: #fff; }
.botao-sucesso    { background: #28a745; color: #fff; }
.botao:hover:not(:disabled) { opacity: .85; }
</style>
