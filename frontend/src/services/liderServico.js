import http from './http.js'

export default {
  async listar(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/lideres', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async buscarPorId(id) {
    const { data } = await http.get(`/api/lideres/${id}`)
    return data
  },

  async cadastrar(dados) {
    const { data } = await http.post('/api/lideres', dados)
    return data
  },

  async atualizar(id, dados) {
    const { data } = await http.put(`/api/lideres/${id}`, dados)
    return data
  },

  async remover(id) {
    const { data } = await http.delete(`/api/lideres/${id}`)
    return data
  },

  async listarApoiadores(liderId, pagina = 1, limite = 15) {
    const { data } = await http.get(`/api/lideres/${liderId}/apoiadores`, {
      params: { pagina, limite },
    })
    return data
  },
}
