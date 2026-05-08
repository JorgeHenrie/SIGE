import http from './http.js'

export default {
  async resumo() {
    const { data } = await http.get('/api/relatorios/resumo')
    return data
  },

  async porLider() {
    const { data } = await http.get('/api/relatorios/por-lider')
    return data
  },

  async porBairro() {
    const { data } = await http.get('/api/relatorios/por-bairro')
    return data
  },

  async consolidado() {
    const { data } = await http.get('/api/relatorios/consolidado')
    return data
  },
}
