import http from './http.js'

export default {
  async listarFornecedores(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/financeiro/fornecedores', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async cadastrarFornecedor(dados) {
    const { data } = await http.post('/api/financeiro/fornecedores', dados)
    return data
  },

  async atualizarFornecedor(id, dados) {
    const { data } = await http.put(`/api/financeiro/fornecedores/${id}`, dados)
    return data
  },

  async removerFornecedor(id) {
    const { data } = await http.delete(`/api/financeiro/fornecedores/${id}`)
    return data
  },

  async listarReceitas(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/financeiro/receitas', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async cadastrarReceita(dados) {
    const { data } = await http.post('/api/financeiro/receitas', dados)
    return data
  },

  async atualizarReceita(id, dados) {
    const { data } = await http.put(`/api/financeiro/receitas/${id}`, dados)
    return data
  },

  async removerReceita(id) {
    const { data } = await http.delete(`/api/financeiro/receitas/${id}`)
    return data
  },

  async listarDespesas(pagina = 1, limite = 15, busca = '') {
    const { data } = await http.get('/api/financeiro/despesas', {
      params: { pagina, limite, busca },
    })
    return data
  },

  async cadastrarDespesa(dados) {
    const { data } = await http.post('/api/financeiro/despesas', dados)
    return data
  },

  async cadastrarDespesaPessoalLider(dados) {
    const { data } = await http.post('/api/financeiro/despesas/pessoal/lideres', dados)
    return data
  },

  async atualizarDespesa(id, dados) {
    const { data } = await http.put(`/api/financeiro/despesas/${id}`, dados)
    return data
  },

  async removerDespesa(id) {
    const { data } = await http.delete(`/api/financeiro/despesas/${id}`)
    return data
  },

  async obterSaldos(candidatoId = null) {
    const { data } = await http.get('/api/financeiro/saldos', {
      params: candidatoId ? { candidato_id: candidatoId } : {},
    })
    return data
  },

  async obterRelatorioInteligente({ candidatoId = null, dataInicio = null, dataFim = null } = {}) {
    const params = {}

    if (candidatoId) params.candidato_id = candidatoId
    if (dataInicio) params.data_inicio = dataInicio
    if (dataFim) params.data_fim = dataFim

    const { data } = await http.get('/api/financeiro/relatorios/inteligente', { params })
    return data
  },

  async obterAlertas(candidatoId = null) {
    const { data } = await http.get('/api/financeiro/alertas', {
      params: candidatoId ? { candidato_id: candidatoId } : {},
    })
    return data
  },

  async obterAuditoria({ candidatoId = null, dataInicio = null, dataFim = null } = {}) {
    const params = {}

    if (candidatoId) params.candidato_id = candidatoId
    if (dataInicio) params.data_inicio = dataInicio
    if (dataFim) params.data_fim = dataFim

    const { data } = await http.get('/api/financeiro/auditoria', { params })
    return data
  },
}
