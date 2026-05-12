<template>
  <aside class="sidebar" :class="{ 'sidebar--colapsada': colapsada }">
    <!-- ===== Logo ===== -->
    <div class="sidebar-logo">
      <button
        class="sidebar-toggle"
        @click="colapsada = !colapsada"
        :title="colapsada ? 'Expandir menu' : 'Recolher menu'"
        :aria-expanded="!colapsada"
        aria-label="Menu"
      >
        <span class="hamburger" :class="{ 'hamburger--aberto': !colapsada }">
          <span></span>
          <span></span>
          <span></span>
        </span>
      </button>
      <Transition name="fade-text">
        <span v-if="!colapsada" class="sidebar-logo-texto">SIGE</span>
      </Transition>
    </div>

    <!-- ===== Avatar do usuário ===== -->
    <div class="sidebar-usuario" :class="{ 'sidebar-usuario--mini': colapsada }">
      <div class="usuario-avatar">
        {{ iniciais }}
      </div>
      <Transition name="fade-text">
        <div v-if="!colapsada" class="usuario-info">
          <span class="usuario-nome">{{ authStore.usuario?.nome }}</span>
          <span class="usuario-perfil">{{ perfilLabel }}</span>
        </div>
      </Transition>
    </div>

    <!-- ===== Navegação ===== -->
    <nav class="sidebar-nav">
      <div class="sidebar-secao">
        <Transition name="fade-text">
          <span v-if="!colapsada" class="sidebar-secao-titulo">Menu Principal</span>
        </Transition>

        <RouterLink
          v-for="item in itensMenu"
          :key="item.rota"
          :to="item.rota"
          class="sidebar-item"
          :class="{ 'sidebar-item--ativo': rotaAtiva(item) }"
          :title="colapsada ? item.label : ''"
        >
          <span class="sidebar-item-icone" v-html="item.icone"></span>
          <Transition name="fade-text">
            <span v-if="!colapsada" class="sidebar-item-label">{{ item.label }}</span>
          </Transition>
          <Transition name="fade-text">
            <span v-if="!colapsada && item.badge" class="sidebar-badge">{{ item.badge }}</span>
          </Transition>
        </RouterLink>
      </div>
    </nav>

    <!-- ===== Rodapé (Sair) ===== -->
    <div class="sidebar-rodape">
      <button class="sidebar-item sidebar-sair" @click="$emit('sair')"
              :title="colapsada ? 'Sair' : ''">
        <span class="sidebar-item-icone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </span>
        <Transition name="fade-text">
          <span v-if="!colapsada" class="sidebar-item-label">Sair</span>
        </Transition>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

defineEmits(['sair'])

const authStore = useAuthStore()
const rota      = useRoute()
const colapsada = ref(false)

const iniciais = computed(() => {
  const nome = authStore.usuario?.nome ?? ''
  return nome.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase()
})

const perfilLabel = computed(() => {
  const mapa = { admin: 'Administrador', lider: 'Líder', coordenador: 'Coordenador', supervisor: 'Supervisor', gestor: 'Gestor' }
  return mapa[authStore.usuario?.perfil] ?? authStore.usuario?.perfil ?? ''
})

const itensMenu = [
  {
    rota: '/inicio',
    label: 'Início',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 10.5 12 3l9 7.5"/>
      <path d="M5 9.5V21h14V9.5"/>
      <path d="M9 21v-6h6v6"/>
    </svg>`,
  },
  {
    rota: '/dashboard',
    label: 'Dashboard',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="7" height="7"/>
      <rect x="14" y="3" width="7" height="7"/>
      <rect x="14" y="14" width="7" height="7"/>
      <rect x="3" y="14" width="7" height="7"/>
    </svg>`,
  },
  {
    rota: '/lideres',
    label: 'Líderes',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
      <path d="M23 21v-2a4 4 0 00-3-3.87"/>
      <path d="M16 3.13a4 4 0 010 7.75"/>
    </svg>`,
  },
  {
    rota: '/equipe-campanha',
    label: 'Equipe de campanha',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="7" r="4"/>
      <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
      <path d="M3 11h3"/>
      <path d="M18 11h3"/>
    </svg>`,
  },
  {
    rota: '/agenda',
    label: 'Agenda',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
      <line x1="16" y1="2" x2="16" y2="6"/>
      <line x1="8" y1="2" x2="8" y2="6"/>
      <line x1="3" y1="10" x2="21" y2="10"/>
    </svg>`,
  },
  {
    rota: '/combustivel',
    label: 'Combustível',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 21h12"/>
      <path d="M5 21V7a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v14"/>
      <path d="M14 11h2a2 2 0 0 1 2 2v8"/>
      <path d="M8 9h4"/>
    </svg>`,
  },
  {
    rota: '/financeiro',
    label: 'Financeiro',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 6h18"/>
      <path d="M7 12h10"/>
      <path d="M10 18h4"/>
      <circle cx="18" cy="18" r="3"/>
    </svg>`,
  },
  {
    rota: '/roteiros',
    label: 'Roteirização',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="6" cy="18" r="2"/>
      <circle cx="18" cy="6" r="2"/>
      <circle cx="18" cy="18" r="2"/>
      <path d="M8 17l8-10"/>
      <path d="M8 18h8"/>
    </svg>`,
  },
  {
    rota: '/relatorios',
    label: 'Relatórios',
    icone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="18" y1="20" x2="18" y2="10"/>
      <line x1="12" y1="20" x2="12" y2="4"/>
      <line x1="6" y1="20" x2="6" y2="14"/>
    </svg>`,
  },
]

function rotaAtiva(item) {
  if (item.rota === '/inicio') return rota.path === '/inicio'
  if (item.rota === '/dashboard') return rota.path === '/dashboard'
  return rota.path.startsWith(item.rota)
}
</script>

<style scoped>
/* ============================================================
   Sidebar
   ============================================================ */
.sidebar {
  display: flex;
  flex-direction: column;
  width: 260px;
  min-height: 100vh;
  background: #0f172a;
  color: #cbd5e1;
  flex-shrink: 0;
  transition: width .25s cubic-bezier(.4,0,.2,1);
  overflow: hidden;
  position: sticky;
  top: 0;
  height: 100vh;
}

.sidebar--colapsada { width: 56px; }
.sidebar--colapsada .sidebar-logo { justify-content: center; gap: 0; }

/* ===== Logo ===== */
.sidebar-logo {
  display: flex;
  align-items: center;
  padding: 1rem;
  gap: .875rem;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.sidebar-logo-texto {
  font-size: 1.375rem;
  font-weight: 800;
  color: #f1f5f9;
  letter-spacing: -.025em;
  white-space: nowrap;
}

/* ===== Botão hamburguer ===== */
.sidebar-toggle {
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  padding: .375rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: .5rem;
  transition: background .15s;
}
.sidebar-toggle:hover { background: rgba(255,255,255,.08); }

/* As três linhas */
.hamburger {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: 20px;
  height: 14px;
}
.hamburger span {
  display: block;
  height: 2px;
  border-radius: 2px;
  background: #94a3b8;
  transform-origin: center;
  transition: transform .3s cubic-bezier(.4,0,.2,1),
              opacity  .3s cubic-bezier(.4,0,.2,1),
              width    .3s cubic-bezier(.4,0,.2,1),
              background .15s;
}
.sidebar-toggle:hover .hamburger span { background: #f1f5f9; }

/* Estado aberto: linha do meio some, 1ª e 3ª viram X */
.hamburger--aberto span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
.hamburger--aberto span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger--aberto span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

/* ===== Avatar ===== */
.sidebar-usuario {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 1rem 1rem .875rem;
  border-bottom: 1px solid rgba(255,255,255,.06);
  overflow: hidden;
}
.sidebar-usuario--mini { justify-content: center; padding: .875rem .5rem; }

.usuario-avatar {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: .625rem;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: #fff;
  font-size: .8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: .025em;
}

.usuario-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}
.usuario-nome {
  font-size: .875rem;
  font-weight: 600;
  color: #f1f5f9;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.usuario-perfil {
  font-size: .72rem;
  color: #64748b;
  text-transform: capitalize;
  margin-top: .1rem;
}

/* ===== Navegação ===== */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: .875rem .625rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.1) transparent;
}

.sidebar-secao { margin-bottom: .5rem; }

.sidebar-secao-titulo {
  display: block;
  font-size: .65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: #475569;
  padding: 0 .625rem .5rem;
  white-space: nowrap;
}

/* Item de menu */
.sidebar-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .625rem .75rem;
  border-radius: .625rem;
  text-decoration: none;
  color: #94a3b8;
  font-size: .9rem;
  font-weight: 500;
  transition: background .15s, color .15s;
  margin-bottom: .125rem;
  white-space: nowrap;
  width: 100%;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
}

.sidebar-item:hover {
  background: rgba(255,255,255,.06);
  color: #f1f5f9;
}

.sidebar-item--ativo {
  background: rgba(59,130,246,.15);
  color: #60a5fa;
}
.sidebar-item--ativo .sidebar-item-icone { color: #3b82f6; }

.sidebar-item-icone {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: inherit;
}
.sidebar-item-icone :deep(svg) {
  width: 18px;
  height: 18px;
  display: block;
}

.sidebar-item-label { flex: 1; }

.sidebar-badge {
  background: #1d4ed8;
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  padding: .15rem .4rem;
  border-radius: 999px;
}

/* ===== Rodapé ===== */
.sidebar-rodape {
  padding: .625rem;
  border-top: 1px solid rgba(255,255,255,.06);
}

.sidebar-sair { color: #64748b; }
.sidebar-sair:hover { color: #ef4444; background: rgba(239,68,68,.08); }

/* ===== Transição fade para textos ===== */
.fade-text-enter-active,
.fade-text-leave-active { transition: opacity .15s, width .15s; overflow: hidden; }
.fade-text-enter-from,
.fade-text-leave-to { opacity: 0; }
</style>
