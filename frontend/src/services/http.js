import axios from 'axios'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Interceptor de requisição — injeta token JWT quando disponível
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('sige_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor de resposta — normaliza erros; redireciona para login em 401
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('sige_token')
      localStorage.removeItem('sige_usuario')
      // Redireciona sem depender do roteador (evita import circular)
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }

    const mensagem =
      error.response?.data?.mensagem || 'Erro de conexão com o servidor.'
    const erros = error.response?.data?.erros || {}
    const err = new Error(mensagem)
    err.erros  = erros
    err.status = error.response?.status
    return Promise.reject(err)
  }
)

export default http
