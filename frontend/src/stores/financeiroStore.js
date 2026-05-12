import { defineStore } from 'pinia'
import { ref } from 'vue'
import financeiroServico from '@/services/financeiroServico.js'

export const useFinanceiroStore = defineStore('financeiro', () => {
  const fornecedores = ref([])
  const receitas = ref([])
  const despesas = ref([])
  const paginacaoFornecedores = ref({})
  const paginacaoReceitas = ref({})
  const paginacaoDespesas = ref({})

  const saldos = ref(null)
  const relatorio = ref(null)
  const alertas = ref([])
  const auditoria = ref([])

  const carregando = ref(false)
  const erro = ref(null)

  function limparErro() {
    erro.value = null
  }

  async function carregarFornecedores(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.listarFornecedores(pagina, limite, busca)
      fornecedores.value = resposta.dados || []
      paginacaoFornecedores.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarFornecedor(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.cadastrarFornecedor(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarFornecedor(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.atualizarFornecedor(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerFornecedor(id) {
    carregando.value = true
    erro.value = null

    try {
      await financeiroServico.removerFornecedor(id)
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function carregarReceitas(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.listarReceitas(pagina, limite, busca)
      receitas.value = resposta.dados || []
      paginacaoReceitas.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarReceita(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.cadastrarReceita(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarReceita(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.atualizarReceita(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerReceita(id) {
    carregando.value = true
    erro.value = null

    try {
      await financeiroServico.removerReceita(id)
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function carregarDespesas(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.listarDespesas(pagina, limite, busca)
      despesas.value = resposta.dados || []
      paginacaoDespesas.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarDespesa(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.cadastrarDespesa(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarDespesaPessoalLider(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.cadastrarDespesaPessoalLider(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarDespesa(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.atualizarDespesa(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerDespesa(id) {
    carregando.value = true
    erro.value = null

    try {
      await financeiroServico.removerDespesa(id)
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function carregarSaldos(candidatoId = null) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.obterSaldos(candidatoId)
      saldos.value = resposta.dados || null
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function carregarRelatorio(params = {}) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.obterRelatorioInteligente(params)
      relatorio.value = resposta.dados || null
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function carregarAlertas(candidatoId = null) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.obterAlertas(candidatoId)
      alertas.value = resposta.dados || []
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function carregarAuditoria(params = {}) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await financeiroServico.obterAuditoria(params)
      auditoria.value = resposta.dados || []
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  return {
    fornecedores,
    receitas,
    despesas,
    paginacaoFornecedores,
    paginacaoReceitas,
    paginacaoDespesas,
    saldos,
    relatorio,
    alertas,
    auditoria,
    carregando,
    erro,
    limparErro,
    carregarFornecedores,
    cadastrarFornecedor,
    atualizarFornecedor,
    removerFornecedor,
    carregarReceitas,
    cadastrarReceita,
    atualizarReceita,
    removerReceita,
    carregarDespesas,
    cadastrarDespesa,
    cadastrarDespesaPessoalLider,
    atualizarDespesa,
    removerDespesa,
    carregarSaldos,
    carregarRelatorio,
    carregarAlertas,
    carregarAuditoria,
  }
})
