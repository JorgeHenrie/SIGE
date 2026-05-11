<template>
  <div class="form-page">
    <div class="form-page-header">
      <button class="btn-voltar" @click="$router.push({ name: 'roteiros' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar para Roteiros
      </button>

      <span class="page-chip">{{ editando ? 'Roteiro em edição' : 'Novo roteiro' }}</span>
    </div>

    <div class="form-card">
      <div class="form-card-header" :class="editando ? 'form-card-header--editar' : 'form-card-header--novo'">
        <div class="form-card-header-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 7h18"/>
            <path d="M6 7l1.5 12h9L18 7"/>
            <path d="M9 11h.01"/>
            <path d="M12 11h.01"/>
            <path d="M15 11h.01"/>
          </svg>
        </div>

        <div>
          <h1 class="form-card-titulo">{{ editando ? 'Recalcular rota do dia' : 'Planejar rota do dia' }}</h1>
          <p class="form-card-subtitulo">
            Informe o ponto de partida e pelo menos 2 compromissos presenciais do mesmo dia. O sistema sugere a melhor sequência para reduzir deslocamento, tempo e custo estimado.
          </p>
        </div>
      </div>

      <div class="form-card-body">
        <AlertaMensagem v-if="mensagem.texto" :tipo="mensagem.tipo" :mensagem="mensagem.texto" />
        <AlertaMensagem v-else-if="store.erro" tipo="erro" :mensagem="store.erro" />

        <form ref="formEl" @submit.prevent="salvar">
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">1</span>
              Contexto operacional
            </div>

            <div class="form-grid form-grid--3">
              <div v-if="mostrarSelectorLider" class="form-grupo form-grupo--span3">
                <label class="form-label" for="lider_id">Líder responsável <span class="obrigatorio">*</span></label>
                <select id="lider_id" v-model="form.lider_id" class="form-select" required>
                  <option value="">Selecione um líder...</option>
                  <option v-for="lider in lideres" :key="lider.id" :value="lider.id">{{ lider.nome }}</option>
                </select>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="data_roteiro">Data do roteiro <span class="obrigatorio">*</span></label>
                <input id="data_roteiro" v-model="form.data_roteiro" type="date" class="form-input" required />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="transporte">Transporte <span class="obrigatorio">*</span></label>
                <select id="transporte" v-model="form.transporte" class="form-select" required>
                  <option value="carro">Carro</option>
                  <option value="moto">Moto</option>
                  <option value="a_pe">A pé</option>
                </select>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="raio_cluster_km">Raio de agrupamento</label>
                <input id="raio_cluster_km" v-model="form.raio_cluster_km" type="number" min="2" max="5" step="0.5" class="form-input" />
              </div>

              <div class="form-grupo form-grupo--span3">
                <label class="form-label" for="local_saida">Local de saída <span class="obrigatorio">*</span></label>
                <input id="local_saida" v-model="form.local_saida" type="text" class="form-input" required placeholder="Rua/local, número ou referência, bairro, cidade, UF" />
              </div>
            </div>
          </div>

          <div class="form-secao">
            <div class="form-secao-topo">
              <div class="form-secao-titulo">
                <span class="form-secao-num">2</span>
                Importar agenda aprovada
              </div>
              <span class="form-ajuda">Reuniões e visitas presenciais já aprovadas podem entrar direto na sugestão do dia.</span>
            </div>

            <div v-if="agendaDisponivel.length" class="importacao-grid">
              <article v-for="evento in agendaDisponivel" :key="evento.id" class="importacao-card">
                <div>
                  <strong>{{ evento.titulo }}</strong>
                  <span>{{ evento.local_evento || 'Local não informado' }}</span>
                  <span>{{ formatarDataHora(evento.data_confirmada_inicio || evento.data_solicitada_inicio) }}</span>
                </div>
                <button type="button" class="btn-importar" @click="adicionarDaAgenda(evento)">Adicionar</button>
              </article>
            </div>

            <div v-else class="bloco-vazio">
              Nenhuma visita aprovada da agenda disponível para importação.
            </div>
          </div>

          <div class="form-secao form-secao--ultima">
            <div class="form-secao-topo">
              <div class="form-secao-titulo">
                <span class="form-secao-num">3</span>
                Compromissos do dia
              </div>
              <button type="button" class="btn-adicionar" @click="adicionarVisita">Adicionar compromisso</button>
            </div>

            <div v-if="!form.visitas.length" class="bloco-vazio">
              Adicione pelo menos 2 compromissos presenciais ou importe reuniões aprovadas da agenda.
            </div>

            <div v-else class="visitas-stack">
              <article v-for="(visita, indice) in form.visitas" :key="visita.uid" class="visita-card">
                <div class="visita-topo">
                  <div>
                    <span class="visita-eyebrow">Compromisso {{ indice + 1 }}</span>
                    <strong class="visita-titulo">{{ visita.nome || 'Novo compromisso' }}</strong>
                  </div>
                  <button type="button" class="btn-remover-visita" @click="removerVisita(indice)">Remover</button>
                </div>

                <div class="form-grid form-grid--2">
                  <div class="form-grupo form-grupo--span2">
                    <label class="form-label">Nome do compromisso <span class="obrigatorio">*</span></label>
                    <input v-model="visita.nome" type="text" class="form-input" placeholder="Ex: Reunião com lideranças do bairro" />
                  </div>

                  <div class="form-grupo form-grupo--span2">
                    <label class="form-label">Endereço completo <span class="obrigatorio">*</span></label>
                    <input v-model="visita.endereco" type="text" class="form-input" placeholder="Rua/local, número ou referência, bairro, cidade, UF" />
                  </div>

                  <div class="form-grupo">
                    <label class="form-label">Prioridade</label>
                    <select v-model="visita.prioridade" class="form-select">
                      <option value="alta">Alta</option>
                      <option value="media">Média</option>
                      <option value="baixa">Baixa</option>
                    </select>
                  </div>

                  <div class="form-grupo">
                    <label class="form-label">Apoiador relacionado</label>
                    <select v-model="visita.apoiador_id" class="form-select" @change="aoSelecionarApoiador(visita)">
                      <option value="">Nenhum</option>
                      <option v-for="apoiador in apoiadores" :key="apoiador.id" :value="apoiador.id">{{ apoiador.nome }}</option>
                    </select>
                  </div>

                  <div class="form-grupo">
                    <label class="form-label">Horário início</label>
                    <input v-model="visita.horario_inicio" type="datetime-local" class="form-input" />
                  </div>

                  <div class="form-grupo">
                    <label class="form-label">Horário fim</label>
                    <input v-model="visita.horario_fim" type="datetime-local" class="form-input" />
                  </div>
                </div>
              </article>
            </div>
          </div>

          <div class="form-acoes">
            <button type="button" class="form-btn form-btn--secundario" @click="sugerir" :disabled="store.carregando">
              {{ store.carregando ? 'Processando...' : 'Sugerir melhor sequência' }}
            </button>
            <button type="submit" class="form-btn form-btn--salvar" :disabled="store.carregando">
              {{ store.carregando ? 'Salvando...' : (editando ? 'Recalcular e salvar' : 'Salvar planejamento') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <section v-if="preview" class="resultado-card">
      <div class="resultado-header">
        <div>
          <span class="resultado-eyebrow">Resultado</span>
          <h2 class="resultado-titulo">Sequência sugerida do dia</h2>
        </div>
      </div>

      <div class="resultado-grid">
        <article class="resultado-metrica">
          <span>Distância total</span>
          <strong>{{ formatarKm(preview.distancia_total_km) }}</strong>
        </article>
        <article class="resultado-metrica">
          <span>Tempo estimado</span>
          <strong>{{ formatarNumero(preview.tempo_total_min) }} min</strong>
        </article>
        <article class="resultado-metrica">
          <span>Custo estimado</span>
          <strong>{{ formatarMoeda(preview.custo_estimado) }}</strong>
        </article>
      </div>

      <article class="painel-card painel-card--sequencia">
        <div class="painel-header">
          <span class="painel-eyebrow">Ordem sugerida</span>
          <h3 class="painel-titulo">Compromissos organizados</h3>
        </div>
        <div class="visitas-resultado">
          <div v-for="visita in preview.visitas || []" :key="visita.id || visita.ordem_sugerida" class="visita-resultado-card">
            <div class="visita-resultado-topo">
              <span class="ordem-pill">#{{ visita.ordem_sugerida }}</span>
            </div>
            <strong>{{ visita.nome }}</strong>
            <span>{{ visita.endereco }}</span>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore.js'
import { useRoteiroStore } from '@/stores/roteiroStore.js'
import agendaServico from '@/services/agendaServico.js'
import apoiadorServico from '@/services/apoiadorServico.js'
import liderServico from '@/services/liderServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota = useRoute()
const roteador = useRouter()
const authStore = useAuthStore()
const store = useRoteiroStore()

const lideres = ref([])
const apoiadores = ref([])
const agendaAprovada = ref([])
const mensagem = reactive({ tipo: '', texto: '' })
const uid = ref(0)
const formEl = ref(null)
const MINIMO_COMPROMISSOS = 2

const editando = computed(() => !!rota.params.id)
const ehLider = computed(() => authStore.usuario?.perfil === 'lider')
const mostrarSelectorLider = computed(() => !ehLider.value)
const preview = computed(() => store.preview)
const agendaDisponivel = computed(() => {
  return (agendaAprovada.value || []).filter((item) => ['visita', 'reuniao'].includes(item.tipo))
})

const form = reactive({
  lider_id: '',
  data_roteiro: new Date().toISOString().slice(0, 10),
  transporte: 'carro',
  raio_cluster_km: 3,
  local_saida: '',
  visitas: [],
})

onMounted(async () => {
  await Promise.all([
    carregarLideres(),
    carregarApoiadores(),
    carregarAgendaAprovada(),
  ])

  if (ehLider.value) {
    form.lider_id = authStore.usuario?.id || ''
  }

  if (editando.value) {
    const roteiro = await store.buscarRoteiro(rota.params.id)
    if (roteiro) preencherFormulario(roteiro)
  } else {
    adicionarVisita()
  }
})

async function carregarLideres() {
  if (!mostrarSelectorLider.value) return
  const resposta = await liderServico.listar(1, 100)
  lideres.value = resposta.dados || []
}

async function carregarApoiadores() {
  const resposta = await apoiadorServico.listar(1, 100)
  apoiadores.value = resposta.dados || []
}

async function carregarAgendaAprovada() {
  const resposta = await agendaServico.listar(1, 100, '', 'aprovado')
  agendaAprovada.value = resposta.dados || []
}

function criarVisitaBase() {
  uid.value += 1
  return {
    uid: `visita-${uid.value}`,
    agenda_evento_id: '',
    apoiador_id: '',
    nome: '',
    endereco: '',
    prioridade: 'media',
    horario_inicio: '',
    horario_fim: '',
  }
}

function adicionarVisita() {
  form.visitas.push(criarVisitaBase())
}

function removerVisita(indice) {
  form.visitas.splice(indice, 1)
}

function adicionarDaAgenda(evento) {
  const jaExiste = form.visitas.some((visita) => visita.agenda_evento_id === evento.id)
  if (jaExiste) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'Essa visita de agenda já foi adicionada ao roteiro.'
    return
  }

  const visita = criarVisitaBase()
  visita.agenda_evento_id = evento.id
  visita.nome = evento.titulo || ''
  visita.endereco = evento.local_evento || ''
  visita.prioridade = 'media'
  visita.horario_inicio = paraInputDataHora(evento.data_confirmada_inicio || evento.data_solicitada_inicio)
  visita.horario_fim = paraInputDataHora(evento.data_confirmada_fim || evento.data_solicitada_fim)
  form.visitas.push(visita)

  mensagem.tipo = 'sucesso'
  mensagem.texto = 'Visita aprovada importada para o roteiro.'
}

function aoSelecionarApoiador(visita) {
  const apoiador = apoiadores.value.find((item) => item.id === visita.apoiador_id)
  if (!apoiador) return
  if (!visita.nome) visita.nome = apoiador.nome || ''
  if (!visita.endereco) visita.endereco = apoiador.bairro || ''
}

async function sugerir() {
  mensagem.texto = ''
  if (!validarFormulario()) return

  const dados = montarPayload()
  try {
    const roteiro = await store.sugerirRoteiro(dados)
    if (roteiro) {
      mensagem.tipo = 'sucesso'
      mensagem.texto = 'Sugestão gerada com sucesso.'
    }
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = montarMensagemErro(e)
  }
}

async function salvar() {
  mensagem.texto = ''
  if (!validarFormulario()) return

  const dados = montarPayload()

  try {
    const roteiro = editando.value
      ? await store.recalcularRoteiro(rota.params.id, dados)
      : await store.cadastrarRoteiro(dados)

    if (roteiro) {
      preencherFormulario(roteiro)
      mensagem.tipo = 'sucesso'
      mensagem.texto = editando.value
        ? 'Roteiro recalculado e salvo com sucesso.'
        : 'Roteiro criado com sucesso.'

      if (!editando.value && roteiro.id) {
        await roteador.replace({ name: 'roteiros-detalhe', params: { id: roteiro.id } })
      }
    }
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = montarMensagemErro(e)
  }
}

function validarFormulario() {
  if (formEl.value) {
    const valido = formEl.value.reportValidity()
    if (!valido) {
      mensagem.tipo = 'erro'
      mensagem.texto = 'Preencha os campos obrigatórios para continuar.'
      return false
    }
  }

  const compromissos = form.visitas.filter((visita) => visitaTemConteudo(visita))

  if (compromissos.length < MINIMO_COMPROMISSOS) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'Adicione pelo menos 2 compromissos presenciais para otimizar a sequência do dia.'
    return false
  }

  if (!enderecoPareceCompleto(form.local_saida)) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'Informe o ponto de partida com endereço completo: rua/local, número ou referência, bairro, cidade e UF.'
    return false
  }

  const compromissoSemNome = compromissos.find((visita) => !String(visita.nome || '').trim())
  if (compromissoSemNome) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'Preencha o nome de todos os compromissos incluídos no planejamento.'
    return false
  }

  const compromissoSemEndereco = compromissos.find((visita) => !enderecoPareceCompleto(visita.endereco))
  if (compromissoSemEndereco) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'Preencha o endereço completo de cada compromisso: rua/local, número ou referência, bairro, cidade e UF.'
    return false
  }

  return true
}

function montarMensagemErro(erro) {
  const mensagemBase = erro?.message || 'Não foi possível processar o roteiro.'
  const erros = erro?.erros

  if (!erros || typeof erros !== 'object') {
    return mensagemBase
  }

  const detalhes = Object.values(erros)
    .filter((item) => typeof item === 'string' && item.trim() !== '')
    .slice(0, 4)

  if (!detalhes.length) {
    return mensagemBase
  }

  return `${mensagemBase} ${detalhes.join(' ')}`
}

function montarPayload() {
  const payload = {
    data_roteiro: form.data_roteiro,
    local_saida: form.local_saida,
    transporte: form.transporte,
    raio_cluster_km: Number(form.raio_cluster_km || 3),
    visitas: form.visitas.map((visita) => {
      const item = {
        agenda_evento_id: visita.agenda_evento_id || undefined,
        apoiador_id: visita.apoiador_id || undefined,
        nome: visita.nome,
        endereco: visita.endereco,
        prioridade: visita.prioridade,
        horario_inicio: visita.horario_inicio || undefined,
        horario_fim: visita.horario_fim || undefined,
      }

      Object.keys(item).forEach((chave) => item[chave] === undefined && delete item[chave])
      return item
    }),
  }

  if (mostrarSelectorLider.value) payload.lider_id = form.lider_id
  return payload
}

function preencherFormulario(roteiro) {
  form.lider_id = roteiro.lider_id || form.lider_id
  form.data_roteiro = roteiro.data_roteiro || form.data_roteiro
  form.transporte = roteiro.transporte || 'carro'
  form.raio_cluster_km = Number(roteiro.raio_cluster_km || 3)
  form.local_saida = roteiro.local_saida || ''
  form.visitas = (roteiro.visitas || []).map((visita) => ({
    uid: visita.id || `visita-${++uid.value}`,
    agenda_evento_id: visita.agenda_evento_id || '',
    apoiador_id: visita.apoiador_id || '',
    nome: visita.nome || '',
    endereco: visita.endereco || '',
    prioridade: visita.prioridade || 'media',
    horario_inicio: paraInputDataHora(visita.horario_inicio),
    horario_fim: paraInputDataHora(visita.horario_fim),
  }))

  if (!form.visitas.length) adicionarVisita()
}

function visitaTemConteudo(visita) {
  return [visita.nome, visita.endereco, visita.agenda_evento_id, visita.apoiador_id]
    .some((valor) => String(valor || '').trim() !== '')
}

function enderecoPareceCompleto(valor) {
  const partes = String(valor || '')
    .split(',')
    .map((parte) => parte.trim())
    .filter(Boolean)

  if (partes.length < 4) return false

  return /^[A-Za-z]{2}$/.test(partes[partes.length - 1])
}

function paraInputDataHora(valor) {
  return valor ? String(valor).replace(' ', 'T').slice(0, 16) : ''
}

function formatarDataHora(valor) {
  if (!valor) return 'Sem horário definido'
  return new Date(String(valor).replace(' ', 'T')).toLocaleString('pt-BR')
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
</script>

<style scoped>
.form-page { display: flex; flex-direction: column; gap: 1.25rem; max-width: 1280px; }
.form-page-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.btn-voltar { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0; border: none; background: none; color: #64748b; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
.btn-voltar svg { width: 16px; height: 16px; }
.page-chip { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.85rem; border-radius: 999px; background: #ecfeff; color: #0f766e; font-size: 0.78rem; font-weight: 800; }
.form-card, .resultado-card { overflow: hidden; border-radius: 24px; background: #fff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08); }
.form-card-header { display: flex; align-items: center; gap: 1.2rem; padding: 1.8rem 2rem; color: #fff; }
.form-card-header--novo { background: radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 26%), linear-gradient(135deg, #0f172a 0%, #0f766e 50%, #14b8a6 100%); }
.form-card-header--editar { background: radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 26%), linear-gradient(135deg, #1e293b 0%, #0f766e 55%, #38bdf8 100%); }
.form-card-header-icone { width: 58px; height: 58px; padding: 0.9rem; border-radius: 18px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; }
.form-card-header-icone svg { width: 100%; height: 100%; }
.form-card-titulo { font-size: 1.52rem; font-weight: 900; letter-spacing: -0.03em; }
.form-card-subtitulo { margin-top: 0.3rem; max-width: 64ch; color: rgba(255,255,255,0.82); font-size: 0.92rem; line-height: 1.7; }
.form-card-body { padding: 2rem; }
.form-secao { display: flex; flex-direction: column; gap: 1rem; padding: 1.45rem 0; border-bottom: 1px solid #f1f5f9; }
.form-secao--ultima { border-bottom: none; }
.form-secao-topo { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.form-secao-titulo { display: flex; align-items: center; gap: 0.65rem; color: #94a3b8; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.form-secao-num { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ccfbf1; color: #0f766e; font-size: 0.74rem; }
.form-ajuda { color: #64748b; font-size: 0.85rem; }
.form-grid { display: grid; gap: 1rem; }
.form-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.form-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.form-grupo { display: flex; flex-direction: column; gap: 0.42rem; }
.form-grupo--span2 { grid-column: span 2; }
.form-grupo--span3 { grid-column: span 3; }
.form-label { font-size: 0.82rem; font-weight: 700; color: #475569; }
.obrigatorio { color: #dc2626; }
.form-input, .form-select { width: 100%; padding: 0.82rem 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #0f172a; font-size: 0.92rem; outline: none; }
.form-input:focus, .form-select:focus { border-color: #14b8a6; background: #fff; box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12); }
.importacao-grid, .visitas-stack, .visitas-resultado { display: flex; flex-direction: column; gap: 0.85rem; }
.importacao-card, .visita-card, .visita-resultado-card { border: 1px solid #e2e8f0; border-radius: 18px; background: #f8fafc; }
.importacao-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.importacao-card strong, .visita-resultado-card strong { color: #0f172a; }
.importacao-card span, .visita-resultado-card span { display: block; font-size: 0.84rem; color: #64748b; margin-top: 0.18rem; }
.btn-importar, .btn-adicionar, .btn-remover-visita { border: none; cursor: pointer; font-weight: 800; }
.btn-importar { padding: 0.75rem 1rem; border-radius: 12px; background: #0f766e; color: #fff; }
.btn-adicionar { padding: 0.75rem 1rem; border-radius: 12px; background: #eff6ff; color: #1d4ed8; }
.btn-remover-visita { padding: 0.55rem 0.8rem; border-radius: 999px; background: #fef2f2; color: #dc2626; }
.visita-card { padding: 1rem 1.1rem; }
.visita-topo { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.visita-eyebrow { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.visita-titulo { color: #0f172a; font-size: 1rem; }
.form-acoes { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.6rem; }
.form-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 180px; padding: 0.92rem 1.2rem; border-radius: 12px; border: none; font-size: 0.92rem; font-weight: 800; cursor: pointer; }
.form-btn--secundario { background: #eff6ff; color: #1d4ed8; }
.form-btn--salvar { background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; }
.bloco-vazio { padding: 1rem 1.1rem; border-radius: 16px; background: #f8fafc; color: #64748b; font-size: 0.9rem; }
.resultado-card { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.resultado-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.resultado-eyebrow, .painel-eyebrow { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.resultado-titulo, .painel-titulo { color: #0f172a; font-size: 1.2rem; font-weight: 900; }
.resultado-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.resultado-metrica { padding: 1rem 1.1rem; border-radius: 18px; background: #f8fafc; display: flex; flex-direction: column; gap: 0.35rem; }
.resultado-metrica span { color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
.resultado-metrica strong { color: #0f172a; font-size: 1.18rem; font-weight: 900; }
.painel-card { padding: 1rem; border-radius: 22px; background: #fff; border: 1px solid #e2e8f0; }
.painel-card--sequencia { margin-top: 0.1rem; }
.painel-header { margin-bottom: 0.9rem; }
.visita-resultado-card { padding: 1rem 1.05rem; }
.visita-resultado-topo { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.55rem; }
.ordem-pill { display: inline-flex; align-items: center; justify-content: center; padding: 0.32rem 0.58rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
.ordem-pill { background: #e2e8f0; color: #0f172a; }
@media (max-width: 1080px) { .resultado-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 768px) { .form-page-header, .form-acoes, .form-secao-topo, .resultado-header, .importacao-card, .visita-topo { flex-direction: column; align-items: stretch; } .form-grid--2, .form-grid--3, .resultado-grid { grid-template-columns: 1fr; } .form-grupo--span2, .form-grupo--span3 { grid-column: span 1; } .form-card-header, .form-card-body, .resultado-card { padding: 1.25rem; } .form-btn, .btn-importar, .btn-adicionar, .btn-remover-visita { width: 100%; justify-content: center; } }
</style>