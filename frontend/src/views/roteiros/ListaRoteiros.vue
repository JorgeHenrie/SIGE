<template>
  <div class="roteiros-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Operação de campo</span>
        <h1 class="hero-titulo">Roteirização Inteligente</h1>
        <p class="hero-subtitulo">
          Monte, revise e acompanhe sequências diárias de compromissos com distância, tempo e custo estimado.
        </p>
      </div>

      <button v-if="podeOperar" class="btn-principal" @click="$router.push({ name: 'roteiros-novo' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Novo roteiro
      </button>
    </section>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="resumo-grid">
      <article class="resumo-card resumo-card--neutro">
        <span class="resumo-label">Total no resultado</span>
        <strong class="resumo-valor">{{ formatarNumero(store.paginacao?.total || roteiros.length) }}</strong>
        <span class="resumo-ajuda">Roteiros carregados com o filtro atual</span>
      </article>
      <article class="resumo-card resumo-card--azul">
        <span class="resumo-label">Distância somada</span>
        <strong class="resumo-valor">{{ formatarKm(resumo.distanciaTotal) }}</strong>
        <span class="resumo-ajuda">Quilometragem consolidada dos roteiros listados</span>
      </article>
      <article class="resumo-card resumo-card--verde">
        <span class="resumo-label">Tempo somado</span>
        <strong class="resumo-valor">{{ formatarTempo(resumo.tempoTotal) }}</strong>
        <span class="resumo-ajuda">Tempo total estimado dos roteiros listados</span>
      </article>
      <article class="resumo-card resumo-card--laranja">
        <span class="resumo-label">Custo somado</span>
        <strong class="resumo-valor">{{ formatarMoeda(resumo.custoTotal) }}</strong>
        <span class="resumo-ajuda">Custo estimado consolidado dos roteiros listados</span>
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
          placeholder="Buscar por líder, saída, transporte ou data..."
          @input="carregar"
        />
      </div>
    </section>

    <section class="tabela-card">
      <div v-if="store.carregando" class="estado-card">
        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <p>Atualizando os roteiros planejados...</p>
      </div>

      <div v-else-if="!roteiros.length" class="estado-card estado-card--vazio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 6h18"/>
          <path d="M4 6l2 13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l2-13"/>
          <path d="M9 10h6"/>
          <path d="M9 14h6"/>
        </svg>
        <h2>Nenhum roteiro encontrado</h2>
        <p>Gere uma sequência de compromissos do dia para começar a estimar deslocamento, tempo e custo operacional.</p>
      </div>

      <div v-else class="tabela-wrapper">
        <table class="tabela-roteiros">
          <thead>
            <tr>
              <th>Roteiro</th>
              <th>Transporte</th>
              <th>Métricas</th>
              <th class="th-acoes">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="roteiro in roteiros" :key="roteiro.id">
              <td>
                <div class="roteiro-bloco">
                  <span class="roteiro-avatar">{{ iniciais(roteiro.lider_nome || 'RT') }}</span>
                  <div>
                    <strong>{{ roteiro.lider_nome || 'Sem líder' }}</strong>
                    <span>{{ formatarData(roteiro.data_roteiro) }} • {{ roteiro.local_saida }}</span>
                    <span>{{ roteiro.total_visitas }} visitas • {{ roteiro.status }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="transporte-pill" :class="`transporte-pill--${roteiro.transporte}`">
                  {{ labelTransporte(roteiro.transporte) }}
                </div>
              </td>
              <td>
                <div class="metricas-bloco">
                  <strong>{{ formatarKm(roteiro.distancia_total_km) }}</strong>
                  <span>{{ formatarTempo(roteiro.tempo_total_min) }}</span>
                  <span>{{ formatarMoeda(roteiro.custo_estimado) }}</span>
                </div>
              </td>
              <td>
                <div class="acoes-linha">
                  <button class="btn-acao btn-acao--editar" title="Abrir roteiro" @click="abrir(roteiro.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                  <button v-if="podeOperar" class="btn-acao btn-acao--remover" title="Remover roteiro" @click="remover(roteiro.id)">
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
import { useAuthStore } from '@/stores/authStore.js'
import { useRoteiroStore } from '@/stores/roteiroStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const roteador = useRouter()
const authStore = useAuthStore()
const store = useRoteiroStore()

const busca = ref('')

const roteiros = computed(() => store.roteiros || [])
const podeOperar = computed(() => ['admin', 'gestor', 'lider'].includes(authStore.usuario?.perfil))
const resumo = computed(() => {
  return roteiros.value.reduce((acc, roteiro) => {
    acc.distanciaTotal += Number(roteiro.distancia_total_km || 0)
    acc.tempoTotal += Number(roteiro.tempo_total_min || 0)
    acc.custoTotal += Number(roteiro.custo_estimado || 0)
    return acc
  }, { distanciaTotal: 0, tempoTotal: 0, custoTotal: 0 })
})

onMounted(carregar)

function carregar() {
  store.carregarRoteiros(1, 15, busca.value)
}

function abrir(id) {
  roteador.push({ name: 'roteiros-detalhe', params: { id } })
}

async function remover(id) {
  if (!confirm('Confirma a remoção deste roteiro?')) return
  await store.removerRoteiro(id)
  await carregar()
}

function iniciais(valor) {
  return (valor || 'RT')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join('')
}

function labelTransporte(valor) {
  const mapa = { carro: 'Carro', moto: 'Moto', a_pe: 'A pé' }
  return mapa[valor] || valor || '—'
}

function formatarNumero(valor) {
  return Number(valor || 0).toLocaleString('pt-BR')
}

function formatarMoeda(valor) {
  return Number(valor || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  })
}

function formatarKm(valor) {
  return `${Number(valor || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 2,
  })} km`
}

function formatarTempo(valor) {
  return `${formatarNumero(valor)} min`
}

function formatarData(valor) {
  if (!valor) return 'Sem data'
  return new Date(`${valor}T00:00:00`).toLocaleDateString('pt-BR')
}
</script>

<style scoped>
.roteiros-page { display: flex; flex-direction: column; gap: 1.3rem; }
.hero-card { display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; padding: 1.85rem 2rem; border-radius: 28px; background: radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 26%), linear-gradient(135deg, #111827 0%, #0f766e 52%, #14b8a6 100%); color: #fff; box-shadow: 0 22px 48px rgba(15, 23, 42, 0.18); }
.hero-texto { display: flex; flex-direction: column; gap: 0.4rem; }
.hero-eyebrow { font-size: 0.76rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.74); }
.hero-titulo { font-size: 2rem; font-weight: 900; letter-spacing: -0.04em; }
.hero-subtitulo { max-width: 62ch; color: rgba(255,255,255,0.82); font-size: 0.96rem; line-height: 1.7; }
.btn-principal { display: inline-flex; align-items: center; gap: 0.6rem; border: none; border-radius: 16px; padding: 0.95rem 1.25rem; background: #fff; color: #0f766e; font-weight: 800; cursor: pointer; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18); }
.btn-principal svg { width: 18px; height: 18px; }
.resumo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.resumo-card { display: flex; flex-direction: column; gap: 0.35rem; padding: 1.25rem 1.3rem; border-radius: 22px; background: #fff; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06); }
.resumo-card--azul { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); }
.resumo-card--verde { background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%); }
.resumo-card--laranja { background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); }
.resumo-label { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.resumo-valor { font-size: 1.6rem; font-weight: 900; letter-spacing: -0.04em; color: #0f172a; }
.resumo-ajuda { font-size: 0.85rem; color: #64748b; }
.toolbar-card, .tabela-card { border-radius: 24px; background: #fff; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06); }
.toolbar-card { padding: 1rem 1.1rem; }
.busca-wrapper { position: relative; }
.busca-icone { position: absolute; top: 50%; left: 1rem; width: 18px; height: 18px; color: #94a3b8; transform: translateY(-50%); }
.busca-input { width: 100%; padding: 0.9rem 1rem 0.9rem 2.8rem; border: 1.5px solid #e2e8f0; border-radius: 16px; background: #f8fafc; color: #0f172a; font-size: 0.95rem; outline: none; }
.tabela-card { padding: 1rem; }
.estado-card { display: grid; place-items: center; gap: 0.9rem; min-height: 240px; color: #64748b; text-align: center; }
.estado-card--vazio h2 { font-size: 1.2rem; color: #0f172a; }
.estado-card svg { width: 48px; height: 48px; }
.spinner { animation: girar 1s linear infinite; }
.tabela-wrapper { overflow-x: auto; }
.tabela-roteiros { width: 100%; border-collapse: collapse; }
.tabela-roteiros th { padding: 0.9rem 0.8rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.tabela-roteiros td { padding: 1rem 0.8rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.roteiro-bloco { display: flex; align-items: flex-start; gap: 0.85rem; }
.roteiro-avatar { width: 42px; height: 42px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; font-weight: 800; }
.roteiro-bloco strong { display: block; color: #0f172a; }
.roteiro-bloco span { display: block; font-size: 0.84rem; color: #64748b; margin-top: 0.18rem; }
.transporte-pill { display: inline-flex; align-items: center; justify-content: center; padding: 0.45rem 0.8rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; }
.transporte-pill--carro { background: #eff6ff; color: #1d4ed8; }
.transporte-pill--moto { background: #ecfdf5; color: #047857; }
.transporte-pill--a_pe { background: #fff7ed; color: #c2410c; }
.metricas-bloco { display: flex; flex-direction: column; gap: 0.2rem; }
.metricas-bloco strong { color: #0f172a; }
.metricas-bloco span { font-size: 0.84rem; color: #64748b; }
.acoes-linha { display: flex; align-items: center; gap: 0.55rem; }
.btn-acao { width: 38px; height: 38px; border: none; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.btn-acao svg { width: 18px; height: 18px; }
.btn-acao--editar { background: #eff6ff; color: #1d4ed8; }
.btn-acao--remover { background: #fef2f2; color: #dc2626; }
.th-acoes { width: 88px; }
@keyframes girar { to { transform: rotate(360deg); } }
@media (max-width: 1080px) { .resumo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 768px) { .hero-card { flex-direction: column; align-items: stretch; } .resumo-grid { grid-template-columns: 1fr; } .btn-principal { width: 100%; justify-content: center; } }
</style>