import { defineStore } from 'pinia'
import { ref } from 'vue'
import combustivelServico from '@/services/combustivelServico.js'

export const useCombustivelStore = defineStore('combustivel', () => {
  const abastecimentos = ref([])
  const abastecimentoAtual = ref(null)
  const paginacao = ref({})
  const carregando = ref(false)
  const erro = ref(null)

  async function carregarAbastecimentos(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await combustivelServico.listar(pagina, limite, busca)
      abastecimentos.value = resposta.dados || []
      paginacao.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function buscarAbastecimento(id) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await combustivelServico.buscarPorId(id)
      abastecimentoAtual.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      return null
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarAbastecimento(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await combustivelServico.cadastrar(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarAbastecimento(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await combustivelServico.atualizar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerAbastecimento(id) {
    carregando.value = true
    erro.value = null

    try {
      await combustivelServico.remover(id)
      abastecimentos.value = abastecimentos.value.filter((item) => item.id !== id)
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  return {
    abastecimentos,
    abastecimentoAtual,
    paginacao,
    carregando,
    erro,
    carregarAbastecimentos,
    buscarAbastecimento,
    cadastrarAbastecimento,
    atualizarAbastecimento,
    removerAbastecimento,
  }
})