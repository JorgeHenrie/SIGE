<template>
  <div class="apoiadores-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Base territorial</span>
        <h1 class="hero-titulo">Apoiadores</h1>
        <p class="hero-subtitulo">
          Organize a rede de mobilização por líder, bairro e intenção política com uma leitura rápida do cenário atual.
        </p>
        <div class="hero-tags">
          <span class="hero-tag">{{ formatarNumero(totalRegistros) }} registros filtrados</span>
          <span class="hero-tag">{{ formatarNumero(apoiadoresPagina.length) }} em tela</span>
        </div>
      </div>

      <button class="btn-principal" @click="$router.push({ name: 'apoiadores-novo' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Novo Apoiador
      </button>
    </section>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="resumo-grid">
      <article class="resumo-card resumo-card--neutro">
        <span class="resumo-rotulo">Base carregada</span>
        <strong class="resumo-valor">{{ formatarNumero(apoiadoresPagina.length) }}</strong>
        <span class="resumo-ajuda">Apoiadores exibidos na página atual</span>
      </article>

      <article class="resumo-card resumo-card--apoio">
        <span class="resumo-rotulo">Apoiadores firmes</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.apoiador) }}</strong>
        <span class="resumo-ajuda">Prontos para mobilização direta</span>
      </article>

      <article class="resumo-card resumo-card--indeciso">
        <span class="resumo-rotulo">Em conversão</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.indeciso) }}</strong>
        <span class="resumo-ajuda">Demandam contato e acompanhamento</span>
      </article>

      <article class="resumo-card resumo-card--oposicao">
        <span class="resumo-rotulo">Resistência</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.oposicao) }}</strong>
        <span class="resumo-ajuda">Registros com posicionamento adverso</span>
      </article>
    </section>

    <section class="toolbar-card">
      <div class="busca-wrapper">
        <svg class="busca-icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          v-model="busca"
          type="text"
          class="busca-input"
          placeholder="Buscar por nome, líder ou bairro..."
          @input="pesquisar"
        />
      </div>

      <div class="toolbar-meta">
        <span class="toolbar-chip">{{ formatarNumero(totalRegistros) }} no total</span>
        <span class="toolbar-chip toolbar-chip--muted">{{ formatarNumero(apoiadoresPagina.length) }} visíveis</span>
      </div>
    </section>

    <section class="tabela-card">
      <div v-if="store.carregando" class="estado-card">
        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <p>Atualizando a base de apoiadores...</p>
      </div>

      <div v-else-if="!apoiadoresPagina.length" class="estado-card estado-card--vazio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="19" y1="8" x2="19" y2="14"/>
          <line x1="16" y1="11" x2="22" y2="11"/>
        </svg>
        <h2>Nenhum apoiador encontrado</h2>
        <p>Ajuste a busca ou cadastre um novo contato para começar a mapear a base política.</p>
        <button class="btn-secundario" @click="$router.push({ name: 'apoiadores-novo' })">
          Cadastrar primeiro apoiador
        </button>
      </div>

      <div v-else class="tabela-wrapper">
        <table class="tabela-apoiadores">
          <thead>
            <tr>
              <th>Apoiador</th>
              <th>Líder vinculado</th>
              <th>Bairro</th>
              <th>Status</th>
              <th class="th-acoes">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="apoiador in apoiadoresPagina" :key="apoiador.id">
              <td>
                <div class="pessoa-bloco">
                  <span class="pessoa-avatar">{{ iniciais(apoiador.nome) }}</span>
                  <div>
                    <strong class="pessoa-nome">{{ apoiador.nome }}</strong>
                    <span class="pessoa-meta">{{ apoiador.telefone || 'Telefone não informado' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="vinculo-bloco">
                  <strong>{{ apoiador.lider_nome || 'Sem líder vinculado' }}</strong>
                  <span>{{ apoiador.cadastrado_por_nome || 'Cadastro interno' }}</span>
                </div>
              </td>
              <td class="td-bairro">{{ apoiador.bairro || 'Não informado' }}</td>
              <td>
                <span :class="['status-badge', `status-badge--${apoiador.status_politico || 'indeciso'}`]">
                  {{ labelStatus(apoiador.status_politico) }}
                </span>
              </td>
              <td>
                <div class="acoes-linha">
                  <button class="btn-acao btn-acao--editar" @click="editar(apoiador.id)" title="Editar apoiador">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <button class="btn-acao btn-acao--remover" @click="remover(apoiador.id)" title="Remover apoiador">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/>
                      <path d="M14 11v6"/>
                      <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApoiadorStore } from '@/stores/apoiadorStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const roteador = useRouter()
const store = useApoiadorStore()
const busca = ref('')

const apoiadoresPagina = computed(() => store.apoiadores || [])
const totalRegistros = computed(() => Number(store.paginacao?.total || apoiadoresPagina.value.length || 0))
const resumoStatus = computed(() => {
  return apoiadoresPagina.value.reduce((acc, apoiador) => {
    const chave = apoiador.status_politico || 'indeciso'
    acc[chave] = (acc[chave] || 0) + 1
    return acc
  }, { apoiador: 0, indeciso: 0, oposicao: 0 })
})

const mapaStatus = {
  apoiador: 'Apoiador',
  indeciso: 'Indeciso',
  oposicao: 'Oposição',
}

onMounted(() => store.carregarApoiadores())

function pesquisar() {
  store.carregarApoiadores(1, 15, busca.value)
}

function editar(id) {
  roteador.push({ name: 'apoiadores-editar', params: { id } })
}

function labelStatus(valor) {
  return mapaStatus[valor] || 'Indefinido'
}

function formatarNumero(valor) {
  return Number(valor || 0).toLocaleString('pt-BR')
}

function iniciais(nome) {
  if (!nome) return 'AP'

  return nome
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join('')
}

async function remover(id) {
  if (!confirm('Confirma a remoção deste apoiador?')) return
  await store.removerApoiador(id)
}
</script>

<style scoped>
.apoiadores-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.25rem;
  padding: 1.6rem 1.75rem;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(16, 185, 129, 0.2), transparent 30%),
    linear-gradient(135deg, #0f172a 0%, #12354d 52%, #0f766e 100%);
  color: #f8fafc;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
}

.hero-texto {
  max-width: 640px;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-titulo {
  margin-top: 0.95rem;
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-subtitulo {
  margin-top: 0.55rem;
  font-size: 0.96rem;
  line-height: 1.7;
  color: rgba(248, 250, 252, 0.82);
}

.hero-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1rem;
}

.hero-tag {
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
  font-size: 0.82rem;
  font-weight: 600;
}

.btn-principal,
.btn-secundario {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border: none;
  border-radius: 12px;
  padding: 0.85rem 1.2rem;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
  font-family: inherit;
}

.btn-principal {
  color: #0f172a;
  background: linear-gradient(135deg, #f8fafc 0%, #d1fae5 100%);
  box-shadow: 0 16px 30px rgba(6, 95, 70, 0.18);
}

.btn-principal:hover,
.btn-secundario:hover {
  transform: translateY(-1px);
}

.btn-principal svg {
  width: 16px;
  height: 16px;
}

.btn-secundario {
  background: #0f172a;
  color: #f8fafc;
}

.resumo-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.resumo-card {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 1.2rem 1.25rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid rgba(148, 163, 184, 0.14);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.resumo-card::after {
  content: '';
  position: absolute;
  inset: auto -24px -24px auto;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.25);
}

.resumo-card--neutro {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.resumo-card--apoio {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.resumo-card--indeciso {
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
}

.resumo-card--oposicao {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.resumo-rotulo {
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.resumo-valor {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #0f172a;
}

.resumo-ajuda {
  max-width: 18ch;
  font-size: 0.82rem;
  line-height: 1.5;
  color: #475569;
}

.toolbar-card,
.tabela-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.toolbar-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
}

.busca-wrapper {
  position: relative;
  flex: 1;
  max-width: 460px;
}

.busca-icone {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  width: 18px;
  height: 18px;
  color: #94a3b8;
  transform: translateY(-50%);
  pointer-events: none;
}

.busca-input {
  width: 100%;
  padding: 0.8rem 1rem 0.8rem 2.8rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
  color: #0f172a;
  font-size: 0.92rem;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
  outline: none;
  font-family: inherit;
}

.busca-input:focus {
  background: #fff;
  border-color: #14b8a6;
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
}

.toolbar-meta {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.65rem;
}

.toolbar-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 0.85rem;
  border-radius: 999px;
  background: #e6fffb;
  color: #0f766e;
  font-size: 0.8rem;
  font-weight: 700;
}

.toolbar-chip--muted {
  background: #f1f5f9;
  color: #64748b;
}

.estado-card {
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  padding: 2.5rem;
  color: #64748b;
  text-align: center;
}

.estado-card h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.estado-card p {
  max-width: 44ch;
  line-height: 1.7;
}

.estado-card--vazio svg {
  width: 54px;
  height: 54px;
  color: #94a3b8;
}

.spinner {
  width: 24px;
  height: 24px;
  animation: girar 1s linear infinite;
}

.tabela-wrapper {
  overflow-x: auto;
}

.tabela-apoiadores {
  width: 100%;
  border-collapse: collapse;
}

.tabela-apoiadores thead tr {
  background: #f8fafc;
}

.tabela-apoiadores th {
  padding: 1rem 1.15rem;
  text-align: left;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.tabela-apoiadores td {
  padding: 1rem 1.15rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  color: #334155;
}

.tabela-apoiadores tbody tr {
  transition: background 0.16s ease;
}

.tabela-apoiadores tbody tr:hover {
  background: #f8fafc;
}

.th-acoes {
  text-align: right;
}

.pessoa-bloco,
.vinculo-bloco {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.vinculo-bloco {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.18rem;
}

.pessoa-avatar {
  width: 42px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: linear-gradient(135deg, #0f766e, #14b8a6);
  color: #fff;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  flex-shrink: 0;
  box-shadow: 0 10px 20px rgba(20, 184, 166, 0.2);
}

.pessoa-nome,
.vinculo-bloco strong {
  display: block;
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
}

.pessoa-meta,
.vinculo-bloco span,
.td-bairro {
  font-size: 0.84rem;
  color: #64748b;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 96px;
  padding: 0.42rem 0.78rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.status-badge--apoiador {
  background: #dcfce7;
  color: #15803d;
}

.status-badge--indeciso {
  background: #ffedd5;
  color: #c2410c;
}

.status-badge--oposicao {
  background: #fee2e2;
  color: #b91c1c;
}

.acoes-linha {
  display: flex;
  justify-content: flex-end;
  gap: 0.45rem;
}

.btn-acao {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.14s ease, background 0.14s ease;
}

.btn-acao:hover {
  transform: translateY(-1px);
}

.btn-acao svg {
  width: 16px;
  height: 16px;
}

.btn-acao--editar {
  background: #ecfeff;
  color: #0f766e;
}

.btn-acao--remover {
  background: #fef2f2;
  color: #dc2626;
}

@keyframes girar {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 1024px) {
  .resumo-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .hero-card,
  .toolbar-card {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-principal {
    width: 100%;
  }

  .resumo-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-meta {
    justify-content: flex-start;
  }

  .tabela-apoiadores th,
  .tabela-apoiadores td {
    padding: 0.85rem 0.9rem;
  }
}
</style>
