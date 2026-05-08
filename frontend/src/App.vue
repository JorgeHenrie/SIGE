<template>
  <div id="sige">
    <!-- Layout autenticado: sidebar + área principal -->
    <template v-if="authStore.autenticado">
      <SidebarMenu @sair="sair" />

      <div class="sige-conteudo">
        <!-- Topbar -->
        <header class="topbar">
          <div class="topbar-esquerda">
            <h2 class="topbar-titulo">{{ tituloAtual }}</h2>
          </div>
          <div class="topbar-direita">
            <div class="topbar-usuario">
              <div class="topbar-avatar">{{ iniciais }}</div>
              <div class="topbar-usuario-info">
                <span class="topbar-nome">{{ authStore.usuario?.nome }}</span>
                <span class="topbar-perfil">{{ perfilLabel }}</span>
              </div>
            </div>
          </div>
        </header>

        <!-- Conteúdo da página -->
        <main class="sige-main">
          <router-view />
        </main>
      </div>
    </template>

    <!-- Layout público: apenas router-view (tela de login) -->
    <template v-else>
      <router-view />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import SidebarMenu from '@/components/SidebarMenu.vue'

const authStore = useAuthStore()
const roteador  = useRouter()
const rota      = useRoute()

function sair() {
  authStore.logout()
  roteador.push('/login')
}

const tituloAtual = computed(() => rota.meta?.titulo ?? 'SIGE')

const iniciais = computed(() => {
  const nome = authStore.usuario?.nome ?? ''
  return nome.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase()
})

const perfilLabel = computed(() => {
  const mapa = { admin: 'Administrador', lider: 'Líder', coordenador: 'Coordenador', supervisor: 'Supervisor', gestor: 'Gestor' }
  return mapa[authStore.usuario?.perfil] ?? authStore.usuario?.perfil ?? ''
})
</script>

<style>
/* ============================================================
   Reset global + variáveis
   ============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --sidebar-largura: 260px;
  --topbar-altura: 64px;
  --cor-bg: #f1f5f9;
  --cor-texto: #0f172a;
}

html, body { height: 100%; }

body {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--cor-bg);
  color: var(--cor-texto);
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}

#sige {
  display: flex;
  min-height: 100vh;
}

/* ============================================================
   Área de conteúdo (ao lado da sidebar)
   ============================================================ */
.sige-conteudo {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

/* ============================================================
   Topbar
   ============================================================ */
.topbar {
  height: var(--topbar-altura);
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  flex-shrink: 0;
  position: sticky;
  top: 0;
  z-index: 10;
}

.topbar-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
  letter-spacing: -.015em;
}

.topbar-direita {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.topbar-usuario {
  display: flex;
  align-items: center;
  gap: .625rem;
}

.topbar-avatar {
  width: 36px;
  height: 36px;
  border-radius: .5rem;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: #fff;
  font-size: .75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.topbar-usuario-info {
  display: flex;
  flex-direction: column;
}

.topbar-nome {
  font-size: .875rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.2;
}

.topbar-perfil {
  font-size: .72rem;
  color: #94a3b8;
}

/* ============================================================
   Main
   ============================================================ */
.sige-main {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}
</style>


