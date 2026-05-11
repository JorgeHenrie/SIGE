<template>
  <div class="form-page">
    <div class="form-page-header">
      <button class="btn-voltar" @click="$router.push({ name: 'combustivel' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar para Combustível
      </button>

      <span class="page-chip">{{ editando ? 'Edição de lançamento' : 'Novo abastecimento' }}</span>
    </div>

    <div class="form-card">
      <div class="form-card-header" :class="editando ? 'form-card-header--editar' : 'form-card-header--novo'">
        <div class="form-card-header-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21h18"/>
            <path d="M7 21V8l5-5 5 5v13"/>
            <path d="M10 12h4"/>
            <path d="M10 16h4"/>
          </svg>
        </div>

        <div>
          <h1 class="form-card-titulo">{{ editando ? 'Editar Abastecimento' : 'Novo Abastecimento' }}</h1>
          <p class="form-card-subtitulo">
            {{ editando ? 'Atualize os dados da nota de combustivel sem perder a rastreabilidade do lancamento.' : 'Registre a nota real com veiculo, motorista, local, litros, finalidade e numero fiscal.' }}
          </p>
        </div>
      </div>

      <div class="form-card-body">
        <AlertaMensagem v-if="mensagem.texto" :tipo="mensagem.tipo" :mensagem="mensagem.texto" />

        <div v-if="!podeLancar && !editando" class="bloqueio-card">
          Seu perfil não possui permissão para lançar abastecimentos.
        </div>

        <form v-else @submit.prevent="salvar">
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">1</span>
              Vinculo do lancamento
            </div>

            <div class="form-grid form-grid--2">
              <div v-if="mostrarSelectorLider" class="form-grupo form-grupo--span2">
                <label class="form-label" for="lider_id">Líder responsável <span class="obrigatorio">*</span></label>
                <select id="lider_id" v-model="form.lider_id" class="form-select" required>
                  <option value="">Selecione um líder...</option>
                  <option v-for="lider in lideres" :key="lider.id" :value="lider.id">{{ lider.nome }}</option>
                </select>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="data_abastecimento">Data e hora do abastecimento <span class="obrigatorio">*</span></label>
                <input id="data_abastecimento" v-model="form.data_abastecimento" type="datetime-local" class="form-input" required />
              </div>
            </div>
          </div>

          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">2</span>
              Dados da nota
            </div>

            <div class="form-grid form-grid--2">
              <div class="form-grupo">
                <label class="form-label" for="veiculo_descricao">Veiculo <span class="obrigatorio">*</span></label>
                <input id="veiculo_descricao" v-model="form.veiculo_descricao" type="text" class="form-input" required maxlength="120" placeholder="Ex: Carro 1" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="placa_veiculo">Placa do veiculo <span class="obrigatorio">*</span></label>
                <input id="placa_veiculo" v-model="form.placa_veiculo" type="text" class="form-input" required maxlength="8" placeholder="Ex: ABC1D23" @input="form.placa_veiculo = normalizarPlaca(form.placa_veiculo)" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="tipo_combustivel">Tipo de combustivel <span class="obrigatorio">*</span></label>
                <select id="tipo_combustivel" v-model="form.tipo_combustivel" class="form-select" required>
                  <option value="gasolina">Gasolina</option>
                  <option value="diesel">Diesel</option>
                </select>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="motorista_nome">Motorista <span class="obrigatorio">*</span></label>
                <input id="motorista_nome" v-model="form.motorista_nome" type="text" class="form-input" required maxlength="120" placeholder="Ex: Joao" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="local_abastecimento">Local <span class="obrigatorio">*</span></label>
                <input id="local_abastecimento" v-model="form.local_abastecimento" type="text" class="form-input" required maxlength="160" placeholder="Ex: Posto em Fortaleza" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="litros_abastecidos">Litros <span class="obrigatorio">*</span></label>
                <input id="litros_abastecidos" v-model="form.litros_abastecidos" type="number" min="0.01" step="0.01" class="form-input" required placeholder="0,00" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="odometro_atual">Odometro atual <span class="obrigatorio">*</span></label>
                <input id="odometro_atual" v-model="form.odometro_atual" type="number" min="0" step="1" class="form-input" required placeholder="Ex: 45210" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="valor_total">Valor total <span class="obrigatorio">*</span></label>
                <input id="valor_total" v-model="form.valor_total" type="number" min="0.01" step="0.01" class="form-input" required placeholder="0,00" />
              </div>
            </div>
          </div>

          <div class="form-secao form-secao--ultima">
            <div class="form-secao-titulo">
              <span class="form-secao-num">3</span>
              Finalidade e comprovacao
            </div>

            <div class="form-grid form-grid--2">
              <div class="form-grupo form-grupo--span2">
                <label class="form-label" for="finalidade">Finalidade <span class="obrigatorio">*</span></label>
                <textarea id="finalidade" v-model="form.finalidade" class="form-textarea" rows="4" required placeholder="Ex: visita a 3 bairros + reuniao com eleitores"></textarea>
              </div>

              <div class="form-grupo">
                <label class="form-label" for="numero_nota_fiscal">Numero da nota fiscal <span class="obrigatorio">*</span></label>
                <input id="numero_nota_fiscal" v-model="form.numero_nota_fiscal" type="text" class="form-input" required maxlength="40" placeholder="Ex: 12345" />
              </div>

              <div class="form-grupo">
                <label class="form-label" for="foto_nota_fiscal">Foto da nota fiscal</label>
                <input id="foto_nota_fiscal" ref="inputFotoNotaFiscal" type="file" class="form-input form-input--arquivo" accept="image/png,image/jpeg,image/webp" @change="selecionarFotoNotaFiscal" />
                <span class="form-ajuda">Aceita JPG, PNG ou WEBP com até 5 MB.</span>
              </div>

              <div v-if="fotoNotaFiscalPreview" class="form-grupo form-grupo--span2">
                <div class="foto-nota-card">
                  <div class="foto-nota-topo">
                    <div>
                      <span class="foto-nota-label">Foto anexada</span>
                      <strong class="foto-nota-nome">{{ fotoNotaFiscalNome || 'nota-fiscal' }}</strong>
                    </div>

                    <div class="foto-nota-acoes">
                      <a :href="fotoNotaFiscalPreview" target="_blank" rel="noreferrer" class="foto-nota-link">Abrir</a>
                      <button type="button" class="foto-nota-remover" @click="removerFotoNotaFiscal">Remover</button>
                    </div>
                  </div>

                  <img :src="fotoNotaFiscalPreview" alt="Foto da nota fiscal" class="foto-nota-imagem" />
                </div>
              </div>

              <div class="form-grupo form-grupo--span2">
                <label class="form-label" for="observacoes">Observacoes adicionais</label>
                <textarea id="observacoes" v-model="form.observacoes" class="form-textarea" rows="4" placeholder="Campo opcional para algum detalhe extra da operacao."></textarea>
              </div>
            </div>
          </div>

          <div class="form-acoes">
            <button type="button" class="form-btn form-btn--cancelar" @click="$router.push({ name: 'combustivel' })">Cancelar</button>
            <button type="submit" class="form-btn form-btn--salvar" :disabled="carregando || !podeLancar">
              {{ carregando ? 'Salvando...' : (editando ? 'Salvar Alterações' : 'Registrar Abastecimento') }}
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
import { useCombustivelStore } from '@/stores/combustivelStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import liderServico from '@/services/liderServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota = useRoute()
const roteador = useRouter()
const store = useCombustivelStore()
const authStore = useAuthStore()

const editando = computed(() => !!rota.params.id)
const ehLider = computed(() => authStore.usuario?.perfil === 'lider')
const ehGestor = computed(() => ['admin', 'gestor'].includes(authStore.usuario?.perfil))
const podeLancar = computed(() => ehLider.value || ehGestor.value)
const mostrarSelectorLider = computed(() => !ehLider.value)

const carregando = ref(false)
const lideres = ref([])
const fotoNotaFiscalPreview = ref('')
const fotoNotaFiscalNome = ref('')
const inputFotoNotaFiscal = ref(null)
const mensagem = reactive({ tipo: '', texto: '' })

const form = reactive({
  lider_id: rota.query.lider_id || '',
  veiculo_descricao: '',
  placa_veiculo: '',
  tipo_combustivel: 'gasolina',
  motorista_nome: '',
  local_abastecimento: '',
  odometro_atual: '',
  litros_abastecidos: '',
  valor_total: '',
  finalidade: '',
  numero_nota_fiscal: '',
  nota_fiscal_foto_base64: '',
  nota_fiscal_foto_nome: '',
  remover_foto_nota_fiscal: false,
  data_abastecimento: agoraParaInputDataHora(),
  observacoes: '',
})

onMounted(async () => {
  if (mostrarSelectorLider.value) {
    const resposta = await liderServico.listar(1, 100)
    lideres.value = resposta.dados || []
  }

  if (editando.value) {
    const abastecimento = await store.buscarAbastecimento(rota.params.id)

    if (abastecimento) {
      Object.assign(form, {
        lider_id: abastecimento.lider_id || '',
        veiculo_descricao: abastecimento.veiculo_descricao || '',
        placa_veiculo: normalizarPlaca(abastecimento.placa_veiculo || ''),
        tipo_combustivel: abastecimento.tipo_combustivel || 'gasolina',
        motorista_nome: abastecimento.motorista_nome || '',
        local_abastecimento: abastecimento.local_abastecimento || '',
        odometro_atual: abastecimento.odometro_atual ?? '',
        litros_abastecidos: abastecimento.litros_abastecidos ? Number(abastecimento.litros_abastecidos) : '',
        valor_total: Number(abastecimento.valor_total || 0),
        finalidade: abastecimento.finalidade || '',
        numero_nota_fiscal: abastecimento.numero_nota_fiscal || '',
        data_abastecimento: paraInputDataHora(abastecimento.data_abastecimento),
        observacoes: abastecimento.observacoes || '',
      })

      fotoNotaFiscalPreview.value = resolverUrlArquivo(abastecimento.foto_nota_fiscal_caminho)
      fotoNotaFiscalNome.value = abastecimento.foto_nota_fiscal_nome || ''
    }
  }
})

async function salvar() {
  mensagem.texto = ''

  carregando.value = true

  try {
    const payload = {
      veiculo_descricao: form.veiculo_descricao,
      placa_veiculo: normalizarPlaca(form.placa_veiculo),
      tipo_combustivel: form.tipo_combustivel,
      motorista_nome: form.motorista_nome,
      local_abastecimento: form.local_abastecimento,
      odometro_atual: Number(form.odometro_atual),
      litros_abastecidos: Number(form.litros_abastecidos),
      valor_total: Number(form.valor_total),
      finalidade: form.finalidade,
      numero_nota_fiscal: form.numero_nota_fiscal,
      data_abastecimento: form.data_abastecimento,
      observacoes: form.observacoes,
    }

    if (mostrarSelectorLider.value) {
      payload.lider_id = form.lider_id
    }

    if (form.nota_fiscal_foto_base64) {
      payload.nota_fiscal_foto_base64 = form.nota_fiscal_foto_base64
      payload.nota_fiscal_foto_nome = form.nota_fiscal_foto_nome
    }

    if (form.remover_foto_nota_fiscal) {
      payload.remover_foto_nota_fiscal = true
    }

    if (editando.value) {
      await store.atualizarAbastecimento(rota.params.id, payload)
      mensagem.tipo = 'sucesso'
      mensagem.texto = 'Abastecimento atualizado com sucesso.'
      return
    }

    await store.cadastrarAbastecimento(payload)
    roteador.push({ name: 'combustivel' })
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = e.message
  } finally {
    carregando.value = false
  }
}

function normalizarPlaca(valor) {
  return String(valor || '')
    .replace(/[^A-Za-z0-9]/g, '')
    .toUpperCase()
    .slice(0, 7)
}

async function selecionarFotoNotaFiscal(evento) {
  const arquivo = evento.target.files?.[0]

  if (!arquivo) {
    return
  }

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'A foto da nota fiscal deve estar em JPG, PNG ou WEBP.'
    limparInputFoto()
    return
  }

  if (arquivo.size > 5 * 1024 * 1024) {
    mensagem.tipo = 'erro'
    mensagem.texto = 'A foto da nota fiscal deve ter no maximo 5 MB.'
    limparInputFoto()
    return
  }

  try {
    const base64 = await lerArquivoComoDataUrl(arquivo)
    form.nota_fiscal_foto_base64 = base64
    form.nota_fiscal_foto_nome = arquivo.name
    form.remover_foto_nota_fiscal = false
    fotoNotaFiscalPreview.value = base64
    fotoNotaFiscalNome.value = arquivo.name
  } catch (e) {
    mensagem.tipo = 'erro'
    mensagem.texto = e.message
    limparInputFoto()
  }
}

function removerFotoNotaFiscal() {
  form.nota_fiscal_foto_base64 = ''
  form.nota_fiscal_foto_nome = ''
  form.remover_foto_nota_fiscal = true
  fotoNotaFiscalPreview.value = ''
  fotoNotaFiscalNome.value = ''
  limparInputFoto()
}

function limparInputFoto() {
  if (inputFotoNotaFiscal.value) {
    inputFotoNotaFiscal.value.value = ''
  }
}

function lerArquivoComoDataUrl(arquivo) {
  return new Promise((resolve, reject) => {
    const leitor = new FileReader()
    leitor.onload = () => resolve(String(leitor.result || ''))
    leitor.onerror = () => reject(new Error('Nao foi possivel ler a foto selecionada.'))
    leitor.readAsDataURL(arquivo)
  })
}

function resolverUrlArquivo(caminho) {
  if (!caminho) return ''
  if (/^https?:\/\//i.test(caminho)) return caminho

  return new URL(caminho, import.meta.env.VITE_API_URL || 'http://localhost:8000').toString()
}

function agoraParaInputDataHora() {
  const agora = new Date()
  const timezoneOffset = agora.getTimezoneOffset() * 60000
  return new Date(agora.getTime() - timezoneOffset).toISOString().slice(0, 16)
}

function paraInputDataHora(valor) {
  return valor ? valor.replace(' ', 'T').slice(0, 16) : agoraParaInputDataHora()
}
</script>

<style scoped>
.form-page { display: flex; flex-direction: column; gap: 1.25rem; max-width: 1040px; }
.form-page-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.btn-voltar { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0; border: none; background: none; color: #64748b; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
.btn-voltar svg { width: 16px; height: 16px; }
.page-chip { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.85rem; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: 0.78rem; font-weight: 800; }
.form-card { overflow: hidden; border-radius: 22px; background: #fff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08); }
.form-card-header { display: flex; align-items: center; gap: 1.2rem; padding: 1.8rem 2rem; color: #fff; }
.form-card-header--novo { background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%), linear-gradient(135deg, #111827 0%, #9a3412 52%, #f97316 100%); }
.form-card-header--editar { background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 26%), linear-gradient(135deg, #1f2937 0%, #c2410c 55%, #fb923c 100%); }
.form-card-header-icone { width: 56px; height: 56px; padding: 0.85rem; border-radius: 16px; background: rgba(255, 255, 255, 0.18); display: flex; align-items: center; justify-content: center; }
.form-card-header-icone svg { width: 100%; height: 100%; }
.form-card-titulo { font-size: 1.45rem; font-weight: 800; letter-spacing: -0.03em; }
.form-card-subtitulo { margin-top: 0.3rem; max-width: 56ch; color: rgba(255, 255, 255, 0.82); font-size: 0.9rem; line-height: 1.65; }
.form-card-body { padding: 2rem; }
.bloqueio-card { padding: 1rem 1.1rem; border-radius: 16px; font-size: 0.9rem; line-height: 1.65; background: #fef2f2; color: #b91c1c; }
.form-secao { display: flex; flex-direction: column; gap: 1rem; padding: 1.45rem 0; border-bottom: 1px solid #f1f5f9; }
.form-secao--ultima { border-bottom: none; }
.form-secao-titulo { display: flex; align-items: center; gap: 0.65rem; color: #94a3b8; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.form-secao-num { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ffedd5; color: #c2410c; font-size: 0.74rem; }
.form-grid { display: grid; gap: 1rem; }
.form-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.form-grupo { display: flex; flex-direction: column; gap: 0.42rem; }
.form-grupo--span2 { grid-column: span 2; }
.form-label { font-size: 0.82rem; font-weight: 700; color: #475569; }
.form-ajuda { font-size: 0.78rem; color: #64748b; }
.obrigatorio { color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 0.78rem 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #0f172a; font-size: 0.92rem; font-family: inherit; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #f97316; background: #fff; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12); }
.form-input--arquivo { padding: 0.72rem 0.8rem; }
.form-textarea { resize: vertical; min-height: 120px; }
.foto-nota-card { display: flex; flex-direction: column; gap: 0.9rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); }
.foto-nota-topo { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.foto-nota-label { display: block; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #94a3b8; }
.foto-nota-nome { display: block; margin-top: 0.18rem; color: #0f172a; font-size: 0.95rem; }
.foto-nota-acoes { display: flex; align-items: center; gap: 0.75rem; }
.foto-nota-link, .foto-nota-remover { border: none; background: none; padding: 0; font: inherit; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.foto-nota-link { color: #1d4ed8; text-decoration: none; }
.foto-nota-remover { color: #dc2626; }
.foto-nota-imagem { width: 100%; max-height: 340px; object-fit: contain; border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; }
.form-acoes { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.6rem; }
.form-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 180px; padding: 0.9rem 1.2rem; border-radius: 12px; border: none; font-size: 0.92rem; font-weight: 800; cursor: pointer; font-family: inherit; }
.form-btn--cancelar { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
.form-btn--salvar { background: linear-gradient(135deg, #c2410c 0%, #f97316 100%); color: #fff; }
@media (max-width: 768px) { .form-grid--2, .form-page-header, .form-acoes { grid-template-columns: 1fr; flex-direction: column; align-items: stretch; } .form-grupo--span2, .page-chip, .form-btn { grid-column: span 1; width: 100%; } .form-card-header, .form-card-body { padding: 1.4rem; } }
</style>