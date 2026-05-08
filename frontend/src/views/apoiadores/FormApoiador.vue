<template>
  <div class="form-page">
    <div class="form-page-header">
      <button class="btn-voltar" @click="$router.push({ name: 'apoiadores' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar para Apoiadores
      </button>

      <span class="page-chip">{{ lideres.length }} líderes disponíveis para vínculo</span>
    </div>

    <div class="form-card">
      <div class="form-card-header" :class="editando ? 'form-card-header--editar' : 'form-card-header--novo'">
        <div class="form-card-header-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 11h-6"/>
            <path d="M20 8v6"/>
          </svg>
        </div>

        <div class="form-card-header-texto">
          <h1 class="form-card-titulo">{{ editando ? 'Editar Apoiador' : 'Novo Apoiador' }}</h1>
          <p class="form-card-subtitulo">
            {{ editando ? 'Refine o vínculo e atualize o histórico político do apoiador.' : 'Cadastre um novo contato e conecte-o ao líder responsável pela mobilização.' }}
          </p>
        </div>

        <div class="form-card-status">
          <span class="form-card-status-rotulo">Status atual</span>
          <strong>{{ labelStatus(form.status_politico) }}</strong>
        </div>
      </div>

      <div class="form-card-body">
        <AlertaMensagem v-if="mensagem.texto" :tipo="mensagem.tipo" :mensagem="mensagem.texto" />

        <form @submit.prevent="salvar">
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">1</span>
              Vínculo político
            </div>

            <div class="form-grid form-grid--1">
              <div class="form-grupo">
                <label class="form-label" for="lider_id">Líder responsável <span class="obrigatorio">*</span></label>
                <select id="lider_id" v-model="form.lider_id" required :disabled="editando" class="form-select">
                  <option value="">Selecione um líder...</option>
                  <option v-for="lider in lideres" :key="lider.id" :value="lider.id">{{ lider.nome }}</option>
                </select>
                <span class="form-ajuda">
                  {{ editando ? 'O vínculo principal não pode ser alterado nesta edição.' : 'Defina quem será responsável pelo acompanhamento deste contato.' }}
                </span>
              </div>
            </div>
          </div>

          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">2</span>
              Identificação
            </div>

            <div class="form-grid form-grid--2">
              <div class="form-grupo">
                <label class="form-label" for="nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="nome" v-model="form.nome" type="text" required class="form-input" placeholder="Ex: Maria dos Santos" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="cpf">CPF <span class="obrigatorio">*</span></label>
                <input
                  id="cpf"
                  v-model="form.cpf"
                  type="text"
                  required
                  :disabled="editando"
                  class="form-input"
                  placeholder="000.000.000-00"
                />
              </div>
            </div>
          </div>

          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">3</span>
              Território e engajamento
            </div>

            <div class="form-grid form-grid--3">
              <div class="form-grupo">
                <label class="form-label" for="telefone">Telefone</label>
                <input id="telefone" v-model="form.telefone" type="text" class="form-input" placeholder="(00) 00000-0000" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="bairro">Bairro ou região</label>
                <input id="bairro" v-model="form.bairro" type="text" class="form-input" placeholder="Ex: Centro, Nova Esperança..." />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="status_politico">Status político</label>
                <select id="status_politico" v-model="form.status_politico" class="form-select">
                  <option value="apoiador">Apoiador</option>
                  <option value="indeciso">Indeciso</option>
                  <option value="oposicao">Oposição</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-secao form-secao--ultima">
            <div class="form-secao-titulo">
              <span class="form-secao-num">4</span>
              Observações estratégicas
            </div>

            <div class="form-grupo">
              <label class="form-label" for="observacoes">Anotações internas</label>
              <textarea
                id="observacoes"
                v-model="form.observacoes"
                rows="5"
                class="form-textarea"
                placeholder="Registre histórico de contato, temas sensíveis, lideranças locais e próximos passos."
              ></textarea>
            </div>
          </div>

          <div class="form-acoes">
            <button type="button" class="form-btn form-btn--cancelar" @click="$router.push({ name: 'apoiadores' })">
              Cancelar
            </button>
            <button type="submit" class="form-btn form-btn--salvar" :disabled="carregando">
              <svg v-if="carregando" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              {{ carregando ? 'Salvando...' : (editando ? 'Salvar Alterações' : 'Cadastrar Apoiador') }}
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
import { useApoiadorStore } from '@/stores/apoiadorStore.js'
import liderServico from '@/services/liderServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota = useRoute()
const roteador = useRouter()
const store = useApoiadorStore()

const editando = computed(() => !!rota.params.id)
const carregando = ref(false)
const mensagem = reactive({ tipo: '', texto: '' })
const lideres = ref([])

const form = reactive({
  lider_id: rota.query.lider_id || '',
  nome: '',
  cpf: '',
  telefone: '',
  bairro: '',
  status_politico: 'indeciso',
  observacoes: '',
})

onMounted(async () => {
  const resp = await liderServico.listar(1, 100)
  lideres.value = resp.dados || []

  if (editando.value) {
    const apoiador = await store.buscarApoiador(rota.params.id)
    if (apoiador) {
      Object.assign(form, {
        lider_id: apoiador.lider_id,
        nome: apoiador.nome,
        telefone: apoiador.telefone || '',
        bairro: apoiador.bairro || '',
        status_politico: apoiador.status_politico,
        observacoes: apoiador.observacoes || '',
      })
    }
  }
})

function labelStatus(valor) {
  const mapa = {
    apoiador: 'Apoiador',
    indeciso: 'Indeciso',
    oposicao: 'Oposição',
  }

  return mapa[valor] || 'Indefinido'
}

async function salvar() {
  carregando.value = true
  mensagem.texto = ''

  try {
    if (editando.value) {
      await store.atualizarApoiador(rota.params.id, form)
      mensagem.tipo = 'sucesso'
      mensagem.texto = 'Apoiador atualizado com sucesso.'
    } else {
      await store.cadastrarApoiador(form)
      roteador.push({ name: 'apoiadores' })
    }
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = e.message
  } finally {
    carregando.value = false
  }
}
</script>

<style scoped>
.form-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-width: 920px;
}

.form-page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.btn-voltar {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0;
  border: none;
  background: none;
  color: #64748b;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: color 0.15s ease;
}

.btn-voltar:hover {
  color: #0f766e;
}

.btn-voltar svg {
  width: 16px;
  height: 16px;
}

.page-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.85rem;
  border-radius: 999px;
  background: #ecfeff;
  color: #0f766e;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.form-card {
  overflow: hidden;
  border-radius: 22px;
  background: #fff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
}

.form-card-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1.2rem;
  align-items: center;
  padding: 1.8rem 2rem;
  color: #fff;
}

.form-card-header--novo {
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%),
    linear-gradient(135deg, #0f172a 0%, #115e59 50%, #14b8a6 100%);
}

.form-card-header--editar {
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%),
    linear-gradient(135deg, #1f2937 0%, #9a3412 55%, #f97316 100%);
}

.form-card-header-icone {
  width: 56px;
  height: 56px;
  padding: 0.85rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-card-header-icone svg {
  width: 100%;
  height: 100%;
}

.form-card-titulo {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.form-card-subtitulo {
  margin-top: 0.3rem;
  max-width: 56ch;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.9rem;
  line-height: 1.65;
}

.form-card-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.2rem;
  padding: 0.9rem 1rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.14);
  text-align: right;
}

.form-card-status-rotulo {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.74);
}

.form-card-status strong {
  font-size: 1rem;
  font-weight: 800;
}

.form-card-body {
  padding: 2rem;
}

.form-secao {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.45rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.form-secao--ultima {
  border-bottom: none;
}

.form-secao-titulo {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.form-secao-num {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #ccfbf1;
  color: #0f766e;
  font-size: 0.74rem;
}

.form-grid {
  display: grid;
  gap: 1rem;
}

.form-grid--1 {
  grid-template-columns: 1fr;
}

.form-grid--2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.form-grid--3 {
  grid-template-columns: 1.1fr 1.1fr 0.9fr;
}

.form-grupo {
  display: flex;
  flex-direction: column;
  gap: 0.42rem;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #475569;
}

.obrigatorio {
  color: #dc2626;
}

.form-ajuda {
  font-size: 0.78rem;
  line-height: 1.55;
  color: #94a3b8;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.78rem 0.95rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  color: #0f172a;
  font-size: 0.92rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: #14b8a6;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
}

.form-input:disabled,
.form-select:disabled,
.form-textarea:disabled {
  opacity: 0.72;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 132px;
}

.form-acoes {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.6rem;
}

.form-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-width: 170px;
  padding: 0.9rem 1.2rem;
  border-radius: 12px;
  border: none;
  font-size: 0.92rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
  font-family: inherit;
}

.form-btn:hover {
  transform: translateY(-1px);
}

.form-btn:disabled {
  opacity: 0.7;
  cursor: wait;
}

.form-btn--cancelar {
  background: #f8fafc;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.form-btn--salvar {
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
  color: #fff;
  box-shadow: 0 14px 26px rgba(20, 184, 166, 0.22);
}

.spinner {
  width: 18px;
  height: 18px;
  animation: girar 1s linear infinite;
}

@keyframes girar {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
  .form-card-header {
    grid-template-columns: auto 1fr;
  }

  .form-card-status {
    grid-column: 1 / -1;
    align-items: flex-start;
    text-align: left;
  }

  .form-grid--2,
  .form-grid--3 {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .form-page-header,
  .form-acoes {
    flex-direction: column;
    align-items: stretch;
  }

  .page-chip,
  .form-btn {
    width: 100%;
  }

  .form-card-header,
  .form-card-body {
    padding: 1.4rem;
  }
}
</style>
