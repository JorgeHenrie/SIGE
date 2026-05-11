import { defineStore } from 'pinia'
import { ref } from 'vue'
import roteiroServico from '@/services/roteiroServico.js'

export const useRoteiroStore = defineStore('roteiros', () => {
  const roteiros = ref([])
  const roteiroAtual = ref(null)
  const preview = ref(null)
  const paginacao = ref({})
  const carregando = ref(false)
  const erro = ref(null)

  async function carregarRoteiros(pagina = 1, limite = 15, busca = '', liderId = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await roteiroServico.listar(pagina, limite, busca, liderId)
      roteiros.value = resposta.dados || []
      paginacao.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function buscarRoteiro(id) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await roteiroServico.buscarPorId(id)
      roteiroAtual.value = resposta.dados
      preview.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      return null
    } finally {
      carregando.value = false
    }
  }

  async function sugerirRoteiro(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await roteiroServico.sugerir(dados)
      preview.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarRoteiro(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await roteiroServico.cadastrar(dados)
      roteiroAtual.value = resposta.dados
      preview.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function recalcularRoteiro(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await roteiroServico.recalcular(id, dados)
      roteiroAtual.value = resposta.dados
      preview.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerRoteiro(id) {
    carregando.value = true
    erro.value = null

    try {
      await roteiroServico.remover(id)
      roteiros.value = roteiros.value.filter((item) => item.id !== id)
      paginacao.value = {
        ...paginacao.value,
        total: Math.max(0, Number(paginacao.value?.total || roteiros.value.length + 1) - 1),
      }
      if (roteiroAtual.value?.id === id) roteiroAtual.value = null
      if (preview.value?.id === id) preview.value = null
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  function limparPreview() {
    preview.value = null
  }

  return {
    roteiros,
    roteiroAtual,
    preview,
    paginacao,
    carregando,
    erro,
    carregarRoteiros,
    buscarRoteiro,
    sugerirRoteiro,
    cadastrarRoteiro,
    recalcularRoteiro,
    removerRoteiro,
    limparPreview,
  }
})