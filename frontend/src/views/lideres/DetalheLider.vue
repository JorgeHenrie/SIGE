<template>
  <div v-if="store.liderAtual">
    <div class="cabecalho-pagina">
      <h1>{{ store.liderAtual.nome }}</h1>
      <div class="acoes-cabecalho">
        <BotaoAcao label="Editar" variante="aviso" @click="$router.push({ name: 'lideres-editar', params: { id: store.liderAtual.id } })" />
        <BotaoAcao label="Voltar" variante="secundario" @click="$router.push({ name: 'lideres' })" />
      </div>
    </div>

    <div class="card-detalhe">
      <div class="linha-info"><strong>Bairro/Região:</strong> {{ store.liderAtual.bairro || '—' }}</div>
      <div class="linha-info"><strong>Telefone:</strong> {{ store.liderAtual.telefone || '—' }}</div>
      <div class="linha-info"><strong>Votos Estimados:</strong> {{ store.liderAtual.votos_estimados }}</div>
      <div class="linha-info"><strong>Cadastrado por:</strong> {{ store.liderAtual.criado_por_nome || '—' }}</div>
      <div class="linha-info"><strong>Status:</strong> {{ store.liderAtual.status ? 'Ativo' : 'Inativo' }}</div>
      <div class="linha-info"><strong>Observações:</strong> {{ store.liderAtual.observacoes || '—' }}</div>
    </div>

    <div class="secao-apoiadores">
      <div class="cabecalho-secao">
        <h2>Apoiadores Vinculados</h2>
        <BotaoAcao label="Novo Apoiador" variante="primario" @click="novoApoiador" />
      </div>
      <TabelaDados :colunas="colunas" :dados="apoiadores" :carregando="carregandoApoiadores">
        <template #celula-status_politico="{ valor }">
          <span :class="`badge-${valor}`">{{ valor }}</span>
        </template>
      </TabelaDados>
    </div>
  </div>

  <AlertaMensagem v-else-if="store.erro" tipo="erro" :mensagem="store.erro" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLiderStore } from '@/stores/liderStore.js'
import liderServico from '@/services/liderServico.js'
import TabelaDados from '@/components/TabelaDados.vue'
import BotaoAcao from '@/components/BotaoAcao.vue'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota     = useRoute()
const roteador = useRouter()
const store    = useLiderStore()

const apoiadores          = ref([])
const carregandoApoiadores = ref(false)

const colunas = [
  { chave: 'nome',           label: 'Nome' },
  { chave: 'bairro',         label: 'Bairro' },
  { chave: 'status_politico', label: 'Status Político' },
  { chave: 'telefone',       label: 'Telefone' },
]

onMounted(async () => {
  await store.buscarLider(rota.params.id)
  carregarApoiadores()
})

async function carregarApoiadores() {
  carregandoApoiadores.value = true
  try {
    const resposta = await liderServico.listarApoiadores(rota.params.id)
    apoiadores.value = resposta.dados || []
  } finally {
    carregandoApoiadores.value = false
  }
}

function novoApoiador() {
  roteador.push({ name: 'apoiadores-novo', query: { lider_id: rota.params.id } })
}
</script>
