<template>
  <div class="equipe-page">
    <header class="equipe-header">
      <div>
        <h1>Equipe de campanha</h1>
        <p>Cadastre os membros e acompanhe o mapa da forca por area.</p>
      </div>
      <button class="btn-novo-membro" @click="abrirModalCadastro">Novo membro</button>
    </header>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="kpi-grid">
      <article class="kpi-card">
        <span>Total de membros</span>
        <strong>{{ store.lideres.length }}</strong>
      </article>
      <article class="kpi-card" v-for="area in areasEquipe" :key="`kpi-${area.id}`">
        <span>{{ area.label }}</span>
        <strong>{{ membrosPorArea[area.id]?.length || 0 }}</strong>
      </article>
    </section>

    <section class="filtro-card">
      <input v-model="busca" type="text" placeholder="Buscar membro por nome ou bairro..." @input="pesquisar" />
    </section>

    <section class="mapa-grid">
      <article class="mapa-card" v-for="area in areasEquipe" :key="area.id">
        <div class="mapa-card-topo">
          <h2>{{ area.label }}</h2>
          <span>{{ membrosPorArea[area.id]?.length || 0 }} membro(s)</span>
        </div>

        <ul v-if="(membrosPorArea[area.id] || []).length" class="membros-lista">
          <li v-for="membro in membrosPorArea[area.id]" :key="`${area.id}-${membro.id}`">
            <div>
              <strong>{{ membro.nome }}</strong>
              <small>{{ membro.equipe_funcao || 'Funcao nao definida' }}</small>
            </div>
            <button class="btn-link" @click="editarCategoria(membro)">Editar</button>
          </li>
        </ul>

        <p v-else class="vazio">Sem membros nesta area.</p>
      </article>
    </section>

    <section class="bloco-card">
      <h3>Membros sem categoria</h3>
      <div v-if="membrosSemCategoria.length" class="sem-categoria-lista">
        <div class="sem-categoria-item" v-for="membro in membrosSemCategoria" :key="`sem-${membro.id}`">
          <div>
            <strong>{{ membro.nome }}</strong>
            <small>{{ membro.bairro || 'Sem bairro' }}</small>
          </div>
          <button class="btn-link" @click="editarCategoria(membro)">Classificar</button>
        </div>
      </div>
      <p v-else class="vazio">Todos os membros estao categorizados.</p>
    </section>

    <div v-if="mostrarModal" class="modal-overlay" @click.self="fecharModal">
      <article class="modal-card">
        <h3>{{ editandoId ? 'Editar membro da equipe' : 'Novo membro da equipe' }}</h3>
        <form @submit.prevent="salvarMembro">
          <input v-model.trim="form.nome" type="text" placeholder="Nome" required />
          <input v-model.trim="form.cpf" type="text" placeholder="CPF (somente numeros)" :required="!editandoId" />
          <input v-model.trim="form.telefone" type="text" placeholder="Telefone" />
          <input v-model.trim="form.bairro" type="text" placeholder="Bairro" />
          <select v-model="form.equipe_area" required>
            <option value="">Selecione a area da equipe</option>
            <option v-for="area in areasEquipe" :key="`area-${area.id}`" :value="area.id">{{ area.label }}</option>
          </select>
          <select v-model="form.equipe_funcao" required>
            <option value="">Selecione a funcao</option>
            <option v-for="funcao in funcoesPorAreaAtiva" :key="`funcao-${funcao}`" :value="funcao">{{ funcao }}</option>
          </select>
          <input v-if="mostrarSalario" v-model.number="form.salario_mensal" type="number" min="0.01" step="0.01" placeholder="Salario mensal" :required="!editandoId" />
          <textarea v-model.trim="form.observacoes" rows="2" placeholder="Observacoes"></textarea>

          <div class="modal-acoes">
            <button type="button" class="btn-secundario" @click="fecharModal">Cancelar</button>
            <button type="submit" class="btn-primario">{{ editandoId ? 'Salvar ajustes' : 'Cadastrar membro' }}</button>
          </div>
        </form>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useLiderStore } from '@/stores/liderStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const store = useLiderStore()
const authStore = useAuthStore()
const busca = ref('')
const mostrarModal = ref(false)
const editandoId = ref(null)

const areasEquipe = [
  {
    id: 'direcao_estrategia',
    label: 'Direcao e estrategia',
    funcoes: [
      'Coordenador geral de campanha',
      'Coordenador politico',
      'Coordenador financeiro',
      'Coordenador juridico',
    ],
  },
  {
    id: 'financeiro_juridico',
    label: 'Financeiro e juridico',
    funcoes: ['Contador', 'Advogado eleitoral', 'Tesoureiro', 'Equipe de prestacao de contas'],
  },
  {
    id: 'marketing_comunicacao',
    label: 'Marketing e comunicacao',
    funcoes: [
      'Coordenador de marketing',
      'Social media',
      'Designer',
      'Videomaker',
      'Copywriter',
      'Gestor de trafego',
      'Assessoria de imprensa',
    ],
  },
  {
    id: 'operacao_rua',
    label: 'Operacao de rua',
    funcoes: [
      'Coordenador de mobilizacao',
      'Coordenador regional',
      'Lider de equipe',
      'Cabo eleitoral',
      'Equipe de eventos',
    ],
  },
  {
    id: 'logistica',
    label: 'Logistica',
    funcoes: [
      'Coordenador logistico',
      'Motorista',
      'Controle de combustivel',
      'Distribuicao de material',
      'Almoxarifado',
    ],
  },
  {
    id: 'agenda_apoio',
    label: 'Agenda e apoio direto',
    funcoes: ['Assessor pessoal', 'Equipe de agenda', 'Recepcao e organizacao de visitas'],
  },
]

const form = reactive({
  nome: '',
  cpf: '',
  telefone: '',
  bairro: '',
  equipe_area: '',
  equipe_funcao: '',
  salario_mensal: null,
  observacoes: '',
})

const mostrarSalario = computed(() => authStore.usuario?.perfil === 'admin')

const membrosPorArea = computed(() => {
  const agrupado = Object.fromEntries(areasEquipe.map((area) => [area.id, []]))

  for (const membro of store.lideres) {
    const area = String(membro.equipe_area || '')
    if (agrupado[area]) {
      agrupado[area].push(membro)
    }
  }

  for (const area of areasEquipe) {
    agrupado[area.id].sort((a, b) => String(a.nome || '').localeCompare(String(b.nome || '')))
  }

  return agrupado
})

const membrosSemCategoria = computed(() => {
  return (store.lideres || [])
    .filter((membro) => !membro.equipe_area)
    .sort((a, b) => String(a.nome || '').localeCompare(String(b.nome || '')))
})

const funcoesPorAreaAtiva = computed(() => {
  const area = areasEquipe.find((item) => item.id === form.equipe_area)
  return area?.funcoes || []
})

onMounted(() => carregarMembros())

function carregarMembros() {
  store.carregarLideres(1, 100, busca.value)
}

function pesquisar() {
  store.carregarLideres(1, 100, busca.value)
}

function abrirModalCadastro() {
  resetForm()
  editandoId.value = null
  mostrarModal.value = true
}

function editarCategoria(membro) {
  editandoId.value = membro.id
  form.nome = membro.nome || ''
  form.cpf = ''
  form.telefone = membro.telefone || ''
  form.bairro = membro.bairro || ''
  form.equipe_area = membro.equipe_area || ''
  form.equipe_funcao = membro.equipe_funcao || ''
  form.salario_mensal = membro.salario_mensal ? Number(membro.salario_mensal) : null
  form.observacoes = membro.observacoes || ''
  mostrarModal.value = true
}

async function salvarMembro() {
  if (!editandoId.value) {
    const payload = {
      nome: form.nome,
      cpf: form.cpf,
      telefone: form.telefone || null,
      bairro: form.bairro || null,
      equipe_area: form.equipe_area,
      equipe_funcao: form.equipe_funcao,
      votos_estimados: 0,
      observacoes: form.observacoes || null,
      status: true,
    }

    if (mostrarSalario.value) {
      payload.salario_mensal = form.salario_mensal
    }

    await store.cadastrarLider(payload)
  } else {
    const payload = {
      nome: form.nome,
      telefone: form.telefone || null,
      bairro: form.bairro || null,
      equipe_area: form.equipe_area,
      equipe_funcao: form.equipe_funcao,
      observacoes: form.observacoes || null,
    }

    if (mostrarSalario.value && form.salario_mensal) {
      payload.salario_mensal = form.salario_mensal
    }

    await store.atualizarLider(editandoId.value, payload)
  }

  fecharModal()
  await carregarMembros()
}

function fecharModal() {
  mostrarModal.value = false
  editandoId.value = null
  resetForm()
}

function resetForm() {
  form.nome = ''
  form.cpf = ''
  form.telefone = ''
  form.bairro = ''
  form.equipe_area = ''
  form.equipe_funcao = ''
  form.salario_mensal = null
  form.observacoes = ''
}
</script>

<style scoped>
.equipe-page { display: flex; flex-direction: column; gap: 1rem; }
.equipe-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.8rem; }
.equipe-header h1 { color: #0f172a; font-size: 1.45rem; }
.equipe-header p { color: #64748b; font-size: 0.88rem; margin-top: 0.2rem; }

.btn-novo-membro { border: none; border-radius: 10px; padding: 0.6rem 0.9rem; background: #16a34a; color: #fff; font-weight: 800; cursor: pointer; }
.btn-novo-membro:hover { background: #15803d; }

.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; }
.kpi-card { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 0.7rem; display: flex; flex-direction: column; gap: 0.2rem; }
.kpi-card span { font-size: 0.74rem; color: #64748b; text-transform: uppercase; font-weight: 700; }
.kpi-card strong { font-size: 1.15rem; color: #0f172a; }

.filtro-card { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 0.65rem; }
.filtro-card input { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.55rem 0.65rem; }

.mapa-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.8rem; }
.mapa-card { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 0.75rem; }
.mapa-card-topo { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.mapa-card-topo h2 { font-size: 0.95rem; color: #0f172a; }
.mapa-card-topo span { font-size: 0.78rem; color: #64748b; }

.membros-lista { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.membros-lista li { border: 1px solid #f1f5f9; border-radius: 10px; padding: 0.5rem; display: flex; justify-content: space-between; gap: 0.5rem; align-items: center; }
.membros-lista strong { display: block; color: #0f172a; font-size: 0.86rem; }
.membros-lista small { color: #64748b; font-size: 0.76rem; }

.bloco-card { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 0.75rem; }
.bloco-card h3 { color: #0f172a; margin-bottom: 0.5rem; }
.sem-categoria-lista { display: flex; flex-direction: column; gap: 0.45rem; }
.sem-categoria-item { border: 1px solid #f1f5f9; border-radius: 10px; padding: 0.45rem; display: flex; align-items: center; justify-content: space-between; }
.sem-categoria-item small { color: #64748b; }
.vazio { color: #64748b; font-size: 0.84rem; }

.btn-link { border: none; background: transparent; color: #1d4ed8; cursor: pointer; font-weight: 700; }

.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 1000; }
.modal-card { width: 100%; max-width: 520px; border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; padding: 0.9rem; }
.modal-card h3 { margin-bottom: 0.7rem; color: #0f172a; }
.modal-card form { display: flex; flex-direction: column; gap: 0.55rem; }
.modal-card input, .modal-card select, .modal-card textarea { border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.7rem; font-family: inherit; }
.modal-acoes { display: flex; justify-content: flex-end; gap: 0.45rem; margin-top: 0.3rem; }
.btn-secundario, .btn-primario { border: none; border-radius: 10px; padding: 0.55rem 0.8rem; font-weight: 700; cursor: pointer; }
.btn-secundario { background: #e2e8f0; color: #0f172a; }
.btn-primario { background: #0f172a; color: #fff; }

@media (max-width: 1100px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .mapa-grid { grid-template-columns: 1fr; }
}
</style>
