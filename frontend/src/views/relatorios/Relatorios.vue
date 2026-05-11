<template>
  <div class="relatorios-page">
    <!-- Hero -->
    <header class="hero-card">
      <div class="hero-info">
        <span class="hero-eyebrow">Visão executiva</span>
        <h1 class="hero-titulo">Relatórios</h1>
      </div>
      <button class="btn-atualizar" @click="carregarTodos" :disabled="carregando">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"/>
          <polyline points="1 20 1 14 7 14"/>
          <path d="M3.51 9a9 9 0 0 1 14.13-3.36L23 10"/>
          <path d="M20.49 15a9 9 0 0 1-14.13 3.36L1 14"/>
        </svg>
        {{ carregando ? 'Atualizando...' : 'Atualizar' }}
      </button>
    </header>

    <AlertaMensagem v-if="erro" tipo="erro" :mensagem="erro" />

    <!-- Loading -->
    <section v-if="carregando && !resumo" class="estado-card">
      <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
      </svg>
      <p>Consolidando indicadores...</p>
    </section>

    <template v-else-if="resumo">
      <!-- KPIs resumo: linha de 6 cards -->
      <section class="kpis-strip">
        <article class="kpi-card kpi-card--destaque">
          <span class="kpi-label">Votos estimados</span>
          <strong class="kpi-valor">{{ formatarNumero(resumo.total_votos_estimados) }}</strong>
        </article>
        <article class="kpi-card">
          <span class="kpi-label">Líderes</span>
          <strong class="kpi-valor">{{ formatarNumero(resumo.total_lideres) }}</strong>
        </article>
        <article class="kpi-card kpi-card--verde">
          <span class="kpi-label">Folha mensal</span>
          <strong class="kpi-valor">{{ formatarMoeda(resumo.total_folha_lideres_mensal) }}</strong>
        </article>
        <article class="kpi-card">
          <span class="kpi-label">Salário médio</span>
          <strong class="kpi-valor">{{ formatarMoeda(resumo.media_salario_lider) }}</strong>
        </article>
        <article class="kpi-card kpi-card--azul">
          <span class="kpi-label">Combustível no mês</span>
          <strong class="kpi-valor">{{ formatarMoeda(resumo.total_gasto_combustivel_mes_atual) }}</strong>
        </article>
        <article class="kpi-card kpi-card--laranja">
          <span class="kpi-label">Custo operacional</span>
          <strong class="kpi-valor">{{ formatarMoeda(resumo.total_custo_operacional_mes_atual) }}</strong>
        </article>
      </section>

      <!-- Destaques contextuais -->
      <section class="destaques-strip">
        <div class="destaque-item">
          <span class="destaque-label">Líder em destaque</span>
          <strong class="destaque-valor">{{ topLider?.lider_nome || '—' }}</strong>
          <span class="destaque-sub">{{ formatarNumero(topLider?.votos_estimados) }} votos est.</span>
        </div>
        <div class="destaque-sep"></div>
        <div class="destaque-item">
          <span class="destaque-label">Bairro em evidência</span>
          <strong class="destaque-valor">{{ bairroDestaque?.bairro || '—' }}</strong>
          <span class="destaque-sub">{{ formatarNumero(bairroDestaque?.votos_estimados) }} votos estimados</span>
        </div>
        <div class="destaque-sep"></div>
        <div class="destaque-item">
          <span class="destaque-label">Média por líder</span>
          <strong class="destaque-valor">{{ formatarDecimal(resumo.media_votos_por_lider) }}</strong>
          <span class="destaque-sub">votos estimados</span>
        </div>
        <div class="destaque-sep"></div>
        <div class="destaque-item destaque-item--oposicao">
          <span class="destaque-label">Maior custo mensal</span>
          <strong class="destaque-valor">{{ topCustoMensal?.lider_nome || '—' }}</strong>
          <span class="destaque-sub">{{ formatarMoeda(topCustoMensal?.custo_mensal_total) }}/mês</span>
        </div>
        <div class="destaque-sep"></div>
        <div class="destaque-item destaque-item--risco">
          <span class="destaque-label">Alertas de combustível</span>
          <strong class="destaque-valor">{{ formatarNumero(totalAlertasCombustivel) }}</strong>
          <span class="destaque-sub">{{ formatarNumero(alertasAltos) }} altos</span>
        </div>
      </section>

      <!-- Abas de detalhes -->
      <nav class="abas-nav">
        <button
          v-for="aba in abas"
          :key="aba.id"
          class="aba-btn"
          :class="{ 'aba-btn--ativa': abaAtiva === aba.id }"
          @click="abaAtiva = aba.id"
        >
          {{ aba.label }}
        </button>
      </nav>

      <!-- Aba: Base política -->
      <section v-if="abaAtiva === 'base'" class="aba-conteudo">
        <div class="conteudo-grid">
          <article class="bloco-card">
            <div class="bloco-header">
              <div>
                <span class="bloco-eyebrow">Pessoal</span>
                <h2 class="bloco-titulo">Ranking de Líderes</h2>
              </div>
              <span class="bloco-info">{{ formatarNumero(porLider.length) }} posições</span>
            </div>
            <div v-if="!porLider.length" class="bloco-vazio">Nenhum dado disponível.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead>
                  <tr>
                    <th>#</th><th>Líder</th><th>Bairro</th><th>Votos Est.</th><th>Salário</th><th>Combustível Mês</th><th>Custo Mensal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in porLider" :key="`${item.lider_nome}-${item.posicao_ranking}`">
                    <td><span class="ranking-pill">{{ item.posicao_ranking || '—' }}</span></td>
                    <td>
                      <div class="lider-bloco">
                        <strong>{{ item.lider_nome || 'Sem nome' }}</strong>
                        <span>{{ item.ultimo_abastecimento ? `Último abastecimento em ${formatarDataHora(item.ultimo_abastecimento)}` : 'Sem abastecimento recente' }}</span>
                      </div>
                    </td>
                    <td>{{ item.lider_bairro || '—' }}</td>
                    <td class="td-destaque">{{ formatarNumero(item.votos_estimados) }}</td>
                    <td>{{ formatarMoeda(item.salario_mensal) }}</td>
                    <td>{{ formatarMoeda(item.total_combustivel_mes_atual) }}</td>
                    <td class="td-destaque">{{ formatarMoeda(item.custo_mensal_total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <article class="bloco-card">
            <div class="bloco-header">
              <div>
                <span class="bloco-eyebrow">Distribuição</span>
                <h2 class="bloco-titulo">Custos por Bairro</h2>
              </div>
              <span class="bloco-info">{{ formatarNumero(porBairro.length) }} bairros</span>
            </div>
            <div v-if="!porBairro.length" class="bloco-vazio">Nenhum consolidado territorial.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead>
                  <tr><th>Bairro</th><th>Líderes</th><th>Votos Est.</th><th>Folha Mensal</th><th>Custo Mensal</th></tr>
                </thead>
                <tbody>
                  <tr v-for="item in porBairro" :key="`${item.bairro}-${item.tipo}`">
                    <td><strong class="bairro-nome">{{ item.bairro || '—' }}</strong></td>
                    <td>{{ formatarNumero(item.total) }}</td>
                    <td>{{ formatarNumero(item.votos_estimados) }}</td>
                    <td>{{ formatarMoeda(item.total_folha_mensal) }}</td>
                    <td class="td-destaque">{{ formatarMoeda(item.custo_mensal_total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>
      </section>

      <!-- Aba: Combustível -->
      <section v-if="abaAtiva === 'combustivel'" class="aba-conteudo">
        <section class="combustivel-metricas-grid">
          <article class="combustivel-metrica-card">
            <span class="kpi-label">Litros totais</span>
            <strong class="kpi-valor">{{ formatarLitros(resumo.total_litros_combustivel) }}</strong>
          </article>
          <article class="combustivel-metrica-card combustivel-metrica-card--claro">
            <span class="kpi-label">Litros na semana</span>
            <strong class="kpi-valor">{{ formatarLitros(resumo.total_litros_combustivel_semana_atual) }}</strong>
          </article>
          <article class="combustivel-metrica-card combustivel-metrica-card--claro">
            <span class="kpi-label">Litros no mês</span>
            <strong class="kpi-valor">{{ formatarLitros(resumo.total_litros_combustivel_mes_atual) }}</strong>
          </article>
          <article class="combustivel-metrica-card combustivel-metrica-card--forte">
            <span class="kpi-label">Custo médio por litro</span>
            <strong class="kpi-valor">{{ formatarMoeda(resumo.custo_medio_litro_combustivel) }}</strong>
          </article>
          <article class="combustivel-metrica-card combustivel-metrica-card--alerta">
            <span class="kpi-label">Alertas ativos</span>
            <strong class="kpi-valor">{{ formatarNumero(totalAlertasCombustivel) }}</strong>
            <span class="kpi-sub">{{ formatarNumero(alertasAltos) }} altos • {{ formatarNumero(alertasMedios) }} medios</span>
          </article>
        </section>

        <div class="combustivel-grid">
          <article class="bloco-card">
            <div class="bloco-header">
              <div><span class="bloco-eyebrow">Combustível</span><h2 class="bloco-titulo">Semanal</h2></div>
              <span class="bloco-info">{{ formatarNumero(combustivelSemanal.length) }} semanas</span>
            </div>
            <div v-if="!combustivelSemanal.length" class="bloco-vazio">Nenhum lançamento semanal.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead><tr><th>Semana</th><th>Abastecimentos</th><th>Líderes</th><th>Litros</th><th>Total</th><th>Custo/L</th></tr></thead>
                <tbody>
                  <tr v-for="item in combustivelSemanal" :key="item.semana_referencia">
                    <td><strong>{{ formatarData(item.semana_referencia) }}</strong></td>
                    <td>{{ formatarNumero(item.total_abastecimentos) }}</td>
                    <td>{{ formatarNumero(item.total_lideres) }}</td>
                    <td>{{ formatarLitros(item.total_litros) }}</td>
                    <td class="td-destaque">{{ formatarMoeda(item.total_gasto) }}</td>
                    <td>{{ formatarMoeda(item.custo_medio_litro) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <article class="bloco-card">
            <div class="bloco-header">
              <div><span class="bloco-eyebrow">Combustível</span><h2 class="bloco-titulo">Mensal</h2></div>
              <span class="bloco-info">{{ formatarNumero(combustivelMensal.length) }} meses</span>
            </div>
            <div v-if="!combustivelMensal.length" class="bloco-vazio">Nenhum lançamento mensal.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead><tr><th>Mês</th><th>Abastecimentos</th><th>Líderes</th><th>Litros</th><th>Total</th><th>Custo/L</th></tr></thead>
                <tbody>
                  <tr v-for="item in combustivelMensal" :key="item.mes_referencia">
                    <td><strong>{{ formatarMesAno(item.mes_referencia) }}</strong></td>
                    <td>{{ formatarNumero(item.total_abastecimentos) }}</td>
                    <td>{{ formatarNumero(item.total_lideres) }}</td>
                    <td>{{ formatarLitros(item.total_litros) }}</td>
                    <td class="td-destaque">{{ formatarMoeda(item.total_gasto) }}</td>
                    <td>{{ formatarMoeda(item.custo_medio_litro) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <article class="bloco-card bloco-card--full">
            <div class="bloco-header">
              <div><span class="bloco-eyebrow">Combustível</span><h2 class="bloco-titulo">Por Líder</h2></div>
              <span class="bloco-info">{{ formatarNumero(combustivelPorLider.length) }} líderes</span>
            </div>
            <div v-if="!combustivelPorLider.length" class="bloco-vazio">Nenhum consolidado por líder.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead><tr><th>Líder</th><th>Bairro</th><th>Litros</th><th>Custo/L</th><th>Total</th><th>Semana</th><th>Mês</th><th>Último abastecimento</th></tr></thead>
                <tbody>
                  <tr v-for="item in combustivelPorLider" :key="item.lider_id">
                    <td>
                      <div class="lider-bloco">
                        <strong>{{ item.lider_nome || '—' }}</strong>
                        <span>{{ formatarNumero(item.total_abastecimentos) }} lançamentos</span>
                      </div>
                    </td>
                    <td>{{ item.lider_bairro || '—' }}</td>
                    <td>{{ formatarLitros(item.total_litros) }}</td>
                    <td>{{ formatarMoeda(item.custo_medio_litro) }}</td>
                    <td class="td-destaque">{{ formatarMoeda(item.total_gasto) }}</td>
                    <td>{{ formatarMoeda(item.total_semana_atual) }}</td>
                    <td>{{ formatarMoeda(item.total_mes_atual) }}</td>
                    <td>{{ item.ultimo_abastecimento ? formatarDataHora(item.ultimo_abastecimento) : '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <article class="bloco-card bloco-card--full">
            <div class="bloco-header">
              <div><span class="bloco-eyebrow">Controle inteligente</span><h2 class="bloco-titulo">Alertas de combustível</h2></div>
              <span class="bloco-info">{{ formatarNumero(combustivelAlertas.length) }} ocorrências</span>
            </div>
            <div v-if="!combustivelAlertas.length" class="bloco-vazio">Nenhum alerta ativo no momento.</div>
            <div v-else class="tabela-wrapper">
              <table class="tabela">
                <thead><tr><th>Nível</th><th>Placa e veículo</th><th>Líder</th><th>Indicadores</th><th>Descrição</th></tr></thead>
                <tbody>
                  <tr v-for="item in combustivelAlertas" :key="`${item.id}-${item.alerta_codigo}`">
                    <td>
                      <span :class="['alerta-badge', `alerta-badge--${item.alerta_nivel || 'baixo'}`]">
                        {{ labelNivelAlerta(item.alerta_nivel) }}
                      </span>
                    </td>
                    <td>
                      <div class="lider-bloco">
                        <strong>{{ formatarPlaca(item.placa_veiculo) }}</strong>
                        <span>{{ item.veiculo_descricao || 'Veiculo sem descricao' }} • {{ formatarTipoCombustivel(item.tipo_combustivel) }}</span>
                        <span>{{ formatarDataHora(item.data_abastecimento) }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="lider-bloco">
                        <strong>{{ item.lider_nome || '—' }}</strong>
                        <span>{{ item.lider_bairro || 'Sem bairro informado' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="indicadores-alerta">
                        <span>Odometro {{ formatarOdometro(item.odometro_atual) }}</span>
                        <span v-if="temValor(item.km_rodados)">Rodagem {{ formatarDecimal(item.km_rodados) }} km</span>
                        <span v-if="temValor(item.consumo_km_l)">Consumo {{ formatarDecimal(item.consumo_km_l) }} km/L</span>
                        <span>Custo/L {{ formatarMoeda(item.custo_por_litro) }}</span>
                        <span v-if="temValor(item.desvio_percentual)">Desvio {{ formatarPercentual(item.desvio_percentual) }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="descricao-alerta">
                        <strong>{{ item.alerta_titulo || 'Alerta' }}</strong>
                        <span>{{ item.alerta_descricao || 'Sem detalhes adicionais.' }}</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import relatorioServico from '@/services/relatorioServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const carregando = ref(false)
const erro = ref(null)
const resumo = ref(null)
const porLider = ref([])
const porBairro = ref([])
const combustivelSemanal = ref([])
const combustivelMensal = ref([])
const combustivelPorLider = ref([])
const combustivelAlertas = ref([])
const abaAtiva = ref('base')

const abas = [
  { id: 'base', label: 'Lideranças' },
  { id: 'combustivel', label: 'Combustível' },
]

const topLider = computed(() => porLider.value[0] || null)
const bairroDestaque = computed(() => {
  if (!porBairro.value.length) return null

  return [...porBairro.value]
    .sort((a, b) => Number(b.votos_estimados || 0) - Number(a.votos_estimados || 0))[0]
})
const topCustoMensal = computed(() => {
  if (!porLider.value.length) return null

  return [...porLider.value]
    .sort((a, b) => Number(b.custo_mensal_total || 0) - Number(a.custo_mensal_total || 0))[0]
})
const totalAlertasCombustivel = computed(() => Number(resumo.value?.total_alertas_combustivel ?? combustivelAlertas.value.length))
const alertasAltos = computed(() => combustivelAlertas.value.filter((item) => item.alerta_nivel === 'alto').length)
const alertasMedios = computed(() => combustivelAlertas.value.filter((item) => item.alerta_nivel === 'medio').length)

onMounted(carregarTodos)

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

function formatarPercentual(valor) {
  return `${Number(valor || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  })}%`
}

function formatarLitros(valor) {
  return `${Number(valor || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} L`
}

function formatarOdometro(valor) {
  if (valor === null || valor === undefined || valor === '') return '—'
  return Number(valor).toLocaleString('pt-BR')
}

function formatarPlaca(valor) {
  const placa = String(valor || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase()
  if (placa.length !== 7) return valor || '—'
  return `${placa.slice(0, 3)}-${placa.slice(3)}`
}

function formatarTipoCombustivel(valor) {
  if (!valor) return 'Combustivel nao informado'
  return String(valor).charAt(0).toUpperCase() + String(valor).slice(1)
}

function formatarData(valor) {
  if (!valor) return '—'

  return new Date(`${valor}T00:00:00`).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function formatarMesAno(valor) {
  if (!valor) return '—'

  return new Date(`${valor}T00:00:00`).toLocaleDateString('pt-BR', {
    month: 'long',
    year: 'numeric',
  })
}

function formatarDataHora(valor) {
  if (!valor) return '—'

  return new Date(valor.replace(' ', 'T')).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function labelNivelAlerta(nivel) {
  const mapa = {
    alto: 'Alto',
    medio: 'Medio',
    baixo: 'Baixo',
  }

  return mapa[nivel] || 'Alerta'
}

function temValor(valor) {
  return valor !== null && valor !== undefined && valor !== ''
}

async function carregarTodos() {
  carregando.value = true
  erro.value = null

  try {
    const [r1, r2, r3, r4, r5, r6, r7] = await Promise.all([
      relatorioServico.resumo(),
      relatorioServico.consolidado(),
      relatorioServico.porBairro(),
      relatorioServico.combustivelSemanal(),
      relatorioServico.combustivelMensal(),
      relatorioServico.combustivelPorLider(),
      relatorioServico.combustivelAlertas(),
    ])

    resumo.value = r1.dados
    porLider.value = r2.dados || []
    porBairro.value = r3.dados || []
    combustivelSemanal.value = r4.dados || []
    combustivelMensal.value = r5.dados || []
    combustivelPorLider.value = r6.dados || []
    combustivelAlertas.value = r7.dados || []
  } catch (e) {
    erro.value = e.message
  } finally {
    carregando.value = false
  }
}
</script>

<style scoped>
.relatorios-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ─── Hero ─── */
.hero-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(251, 191, 36, 0.24), transparent 32%),
    linear-gradient(135deg, #0f172a 0%, #172554 48%, #1d4ed8 100%);
  color: #f8fafc;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
}

.hero-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.hero-eyebrow {
  display: inline-flex;
  padding: 0.3rem 0.72rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.16);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(248, 250, 252, 0.8);
  width: fit-content;
}

.hero-titulo {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.btn-atualizar {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.2rem;
  border: none;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #f8fafc;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s ease;
  font-family: inherit;
  white-space: nowrap;
}

.btn-atualizar:hover {
  background: rgba(255, 255, 255, 0.18);
}

.btn-atualizar:disabled {
  opacity: 0.6;
  cursor: wait;
}

.btn-atualizar svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

/* ─── Estado ─── */
.estado-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  padding: 2rem;
  border-radius: 20px;
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #64748b;
  gap: 0.8rem;
  text-align: center;
}

.spinner {
  width: 24px;
  height: 24px;
  animation: girar 1s linear infinite;
}

/* ─── KPIs strip ─── */
.kpis-strip {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 0.85rem;
}

.kpi-card {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 1.1rem 1.2rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
}

.kpi-card--destaque {
  background: linear-gradient(135deg, #0f172a 0%, #172554 100%);
  color: #f8fafc;
  border-color: transparent;
}

.kpi-card--destaque .kpi-label,
.kpi-card--destaque .kpi-valor,
.kpi-card--destaque .kpi-sub { color: inherit; }

.kpi-card--verde { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
.kpi-card--laranja { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); }
.kpi-card--azul { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }

.kpi-label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.kpi-valor {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #0f172a;
}

.kpi-sub {
  font-size: 0.78rem;
  color: #64748b;
}

/* ─── Destaques strip ─── */
.destaques-strip {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 1.25rem 1.5rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
  overflow-x: auto;
}

.destaque-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex: 1;
  min-width: 130px;
  padding: 0 1.25rem;
}

.destaque-item:first-child { padding-left: 0; }
.destaque-item:last-child { padding-right: 0; }

.destaque-label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
}

.destaque-valor {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.destaque-sub {
  font-size: 0.78rem;
  color: #64748b;
}

.destaque-item--oposicao .destaque-valor { color: #c2410c; }
.destaque-item--risco .destaque-valor { color: #b91c1c; }

.destaque-sep {
  width: 1px;
  height: 44px;
  background: #e2e8f0;
  flex-shrink: 0;
}

/* ─── Abas ─── */
.abas-nav {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0;
}

.aba-btn {
  padding: 0.65rem 1.25rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  font-size: 0.9rem;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  transition: color 0.15s ease, border-color 0.15s ease;
  font-family: inherit;
}

.aba-btn:hover {
  color: #0f172a;
}

.aba-btn--ativa {
  color: #1d4ed8;
  border-bottom-color: #1d4ed8;
}

/* ─── Conteúdo das abas ─── */
.aba-conteudo {
  animation: fadeIn 0.18s ease;
}

.conteudo-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(300px, 1fr);
  gap: 1rem;
}

.combustivel-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.combustivel-metricas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.85rem;
  margin-bottom: 1rem;
}

.combustivel-metrica-card {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 1rem 1.15rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
}

.combustivel-metrica-card--claro {
  background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
}

.combustivel-metrica-card--forte {
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
}

.combustivel-metrica-card--alerta {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.bloco-card--full {
  grid-column: span 2;
}

/* ─── Blocos ─── */
.bloco-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

.bloco-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem 1.25rem 0;
}

.bloco-eyebrow {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
}

.bloco-titulo {
  margin-top: 0.2rem;
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.03em;
}

.bloco-info {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
}

.bloco-vazio {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 120px;
  color: #94a3b8;
  font-size: 0.88rem;
  padding: 1.5rem;
}

/* ─── Tabelas ─── */
.tabela-wrapper {
  overflow-x: auto;
  padding: 0.85rem 1.25rem 1.25rem;
}

.tabela {
  width: 100%;
  border-collapse: collapse;
}

.tabela th,
.tabela td {
  padding: 0.85rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
  font-size: 0.88rem;
}

.tabela th {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
}

.tabela tbody tr:hover {
  background: #f8fafc;
}

.ranking-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1d4ed8;
  font-weight: 800;
  font-size: 0.84rem;
}

.lider-bloco {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.lider-bloco strong,
.bairro-nome {
  color: #0f172a;
  font-weight: 700;
}

.lider-bloco span {
  color: #64748b;
  font-size: 0.8rem;
}

.td-destaque {
  font-weight: 800;
  color: #1d4ed8;
}

.composicao-bloco {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.chip,
.tipo-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.3rem 0.55rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 800;
}

.chip--apoio,
.tipo-badge--apoiador { background: #dcfce7; color: #15803d; }
.chip--indeciso,
.tipo-badge--indeciso { background: #ffedd5; color: #c2410c; }
.chip--oposicao,
.tipo-badge--oposicao { background: #fee2e2; color: #b91c1c; }
.tipo-badge--indefinido { background: #e2e8f0; color: #475569; }

.alerta-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 70px;
  padding: 0.34rem 0.65rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.alerta-badge--alto {
  background: #fee2e2;
  color: #b91c1c;
}

.alerta-badge--medio {
  background: #ffedd5;
  color: #c2410c;
}

.alerta-badge--baixo {
  background: #dbeafe;
  color: #1d4ed8;
}

.indicadores-alerta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.indicadores-alerta span {
  display: inline-flex;
  align-items: center;
  padding: 0.34rem 0.55rem;
  border-radius: 999px;
  background: #f8fafc;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 700;
}

.descricao-alerta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 260px;
}

.descricao-alerta strong {
  color: #0f172a;
  font-weight: 700;
}

.descricao-alerta span {
  color: #64748b;
  line-height: 1.55;
}

/* ─── Animações ─── */
@keyframes girar {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Responsivo ─── */
@media (max-width: 1200px) {
  .kpis-strip {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .kpis-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .combustivel-metricas-grid,
  .conteudo-grid,
  .combustivel-grid {
    grid-template-columns: 1fr;
  }
  .bloco-card--full {
    grid-column: span 1;
  }
}

@media (max-width: 640px) {
  .hero-card {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.4rem;
  }
  .btn-atualizar {
    width: 100%;
    justify-content: center;
  }
  .kpis-strip {
    grid-template-columns: 1fr 1fr;
  }
  .destaques-strip {
    flex-wrap: wrap;
    gap: 1rem;
  }
  .destaque-sep { display: none; }
  .destaque-item { flex: none; width: calc(50% - 0.5rem); padding: 0; }
}
</style>
