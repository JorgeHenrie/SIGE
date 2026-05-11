import http from './http.js'

export default {
  async listar(pagina = 1, limite = 15, busca = '', liderId = '') {
    const { data } = await http.get('/api/roteiros', {
      params: {
        pagina,
        limite,
        busca,
        lider_id: liderId || undefined,
      },
    })
    return data
  },

  async buscarPorId(id) {
    const { data } = await http.get(`/api/roteiros/${id}`)
    return data
  },

  async sugerir(dados) {
    const { data } = await http.post('/api/roteiros/sugerir', dados)
    return data
  },

  async cadastrar(dados) {
    const { data } = await http.post('/api/roteiros', dados)
    return data
  },

  async recalcular(id, dados) {
    const { data } = await http.put(`/api/roteiros/${id}/recalcular`, dados)
    return data
  },

  async remover(id) {
    const { data } = await http.delete(`/api/roteiros/${id}`)
    return data
  },
}