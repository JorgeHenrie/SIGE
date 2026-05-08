import { defineStore } from 'pinia'
import { ref } from 'vue'
import agendaServico from '@/services/agendaServico.js'

export const useAgendaStore = defineStore('agenda', () => {
  const eventos = ref([])
  const eventoAtual = ref(null)
  const paginacao = ref({})
  const carregando = ref(false)
  const erro = ref(null)

  async function carregarEventos(pagina = 1, limite = 15, busca = '', status = '') {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.listar(pagina, limite, busca, status)
      eventos.value = resposta.dados || []
      paginacao.value = resposta.paginacao || {}
    } catch (e) {
      erro.value = e.message
    } finally {
      carregando.value = false
    }
  }

  async function buscarEvento(id) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.buscarPorId(id)
      eventoAtual.value = resposta.dados
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      return null
    } finally {
      carregando.value = false
    }
  }

  async function cadastrarEvento(dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.cadastrar(dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function atualizarEvento(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.atualizar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function aprovarEvento(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.aprovar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function recusarEvento(id, dados) {
    carregando.value = true
    erro.value = null

    try {
      const resposta = await agendaServico.recusar(id, dados)
      return resposta.dados
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  async function removerEvento(id) {
    carregando.value = true
    erro.value = null

    try {
      await agendaServico.remover(id)
      eventos.value = eventos.value.filter((evento) => evento.id !== id)
    } catch (e) {
      erro.value = e.message
      throw e
    } finally {
      carregando.value = false
    }
  }

  return {
    eventos,
    eventoAtual,
    paginacao,
    carregando,
    erro,
    carregarEventos,
    buscarEvento,
    cadastrarEvento,
    atualizarEvento,
    aprovarEvento,
    recusarEvento,
    removerEvento,
  }
})