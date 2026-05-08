import { defineStore } from 'pinia'
import { ref } from 'vue'
import apoiadorServico from '@/services/apoiadorServico.js'

export const useApoiadorStore = defineStore('apoiador', () => {
  const apoiadores    = ref([])
  const apoiadorAtual = ref(null)
  const paginacao     = ref({})
  const carregando    = ref(false)
  const erro          = ref(null)

  async function carregarApoiadores(pagina = 1, limite = 15, busca = '') {
    carregando.value = true
    erro.value = null
    try {
      const resposta    = await apoiadorServico.listar(pagina, limite, busca)
      apoiadores.value  = resposta.dados
      paginacao.value   = resposta.paginacao
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function buscarApoiador(id) {
    carregando.value = true
    erro.value = null
    try {
      const resposta      = await apoiadorServico.buscarPorId(id)
      apoiadorAtual.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      return null
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarApoiador(dados) {
    carregando.value = true
    erro.value = null
    try {
      const resposta = await apoiadorServico.cadastrar(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarApoiador(id, dados) {
    carregando.value = true
    erro.value = null
    try {
      const resposta = await apoiadorServico.atualizar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerApoiador(id) {
    carregando.value = true
    erro.value = null
    try {
      await apoiadorServico.remover(id)
      apoiadores.value = apoiadores.value.filter((a) => a.id !== id)
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  return {
    apoiadores,
    apoiadorAtual,
    paginacao,
    carregando,
    erro,
    carregarApoiadores,
    buscarApoiador,
    cadastrarApoiador,
    atualizarApoiador,
    removerApoiador,
  }
})
