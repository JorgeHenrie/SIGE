<template>
  <div class="inicio-page">
    <section class="hero-card">
      <div class="hero-texto">
        <h1 class="hero-titulo">{{ saudacao }}, {{ nomeExibicao }}</h1>
        <p class="hero-subtitulo">
          Escolha um módulo para continuar a operação no SIGE.
        </p>
      </div>

      <span class="hero-perfil">{{ perfilLabel }}</span>
    </section>

    <section class="modulos-grid">
      <button class="modulo-card modulo-card--dashboard" @click="ir('/dashboard')">
        <div class="modulo-topo">
          <span class="modulo-icone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
          </span>
          <span class="modulo-badge">Painel</span>
        </div>
        <strong class="modulo-titulo">Dashboard</strong>
        <div class="modulo-hover">
          <span class="modulo-eyebrow">Visão executiva</span>
          <p class="modulo-descricao">Indicadores, performance da base, conversão e panorama territorial em leitura executiva.</p>
          <span class="modulo-link">Abrir dashboard</span>
        </div>
      </button>

      <button class="modulo-card modulo-card--agenda" @click="ir('/agenda')">
        <div class="modulo-topo">
          <span class="modulo-icone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </span>
          <span class="modulo-badge">Agenda</span>
        </div>
        <strong class="modulo-titulo">Agenda</strong>
        <div class="modulo-hover">
          <span class="modulo-eyebrow">Agenda política</span>
          <p class="modulo-descricao">Solicite visitas e reuniões, acompanhe análise do gestor e controle aprovações do gabinete.</p>
          <span class="modulo-link">Entrar no módulo</span>
        </div>
      </button>

      <button class="modulo-card modulo-card--lideres" @click="ir('/lideres')">
        <div class="modulo-topo">
          <span class="modulo-icone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </span>
          <span class="modulo-badge">{{ formatarNumero(totalLideres) }}</span>
        </div>
        <strong class="modulo-titulo">Líderes</strong>
        <div class="modulo-hover">
          <span class="modulo-eyebrow">Coordenação</span>
          <p class="modulo-descricao">Cadastro, acompanhamento regional, potencial eleitoral e manutenção da rede de liderança.</p>
          <span class="modulo-link">Entrar no módulo</span>
        </div>
      </button>

      <button class="modulo-card modulo-card--apoiadores" @click="ir('/apoiadores')">
        <div class="modulo-topo">
          <span class="modulo-icone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
          </span>
          <span class="modulo-badge">{{ formatarNumero(totalApoiadores) }}</span>
        </div>
        <strong class="modulo-titulo">Apoiadores</strong>
        <div class="modulo-hover">
          <span class="modulo-eyebrow">Base política</span>
          <p class="modulo-descricao">Organize vínculos, intenção política, bairro e histórico de relacionamento da base.</p>
          <span class="modulo-link">Entrar no módulo</span>
        </div>
      </button>

      <button class="modulo-card modulo-card--relatorios" @click="ir('/relatorios')">
        <div class="modulo-topo">
          <span class="modulo-icone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
              <polyline points="10 9 9 9 8 9"/>
            </svg>
          </span>
          <span class="modulo-badge">{{ formatarNumero(totalVotosEstimados) }}</span>
        </div>
        <strong class="modulo-titulo">Relatórios</strong>
        <div class="modulo-hover">
          <span class="modulo-eyebrow">Análise</span>
          <p class="modulo-descricao">Leia ranking, concentração territorial e potencial consolidado para apoiar decisões rápidas.</p>
          <span class="modulo-link">Entrar no módulo</span>
        </div>
      </button>
    </section>

    <section class="operacao-card">
      <span class="operacao-eyebrow">Ritmo da operação</span>

      <div class="operacao-lista">
        <article class="operacao-item">
          <strong>Cadastro</strong>
          <span>Líderes e apoiadores</span>
        </article>
        <article class="operacao-item">
          <strong>Leitura</strong>
          <span>Dashboard e relatórios</span>
        </article>
        <article class="operacao-item">
          <strong>Agenda</strong>
          <span>Visitas, reuniões e decisões</span>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore.js'
import { useLiderStore } from '@/stores/liderStore.js'
import { useApoiadorStore } from '@/stores/apoiadorStore.js'
import relatorioServico from '@/services/relatorioServico.js'

const roteador = useRouter()
const authStore = useAuthStore()
const liderStore = useLiderStore()
const apoiadorStore = useApoiadorStore()

const totalLideres = ref(0)
const totalApoiadores = ref(0)
const totalVotosEstimados = ref(0)

const nomeExibicao = computed(() => authStore.usuario?.nome || 'Operador')
const saudacao = computed(() => {
  const horaAtual = new Date().getHours()

  if (horaAtual < 12) {
    return 'Bom dia'
  }

  if (horaAtual < 18) {
    return 'Boa tarde'
  }

  return 'Boa noite'
})

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

onMounted(async () => {
  const [_, __, resumo] = await Promise.all([
    liderStore.carregarLideres(1, 1),
    apoiadorStore.carregarApoiadores(1, 1),
    relatorioServico.resumo(),
  ])

  totalLideres.value = Number(liderStore.paginacao?.total || resumo?.dados?.total_lideres || 0)
  totalApoiadores.value = Number(apoiadorStore.paginacao?.total || resumo?.dados?.total_apoiadores || 0)
  totalVotosEstimados.value = Number(resumo?.dados?.total_votos_estimados || 0)
})
</script>

<style scoped>
.inicio-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.3rem 1.5rem;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 30%),
    linear-gradient(135deg, #0f172a 0%, #12354d 54%, #0f766e 100%);
  color: #f8fafc;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
}

.modulo-eyebrow,
.operacao-eyebrow {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-titulo {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-subtitulo {
  margin-top: 0.35rem;
  max-width: 52ch;
  line-height: 1.55;
  color: rgba(248, 250, 252, 0.82);
}

.hero-perfil {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 0.95rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 0.74rem;
  color: rgba(248, 250, 252, 0.9);
}

.modulos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.modulo-card,
.operacao-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.modulo-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  min-height: 162px;
  text-align: left;
  padding: 1rem;
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
}

.modulo-card:hover,
.modulo-card:focus-visible {
  transform: translateY(-2px);
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.1);
  border-color: rgba(15, 23, 42, 0.1);
}

.modulo-card--dashboard {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.modulo-card--agenda {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.modulo-card--lideres {
  background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
}

.modulo-card--apoiadores {
  background: linear-gradient(135deg, #fdf4ff 0%, #f5d0fe 100%);
}

.modulo-card--relatorios {
  background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
}

.modulo-eyebrow {
  color: #64748b;
}

.modulo-topo {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modulo-icone {
  width: 42px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  color: #0f172a;
}

.modulo-icone svg {
  width: 18px;
  height: 18px;
}

.modulo-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.34rem 0.62rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.82);
  color: #0f172a;
  font-size: 0.76rem;
  font-weight: 800;
}

.modulo-titulo {
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
}

.modulo-hover {
  position: absolute;
  inset: auto 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding: 1.35rem 1rem 1rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.72) 25%, rgba(255, 255, 255, 0.96) 100%);
  opacity: 0;
  transform: translateY(10px);
  pointer-events: none;
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.modulo-card:hover .modulo-hover,
.modulo-card:focus-visible .modulo-hover {
  opacity: 1;
  transform: translateY(0);
}

.modulo-descricao {
  font-size: 0.84rem;
  line-height: 1.55;
  color: #475569;
}

.modulo-link {
  font-size: 0.82rem;
  font-weight: 800;
  color: #0f172a;
}

.operacao-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem;
}

.operacao-eyebrow {
  color: #94a3b8;
  flex-shrink: 0;
}

.operacao-lista {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
  flex: 1;
}

.operacao-item {
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
  padding: 0.9rem 1rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0;
}

.operacao-item strong {
  display: block;
  font-size: 0.86rem;
  font-weight: 800;
  color: #0f172a;
}

.operacao-item span {
  font-size: 0.8rem;
  line-height: 1.45;
  color: #64748b;
}

@media (max-width: 1180px) {
  .operacao-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .operacao-lista {
    width: 100%;
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 900px) {
  .operacao-lista {
    grid-template-columns: 1fr;
  }
}

@media (hover: none), (max-width: 768px) {
  .modulo-card {
    min-height: 0;
  }

  .modulo-hover {
    position: static;
    padding: 0;
    background: none;
    opacity: 1;
    transform: none;
    pointer-events: auto;
  }
}

@media (max-width: 640px) {
  .hero-card,
  .operacao-card {
    padding: 1.2rem;
  }

  .hero-card {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>