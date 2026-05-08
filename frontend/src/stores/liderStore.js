import { defineStore } from 'pinia'
import { ref } from 'vue'
import liderServico from '@/services/liderServico.js'

export const useLiderStore = defineStore('lider', () => {
  const lideres     = ref([])
  const liderAtual  = ref(null)
  const paginacao   = ref({})
  const carregando  = ref(false)
  const erro        = ref(null)

  async function carregarLideres(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null
    try {
      const resposta = await liderServico.listar(pagina, limite, busca)
      lideres.value  = resposta.dados
      paginacao.value = resposta.paginacao
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function buscarLider(id) {
    carregando.value = true
    erro.value = null
    try {
      const resposta  = await liderServico.buscarPorId(id)
      liderAtual.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      return null
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarLider(dados) {
    carregando.value = true
    erro.value = null
    try {
      const resposta = await liderServico.cadastrar(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarLider(id, dados) {
    carregando.value = true
    erro.value = null
    try {
      const resposta = await liderServico.atualizar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerLider(id) {
    carregando.value = true
    erro.value = null
    try {
      await liderServico.remover(id)
      lideres.value = lideres.value.filter((l) => l.id !== id)
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  return {
    lideres,
    liderAtual,
    paginacao,
    carregando,
    erro,
    carregarLideres,
    buscarLider,
    cadastrarLider,
    atualizarLider,
    removerLider,
  }
})
