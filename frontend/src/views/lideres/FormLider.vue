<template>
  <div class="form-page">
    <!-- Cabeçalho com voltar -->
    <div class="form-page-header">
      <button class="btn-voltar" @click="$router.push({ name: 'lideres' })">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Voltar para Líderes
      </button>
    </div>

    <div class="form-card">
      <!-- Header do card -->
      <div class="form-card-header" :class="editando ? 'form-card-header--editar' : 'form-card-header--novo'">
        <div class="form-card-header-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <template v-if="!editando">
              <line x1="19" y1="8" x2="19" y2="14"/>
              <line x1="16" y1="11" x2="22" y2="11"/>
            </template>
            <template v-else>
              <path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/>
              <path d="M16 3l5 5-9 9H7v-5z"/>
            </template>
          </svg>
        </div>
        <div>
          <h1 class="form-card-titulo">{{ editando ? 'Editar Líder' : 'Novo Líder' }}</h1>
          <p class="form-card-subtitulo">{{ editando ? 'Atualize os dados do líder abaixo.' : 'Preencha os dados para cadastrar um novo líder.' }}</p>
        </div>
      </div>

      <!-- Alerta -->
      <div class="form-card-body">
        <AlertaMensagem v-if="mensagem.texto" :tipo="mensagem.tipo" :mensagem="mensagem.texto" />

        <form @submit.prevent="salvar">
          <!-- Seção: Identificação -->
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">1</span>
              Identificação
            </div>
            <div class="form-grid form-grid--2">
              <div class="form-grupo">
                <label class="form-label" for="nome">Nome completo <span class="obrigatorio">*</span></label>
                <input id="nome" v-model="form.nome" type="text" required class="form-input" placeholder="Ex: João da Silva" />
              </div>
              <div class="form-grupo">
                <label class="form-label" for="cpf">CPF <span class="obrigatorio">*</span></label>
                <input id="cpf" v-model="form.cpf" type="text" required :disabled="editando" class="form-input" placeholder="000.000.000-00" />
              </div>
            </div>
          </div>

          <!-- Seção: Contato -->
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">2</span>
              Contato e Localização
            </div>
            <div class="form-grid form-grid--3">
              <div class="form-grupo">
                <label class="form-label" for="telefone">Telefone</label>
                <input id="telefone" v-model="form.telefone" type="text" class="form-input" placeholder="(00) 00000-0000" />
              </div>
              <div class="form-grupo form-grupo--span2">
                <label class="form-label" for="bairro">Bairro / Região</label>
                <input id="bairro" v-model="form.bairro" type="text" class="form-input" placeholder="Ex: Centro, Vila Nova..." />
              </div>
            </div>
          </div>

          <!-- Seção: Dados eleitorais -->
          <div class="form-secao">
            <div class="form-secao-titulo">
              <span class="form-secao-num">3</span>
              Dados Eleitorais e Contrato
            </div>
            <div class="form-grid form-grid--2">
              <div v-if="podeGerenciarContrato" class="form-grupo">
                <label class="form-label" for="salario_mensal">Salário mensal do contrato <span v-if="!editando" class="obrigatorio">*</span></label>
                <input id="salario_mensal" v-model="form.salario_mensal" type="number" min="0.01" step="0.01" class="form-input" placeholder="0,00" :required="!editando" />
              </div>
              <div class="form-grupo">
                <label class="form-label" for="votos_estimados">Votos Estimados</label>
                <input id="votos_estimados" v-model.number="form.votos_estimados" type="number" min="0" class="form-input" placeholder="0" />
              </div>
              <div class="form-grupo">
                <label class="form-label" for="status">Status</label>
                <select id="status" v-model="form.status" class="form-select">
                  <option :value="true">✓ Ativo</option>
                  <option :value="false">✗ Inativo</option>
                </select>
              </div>
            </div>
            <div class="form-grupo">
              <label class="form-label" for="observacoes">Observações</label>
              <textarea id="observacoes" v-model="form.observacoes" rows="4" class="form-textarea" placeholder="Informações adicionais sobre o líder..."></textarea>
            </div>
          </div>

          <!-- Ações -->
          <div class="form-acoes">
            <button type="button" class="form-btn form-btn--cancelar" @click="$router.push({ name: 'lideres' })">
              Cancelar
            </button>
            <button type="submit" class="form-btn form-btn--salvar" :disabled="carregando">
              <svg v-if="carregando" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              {{ carregando ? 'Salvando...' : (editando ? 'Salvar Alterações' : 'Cadastrar Líder') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLiderStore } from '@/stores/liderStore.js'
import { useAuthStore } from '@/stores/authStore.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const rota      = useRoute()
const roteador  = useRouter()
const store     = useLiderStore()
const authStore = useAuthStore()

const editando   = computed(() => !!rota.params.id)
const podeGerenciarContrato = computed(() => authStore.usuario?.perfil === 'admin')
const carregando = ref(false)
const mensagem   = reactive({ tipo: '', texto: '' })

const form = reactive({
  nome: '', cpf: '', telefone: '', bairro: '',
  votos_estimados: 0, salario_mensal: '', observacoes: '', status: true,
})

onMounted(async () => {
  if (editando.value) {
    const lider = await store.buscarLider(rota.params.id)
    if (lider) {
      Object.assign(form, {
        nome: lider.nome,
        telefone: lider.telefone || '',
        bairro: lider.bairro || '',
        votos_estimados: lider.votos_estimados,
        salario_mensal: lider.salario_mensal ?? '',
        observacoes: lider.observacoes || '',
        status: lider.status,
      })
    }
  }
})

async function salvar() {
  carregando.value = true
  mensagem.texto   = ''
  try {
    const payload = {
      ...form,
    }

    if (podeGerenciarContrato.value) {
      if (!editando.value || (form.salario_mensal !== '' && form.salario_mensal !== null)) {
        payload.salario_mensal = Number(form.salario_mensal)
      } else {
        delete payload.salario_mensal
      }
    } else {
      delete payload.salario_mensal
    }

    if (editando.value) {
      await store.atualizarLider(rota.params.id, payload)
      mensagem.tipo  = 'sucesso'
      mensagem.texto = 'Líder atualizado com sucesso.'
    } else {
      await store.cadastrarLider(payload)
      roteador.push({ name: 'lideres' })
    }
  } catch (e) {
    mensagem.tipo  = 'erro'
    mensagem.texto = e.message
  } finally {
    carregando.value = false
  }
}
</script>

<style scoped>
/* ---- Página ---- */
.form-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-width: 800px;
}

.form-page-header {
  display: flex;
  align-items: center;
}

.btn-voltar {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  padding: 0.375rem 0;
  transition: color 0.15s;
}

.btn-voltar svg {
  width: 16px;
  height: 16px;
}

.btn-voltar:hover {
  color: #1d4ed8;
}

/* ---- Card ---- */
.form-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.06);
  overflow: hidden;
}

/* ---- Header do card ---- */
.form-card-header {
  padding: 2rem 2rem 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  color: white;
}

.form-card-header--novo {
  background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
}

.form-card-header--editar {
  background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
}

.form-card-header-icone {
  width: 52px;
  height: 52px;
  background: rgba(255,255,255,0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
  flex-shrink: 0;
}

.form-card-header-icone svg {
  width: 100%;
  height: 100%;
  stroke: white;
}

.form-card-titulo {
  font-size: 1.375rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  line-height: 1.2;
}

.form-card-subtitulo {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.75);
  margin-top: 0.2rem;
}

/* ---- Body ---- */
.form-card-body {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* ---- Seções ---- */
.form-secao {
  padding: 1.5rem 0;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-secao:last-of-type {
  border-bottom: none;
}

.form-secao-titulo {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #94a3b8;
}

.form-secao-num {
  width: 22px;
  height: 22px;
  background: #e2e8f0;
  color: #475569;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ---- Grid de campos ---- */
.form-grid {
  display: grid;
  gap: 1rem;
}

.form-grid--2 {
  grid-template-columns: 1fr 1fr;
}

.form-grid--3 {
  grid-template-columns: 1fr 2fr;
}

.form-grupo {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-grupo--span2 {
  grid-column: span 2;
}

/* ---- Elementos de input ---- */
.form-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  letter-spacing: 0.01em;
}

.obrigatorio {
  color: #ef4444;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  color: #0f172a;
  background: #f8fafc;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
  outline: none;
  font-family: inherit;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: #3b82f6;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}

.form-input:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: #cbd5e1;
}

.form-textarea {
  resize: vertical;
  min-height: 90px;
}

.form-select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 16px;
  padding-right: 2.5rem;
}

/* ---- Botões de ação ---- */
.form-acoes {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.5rem;
  margin-top: 0.5rem;
  border-top: 1px solid #f1f5f9;
}

.form-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.form-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-btn--cancelar {
  background: #f1f5f9;
  color: #64748b;
}

.form-btn--cancelar:hover:not(:disabled) {
  background: #e2e8f0;
}

.form-btn--salvar {
  background: linear-gradient(135deg, #1e3a5f, #1d4ed8);
  color: white;
  box-shadow: 0 4px 12px rgba(29,78,216,0.3);
}

.form-btn--salvar:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(29,78,216,0.4);
}

/* ---- Spinner ---- */
.spinner {
  width: 16px;
  height: 16px;
  animation: girar 1s linear infinite;
}

@keyframes girar {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* ---- Responsivo ---- */
@media (max-width: 600px) {
  .form-grid--2,
  .form-grid--3 {
    grid-template-columns: 1fr;
  }
  .form-grupo--span2 {
    grid-column: span 1;
  }
  .form-card-header {
    padding: 1.5rem;
  }
  .form-card-body {
    padding: 1.25rem;
  }
}
</style>
