import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import http from '@/services/http'

export const useAuthStore = defineStore('auth', () => {
  const token   = ref(localStorage.getItem('sige_token') ?? null)
  const usuario = ref(JSON.parse(localStorage.getItem('sige_usuario') ?? 'null'))

  const autenticado = computed(() => !!token.value)

  async function login(cpf, senha) {
    const { data } = await http.post('/api/auth/login', { cpf, senha })
    token.value   = data.dados.token
    usuario.value = data.dados.usuario
    localStorage.setItem('sige_token',   token.value)
    localStorage.setItem('sige_usuario', JSON.stringify(usuario.value))
  }

  function logout() {
    token.value   = null
    usuario.value = null
    localStorage.removeItem('sige_token')
    localStorage.removeItem('sige_usuario')
  }

  return { token, usuario, autenticado, login, logout }
})
