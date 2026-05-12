<template>
  <div class="inicio-page">
    <header class="hero-card">
      <div class="hero-info">
        <span class="hero-perfil-pill">{{ perfilLabel }}</span>
        <h1 class="hero-titulo">{{ nomeExibicao }}</h1>
        <p class="hero-data">{{ dataFormatada }}</p>
      </div>

      <div class="hero-kpis">
        <div class="kpi">
          <span class="kpi-valor">{{ formatarNumero(totalLideres) }}</span>
          <span class="kpi-label">Líderes</span>
        </div>
        <div class="kpi-sep"></div>
        <div class="kpi">
          <span class="kpi-valor">{{ formatarMoeda(totalFolhaLideres) }}</span>
          <span class="kpi-label">Folha mensal</span>
        </div>
        <div class="kpi-sep"></div>
        <div class="kpi">
          <span class="kpi-valor">{{ formatarNumero(totalVotosEstimados) }}</span>
          <span class="kpi-label">Votos estimados</span>
        </div>
      </div>
    </header>

    <section class="modulos-grid">
      <button class="modulo-card modulo-card--dashboard" @click="ir('/dashboard')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Dashboard</strong>
        <p class="modulo-desc">Indicadores e panorama executivo da base.</p>
        <span class="modulo-cta">Abrir →</span>
      </button>

      <button class="modulo-card modulo-card--equipe" @click="ir('/equipe-campanha')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="7" r="4"/>
            <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
            <path d="M3 11h3"/>
            <path d="M18 11h3"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Equipe de campanha</strong>
        <p class="modulo-desc">Mapa da força por área com cadastro e categorização dos membros.</p>
        <span class="modulo-cta">Organizar equipe →</span>
      </button>

      <button class="modulo-card modulo-card--lideres" @click="ir('/lideres')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Líderes</strong>
        <p class="modulo-desc">Coordenação regional e rede de liderança.</p>
        <span class="modulo-cta">{{ formatarNumero(totalLideres) }} cadastrados →</span>
      </button>

      <button class="modulo-card modulo-card--agenda" @click="ir('/agenda')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Agenda</strong>
        <p class="modulo-desc">Visitas, reuniões e aprovações do gabinete.</p>
        <span class="modulo-cta">Gerenciar →</span>
      </button>

      <button class="modulo-card modulo-card--combustivel" @click="ir('/combustivel')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21h12"/><path d="M5 21V7a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v14"/><path d="M14 11h2a2 2 0 0 1 2 2v8"/><path d="M8 9h4"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Combustível</strong>
        <p class="modulo-desc">Lançamentos por líder e controle financeiro de campo.</p>
        <span class="modulo-cta">{{ formatarNumero(totalAbastecimentosCombustivel) }} lançamentos →</span>
      </button>

      <button class="modulo-card modulo-card--relatorios" @click="ir('/relatorios')">
        <span class="modulo-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </span>
        <strong class="modulo-titulo">Relatórios</strong>
        <p class="modulo-desc">Custos de pessoal, desempenho das lideranças e operação consolidada.</p>
        <span class="modulo-cta">Analisar →</span>
      </button>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore.js'
import relatorioServico from '@/services/relatorioServico.js'

const roteador = useRouter()
const authStore = useAuthStore()

const totalLideres = ref(0)
const totalFolhaLideres = ref(0)
const totalVotosEstimados = ref(0)
const totalAbastecimentosCombustivel = ref(0)

const nomeExibicao = computed(() => authStore.usuario?.nome || 'Operador')
const dataFormatada = computed(() =>
  new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

const perfilLabel = computed(() => {
  const mapa = {
    admin: 'Administrador',
    coordenador: 'Coordenador',
    supervisor: 'Supervisor',
    lider: 'Líder',
    gestor: 'Gestor',
  }

  return mapa[authStore.usuario?.perfil] || authStore.usuario?.perfil || 'Operador'
})

function ir(rota) {
  roteador.push(rota)
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

onMounted(async () => {
  const resumo = await relatorioServico.resumo()

  totalLideres.value = Number(resumo?.dados?.total_lideres || 0)
  totalFolhaLideres.value = Number(resumo?.dados?.total_folha_lideres_mensal || 0)
  totalVotosEstimados.value = Number(resumo?.dados?.total_votos_estimados || 0)
  totalAbastecimentosCombustivel.value = Number(resumo?.dados?.total_abastecimentos_combustivel || 0)
})
</script>

<style scoped>
.inicio-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ─── Hero ─── */
.hero-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  padding: 2rem 2.25rem;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(20, 184, 166, 0.2), transparent 35%),
    linear-gradient(135deg, #0f172a 0%, #12354d 54%, #0f766e 100%);
  color: #f8fafc;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
}

.hero-perfil-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(248, 250, 252, 0.85);
  margin-bottom: 0.65rem;
}

.hero-titulo {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.hero-data {
  margin-top: 0.35rem;
  font-size: 0.85rem;
  color: rgba(248, 250, 252, 0.55);
  text-transform: capitalize;
}

.hero-kpis {
  display: flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 18px;
  padding: 1.1rem 1.5rem;
  gap: 1.5rem;
}

.kpi {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.18rem;
}

.kpi-valor {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #f8fafc;
}

.kpi-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(248, 250, 252, 0.5);
}

.kpi-sep {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.12);
}

/* ─── Grid de módulos ─── */
.modulos-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.modulo-card {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 1.35rem 1.25rem;
  border-radius: 20px;
  border: 1px solid rgba(15, 23, 42, 0.06);
  text-align: left;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}

.modulo-card:hover,
.modulo-card:focus-visible {
  transform: translateY(-3px);
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
}

.modulo-card--dashboard  { background: linear-gradient(150deg, #eff6ff 0%, #dbeafe 100%); }
.modulo-card--lideres    { background: linear-gradient(150deg, #eef2ff 0%, #e0e7ff 100%); }
.modulo-card--equipe     { background: linear-gradient(150deg, #f0fdf4 0%, #dcfce7 100%); }
.modulo-card--agenda     { background: linear-gradient(150deg, #ecfdf5 0%, #d1fae5 100%); }
.modulo-card--combustivel{ background: linear-gradient(150deg, #fff7ed 0%, #fed7aa 100%); }
.modulo-card--relatorios { background: linear-gradient(150deg, #ecfeff 0%, #cffafe 100%); }

.modulo-icone {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.75);
  color: #0f172a;
  margin-bottom: 0.25rem;
}

.modulo-icone svg {
  width: 20px;
  height: 20px;
}

.modulo-titulo {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.modulo-desc {
  font-size: 0.83rem;
  line-height: 1.5;
  color: #475569;
  flex: 1;
}

.modulo-cta {
  margin-top: 0.25rem;
  font-size: 0.82rem;
  font-weight: 800;
  color: #0f172a;
  opacity: 0.6;
}

.modulo-card:hover .modulo-cta {
  opacity: 1;
}

/* ─── Responsivo ─── */
@media (max-width: 1100px) {
  .modulos-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 860px) {
  .hero-card {
    flex-direction: column;
    align-items: flex-start;
  }
  .hero-kpis {
    width: 100%;
    justify-content: space-around;
  }
  .modulos-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .hero-card {
    padding: 1.4rem;
  }
  .modulos-grid {
    grid-template-columns: 1fr;
  }
}
</style>