<template>
  <div class="lista-page">
    <!-- Cabeçalho -->
    <div class="lista-header">
      <div class="lista-header-texto">
        <h1 class="lista-titulo">Líderes</h1>
        <p class="lista-subtitulo">Gerencie os líderes políticos cadastrados no sistema.</p>
      </div>
      <button class="btn-novo" @click="$router.push({ name: 'lideres-novo' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Novo Líder
      </button>
    </div>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <!-- Barra de busca -->
    <div class="lista-toolbar">
      <div class="busca-wrapper">
        <svg class="busca-icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input v-model="busca" type="text" class="busca-input" placeholder="Buscar por nome ou bairro..." @input="pesquisar" />
      </div>
      <span v-if="store.paginacao?.total" class="lista-contagem">
        {{ store.paginacao.total }} registro{{ store.paginacao.total !== 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Tabela -->
    <div class="lista-card">
      <div v-if="store.carregando" class="lista-vazia">
        <div class="spinner-container">
          <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
          Carregando...
        </div>
      </div>

      <div v-else-if="!store.lideres.length" class="lista-vazia">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="23" y1="11" x2="17" y2="11"/>
        </svg>
        <p>Nenhum líder encontrado.</p>
        <button class="btn-novo btn-novo--outline" @click="$router.push({ name: 'lideres-novo' })">Cadastrar primeiro líder</button>
      </div>

      <table v-else class="tabela">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Bairro</th>
            <th>Votos Est.</th>
            <th>Cadastrado por</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lider in store.lideres" :key="lider.id" class="tabela-linha">
            <td class="td-nome">{{ lider.nome }}</td>
            <td class="td-bairro">{{ lider.bairro || '—' }}</td>
            <td class="td-votos">{{ lider.votos_estimados?.toLocaleString('pt-BR') ?? '—' }}</td>
            <td class="td-usuario">{{ lider.criado_por_nome || '—' }}</td>
            <td>
              <span :class="lider.status ? 'badge-ativo' : 'badge-inativo'">
                {{ lider.status ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="td-acoes">
              <button class="btn-tabela btn-tabela--ver" @click="ver(lider.id)" title="Ver detalhes">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
              <button class="btn-tabela btn-tabela--editar" @click="editar(lider.id)" title="Editar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button class="btn-tabela btn-tabela--remover" @click="remover(lider.id)" title="Remover">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6"/><path d="M14 11v6"/>
                  <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useLiderStore } from '@/stores/liderStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const roteador = useRouter()
const store    = useLiderStore()
const busca    = ref('')

onMounted(() => store.carregarLideres())

function pesquisar() {
  store.carregarLideres(1, 15, busca.value)
}

function ver(id)    { roteador.push({ name: 'lideres-detalhe', params: { id } }) }
function editar(id) { roteador.push({ name: 'lideres-editar',  params: { id } }) }

async function remover(id) {
  if (!confirm('Confirma a remoção deste líder?')) return
  await store.removerLider(id)
}
</script>

<style scoped>
.lista-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ---- Header ---- */
.lista-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.lista-titulo {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.03em;
}

.lista-subtitulo {
  font-size: 0.85rem;
  color: #94a3b8;
  margin-top: 0.2rem;
}

/* ---- Botão Novo ---- */
.btn-novo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  background: linear-gradient(135deg, #1e3a5f, #1d4ed8);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(29,78,216,0.25);
  white-space: nowrap;
  font-family: inherit;
}

.btn-novo svg {
  width: 16px;
  height: 16px;
}

.btn-novo:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(29,78,216,0.35);
}

.btn-novo--outline {
  background: none;
  box-shadow: none;
  border: 1.5px solid #1d4ed8;
  color: #1d4ed8;
  margin-top: 0.5rem;
}

.btn-novo--outline:hover {
  background: #eff6ff;
  box-shadow: none;
  transform: none;
}

/* ---- Toolbar ---- */
.lista-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.busca-wrapper {
  position: relative;
  flex: 1;
  max-width: 380px;
}

.busca-icone {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  stroke: #94a3b8;
  pointer-events: none;
}

.busca-input {
  width: 100%;
  padding: 0.6rem 0.875rem 0.6rem 2.25rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  font-size: 0.875rem;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  font-family: inherit;
}

.busca-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}

.busca-input::placeholder {
  color: #cbd5e1;
}

.lista-contagem {
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 500;
  white-space: nowrap;
}

/* ---- Card da tabela ---- */
.lista-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 4px 16px rgba(0,0,0,0.05);
  overflow: hidden;
}

/* ---- Estado vazio / carregando ---- */
.lista-vazia {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 4rem 2rem;
  color: #94a3b8;
}

.lista-vazia svg {
  width: 48px;
  height: 48px;
  stroke: #cbd5e1;
}

.lista-vazia p {
  font-size: 0.9rem;
  font-weight: 500;
}

.spinner-container {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  color: #94a3b8;
  font-size: 0.875rem;
}

.spinner {
  width: 20px;
  height: 20px;
  animation: girar 1s linear infinite;
}

@keyframes girar {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* ---- Tabela ---- */
.tabela {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.tabela thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.tabela th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
}

.tabela-linha {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.12s;
}

.tabela-linha:last-child {
  border-bottom: none;
}

.tabela-linha:hover {
  background: #f8fafc;
}

.tabela td {
  padding: 0.875rem 1rem;
  color: #334155;
  vertical-align: middle;
}

.td-nome {
  font-weight: 600;
  color: #0f172a !important;
}

.td-bairro,
.td-usuario {
  color: #64748b !important;
}

.td-votos {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
  color: #1d4ed8 !important;
}

/* ---- Badges ---- */
.badge-ativo,
.badge-inativo {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  border-radius: 100px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.badge-ativo {
  background: #dcfce7;
  color: #16a34a;
}

.badge-inativo {
  background: #fee2e2;
  color: #dc2626;
}

/* ---- Botões da tabela ---- */
.td-acoes {
  display: flex;
  gap: 0.375rem;
  align-items: center;
}

.btn-tabela {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-tabela svg {
  width: 15px;
  height: 15px;
}

.btn-tabela--ver {
  background: #eff6ff;
  color: #2563eb;
}

.btn-tabela--ver:hover {
  background: #dbeafe;
}

.btn-tabela--editar {
  background: #fffbeb;
  color: #d97706;
}

.btn-tabela--editar:hover {
  background: #fef3c7;
}

.btn-tabela--remover {
  background: #fef2f2;
  color: #dc2626;
}

.btn-tabela--remover:hover {
  background: #fee2e2;
}
</style>
