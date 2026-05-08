<template>
  <div class="agenda-page">
    <section class="hero-card">
      <div class="hero-texto">
        <span class="hero-eyebrow">Agenda política</span>
        <h1 class="hero-titulo">Solicitações de eventos</h1>
        <p class="hero-subtitulo">
          Líderes registram pedidos de visita e reunião. Gestores confirmam ou recusam conforme a disponibilidade do deputado.
        </p>
      </div>

      <button v-if="podeCriar" class="btn-principal" @click="$router.push({ name: 'agenda-nova' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nova solicitação
      </button>
    </section>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="resumo-grid">
      <article class="resumo-card resumo-card--neutro">
        <span class="resumo-label">Total no resultado</span>
        <strong class="resumo-valor">{{ formatarNumero(store.paginacao?.total || eventos.length) }}</strong>
        <span class="resumo-ajuda">Solicitações retornadas pelo filtro atual</span>
      </article>
      <article class="resumo-card resumo-card--pendente">
        <span class="resumo-label">Pendentes</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.pendente) }}</strong>
        <span class="resumo-ajuda">Aguardando análise do gestor</span>
      </article>
      <article class="resumo-card resumo-card--aprovado">
        <span class="resumo-label">Aprovadas</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.aprovado) }}</strong>
        <span class="resumo-ajuda">Datas já confirmadas</span>
      </article>
      <article class="resumo-card resumo-card--recusado">
        <span class="resumo-label">Recusadas</span>
        <strong class="resumo-valor">{{ formatarNumero(resumoStatus.recusado) }}</strong>
        <span class="resumo-ajuda">Pedidos devolvidos com justificativa</span>
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
          placeholder="Buscar por título, líder ou local..."
          @input="carregar"
        />
      </div>

      <select v-model="statusFiltro" class="status-select" @change="carregar">
        <option value="">Todos os status</option>
        <option value="pendente">Pendentes</option>
        <option value="aprovado">Aprovadas</option>
        <option value="recusado">Recusadas</option>
      </select>
    </section>

    <section class="tabela-card">
      <div v-if="store.carregando" class="estado-card">
        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <p>Atualizando a agenda política...</p>
      </div>

      <div v-else-if="!eventos.length" class="estado-card estado-card--vazio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <h2>Nenhuma solicitação encontrada</h2>
        <p>Ajuste os filtros ou abra uma nova solicitação para começar a organizar a agenda.</p>
        <button v-if="podeCriar" class="btn-secundario" @click="$router.push({ name: 'agenda-nova' })">
          Abrir solicitação
        </button>
      </div>

      <div v-else class="tabela-wrapper">
        <table class="tabela-agenda">
          <thead>
            <tr>
              <th>Evento</th>
              <th>Líder</th>
              <th>Janela solicitada</th>
              <th>Status</th>
              <th>Decisão</th>
              <th class="th-acoes">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="evento in eventos" :key="evento.id">
              <td>
                <div class="evento-bloco">
                  <span class="evento-avatar">{{ iniciais(evento.titulo) }}</span>
                  <div>
                    <strong class="evento-titulo">{{ evento.titulo }}</strong>
                    <span class="evento-meta">{{ labelTipo(evento.tipo) }} • {{ evento.local_evento || 'Local a definir' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="evento-lider">
                  <strong>{{ evento.lider_nome }}</strong>
                  <span>{{ evento.lider_bairro || 'Sem bairro informado' }}</span>
                </div>
              </td>
              <td>
                <div class="evento-data">
                  <strong>{{ formatarDataHora(evento.data_solicitada_inicio) }}</strong>
                  <span>{{ evento.data_solicitada_fim ? `até ${formatarHora(evento.data_solicitada_fim)}` : 'horário aberto' }}</span>
                </div>
              </td>
              <td>
                <span :class="['status-badge', `status-badge--${evento.status}`]">{{ labelStatus(evento.status) }}</span>
              </td>
              <td>
                <div class="evento-decisao">
                  <strong>{{ evento.decidido_por_nome || 'Aguardando decisão' }}</strong>
                  <span>{{ evento.decidido_em ? formatarDataHora(evento.decidido_em) : 'Sem decisão registrada' }}</span>
                </div>
              </td>
              <td>
                <div class="acoes-linha">
                  <button v-if="podeEditarEvento(evento)" class="btn-acao btn-acao--editar" @click="editar(evento.id)" title="Editar solicitação">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>

                  <button v-if="podeDecidirEvento(evento)" class="btn-acao btn-acao--aprovar" @click="abrirDecisao(evento, 'aprovar')" title="Aprovar solicitação">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                  </button>

                  <button v-if="podeDecidirEvento(evento)" class="btn-acao btn-acao--recusar" @click="abrirDecisao(evento, 'recusar')" title="Recusar solicitação">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="18" y1="6" x2="6" y2="18"/>
                      <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                  </button>

                  <button v-if="podeRemoverEvento(evento)" class="btn-acao btn-acao--remover" @click="remover(evento.id)" title="Remover solicitação">
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

    <div v-if="modal.aberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-card">
        <div class="modal-header">
          <div>
            <span class="modal-eyebrow">{{ modal.modo === 'aprovar' ? 'Confirmação' : 'Devolutiva' }}</span>
            <h2 class="modal-titulo">{{ modal.modo === 'aprovar' ? 'Aprovar solicitação' : 'Recusar solicitação' }}</h2>
          </div>
          <button class="modal-fechar" @click="fecharModal">×</button>
        </div>

        <p class="modal-subtitulo">{{ modal.evento?.titulo }} • {{ modal.evento?.lider_nome }}</p>

        <div v-if="modal.erro" class="modal-erro">{{ modal.erro }}</div>

        <div v-if="modal.modo === 'aprovar'" class="modal-grid">
          <div class="form-grupo">
            <label class="form-label" for="data_confirmada_inicio">Início confirmado</label>
            <input id="data_confirmada_inicio" v-model="modal.data_confirmada_inicio" class="form-input" type="datetime-local" />
          </div>
          <div class="form-grupo">
            <label class="form-label" for="data_confirmada_fim">Fim confirmado</label>
            <input id="data_confirmada_fim" v-model="modal.data_confirmada_fim" class="form-input" type="datetime-local" />
          </div>
        </div>

        <div class="form-grupo">
          <label class="form-label" for="observacoes_decisao">
            {{ modal.modo === 'aprovar' ? 'Observações da decisão' : 'Motivo da recusa' }}
          </label>
          <textarea id="observacoes_decisao" v-model="modal.observacoes_decisao" class="form-textarea" rows="4"></textarea>
        </div>

        <div class="modal-acoes">
          <button class="btn-modal btn-modal--cancelar" @click="fecharModal">Cancelar</button>
          <button class="btn-modal" :class="modal.modo === 'aprovar' ? 'btn-modal--aprovar' : 'btn-modal--recusar'" @click="confirmarDecisao" :disabled="modal.carregando">
            {{ modal.carregando ? 'Enviando...' : (modal.modo === 'aprovar' ? 'Confirmar aprovação' : 'Confirmar recusa') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAgendaStore } from '@/stores/agendaStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const roteador = useRouter()
const store = useAgendaStore()
const authStore = useAuthStore()

const busca = ref('')
const statusFiltro = ref('')
const modal = reactive({
  aberto: false,
  modo: 'aprovar',
  evento: null,
  data_confirmada_inicio: '',
  data_confirmada_fim: '',
  observacoes_decisao: '',
  carregando: false,
  erro: '',
})

const eventos = computed(() => store.eventos || [])
const ehLider = computed(() => authStore.usuario?.perfil === 'lider')
const ehGestor = computed(() => ['admin', 'gestor'].includes(authStore.usuario?.perfil))
const podeCriar = computed(() => ehLider.value || ehGestor.value)
const resumoStatus = computed(() => {
  return eventos.value.reduce((acc, evento) => {
    const chave = evento.status || 'pendente'
    acc[chave] = (acc[chave] || 0) + 1
    return acc
  }, { pendente: 0, aprovado: 0, recusado: 0 })
})

onMounted(carregar)

function carregar() {
  store.carregarEventos(1, 15, busca.value, statusFiltro.value)
}

function editar(id) {
  roteador.push({ name: 'agenda-editar', params: { id } })
}

function podeEditarEvento(evento) {
  if (evento.status !== 'pendente') return false
  if (ehGestor.value) return true
  return ehLider.value && authStore.usuario?.id === evento.lider_id
}

function podeDecidirEvento(evento) {
  return ehGestor.value && evento.status === 'pendente'
}

function podeRemoverEvento(evento) {
  return podeEditarEvento(evento)
}

function abrirDecisao(evento, modo) {
  modal.aberto = true
  modal.modo = modo
  modal.evento = evento
  modal.erro = ''
  modal.observacoes_decisao = ''
  modal.data_confirmada_inicio = paraInputDataHora(evento.data_solicitada_inicio)
  modal.data_confirmada_fim = paraInputDataHora(evento.data_solicitada_fim)
}

function fecharModal() {
  modal.aberto = false
  modal.evento = null
  modal.data_confirmada_inicio = ''
  modal.data_confirmada_fim = ''
  modal.observacoes_decisao = ''
  modal.carregando = false
  modal.erro = ''
}

async function confirmarDecisao() {
  if (!modal.evento) return

  modal.carregando = true
  modal.erro = ''

  try {
    if (modal.modo === 'aprovar') {
      await store.aprovarEvento(modal.evento.id, {
        data_confirmada_inicio: modal.data_confirmada_inicio,
        data_confirmada_fim: modal.data_confirmada_fim || null,
        observacoes_decisao: modal.observacoes_decisao,
      })
    } else {
      await store.recusarEvento(modal.evento.id, {
        observacoes_decisao: modal.observacoes_decisao,
      })
    }

    fecharModal()
    carregar()
  } catch (e) {
    modal.erro = e.message
  } finally {
    modal.carregando = false
  }
}

async function remover(id) {
  if (!confirm('Confirma a remoção desta solicitação de agenda?')) return

  await store.removerEvento(id)
  carregar()
}

function iniciais(valor) {
  return (valor || 'AG')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join('')
}

function formatarNumero(valor) {
  return Number(valor || 0).toLocaleString('pt-BR')
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

function formatarHora(valor) {
  if (!valor) return '—'

  return new Date(valor.replace(' ', 'T')).toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function paraInputDataHora(valor) {
  return valor ? valor.replace(' ', 'T').slice(0, 16) : ''
}

function labelTipo(tipo) {
  const mapa = { visita: 'Visita', reuniao: 'Reunião', outro: 'Outro' }
  return mapa[tipo] || 'Outro'
}

function labelStatus(status) {
  const mapa = { pendente: 'Pendente', aprovado: 'Aprovado', recusado: 'Recusado' }
  return mapa[status] || 'Indefinido'
}
</script>

<style scoped>
.agenda-page { display: flex; flex-direction: column; gap: 1.25rem; }
.hero-card { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.6rem 1.75rem; border-radius: 22px; background: radial-gradient(circle at top right, rgba(34, 197, 94, 0.16), transparent 28%), linear-gradient(135deg, #0f172a 0%, #1f2937 48%, #0f766e 100%); color: #f8fafc; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16); }
.hero-eyebrow, .resumo-label, .modal-eyebrow { font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.hero-eyebrow { display: inline-flex; padding: 0.35rem 0.72rem; border-radius: 999px; background: rgba(255, 255, 255, 0.14); }
.hero-titulo { margin-top: 0.95rem; font-size: 2rem; font-weight: 800; letter-spacing: -0.04em; }
.hero-subtitulo { margin-top: 0.55rem; max-width: 58ch; line-height: 1.7; color: rgba(248, 250, 252, 0.82); }
.btn-principal, .btn-secundario, .btn-modal { display: inline-flex; align-items: center; justify-content: center; gap: 0.55rem; padding: 0.85rem 1.15rem; border: none; border-radius: 12px; cursor: pointer; font-weight: 800; transition: transform 0.16s ease; font-family: inherit; }
.btn-principal:hover, .btn-secundario:hover, .btn-modal:hover { transform: translateY(-1px); }
.btn-principal { background: linear-gradient(135deg, #f8fafc 0%, #d1fae5 100%); color: #0f172a; }
.btn-principal svg { width: 16px; height: 16px; }
.btn-secundario { background: #0f172a; color: #f8fafc; }
.resumo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.resumo-card, .toolbar-card, .tabela-card, .modal-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); }
.resumo-card { padding: 1.15rem 1.2rem; }
.resumo-card--neutro { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); }
.resumo-card--pendente { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); }
.resumo-card--aprovado { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
.resumo-card--recusado { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }
.resumo-label { color: #64748b; }
.resumo-valor { display: block; margin-top: 0.35rem; font-size: 1.8rem; font-weight: 800; color: #0f172a; }
.resumo-ajuda { display: block; margin-top: 0.15rem; font-size: 0.82rem; line-height: 1.55; color: #64748b; }
.toolbar-card { display: flex; align-items: center; gap: 1rem; justify-content: space-between; padding: 1rem 1.1rem; }
.busca-wrapper { position: relative; flex: 1; }
.busca-icone { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; }
.busca-input, .status-select, .form-input, .form-textarea { width: 100%; padding: 0.8rem 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 14px; background: #f8fafc; color: #0f172a; font-size: 0.92rem; font-family: inherit; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease; }
.busca-input { padding-left: 2.8rem; }
.busca-input:focus, .status-select:focus, .form-input:focus, .form-textarea:focus { border-color: #0f766e; background: #fff; box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12); }
.status-select { max-width: 220px; }
.estado-card { min-height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.8rem; padding: 2rem; text-align: center; color: #64748b; }
.estado-card h2 { color: #0f172a; font-size: 1.25rem; font-weight: 800; }
.estado-card p { max-width: 46ch; line-height: 1.7; }
.estado-card--vazio svg, .spinner { width: 24px; height: 24px; }
.spinner { animation: girar 1s linear infinite; }
.tabela-wrapper { overflow-x: auto; }
.tabela-agenda { width: 100%; border-collapse: collapse; }
.tabela-agenda th, .tabela-agenda td { padding: 1rem 1.15rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.tabela-agenda th { font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; background: #f8fafc; }
.tabela-agenda tbody tr:hover { background: #f8fafc; }
.th-acoes { text-align: right; }
.evento-bloco, .evento-lider, .evento-data, .evento-decisao { display: flex; flex-direction: column; gap: 0.18rem; }
.evento-bloco { flex-direction: row; align-items: center; gap: 0.8rem; }
.evento-avatar { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 14px; background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; font-size: 0.82rem; font-weight: 800; flex-shrink: 0; }
.evento-titulo, .evento-lider strong, .evento-data strong, .evento-decisao strong { color: #0f172a; font-weight: 700; }
.evento-meta, .evento-lider span, .evento-data span, .evento-decisao span { font-size: 0.82rem; color: #64748b; }
.status-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 100px; padding: 0.42rem 0.78rem; border-radius: 999px; font-size: 0.74rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
.status-badge--pendente { background: #ffedd5; color: #c2410c; }
.status-badge--aprovado { background: #dcfce7; color: #15803d; }
.status-badge--recusado { background: #fee2e2; color: #b91c1c; }
.acoes-linha { display: flex; justify-content: flex-end; gap: 0.45rem; }
.btn-acao { width: 36px; height: 36px; border: none; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.14s ease; }
.btn-acao:hover { transform: translateY(-1px); }
.btn-acao svg { width: 16px; height: 16px; }
.btn-acao--editar { background: #eff6ff; color: #2563eb; }
.btn-acao--aprovar { background: #ecfdf5; color: #15803d; }
.btn-acao--recusar, .btn-acao--remover { background: #fef2f2; color: #dc2626; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.52); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 60; }
.modal-card { width: min(100%, 560px); padding: 1.4rem; }
.modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.modal-eyebrow { color: #94a3b8; }
.modal-titulo { margin-top: 0.25rem; font-size: 1.2rem; font-weight: 800; color: #0f172a; }
.modal-subtitulo { margin-top: 0.5rem; color: #64748b; }
.modal-fechar { width: 32px; height: 32px; border: none; border-radius: 999px; background: #f1f5f9; color: #475569; cursor: pointer; font-size: 1.35rem; line-height: 1; }
.modal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-top: 1rem; }
.form-grupo { display: flex; flex-direction: column; gap: 0.42rem; margin-top: 1rem; }
.form-label { font-size: 0.8rem; font-weight: 700; color: #475569; }
.form-textarea { min-height: 120px; resize: vertical; }
.modal-erro { margin-top: 1rem; padding: 0.85rem 1rem; border-radius: 12px; background: #fef2f2; color: #b91c1c; font-size: 0.86rem; }
.modal-acoes { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.2rem; }
.btn-modal--cancelar { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
.btn-modal--aprovar { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); color: #fff; }
.btn-modal--recusar { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); color: #fff; }
@keyframes girar { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@media (max-width: 1024px) { .resumo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 768px) { .hero-card, .toolbar-card, .modal-acoes { flex-direction: column; } .modal-grid { grid-template-columns: 1fr; } .resumo-grid { grid-template-columns: 1fr; } .status-select, .btn-principal, .btn-secundario, .btn-modal { width: 100%; max-width: none; } }
</style>