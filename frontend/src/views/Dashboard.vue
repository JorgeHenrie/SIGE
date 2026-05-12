<template>
  <div class="dashboard-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Centro de comando</span>
        <h1 class="hero-titulo">{{ saudacao }}, {{ primeiroNome }}</h1>
        <p class="hero-subtitulo">
          Monitore as lideranças ativas, acompanhe o potencial eleitoral e visualize o custo mensal da operação em um painel único.
        </p>

        <div class="hero-chips">
          <span class="hero-chip">Perfil: {{ perfilLabel }}</span>
          <span class="hero-chip">{{ formatarNumero(totalLideres) }} líderes mapeados</span>
          <span class="hero-chip">{{ formatarMoeda(totalFolhaLideres) }} de folha mensal</span>
          <span class="hero-chip">{{ formatarMoeda(totalGastoCombustivelMes) }} em combustível no mês</span>
        </div>
      </div>

      <div class="hero-destaque">
        <span class="hero-destaque-rotulo">Potencial eleitoral</span>
        <strong class="hero-destaque-valor">{{ formatarNumero(totalVotosEstimados) }}</strong>
        <p class="hero-destaque-texto">votos estimados consolidados nas lideranças ativas do sistema.</p>
      </div>
    </section>

    <section class="metricas-grid">
      <article class="metrica-card">
        <span class="metrica-label">Líderes</span>
        <strong class="metrica-valor">{{ formatarNumero(totalLideres) }}</strong>
        <span class="metrica-ajuda">Coordenação ativa monitorada</span>
      </article>

      <article class="metrica-card">
        <span class="metrica-label">Folha mensal</span>
        <strong class="metrica-valor">{{ formatarMoeda(totalFolhaLideres) }}</strong>
        <span class="metrica-ajuda">Custo contratual das lideranças</span>
      </article>

      <article class="metrica-card">
        <span class="metrica-label">Média por líder</span>
        <strong class="metrica-valor">{{ formatarDecimal(mediaVotosPorLider) }}</strong>
        <span class="metrica-ajuda">Votos estimados por liderança</span>
      </article>

      <article class="metrica-card metrica-card--forte">
        <span class="metrica-label">Custo operacional</span>
        <strong class="metrica-valor">{{ formatarMoeda(totalCustoOperacionalMes) }}</strong>
        <span class="metrica-ajuda">Folha mensal somada ao combustível do mês</span>
      </article>
    </section>

    <section class="conteudo-grid">
      <article class="bloco-card bloco-card--modulos">
        <div class="bloco-header">
          <div>
            <span class="bloco-eyebrow">Operação</span>
            <h2 class="bloco-titulo">Módulos estratégicos</h2>
          </div>
        </div>

        <div class="modulos-grid">
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
            <p class="modulo-descricao">Pedidos de visita, reuniões e aprovação de compromissos do deputado.</p>
            <span class="modulo-link">Abrir módulo</span>
          </button>

          <button class="modulo-card modulo-card--combustivel" @click="ir('/combustivel')">
            <div class="modulo-topo">
              <span class="modulo-icone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21h12"/>
                  <path d="M5 21V7a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v14"/>
                  <path d="M14 11h2a2 2 0 0 1 2 2v8"/>
                  <path d="M8 9h4"/>
                </svg>
              </span>
              <span class="modulo-badge">{{ formatarNumero(totalAbastecimentosCombustivel) }}</span>
            </div>
            <strong class="modulo-titulo">Combustível</strong>
            <p class="modulo-descricao">Controle financeiro dos abastecimentos por líder, placa e período de operação.</p>
            <span class="modulo-link">Abrir módulo</span>
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
            <p class="modulo-descricao">Gestão de lideranças, regiões prioritárias e estimativas eleitorais.</p>
            <span class="modulo-link">Abrir módulo</span>
          </button>

          <button class="modulo-card modulo-card--relatorios" @click="ir('/relatorios')">
            <div class="modulo-topo">
              <span class="modulo-icone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="20" x2="18" y2="10"/>
                  <line x1="12" y1="20" x2="12" y2="4"/>
                  <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
              </span>
              <span class="modulo-badge">{{ formatarNumero(totalVotosEstimados) }}</span>
            </div>
            <strong class="modulo-titulo">Relatórios</strong>
            <p class="modulo-descricao">Leitura executiva de ranking, território e potencial de voto consolidado.</p>
            <span class="modulo-link">Abrir módulo</span>
          </button>
        </div>
      </article>

      <article class="bloco-card bloco-card--acoes">
        <div class="bloco-header">
          <div>
            <span class="bloco-eyebrow">Execução</span>
            <h2 class="bloco-titulo">Ações rápidas</h2>
          </div>
        </div>

        <div class="acoes-lista">
          <button class="acao-btn acao-btn--azul" @click="ir('/lideres/novo')">
            <strong>Novo líder</strong>
            <span>Cadastre uma nova liderança e projete o potencial eleitoral.</span>
          </button>

          <button class="acao-btn acao-btn--agenda" @click="ir('/agenda/nova')">
            <strong>Nova solicitação</strong>
            <span>Abra um pedido de visita ou reunião para análise do gestor da agenda.</span>
          </button>

          <button class="acao-btn acao-btn--combustivel" @click="ir('/combustivel/novo')">
            <strong>Novo abastecimento</strong>
            <span>Registre um gasto de combustível e vincule o lançamento ao líder responsável.</span>
          </button>

          <button class="acao-btn acao-btn--escuro" @click="ir('/relatorios')">
            <strong>Analisar relatórios</strong>
            <span>Abra o painel analítico e acompanhe custos, desempenho e alertas operacionais.</span>
          </button>
        </div>
      </article>
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
const mediaVotosPorLider = ref(0)
const totalAbastecimentosCombustivel = ref(0)
const totalGastoCombustivelMes = ref(0)
const totalCustoOperacionalMes = ref(0)

const primeiroNome = computed(() => (authStore.usuario?.nome || 'Operador').split(' ')[0])
const saudacao = computed(() => {
  const hora = new Date().getHours()
  if (hora < 12) return 'Bom dia'
  if (hora < 18) return 'Boa tarde'
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

function formatarDecimal(valor) {
  return Number(valor || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  })
}

function formatarMoeda(valor) {
  return Number(valor || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  })
}

onMounted(async () => {
  const relatorio = await relatorioServico.resumo()

  totalLideres.value = Number(relatorio?.dados?.total_lideres || 0)
  totalFolhaLideres.value = Number(relatorio?.dados?.total_folha_lideres_mensal || 0)
  totalVotosEstimados.value = Number(relatorio?.dados?.total_votos_estimados || 0)
  mediaVotosPorLider.value = Number(relatorio?.dados?.media_votos_por_lider || 0)
  totalAbastecimentosCombustivel.value = Number(relatorio?.dados?.total_abastecimentos_combustivel || 0)
  totalGastoCombustivelMes.value = Number(relatorio?.dados?.total_gasto_combustivel_mes_atual || 0)
  totalCustoOperacionalMes.value = Number(relatorio?.dados?.total_custo_operacional_mes_atual || 0)
})
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-card {
  display: grid;
  grid-template-columns: 1.4fr 0.9fr;
  gap: 1rem;
  padding: 1.7rem 1.8rem;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(250, 204, 21, 0.18), transparent 28%),
    linear-gradient(135deg, #0f172a 0%, #172554 52%, #1d4ed8 100%);
  color: #f8fafc;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
}

.hero-eyebrow,
.bloco-eyebrow,
.metrica-label {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-eyebrow {
  display: inline-flex;
  padding: 0.35rem 0.72rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
}

.hero-titulo {
  margin-top: 0.95rem;
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-subtitulo {
  margin-top: 0.55rem;
  max-width: 58ch;
  line-height: 1.7;
  color: rgba(248, 250, 252, 0.82);
}

.hero-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1rem;
}

.hero-chip {
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
  font-size: 0.8rem;
  font-weight: 700;
}

.hero-destaque {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.35rem;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
}

.hero-destaque-rotulo {
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.72);
}

.hero-destaque-valor {
  margin-top: 0.5rem;
  font-size: 2.5rem;
  font-weight: 800;
  letter-spacing: -0.05em;
}

.hero-destaque-texto {
  color: rgba(248, 250, 252, 0.8);
  line-height: 1.7;
}

.metricas-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.metrica-card,
.bloco-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.metrica-card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 1.25rem;
}

.metrica-card--forte {
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
}

.metrica-card--forte .metrica-label,
.metrica-card--forte .metrica-valor,
.metrica-card--forte .metrica-ajuda {
  color: #f8fafc;
}

.metrica-label {
  color: #64748b;
}

.metrica-valor {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #0f172a;
}

.metrica-ajuda {
  font-size: 0.82rem;
  line-height: 1.55;
  color: #64748b;
}

.conteudo-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.9fr);
  gap: 1rem;
}

.bloco-card {
  padding: 1.25rem;
}

.bloco-header {
  margin-bottom: 1rem;
}

.bloco-eyebrow {
  color: #94a3b8;
}

.bloco-titulo {
  margin-top: 0.25rem;
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.modulos-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}

.modulo-card {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  text-align: left;
  padding: 1.15rem;
  border: none;
  border-radius: 18px;
  cursor: pointer;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.modulo-card:hover,
.acao-btn:hover {
  transform: translateY(-2px);
}

.modulo-card--lideres {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  box-shadow: 0 14px 24px rgba(37, 99, 235, 0.08);
}

.modulo-card--agenda {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  box-shadow: 0 14px 24px rgba(15, 118, 110, 0.08);
}

.modulo-card--combustivel {
  background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
  box-shadow: 0 14px 24px rgba(249, 115, 22, 0.14);
}

.modulo-card--relatorios {
  background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
  box-shadow: 0 14px 24px rgba(8, 145, 178, 0.08);
}

.modulo-topo {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modulo-icone {
  width: 46px;
  height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.75);
  color: #0f172a;
}

.modulo-icone svg {
  width: 20px;
  height: 20px;
}

.modulo-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.8);
  color: #0f172a;
  font-size: 0.8rem;
  font-weight: 800;
}

.modulo-titulo {
  font-size: 1.02rem;
  font-weight: 800;
  color: #0f172a;
}

.modulo-descricao {
  font-size: 0.84rem;
  line-height: 1.6;
  color: #475569;
}

.modulo-link {
  margin-top: auto;
  font-size: 0.82rem;
  font-weight: 800;
  color: #0f172a;
}

.acoes-lista {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.acao-btn {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.28rem;
  padding: 1rem 1.05rem;
  border: none;
  border-radius: 16px;
  cursor: pointer;
  text-align: left;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
  font-family: inherit;
}

.acao-btn strong {
  font-size: 0.92rem;
  font-weight: 800;
}

.acao-btn span {
  font-size: 0.82rem;
  line-height: 1.6;
}

.acao-btn--azul {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #1e3a8a;
}

.acao-btn--verde {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #166534;
}

.acao-btn--agenda {
  background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
  color: #155e75;
}

.acao-btn--combustivel {
  background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
  color: #9a3412;
}

.acao-btn--escuro {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: #f8fafc;
}

@media (max-width: 1180px) {
  .hero-card,
  .conteudo-grid {
    grid-template-columns: 1fr;
  }

  .modulos-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .metricas-grid,
  .modulos-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .hero-card,
  .bloco-card {
    padding: 1.2rem;
  }
}
</style>
