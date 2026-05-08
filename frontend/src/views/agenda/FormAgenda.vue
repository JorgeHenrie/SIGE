<template>
  <div class="form-page">
    <div class="form-page-header">
      <button class="btn-voltar" @click="$router.push({ name: 'agenda' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar para Agenda
      </button>

      <span class="page-chip">{{ editando ? labelStatus(statusAtual) : 'Nova solicitação' }}</span>
    </div>

    <div class="form-card">
      <div class="form-card-header" :class="editando ? 'form-card-header--editar' : 'form-card-header--novo'">
        <div class="form-card-header-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>

        <div>
          <h1 class="form-card-titulo">{{ editando ? 'Editar Solicitação' : 'Nova Solicitação' }}</h1>
          <p class="form-card-subtitulo">
            {{ editando ? 'Ajuste os detalhes da solicitação enquanto ela ainda estiver pendente.' : 'Registre uma visita ou reunião para análise do gestor responsável.' }}
          </p>
        </div>
      </div>

      <div class="form-card-body">
        <AlertaMensagem v-if="mensagem.texto" :tipo="mensagem.tipo" :mensagem="mensagem.texto" />

        <div v-if="!podeCriar && !editando" class="bloqueio-card">
          Seu perfil não possui permissão para abrir solicitações de agenda.
        </div>

        <form v-else @submit.prevent="salvar">
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">1</span>
              Contexto do evento
            </div>

            <div class="form-grid form-grid--2">
              <div v-if="mostrarSelectorLider" class="form-grupo form-grupo--span2">
                <label class="form-label" for="lider_id">Líder responsável <span class="obrigatorio">*</span></label>
                <select id="lider_id" v-model="form.lider_id" class="form-select" required :disabled="somenteLeitura">
                  <option value="">Selecione um líder...</option>
                  <option v-for="lider in lideres" :key="lider.id" :value="lider.id">{{ lider.nome }}</option>
                </select>
              </div>

              <div class="form-grupo form-grupo--span2">
                <label class="form-label" for="titulo">Título da solicitação <span class="obrigatorio">*</span></label>
                <input id="titulo" v-model="form.titulo" type="text" class="form-input" required :disabled="somenteLeitura" placeholder="Ex: Reunião com lideranças do centro" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="tipo">Tipo do evento</label>
                <select id="tipo" v-model="form.tipo" class="form-select" :disabled="somenteLeitura">
                  <option value="reuniao">Reunião</option>
                  <option value="visita">Visita</option>
                  <option value="outro">Outro</option>
                </select>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="local_evento">Local previsto</label>
                <input id="local_evento" v-model="form.local_evento" type="text" class="form-input" :disabled="somenteLeitura" placeholder="Ex: Câmara Municipal, gabinete, bairro..." />
              </div>
            </div>
          </div>

          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">2</span>
              Janela solicitada
            </div>

            <div class="form-grid form-grid--2">
              <div class="form-grupo">
                <label class="form-label" for="data_solicitada_inicio">Início desejado <span class="obrigatorio">*</span></label>
                <input id="data_solicitada_inicio" v-model="form.data_solicitada_inicio" type="datetime-local" class="form-input" required :disabled="somenteLeitura" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="data_solicitada_fim">Fim desejado</label>
                <input id="data_solicitada_fim" v-model="form.data_solicitada_fim" type="datetime-local" class="form-input" :disabled="somenteLeitura" />
              </div>
            </div>
          </div>

          <div class="form-secao form-secao--ultima">
            <div class="form-secao-titulo">
              <span class="form-secao-num">3</span>
              Informações de apoio
            </div>

            <div class="form-grupo">
              <label class="form-label" for="descricao">Descrição do pedido</label>
              <textarea id="descricao" v-model="form.descricao" class="form-textarea" rows="4" :disabled="somenteLeitura" placeholder="Contextualize o objetivo político ou institucional do encontro."></textarea>
            </div>

            <div class="form-grupo">
              <label class="form-label" for="observacoes_solicitacao">Observações da solicitação</label>
              <textarea id="observacoes_solicitacao" v-model="form.observacoes_solicitacao" class="form-textarea" rows="4" :disabled="somenteLeitura" placeholder="Registre urgência, expectativa, nomes envolvidos e detalhes operacionais."></textarea>
            </div>

            <div v-if="editando && eventoAtual?.observacoes_decisao" class="devolutiva-card">
              <span class="devolutiva-label">Devolutiva do gestor</span>
              <p>{{ eventoAtual.observacoes_decisao }}</p>
            </div>
          </div>

          <div class="form-acoes">
            <button type="button" class="form-btn form-btn--cancelar" @click="$router.push({ name: 'agenda' })">Cancelar</button>
            <button v-if="!somenteLeitura" type="submit" class="form-btn form-btn--salvar" :disabled="carregando || !podeCriar">
              {{ carregando ? 'Salvando...' : (editando ? 'Salvar Alterações' : 'Registrar Solicitação') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAgendaStore } from '@/stores/agendaStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import liderServico from '@/services/liderServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota = useRoute()
const roteador = useRouter()
const store = useAgendaStore()
const authStore = useAuthStore()

const editando = computed(() => !!rota.params.id)
const ehLider = computed(() => authStore.usuario?.perfil === 'lider')
const ehGestor = computed(() => ['admin', 'gestor'].includes(authStore.usuario?.perfil))
const podeCriar = computed(() => ehLider.value || ehGestor.value)

const carregando = ref(false)
const lideres = ref([])
const statusAtual = ref('pendente')
const eventoAtual = ref(null)
const mensagem = reactive({ tipo: '', texto: '' })

const form = reactive({
  lider_id: rota.query.lider_id || '',
  titulo: '',
  tipo: 'reuniao',
  local_evento: '',
  data_solicitada_inicio: '',
  data_solicitada_fim: '',
  descricao: '',
  observacoes_solicitacao: '',
})

const mostrarSelectorLider = computed(() => !ehLider.value)
const somenteLeitura = computed(() => editando.value && statusAtual.value !== 'pendente')

onMounted(async () => {
  if (mostrarSelectorLider.value) {
    const resposta = await liderServico.listar(1, 100)
    lideres.value = resposta.dados || []
  }

  if (editando.value) {
    const evento = await store.buscarEvento(rota.params.id)
    eventoAtual.value = evento

    if (evento) {
      statusAtual.value = evento.status || 'pendente'
      Object.assign(form, {
        lider_id: evento.lider_id || '',
        titulo: evento.titulo || '',
        tipo: evento.tipo || 'reuniao',
        local_evento: evento.local_evento || '',
        data_solicitada_inicio: paraInputDataHora(evento.data_solicitada_inicio),
        data_solicitada_fim: paraInputDataHora(evento.data_solicitada_fim),
        descricao: evento.descricao || '',
        observacoes_solicitacao: evento.observacoes_solicitacao || '',
      })
    }
  }
})

async function salvar() {
  mensagem.texto = ''

  if (form.data_solicitada_fim && form.data_solicitada_fim < form.data_solicitada_inicio) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'A data final precisa ser maior ou igual à data inicial.'
    return
  }

  carregando.value = true

  try {
    const payload = {
      titulo: form.titulo,
      tipo: form.tipo,
      local_evento: form.local_evento,
      data_solicitada_inicio: form.data_solicitada_inicio,
      data_solicitada_fim: form.data_solicitada_fim || null,
      descricao: form.descricao,
      observacoes_solicitacao: form.observacoes_solicitacao,
    }

    if (mostrarSelectorLider.value) {
      payload.lider_id = form.lider_id
    }

    if (editando.value) {
      await store.atualizarEvento(rota.params.id, payload)
      mensagem.tipo = 'sucesso'
      mensagem.texto = 'Solicitação atualizada com sucesso.'
    } else {
      await store.cadastrarEvento(payload)
      roteador.push({ name: 'agenda' })
    }
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = e.message
  } finally {
    carregando.value = false
  }
}

function labelStatus(status) {
  const mapa = { pendente: 'Pendente', aprovado: 'Aprovado', recusado: 'Recusado' }
  return mapa[status] || 'Indefinido'
}

function paraInputDataHora(valor) {
  return valor ? valor.replace(' ', 'T').slice(0, 16) : ''
}
</script>

<style scoped>
.form-page { display: flex; flex-direction: column; gap: 1.25rem; max-width: 920px; }
.form-page-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.btn-voltar { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0; border: none; background: none; color: #64748b; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
.btn-voltar svg { width: 16px; height: 16px; }
.page-chip { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.85rem; border-radius: 999px; background: #ecfeff; color: #0f766e; font-size: 0.78rem; font-weight: 800; }
.form-card { overflow: hidden; border-radius: 22px; background: #fff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08); }
.form-card-header { display: flex; align-items: center; gap: 1.2rem; padding: 1.8rem 2rem; color: #fff; }
.form-card-header--novo { background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%), linear-gradient(135deg, #0f172a 0%, #115e59 50%, #14b8a6 100%); }
.form-card-header--editar { background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%), linear-gradient(135deg, #1e293b 0%, #1d4ed8 55%, #38bdf8 100%); }
.form-card-header-icone { width: 56px; height: 56px; padding: 0.85rem; border-radius: 16px; background: rgba(255, 255, 255, 0.18); display: flex; align-items: center; justify-content: center; }
.form-card-header-icone svg { width: 100%; height: 100%; }
.form-card-titulo { font-size: 1.45rem; font-weight: 800; letter-spacing: -0.03em; }
.form-card-subtitulo { margin-top: 0.3rem; max-width: 56ch; color: rgba(255, 255, 255, 0.82); font-size: 0.9rem; line-height: 1.65; }
.form-card-body { padding: 2rem; }
.bloqueio-card, .devolutiva-card { padding: 1rem 1.1rem; border-radius: 16px; font-size: 0.9rem; line-height: 1.65; }
.bloqueio-card { background: #fef2f2; color: #b91c1c; }
.devolutiva-card { background: #eff6ff; color: #1e3a8a; }
.devolutiva-label { display: block; font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.35rem; }
.form-secao { display: flex; flex-direction: column; gap: 1rem; padding: 1.45rem 0; border-bottom: 1px solid #f1f5f9; }
.form-secao--ultima { border-bottom: none; }
.form-secao-titulo { display: flex; align-items: center; gap: 0.65rem; color: #94a3b8; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.form-secao-num { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ccfbf1; color: #0f766e; font-size: 0.74rem; }
.form-grid { display: grid; gap: 1rem; }
.form-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.form-grupo { display: flex; flex-direction: column; gap: 0.42rem; }
.form-grupo--span2 { grid-column: span 2; }
.form-label { font-size: 0.82rem; font-weight: 700; color: #475569; }
.obrigatorio { color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 0.78rem 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #0f172a; font-size: 0.92rem; font-family: inherit; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #14b8a6; background: #fff; box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12); }
.form-input:disabled, .form-select:disabled, .form-textarea:disabled { opacity: 0.72; cursor: not-allowed; }
.form-textarea { resize: vertical; min-height: 120px; }
.form-acoes { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.6rem; }
.form-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 170px; padding: 0.9rem 1.2rem; border-radius: 12px; border: none; font-size: 0.92rem; font-weight: 800; cursor: pointer; font-family: inherit; }
.form-btn--cancelar { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
.form-btn--salvar { background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; }
@media (max-width: 768px) { .form-grid--2, .form-page-header, .form-acoes { grid-template-columns: 1fr; flex-direction: column; align-items: stretch; } .form-grupo--span2, .page-chip, .form-btn { grid-column: span 1; width: 100%; } .form-card-header, .form-card-body { padding: 1.4rem; } }
</style>