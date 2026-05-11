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

  async combustivelSemanal() {
    const { data } = await http.get('/api/relatorios/combustivel-semanal')
    return data
  },

  async combustivelMensal() {
    const { data } = await http.get('/api/relatorios/combustivel-mensal')
    return data
  },

  async combustivelPorLider() {
    const { data } = await http.get('/api/relatorios/combustivel-por-lider')
    return data
  },

  async combustivelAlertas() {
    const { data } = await http.get('/api/relatorios/combustivel-alertas')
    return data
  },
}
