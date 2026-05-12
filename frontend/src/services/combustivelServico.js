import http from './http.js'

export default {
  async listar(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/combustivel', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async buscarPorId(id) {
    const { data } = await http.get(`/api/combustivel/${id}`)
    return data
  },

  async cadastrar(dados) {
    const { data } = await http.post('/api/combustivel', dados)
    return data
  },

  async atualizar(id, dados) {
    const { data } = await http.put(`/api/combustivel/${id}`, dados)
    return data
  },

  async remover(id) {
    const { data } = await http.delete(`/api/combustivel/${id}`)
    return data
  },
}