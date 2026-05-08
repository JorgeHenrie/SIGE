<template>
  <div class="login-bg">
    <!-- Painel esquerdo decorativo -->
    <div class="login-painel-esquerdo" aria-hidden="true">
      <div class="login-painel-conteudo">
        <div class="painel-logo">
          <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="24" r="24" fill="rgba(255,255,255,0.15)"/>
            <path d="M14 34 L24 14 L34 34 M17 28 H31" stroke="white" stroke-width="3"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>SIGE</span>
        </div>
        <h2>Sistema de<br/>Mapeamento Político</h2>
        <p>Gerencie lideranças, apoiadores e relatórios eleitorais com segurança e eficiência.</p>
        <ul class="painel-features">
          <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Gestão de líderes e apoiadores
          </li>
          <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Relatórios por bairro e liderança
          </li>
          <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Dados protegidos com criptografia
          </li>
        </ul>
      </div>
      <div class="painel-decore painel-decore-1"></div>
      <div class="painel-decore painel-decore-2"></div>
    </div>

    <!-- Painel direito: formulário -->
    <div class="login-painel-direito">
    <div class="login-card">
      <div class="login-header">
        <div class="login-logo-mobile">
          <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="#1d4ed8"/>
            <path d="M12 28 L20 12 L28 28 M15 23 H25" stroke="white" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>SIGE</span>
        </div>
        <h1>Bem-vindo de volta</h1>
        <p>Acesse sua conta para continuar</p>
      </div>

      <!-- Formulário -->
      <form @submit.prevent="handleLogin" novalidate>
        <!-- CPF -->
        <div class="campo">
          <label for="cpf">CPF</label>
          <input
            id="cpf"
            v-model="cpfExibido"
            @input="formatarCpf"
            type="text"
            inputmode="numeric"
            placeholder="000.000.000-00"
            maxlength="14"
            autocomplete="username"
            :class="{ erro: erros.cpf }"
            :disabled="carregando"
          />
          <span v-if="erros.cpf" class="msg-erro">{{ erros.cpf }}</span>
        </div>

        <!-- Senha -->
        <div class="campo">
          <label for="senha">Senha</label>
          <div class="senha-wrapper">
            <input
              id="senha"
              v-model="senha"
              :type="mostrarSenha ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              :class="{ erro: erros.senha }"
              :disabled="carregando"
            />
            <button type="button" class="toggle-senha" @click="mostrarSenha = !mostrarSenha"
                    :aria-label="mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'">
              <svg v-if="!mostrarSenha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
          <span v-if="erros.senha" class="msg-erro">{{ erros.senha }}</span>
        </div>

        <!-- Erro geral -->
        <div v-if="erroGeral" class="alerta-erro">
          <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
          {{ erroGeral }}
        </div>

        <!-- Botão -->
        <button type="submit" class="btn-entrar" :disabled="carregando">
          <span v-if="carregando" class="spinner"></span>
          <span>{{ carregando ? 'Entrando...' : 'Entrar no sistema' }}</span>
          <svg v-if="!carregando" viewBox="0 0 20 20" fill="currentColor" style="width:17px;height:17px;">
            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </form>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const roteador  = useRouter()
const authStore = useAuthStore()

const cpfExibido  = ref('')
const cpfNumeros  = ref('')
const senha       = ref('')
const mostrarSenha = ref(false)
const carregando  = ref(false)
const erros       = ref({})
const erroGeral   = ref('')

// Aplica máscara 000.000.000-00 enquanto o usuário digita
function formatarCpf() {
  const nums = cpfExibido.value.replace(/\D/g, '').slice(0, 11)
  cpfNumeros.value = nums
  let mask = nums
  if (nums.length > 9) mask = `${nums.slice(0,3)}.${nums.slice(3,6)}.${nums.slice(6,9)}-${nums.slice(9)}`
  else if (nums.length > 6) mask = `${nums.slice(0,3)}.${nums.slice(3,6)}.${nums.slice(6)}`
  else if (nums.length > 3) mask = `${nums.slice(0,3)}.${nums.slice(3)}`
  cpfExibido.value = mask
}

async function handleLogin() {
  erros.value   = {}
  erroGeral.value = ''

  if (!cpfNumeros.value || cpfNumeros.value.length !== 11) {
    erros.value.cpf = 'Informe um CPF válido (11 dígitos).'
    return
  }

  if (!senha.value) {
    erros.value.senha = 'A senha é obrigatória.'
    return
  }

  carregando.value = true

  try {
    await authStore.login(cpfNumeros.value, senha.value)
    roteador.push('/')
  } catch (err) {
    const mensagem = err?.erros?.cpf ?? err?.message ?? 'CPF ou senha incorretos.'
    erroGeral.value = mensagem
  } finally {
    carregando.value = false
  }
}
</script>

<style scoped>
/* ============================================================
   Layout split-screen
   ============================================================ */
.login-bg {
  min-height: 100vh;
  display: flex;
}

/* ------------ Painel esquerdo (gradiente) ------------ */
.login-painel-esquerdo {
  display: none;
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 50%, #1d4ed8 100%);
  flex: 0 0 45%;
  padding: 3rem;
  flex-direction: column;
  justify-content: center;
  color: #fff;
}
@media (min-width: 900px) {
  .login-painel-esquerdo { display: flex; }
}

.login-painel-conteudo {
  position: relative;
  z-index: 2;
}

.painel-logo {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: 3rem;
}
.painel-logo svg { width: 48px; height: 48px; }
.painel-logo span { font-size: 1.75rem; font-weight: 800; letter-spacing: -.025em; }

.login-painel-esquerdo h2 {
  font-size: 2.25rem;
  font-weight: 700;
  line-height: 1.25;
  margin-bottom: 1rem;
}

.login-painel-esquerdo p {
  color: rgba(255,255,255,.7);
  font-size: 1rem;
  line-height: 1.6;
  margin-bottom: 2.5rem;
}

.painel-features {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: .875rem;
}
.painel-features li {
  display: flex;
  align-items: center;
  gap: .625rem;
  font-size: .95rem;
  color: rgba(255,255,255,.85);
}
.painel-features li svg {
  width: 18px;
  height: 18px;
  color: #60a5fa;
  flex-shrink: 0;
}

/* Decorações geométricas */
.painel-decore {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.painel-decore-1 { width: 400px; height: 400px; bottom: -120px; right: -120px; }
.painel-decore-2 { width: 200px; height: 200px; top: -60px; left: -60px; }

/* ------------ Painel direito (form) ------------ */
.login-painel-direito {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 2rem 1.5rem;
}

.login-card {
  background: #fff;
  border-radius: 1.25rem;
  box-shadow: 0 4px 32px rgba(0,0,0,.08);
  padding: 2.75rem 2.25rem;
  width: 100%;
  max-width: 420px;
}

/* ------------ Header do form ------------ */
.login-header { margin-bottom: 2rem; }

.login-logo-mobile {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: 1.5rem;
}
.login-logo-mobile svg { width: 36px; height: 36px; }
.login-logo-mobile span { font-size: 1.25rem; font-weight: 800; color: #1e3a8a; }
@media (min-width: 900px) { .login-logo-mobile { display: none; } }

.login-header h1 {
  font-size: 1.625rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 .375rem;
  letter-spacing: -.025em;
}

.login-header p {
  font-size: .9rem;
  color: #64748b;
  margin: 0;
}

/* ------------ Campos ------------ */
.campo { margin-bottom: 1.25rem; }

.campo label {
  display: block;
  font-size: .8rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: .4rem;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.campo input {
  width: 100%;
  padding: .7rem 1rem;
  border: 1.5px solid #e2e8f0;
  border-radius: .625rem;
  font-size: .975rem;
  background: #f8fafc;
  color: #0f172a;
  transition: border-color .15s, box-shadow .15s, background .15s;
  box-sizing: border-box;
}
.campo input::placeholder { color: #94a3b8; }

.campo input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,.15);
  background: #fff;
}

.campo input.erro { border-color: #ef4444; }

.campo input:disabled { opacity: .6; cursor: not-allowed; }

.senha-wrapper { position: relative; }
.senha-wrapper input { padding-right: 2.875rem; }

.toggle-senha {
  position: absolute;
  right: .75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #94a3b8;
  padding: .2rem;
  display: flex;
  align-items: center;
  transition: color .15s;
}
.toggle-senha:hover { color: #475569; }
.toggle-senha svg { width: 17px; height: 17px; }

.msg-erro {
  display: block;
  font-size: .78rem;
  color: #ef4444;
  margin-top: .35rem;
}

/* ------------ Alerta erro geral ------------ */
.alerta-erro {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  border-radius: .625rem;
  padding: .75rem 1rem;
  font-size: .875rem;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.alerta-erro svg { width: 18px; height: 18px; flex-shrink: 0; }

/* ------------ Botão entrar ------------ */
.btn-entrar {
  width: 100%;
  padding: .8rem;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: #fff;
  font-size: .975rem;
  font-weight: 600;
  border: none;
  border-radius: .625rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  transition: opacity .2s, box-shadow .2s;
  box-shadow: 0 4px 14px rgba(29,78,216,.35);
  margin-top: .25rem;
}
.btn-entrar:hover:not(:disabled) {
  opacity: .92;
  box-shadow: 0 6px 20px rgba(29,78,216,.45);
}
.btn-entrar:disabled { opacity: .65; cursor: not-allowed; }

/* ------------ Spinner ------------ */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
