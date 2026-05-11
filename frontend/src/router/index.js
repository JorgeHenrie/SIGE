import { createRouter, createWebHistory } from 'vue-router'

const rotas = [
  // Rota pública
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { titulo: 'Login', publica: true },
  },
  {
    path: '/',
    redirect: '/inicio',
  },
  {
    path: '/inicio',
    name: 'inicio',
    component: () => import('@/views/Inicio.vue'),
    meta: { titulo: 'Início', requiresAuth: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { titulo: 'Dashboard', requiresAuth: true },
  },
  {
    path: '/lideres',
    name: 'lideres',
    component: () => import('@/views/lideres/ListaLideres.vue'),
    meta: { titulo: 'Líderes', requiresAuth: true },
  },
  {
    path: '/lideres/novo',
    name: 'lideres-novo',
    component: () => import('@/views/lideres/FormLider.vue'),
    meta: { titulo: 'Novo Líder', requiresAuth: true },
  },
  {
    path: '/lideres/:id',
    name: 'lideres-detalhe',
    component: () => import('@/views/lideres/DetalheLider.vue'),
    meta: { titulo: 'Detalhe do Líder', requiresAuth: true },
  },
  {
    path: '/lideres/:id/editar',
    name: 'lideres-editar',
    component: () => import('@/views/lideres/FormLider.vue'),
    meta: { titulo: 'Editar Líder', requiresAuth: true },
  },
  {
    path: '/apoiadores',
    name: 'apoiadores',
    component: () => import('@/views/apoiadores/ListaApoiadores.vue'),
    meta: { titulo: 'Apoiadores', requiresAuth: true },
  },
  {
    path: '/apoiadores/novo',
    name: 'apoiadores-novo',
    component: () => import('@/views/apoiadores/FormApoiador.vue'),
    meta: { titulo: 'Novo Apoiador', requiresAuth: true },
  },
  {
    path: '/apoiadores/:id/editar',
    name: 'apoiadores-editar',
    component: () => import('@/views/apoiadores/FormApoiador.vue'),
    meta: { titulo: 'Editar Apoiador', requiresAuth: true },
  },
  {
    path: '/agenda',
    name: 'agenda',
    component: () => import('@/views/agenda/ListaAgenda.vue'),
    meta: { titulo: 'Agenda', requiresAuth: true },
  },
  {
    path: '/agenda/nova',
    name: 'agenda-nova',
    component: () => import('@/views/agenda/FormAgenda.vue'),
    meta: { titulo: 'Nova Solicitação', requiresAuth: true },
  },
  {
    path: '/agenda/:id/editar',
    name: 'agenda-editar',
    component: () => import('@/views/agenda/FormAgenda.vue'),
    meta: { titulo: 'Editar Solicitação', requiresAuth: true },
  },
  {
    path: '/combustivel',
    name: 'combustivel',
    component: () => import('@/views/combustivel/ListaCombustivel.vue'),
    meta: { titulo: 'Combustível', requiresAuth: true },
  },
  {
    path: '/combustivel/novo',
    name: 'combustivel-novo',
    component: () => import('@/views/combustivel/FormCombustivel.vue'),
    meta: { titulo: 'Novo Abastecimento', requiresAuth: true },
  },
  {
    path: '/combustivel/:id/editar',
    name: 'combustivel-editar',
    component: () => import('@/views/combustivel/FormCombustivel.vue'),
    meta: { titulo: 'Editar Abastecimento', requiresAuth: true },
  },
  {
    path: '/relatorios',
    name: 'relatorios',
    component: () => import('@/views/relatorios/Relatorios.vue'),
    meta: { titulo: 'Relatórios', requiresAuth: true },
  },
  {
    path: '/roteiros',
    name: 'roteiros',
    component: () => import('@/views/roteiros/ListaRoteiros.vue'),
    meta: { titulo: 'Roteirização', requiresAuth: true },
  },
  {
    path: '/roteiros/novo',
    name: 'roteiros-novo',
    component: () => import('@/views/roteiros/FormRoteiro.vue'),
    meta: { titulo: 'Novo Roteiro', requiresAuth: true },
  },
  {
    path: '/roteiros/:id',
    name: 'roteiros-detalhe',
    component: () => import('@/views/roteiros/FormRoteiro.vue'),
    meta: { titulo: 'Detalhe do Roteiro', requiresAuth: true },
  },
]

const roteador = createRouter({
  history: createWebHistory(),
  routes: rotas,
})

roteador.beforeEach((to) => {
  document.title = `${to.meta.titulo || 'SIGE'} | SIGE`

  const token = localStorage.getItem('sige_token')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.name === 'login' && token) {
    return { name: 'inicio' }
  }
})

export default roteador
