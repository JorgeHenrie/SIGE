import http from './http.js'

export default {
  async listar(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/apoiadores', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async buscarPorId(id) {
    const { data } = await http.get(`/api/apoiadores/${id}`)
    return data
  },

  async cadastrar(dados) {
    const { data } = await http.post('/api/apoiadores', dados)
    return data
  },

  async atualizar(id, dados) {
    const { data } = await http.put(`/api/apoiadores/${id}`, dados)
    return data
  },

  async remover(id) {
    const { data } = await http.delete(`/api/apoiadores/${id}`)
    return data
  },
}
