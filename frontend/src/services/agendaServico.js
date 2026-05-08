import http from './http.js'

export default {
  async listar(pagina = 1, limite = 15, busca = '', status = '') {
    const { data } = await http.get('/api/agenda', {
      params: { pagina, limite, busca, status },
    })
    return data
  },

  async buscarPorId(id) {
    const { data } = await http.get(`/api/agenda/${id}`)
    return data
  },

  async cadastrar(dados) {
    const { data } = await http.post('/api/agenda', dados)
    return data
  },

  async atualizar(id, dados) {
    const { data } = await http.put(`/api/agenda/${id}`, dados)
    return data
  },

  async aprovar(id, dados) {
    const { data } = await http.post(`/api/agenda/${id}/aprovar`, dados)
    return data
  },

  async recusar(id, dados) {
    const { data } = await http.post(`/api/agenda/${id}/recusar`, dados)
    return data
  },

  async remover(id) {
    const { data } = await http.delete(`/api/agenda/${id}`)
    return data
  },
}