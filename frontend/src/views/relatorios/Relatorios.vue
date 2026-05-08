<template>
  <div class="relatorios-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Visão executiva</span>
        <h1 class="hero-titulo">Relatórios</h1>
        <p class="hero-subtitulo">
          Acompanhe liderança, potencial eleitoral e concentração territorial com um painel pronto para leitura estratégica.
        </p>
      </div>

      <button class="btn-atualizar" @click="carregarTodos" :disabled="carregando">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"/>
          <polyline points="1 20 1 14 7 14"/>
          <path d="M3.51 9a9 9 0 0 1 14.13-3.36L23 10"/>
          <path d="M20.49 15a9 9 0 0 1-14.13 3.36L1 14"/>
        </svg>
        {{ carregando ? 'Atualizando...' : 'Atualizar painel' }}
      </button>
    </section>

    <AlertaMensagem v-if="erro" tipo="erro" :mensagem="erro" />

    <section v-if="carregando && !resumo" class="estado-card">
      <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
      </svg>
      <p>Consolidando indicadores do sistema...</p>
    </section>

    <template v-else-if="resumo">
      <section class="painel-superior">
        <article class="destaque-card">
          <span class="destaque-eyebrow">Potencial consolidado</span>
          <strong class="destaque-valor">{{ formatarNumero(resumo.total_votos_estimados) }}</strong>
          <p class="destaque-texto">
            votos estimados em uma base composta por {{ formatarNumero(resumo.total_lideres) }} líderes e
            {{ formatarNumero(resumo.total_apoiadores) }} apoiadores mapeados.
          </p>

          <div class="destaque-grid">
            <div class="destaque-item">
              <span>Conversão confirmada</span>
              <strong>{{ taxaConversao }}</strong>
            </div>
            <div class="destaque-item">
              <span>Bairro em evidência</span>
              <strong>{{ bairroDestaque?.bairro || 'Sem dados' }}</strong>
            </div>
            <div class="destaque-item">
              <span>Líder em destaque</span>
              <strong>{{ topLider?.lider_nome || 'Sem dados' }}</strong>
            </div>
          </div>
        </article>

        <div class="metricas-grid">
          <article class="metrica-card">
            <span class="metrica-label">Líderes</span>
            <strong class="metrica-valor">{{ formatarNumero(resumo.total_lideres) }}</strong>
            <span class="metrica-ajuda">Coordenação atualmente cadastrada</span>
          </article>

          <article class="metrica-card">
            <span class="metrica-label">Apoiadores</span>
            <strong class="metrica-valor">{{ formatarNumero(resumo.total_apoiadores) }}</strong>
            <span class="metrica-ajuda">Base total monitorada</span>
          </article>

          <article class="metrica-card">
            <span class="metrica-label">Média por líder</span>
            <strong class="metrica-valor">{{ formatarDecimal(resumo.media_votos_por_lider) }}</strong>
            <span class="metrica-ajuda">Estimativa média de votos por liderança</span>
          </article>

          <article class="metrica-card metrica-card--forte">
            <span class="metrica-label">Potencial total</span>
            <strong class="metrica-valor">{{ formatarNumero(resumo.total_votos_estimados) }}</strong>
            <span class="metrica-ajuda">Volume máximo consolidado da base</span>
          </article>
        </div>
      </section>

      <section class="status-grid">
        <article class="status-card status-card--apoio">
          <span class="status-label">Apoiadores</span>
          <strong class="status-valor">{{ formatarNumero(resumo.total_apoiadores_confirmados) }}</strong>
          <p>Contatos já confirmados como base direta.</p>
        </article>

        <article class="status-card status-card--indeciso">
          <span class="status-label">Indecisos</span>
          <strong class="status-valor">{{ formatarNumero(resumo.total_indecisos) }}</strong>
          <p>Faixa que exige argumentação e presença territorial.</p>
        </article>

        <article class="status-card status-card--oposicao">
          <span class="status-label">Oposição</span>
          <strong class="status-valor">{{ formatarNumero(resumo.total_oposicao) }}</strong>
          <p>Mapeamento de resistência para leitura de risco local.</p>
        </article>
      </section>

      <section class="conteudo-grid">
        <article class="bloco-card bloco-card--ranking">
          <div class="bloco-header">
            <div>
              <span class="bloco-eyebrow">Performance</span>
              <h2 class="bloco-titulo">Ranking de Líderes</h2>
            </div>
            <span class="bloco-info">{{ formatarNumero(porLider.length) }} posições</span>
          </div>

          <div v-if="!porLider.length" class="bloco-vazio">Nenhum dado de liderança disponível.</div>

          <div v-else class="tabela-wrapper">
            <table class="tabela-ranking">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Líder</th>
                  <th>Bairro</th>
                  <th>Votos Est.</th>
                  <th>Vinculados</th>
                  <th>Composição</th>
                  <th>Potencial</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in porLider" :key="`${item.lider_nome}-${item.posicao_ranking}`">
                  <td>
                    <span class="ranking-pill">{{ item.posicao_ranking || '—' }}</span>
                  </td>
                  <td>
                    <div class="lider-bloco">
                      <strong>{{ item.lider_nome || 'Sem nome' }}</strong>
                      <span>{{ formatarNumero(item.apoiadores) }} apoiadores confirmados</span>
                    </div>
                  </td>
                  <td>{{ item.lider_bairro || 'Não informado' }}</td>
                  <td class="td-destaque">{{ formatarNumero(item.votos_estimados) }}</td>
                  <td>{{ formatarNumero(item.total_vinculados) }}</td>
                  <td>
                    <div class="composicao-bloco">
                      <span class="composicao-chip composicao-chip--apoio">{{ formatarNumero(item.apoiadores) }}</span>
                      <span class="composicao-chip composicao-chip--indeciso">{{ formatarNumero(item.indecisos) }}</span>
                      <span class="composicao-chip composicao-chip--oposicao">{{ formatarNumero(item.oposicao) }}</span>
                    </div>
                  </td>
                  <td>{{ formatarNumero(item.potencial_total_votos) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="bloco-card bloco-card--bairro">
          <div class="bloco-header">
            <div>
              <span class="bloco-eyebrow">Território</span>
              <h2 class="bloco-titulo">Distribuição por Bairro</h2>
            </div>
            <span class="bloco-info">{{ formatarNumero(porBairro.length) }} linhas</span>
          </div>

          <div v-if="!porBairro.length" class="bloco-vazio">Nenhum consolidado territorial disponível.</div>

          <div v-else class="tabela-wrapper">
            <table class="tabela-bairro">
              <thead>
                <tr>
                  <th>Bairro</th>
                  <th>Tipo</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in porBairro" :key="`${item.bairro}-${item.tipo}`">
                  <td>
                    <strong class="bairro-nome">{{ item.bairro || 'Não informado' }}</strong>
                  </td>
                  <td>
                    <span :class="['tipo-badge', `tipo-badge--${item.tipo || 'indefinido'}`]">
                      {{ labelTipo(item.tipo) }}
                    </span>
                  </td>
                  <td class="td-destaque">{{ formatarNumero(item.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
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

const topLider = computed(() => porLider.value[0] || null)
const bairroDestaque = computed(() => {
  if (!porBairro.value.length) return null

  return [...porBairro.value]
    .sort((a, b) => Number(b.total || 0) - Number(a.total || 0))[0]
})
const taxaConversao = computed(() => {
  if (!resumo.value?.total_apoiadores) return '0%'

  const taxa = (Number(resumo.value.total_apoiadores_confirmados || 0) / Number(resumo.value.total_apoiadores || 0)) * 100
  return `${taxa.toFixed(1).replace('.', ',')}%`
})

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

function labelTipo(tipo) {
  const mapa = {
    apoiador: 'Apoiadores',
    indeciso: 'Indecisos',
    oposicao: 'Oposição',
  }

  return mapa[tipo] || 'Indefinido'
}

async function carregarTodos() {
  carregando.value = true
  erro.value = null

  try {
    const [r1, r2, r3] = await Promise.all([
      relatorioServico.resumo(),
      relatorioServico.consolidado(),
      relatorioServico.porBairro(),
    ])

    resumo.value = r1.dados
    porLider.value = r2.dados || []
    porBairro.value = r3.dados || []
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

.hero-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.65rem 1.75rem;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(251, 191, 36, 0.26), transparent 30%),
    linear-gradient(135deg, #0f172a 0%, #172554 48%, #1d4ed8 100%);
  color: #f8fafc;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
}

.hero-eyebrow,
.bloco-eyebrow,
.destaque-eyebrow,
.metrica-label,
.status-label {
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
  margin-top: 0.9rem;
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-subtitulo {
  margin-top: 0.5rem;
  max-width: 58ch;
  color: rgba(248, 250, 252, 0.82);
  line-height: 1.7;
}

.btn-atualizar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0.85rem 1.15rem;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #dbeafe 100%);
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
  box-shadow: 0 14px 30px rgba(30, 64, 175, 0.18);
  font-family: inherit;
}

.btn-atualizar:hover {
  transform: translateY(-1px);
}

.btn-atualizar:disabled {
  opacity: 0.72;
  cursor: wait;
}

.btn-atualizar svg {
  width: 16px;
  height: 16px;
}

.estado-card,
.bloco-vazio {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 220px;
  padding: 2rem;
  border-radius: 20px;
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #64748b;
  text-align: center;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.estado-card {
  flex-direction: column;
  gap: 0.8rem;
}

.spinner {
  width: 24px;
  height: 24px;
  animation: girar 1s linear infinite;
}

.painel-superior {
  display: grid;
  grid-template-columns: 1.35fr 1fr;
  gap: 1rem;
}

.destaque-card,
.metrica-card,
.status-card,
.bloco-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.destaque-card {
  padding: 1.5rem;
  background:
    radial-gradient(circle at bottom right, rgba(251, 191, 36, 0.15), transparent 24%),
    linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
}

.destaque-eyebrow {
  color: #475569;
}

.destaque-valor {
  display: block;
  margin-top: 0.9rem;
  font-size: 2.4rem;
  font-weight: 800;
  letter-spacing: -0.05em;
  color: #0f172a;
}

.destaque-texto {
  margin-top: 0.5rem;
  max-width: 56ch;
  color: #475569;
  line-height: 1.75;
}

.destaque-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.85rem;
  margin-top: 1.25rem;
}

.destaque-item {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 0.95rem 1rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(191, 219, 254, 0.8);
}

.destaque-item span {
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #64748b;
}

.destaque-item strong {
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
}

.metricas-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.metrica-card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 1.25rem;
}

.metrica-card--forte {
  background: linear-gradient(135deg, #0f172a 0%, #172554 100%);
  color: #f8fafc;
}

.metrica-card--forte .metrica-label,
.metrica-card--forte .metrica-ajuda,
.metrica-card--forte .metrica-valor {
  color: inherit;
}

.metrica-label {
  color: #64748b;
}

.metrica-valor,
.status-valor {
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

.status-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.status-card {
  padding: 1.2rem 1.25rem;
}

.status-card p {
  margin-top: 0.35rem;
  font-size: 0.84rem;
  line-height: 1.6;
  color: #475569;
}

.status-card--apoio {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.status-card--indeciso {
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
}

.status-card--oposicao {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.conteudo-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
  gap: 1rem;
}

.bloco-card {
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
  color: #94a3b8;
}

.bloco-titulo {
  margin-top: 0.25rem;
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.03em;
}

.bloco-info {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.76rem;
  font-weight: 700;
}

.tabela-wrapper {
  overflow-x: auto;
  padding: 1rem 1.25rem 1.25rem;
}

.tabela-ranking,
.tabela-bairro {
  width: 100%;
  border-collapse: collapse;
}

.tabela-ranking th,
.tabela-ranking td,
.tabela-bairro th,
.tabela-bairro td {
  padding: 0.95rem 0.8rem;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
}

.tabela-ranking th,
.tabela-bairro th {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
}

.tabela-ranking tbody tr:hover,
.tabela-bairro tbody tr:hover {
  background: #f8fafc;
}

.ranking-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1d4ed8;
  font-weight: 800;
}

.lider-bloco {
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
}

.lider-bloco strong,
.bairro-nome {
  color: #0f172a;
  font-weight: 700;
}

.lider-bloco span {
  color: #64748b;
  font-size: 0.82rem;
}

.td-destaque {
  font-weight: 800;
  color: #1d4ed8;
}

.composicao-bloco {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.composicao-chip,
.tipo-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.composicao-chip--apoio,
.tipo-badge--apoiador {
  background: #dcfce7;
  color: #15803d;
}

.composicao-chip--indeciso,
.tipo-badge--indeciso {
  background: #ffedd5;
  color: #c2410c;
}

.composicao-chip--oposicao,
.tipo-badge--oposicao {
  background: #fee2e2;
  color: #b91c1c;
}

.tipo-badge--indefinido {
  background: #e2e8f0;
  color: #475569;
}

@keyframes girar {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 1100px) {
  .painel-superior,
  .conteudo-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .metricas-grid,
  .status-grid,
  .destaque-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .hero-card,
  .bloco-header {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-atualizar {
    width: 100%;
  }
}
</style>
