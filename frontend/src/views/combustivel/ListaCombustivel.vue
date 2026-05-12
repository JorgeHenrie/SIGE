<template>
  <div class="combustivel-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Controle de combustível</span>
        <h1 class="hero-titulo">Lançamentos de abastecimento</h1>
        <p class="hero-subtitulo">
          Registre abastecimentos por líder, acompanhe o gasto financeiro e mantenha a operação semanal e mensal sob controle.
        </p>
      </div>

      <button v-if="podeLancar" class="btn-principal" @click="$router.push({ name: 'combustivel-novo' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Novo abastecimento
      </button>
    </section>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="resumo-grid">
      <article class="resumo-card resumo-card--neutro">
        <span class="resumo-label">Total no resultado</span>
        <strong class="resumo-valor">{{ formatarNumero(store.paginacao?.total || abastecimentos.length) }}</strong>
        <span class="resumo-ajuda">Abastecimentos retornados pela busca atual</span>
      </article>
      <article class="resumo-card resumo-card--semana">
        <span class="resumo-label">Semana atual</span>
        <strong class="resumo-valor">{{ formatarMoeda(resumo.total_gasto_combustivel_semana_atual) }}</strong>
        <span class="resumo-ajuda">Gasto consolidado na semana corrente</span>
      </article>
      <article class="resumo-card resumo-card--mes">
        <span class="resumo-label">Mês atual</span>
        <strong class="resumo-valor">{{ formatarMoeda(resumo.total_gasto_combustivel_mes_atual) }}</strong>
        <span class="resumo-ajuda">Volume financeiro do mês em andamento</span>
      </article>
      <article class="resumo-card resumo-card--total">
        <span class="resumo-label">Gasto total</span>
        <strong class="resumo-valor">{{ formatarMoeda(resumo.total_gasto_combustivel) }}</strong>
        <span class="resumo-ajuda">Histórico consolidado de combustível</span>
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
          placeholder="Buscar por lider, placa, tipo, odometro, motorista, local, finalidade ou NF..."
          @input="carregar"
        />
      </div>
    </section>

    <section class="tabela-card">
      <div v-if="store.carregando" class="estado-card">
        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <p>Atualizando os lançamentos de combustível...</p>
      </div>

      <div v-else-if="!abastecimentos.length" class="estado-card estado-card--vazio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 21h18"/>
          <path d="M7 21V8l5-5 5 5v13"/>
          <path d="M10 12h4"/>
          <path d="M10 16h4"/>
        </svg>
        <h2>Nenhum abastecimento encontrado</h2>
        <p>Abra um lançamento para começar a controlar os gastos de combustível dos líderes.</p>
        <button v-if="podeLancar" class="btn-secundario" @click="$router.push({ name: 'combustivel-novo' })">
          Registrar abastecimento
        </button>
      </div>

      <div v-else class="tabela-wrapper">
        <table class="tabela-combustivel">
          <thead>
            <tr>
              <th>Líder</th>
              <th>Veículo</th>
              <th>Nota</th>
              <th>Data</th>
              <th>Valor</th>
              <th>Lançamento</th>
              <th class="th-acoes">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in abastecimentos" :key="item.id">
              <td>
                <div class="lider-bloco">
                  <span class="lider-avatar">{{ iniciais(item.lider_nome) }}</span>
                  <div>
                    <strong>{{ item.lider_nome || 'Sem líder' }}</strong>
                    <span>{{ item.lider_bairro || 'Sem bairro informado' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="veiculo-bloco">
                  <strong>{{ item.veiculo_descricao || 'Veiculo sem descricao' }}</strong>
                  <span>{{ formatarPlaca(item.placa_veiculo) }} • {{ formatarTipoCombustivel(item.tipo_combustivel) }}</span>
                  <span>{{ item.motorista_nome || 'Motorista nao informado' }} • Odometro {{ formatarOdometro(item.odometro_atual) }}</span>
                </div>
              </td>
              <td>
                <div class="nota-bloco">
                  <strong>{{ item.local_abastecimento || 'Local nao informado' }}</strong>
                  <span>{{ formatarLitros(item.litros_abastecidos) }} • NF {{ item.numero_nota_fiscal || '—' }}</span>
                  <span>{{ resumirTexto(item.finalidade || item.observacoes, 58) }}</span>
                  <a v-if="item.foto_nota_fiscal_caminho" :href="resolverUrlArquivo(item.foto_nota_fiscal_caminho)" target="_blank" rel="noreferrer" class="nota-link">Ver foto da nota</a>
                </div>
              </td>
              <td>
                <div class="data-bloco">
                  <strong>{{ formatarDataHora(item.data_abastecimento) }}</strong>
                  <span>{{ dataRelativa(item.data_abastecimento) }}</span>
                </div>
              </td>
              <td class="td-destaque">{{ formatarMoeda(item.valor_total) }}</td>
              <td>
                <div class="lancamento-bloco">
                  <strong>{{ item.criado_por_usuario_nome || 'Lançado pelo líder' }}</strong>
                  <span>{{ formatarDataHora(item.criado_em) }}</span>
                </div>
              </td>
              <td>
                <div class="acoes-linha">
                  <button v-if="podeEditar(item)" class="btn-acao btn-acao--editar" @click="editar(item.id)" title="Editar abastecimento">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>

                  <button v-if="podeRemover(item)" class="btn-acao btn-acao--remover" @click="remover(item.id)" title="Remover abastecimento">
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
import { useCombustivelStore } from '@/stores/combustivelStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import relatorioServico from '@/services/relatorioServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const roteador = useRouter()
const store = useCombustivelStore()
const authStore = useAuthStore()

const busca = ref('')
const resumo = ref({})

const abastecimentos = computed(() => store.abastecimentos || [])
const ehLider = computed(() => authStore.usuario?.perfil === 'lider')
const ehGestor = computed(() => ['admin', 'gestor'].includes(authStore.usuario?.perfil))
const podeLancar = computed(() => ehLider.value || ehGestor.value)

onMounted(carregarPainel)

async function carregarPainel() {
  await Promise.all([
    store.carregarAbastecimentos(1, 15, busca.value),
    carregarResumo(),
  ])
}

async function carregar() {
  await store.carregarAbastecimentos(1, 15, busca.value)
}

async function carregarResumo() {
  const resposta = await relatorioServico.resumo()
  resumo.value = resposta.dados || {}
}

function editar(id) {
  roteador.push({ name: 'combustivel-editar', params: { id } })
}

function podeEditar(item) {
  if (ehGestor.value) return true
  return ehLider.value && authStore.usuario?.id === item.lider_id
}

function podeRemover(item) {
  return podeEditar(item)
}

async function remover(id) {
  if (!confirm('Confirma a remoção deste lançamento de combustível?')) return

  await store.removerAbastecimento(id)
  await carregarPainel()
}

function iniciais(valor) {
  return (valor || 'CB')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join('')
}

function resumirTexto(valor, limite = 42) {
  if (!valor) return 'Sem detalhes complementares'
  return valor.length > limite ? `${valor.slice(0, limite)}...` : valor
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

function formatarLitros(valor) {
  if (valor === null || valor === undefined || valor === '') return '—'

  return `${Number(valor).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} L`
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

function formatarOdometro(valor) {
  if (valor === null || valor === undefined || valor === '') return '—'
  return Number(valor).toLocaleString('pt-BR')
}

function resolverUrlArquivo(caminho) {
  if (!caminho) return '#'
  if (/^https?:\/\//i.test(caminho)) return caminho

  return new URL(caminho, import.meta.env.VITE_API_URL || 'http://localhost:8000').toString()
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

function dataRelativa(valor) {
  if (!valor) return 'Sem data'

  const data = new Date(valor.replace(' ', 'T'))
  const hoje = new Date()
  const diferencaDias = Math.round((hoje - data) / (1000 * 60 * 60 * 24))

  if (diferencaDias === 0) return 'Hoje'
  if (diferencaDias === 1) return 'Ontem'
  if (diferencaDias > 1) return `${diferencaDias} dias atrás`
  return 'Programado'
}
</script>

<style scoped>
.combustivel-page { display: flex; flex-direction: column; gap: 1.25rem; }
.hero-card { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.6rem 1.75rem; border-radius: 22px; background: radial-gradient(circle at top right, rgba(251, 191, 36, 0.2), transparent 28%), linear-gradient(135deg, #111827 0%, #7c2d12 48%, #f97316 100%); color: #fff7ed; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16); }
.hero-eyebrow, .resumo-label { font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.hero-eyebrow { display: inline-flex; padding: 0.35rem 0.72rem; border-radius: 999px; background: rgba(255, 255, 255, 0.14); }
.hero-titulo { margin-top: 0.95rem; font-size: 2rem; font-weight: 800; letter-spacing: -0.04em; }
.hero-subtitulo { margin-top: 0.55rem; max-width: 60ch; line-height: 1.7; color: rgba(255, 247, 237, 0.84); }
.btn-principal, .btn-secundario { display: inline-flex; align-items: center; justify-content: center; gap: 0.55rem; padding: 0.85rem 1.15rem; border: none; border-radius: 12px; cursor: pointer; font-weight: 800; transition: transform 0.16s ease; font-family: inherit; }
.btn-principal:hover, .btn-secundario:hover { transform: translateY(-1px); }
.btn-principal { background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); color: #7c2d12; }
.btn-principal svg { width: 16px; height: 16px; }
.btn-secundario { background: #111827; color: #f8fafc; }
.resumo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.resumo-card, .toolbar-card, .tabela-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); }
.resumo-card { padding: 1.15rem 1.2rem; }
.resumo-card--neutro { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); }
.resumo-card--semana { background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); }
.resumo-card--mes { background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); }
.resumo-card--total { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
.resumo-label { color: #64748b; }
.resumo-valor { display: block; margin-top: 0.35rem; font-size: 1.8rem; font-weight: 800; color: #0f172a; }
.resumo-ajuda { display: block; margin-top: 0.15rem; font-size: 0.82rem; line-height: 1.55; color: #64748b; }
.toolbar-card { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.1rem; }
.busca-wrapper { position: relative; flex: 1; }
.busca-icone { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; }
.busca-input { width: 100%; padding: 0.8rem 0.95rem 0.8rem 2.8rem; border: 1.5px solid #e2e8f0; border-radius: 14px; background: #f8fafc; color: #0f172a; font-size: 0.92rem; font-family: inherit; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease; }
.busca-input:focus { border-color: #f97316; background: #fff; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12); }
.estado-card { min-height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.8rem; padding: 2rem; text-align: center; color: #64748b; }
.estado-card h2 { color: #0f172a; font-size: 1.25rem; font-weight: 800; }
.estado-card p { max-width: 46ch; line-height: 1.7; }
.estado-card--vazio svg, .spinner { width: 24px; height: 24px; }
.spinner { animation: girar 1s linear infinite; }
.tabela-wrapper { overflow-x: auto; }
.tabela-combustivel { width: 100%; border-collapse: collapse; }
.tabela-combustivel th, .tabela-combustivel td { padding: 1rem 1.15rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.tabela-combustivel th { font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; background: #f8fafc; }
.tabela-combustivel tbody tr:hover { background: #f8fafc; }
.lider-bloco, .veiculo-bloco, .nota-bloco, .data-bloco, .lancamento-bloco { display: flex; flex-direction: column; gap: 0.18rem; }
.lider-bloco { flex-direction: row; align-items: center; gap: 0.8rem; }
.lider-avatar { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 14px; background: linear-gradient(135deg, #ffedd5 0%, #fdba74 100%); color: #9a3412; font-size: 0.84rem; font-weight: 800; }
.lider-bloco strong, .veiculo-bloco strong, .nota-bloco strong, .data-bloco strong, .lancamento-bloco strong { color: #0f172a; font-weight: 700; }
.lider-bloco span, .veiculo-bloco span, .nota-bloco span, .data-bloco span, .lancamento-bloco span { color: #64748b; font-size: 0.82rem; }
.nota-link { margin-top: 0.12rem; color: #1d4ed8; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.nota-link:hover { text-decoration: underline; }
.td-destaque { font-weight: 800; color: #c2410c; }
.th-acoes { text-align: right; }
.acoes-linha { display: flex; justify-content: flex-end; gap: 0.5rem; }
.btn-acao { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: 12px; cursor: pointer; transition: transform 0.15s ease, opacity 0.15s ease; }
.btn-acao:hover { transform: translateY(-1px); }
.btn-acao svg { width: 16px; height: 16px; }
.btn-acao--editar { background: #eff6ff; color: #1d4ed8; }
.btn-acao--remover { background: #fef2f2; color: #dc2626; }
@keyframes girar { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@media (max-width: 1100px) { .resumo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 768px) { .hero-card { flex-direction: column; align-items: stretch; } .btn-principal { width: 100%; } .resumo-grid { grid-template-columns: 1fr; } }
</style>