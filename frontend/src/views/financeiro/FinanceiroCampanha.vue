<template>
  <div class="financeiro-page">
    <header class="hero-card">
      <div>
        <span class="hero-eyebrow">Compliance eleitoral</span>
        <h1 class="hero-titulo">Gestao Financeira de Campanha</h1>
        <p class="hero-subtitulo">
          Controle receitas e despesas com rastreabilidade completa por origem do recurso.
        </p>
      </div>
      <button class="btn-principal" @click="carregarPainel" :disabled="store.carregando">
        {{ store.carregando ? 'Atualizando...' : 'Atualizar painel' }}
      </button>
    </header>

    <AlertaMensagem v-if="store.erro" tipo="erro" :mensagem="store.erro" />

    <section class="filtros-card">
      <div class="filtro-item" v-if="podeSelecionarCandidato">
        <label for="candidatoId">Candidato (ID)</label>
        <input id="candidatoId" v-model.trim="filtros.candidatoId" type="text" placeholder="UUID do candidato" />
      </div>
      <div class="filtro-item">
        <label for="dataInicio">Data inicial</label>
        <input id="dataInicio" v-model="filtros.dataInicio" type="date" />
      </div>
      <div class="filtro-item">
        <label for="dataFim">Data final</label>
        <input id="dataFim" v-model="filtros.dataFim" type="date" />
      </div>
      <div class="filtro-acoes">
        <button class="btn-secundario" @click="aplicarFiltros">Aplicar filtros</button>
      </div>
    </section>

    <section class="resumo-grid" v-if="store.saldos">
      <article class="resumo-card resumo-card--total">
        <span class="resumo-label">Total recebido</span>
        <strong class="resumo-valor">{{ formatarMoeda(store.saldos.totais?.total_recebido) }}</strong>
      </article>
      <article class="resumo-card resumo-card--uso">
        <span class="resumo-label">Total utilizado</span>
        <strong class="resumo-valor">{{ formatarMoeda(store.saldos.totais?.total_utilizado) }}</strong>
      </article>
      <article class="resumo-card resumo-card--saldo">
        <span class="resumo-label">Saldo restante</span>
        <strong class="resumo-valor">{{ formatarMoeda(store.saldos.totais?.total_saldo) }}</strong>
      </article>
    </section>

    <section class="abas-nav">
      <button
        v-for="aba in abas"
        :key="aba.id"
        :class="['aba-btn', { 'aba-btn--ativa': abaAtiva === aba.id }]"
        @click="abaAtiva = aba.id"
      >
        {{ aba.label }}
      </button>
    </section>

    <section v-if="abaAtiva === 'lancamentos'" class="aba-conteudo">
      <div class="form-grid">
        <article class="form-card">
          <h2>{{ receitaEditId ? 'Editar receita' : 'Receita' }}</h2>
          <form @submit.prevent="enviarReceita">
            <input v-if="podeSelecionarCandidato" v-model.trim="receitaForm.candidato_id" type="text" placeholder="candidato_id" required />
            <select v-model="receitaForm.tipo_recurso" required>
              <option value="fundo_partidario">Fundo partidario</option>
              <option value="fundo_eleitoral">Fundo eleitoral</option>
              <option value="doacao_privada">Doacao privada</option>
            </select>
            <input v-model.number="receitaForm.valor_total" type="number" min="0.01" step="0.01" placeholder="Valor total" required />
            <input v-model="receitaForm.data_recebimento" type="date" required />
            <input v-model.trim="receitaForm.origem" type="text" placeholder="Origem (opcional)" />
            <button type="submit">{{ receitaEditId ? 'Atualizar receita' : 'Cadastrar receita' }}</button>
            <button v-if="receitaEditId" type="button" class="btn-link" @click="cancelarEdicaoReceita">Cancelar edicao</button>
          </form>
        </article>

        <article class="form-card">
          <h2>{{ despesaEditId ? 'Editar despesa' : 'Despesa' }}</h2>
          <form @submit.prevent="enviarDespesa">
            <input v-if="podeSelecionarCandidato" v-model.trim="despesaForm.candidato_id" type="text" placeholder="candidato_id" required />
            <select v-model="despesaForm.receita_id" required>
              <option value="">Selecione a receita de origem</option>
              <option v-for="item in receitasOrdenadasDespesa" :key="item.id" :value="item.id">
                {{ formatarReceitaOpcao(item) }}
              </option>
            </select>
            <select v-model="despesaForm.fornecedor_id" required>
              <option value="">Selecione o fornecedor</option>
              <option v-for="item in fornecedoresOrdenadosDespesa" :key="item.id" :value="item.id">
                {{ item.nome }}{{ item.documento ? ` - ${item.documento}` : '' }}
              </option>
            </select>
            <select v-model="despesaForm.categoria" required>
              <option value="">Selecione a categoria</option>
              <option v-for="item in categoriasDespesa" :key="item.valor" :value="item.valor">
                {{ item.label }}
              </option>
            </select>
            <select v-model="despesaForm.subcategoria" :disabled="!despesaForm.categoria" required>
              <option value="">Selecione a subcategoria</option>
              <option v-for="item in subcategoriasAtivasDespesa" :key="item.valor" :value="item.valor">
                {{ item.label }}
              </option>
            </select>
            <p class="ajuda-categoria">Categorias oficiais para relatorio: grafico, conteudo, digital, equipe, logistica, eventos, terceiros, comunicacao, juridico/contabil.</p>
            <input v-model.number="despesaForm.valor" type="number" min="0.01" step="0.01" placeholder="Valor" required />
            <input v-model="despesaForm.data" type="date" required />
            <textarea v-model.trim="despesaForm.descricao" rows="2" placeholder="Descricao" required></textarea>
            <button type="submit">{{ despesaEditId ? 'Atualizar despesa' : 'Cadastrar despesa' }}</button>
            <button v-if="despesaEditId" type="button" class="btn-link" @click="cancelarEdicaoDespesa">Cancelar edicao</button>
          </form>
        </article>
      </div>

      <div class="tabelas-grid tabela-stack">
        <article class="bloco-card">
          <div class="bloco-header-inline">
            <h3>Fornecedores</h3>
            <button class="btn-mini btn-mini--novo-fornecedor" @click="abrirNovoFornecedor">Novo</button>
          </div>
          <div class="tabela-wrapper" v-if="store.fornecedores.length">
            <table class="tabela">
              <thead>
                <tr><th>Nome</th><th>Documento</th><th>Tipo</th><th>Acoes</th></tr>
              </thead>
              <tbody>
                <tr v-for="item in store.fornecedores" :key="item.id">
                  <td>{{ item.nome }}</td>
                  <td>{{ item.documento || '-' }}</td>
                  <td>{{ item.tipo_fornecedor ? formatarSlugParaCliente(item.tipo_fornecedor) : '-' }}</td>
                  <td class="acoes-cell">
                    <button class="btn-mini" @click="editarFornecedor(item)">Editar</button>
                    <button class="btn-mini btn-mini--danger" @click="removerFornecedor(item.id)">Remover</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="vazio">Sem fornecedores cadastrados.</p>
          <div class="paginacao" v-if="(store.paginacaoFornecedores?.total_paginas || 1) > 1">
            <button @click="carregarFornecedoresPagina((store.paginacaoFornecedores.pagina_atual || 1) - 1)" :disabled="(store.paginacaoFornecedores.pagina_atual || 1) <= 1">Anterior</button>
            <span>Pagina {{ store.paginacaoFornecedores.pagina_atual || 1 }} de {{ store.paginacaoFornecedores.total_paginas || 1 }}</span>
            <button @click="carregarFornecedoresPagina((store.paginacaoFornecedores.pagina_atual || 1) + 1)" :disabled="(store.paginacaoFornecedores.pagina_atual || 1) >= (store.paginacaoFornecedores.total_paginas || 1)">Proxima</button>
          </div>
        </article>

        <article class="bloco-card">
          <h3>Receitas</h3>
          <div class="tabela-wrapper" v-if="store.receitas.length">
            <table class="tabela">
              <thead>
                <tr><th>Tipo</th><th>Origem</th><th>Total</th><th>Disponivel</th><th>Recebimento</th><th>Acoes</th></tr>
              </thead>
              <tbody>
                <tr v-for="item in store.receitas" :key="item.id">
                  <td>{{ formatarSlugParaCliente(item.tipo_recurso) }}</td>
                  <td>{{ item.origem || '-' }}</td>
                  <td>{{ formatarMoeda(item.valor_total) }}</td>
                  <td class="td-destaque">{{ formatarMoeda(item.valor_disponivel) }}</td>
                  <td>{{ formatarData(item.data_recebimento) }}</td>
                  <td class="acoes-cell">
                    <button class="btn-mini" @click="editarReceita(item)">Editar</button>
                    <button class="btn-mini btn-mini--danger" @click="removerReceita(item.id)">Remover</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="vazio">Sem receitas cadastradas.</p>
          <div class="paginacao" v-if="(store.paginacaoReceitas?.total_paginas || 1) > 1">
            <button @click="carregarReceitasPagina((store.paginacaoReceitas.pagina_atual || 1) - 1)" :disabled="(store.paginacaoReceitas.pagina_atual || 1) <= 1">Anterior</button>
            <span>Pagina {{ store.paginacaoReceitas.pagina_atual || 1 }} de {{ store.paginacaoReceitas.total_paginas || 1 }}</span>
            <button @click="carregarReceitasPagina((store.paginacaoReceitas.pagina_atual || 1) + 1)" :disabled="(store.paginacaoReceitas.pagina_atual || 1) >= (store.paginacaoReceitas.total_paginas || 1)">Proxima</button>
          </div>
        </article>

        <article class="bloco-card">
          <h3>Despesas</h3>
          <div class="tabela-wrapper" v-if="store.despesas.length">
            <table class="tabela">
              <thead>
                <tr><th>Categoria</th><th>Valor</th><th>Conformidade</th><th>Fornecedor</th><th>Data</th><th>Acoes</th></tr>
              </thead>
              <tbody>
                <tr v-for="item in store.despesas" :key="item.id">
                  <td>{{ formatarCategoriaCompleta(item.categoria, item.subcategoria) }}</td>
                  <td>{{ formatarMoeda(item.valor) }}</td>
                  <td>
                    <span :class="['badge', `badge--${item.classificacao_conformidade || 'valida'}`]">{{ item.classificacao_conformidade }}</span>
                  </td>
                  <td>{{ item.fornecedor_nome || '-' }}</td>
                  <td>{{ formatarData(item.data_despesa) }}</td>
                  <td class="acoes-cell">
                    <button class="btn-mini" @click="editarDespesa(item)">Editar</button>
                    <button class="btn-mini btn-mini--danger" @click="removerDespesa(item.id)">Remover</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="vazio">Sem despesas cadastradas.</p>
          <div class="paginacao" v-if="(store.paginacaoDespesas?.total_paginas || 1) > 1">
            <button @click="carregarDespesasPagina((store.paginacaoDespesas.pagina_atual || 1) - 1)" :disabled="(store.paginacaoDespesas.pagina_atual || 1) <= 1">Anterior</button>
            <span>Pagina {{ store.paginacaoDespesas.pagina_atual || 1 }} de {{ store.paginacaoDespesas.total_paginas || 1 }}</span>
            <button @click="carregarDespesasPagina((store.paginacaoDespesas.pagina_atual || 1) + 1)" :disabled="(store.paginacaoDespesas.pagina_atual || 1) >= (store.paginacaoDespesas.total_paginas || 1)">Proxima</button>
          </div>
        </article>
      </div>
    </section>

    <section v-if="abaAtiva === 'alertas'" class="aba-conteudo">
      <article class="bloco-card">
        <div class="bloco-header-inline">
          <h3>Alertas automaticos</h3>
          <button class="btn-mini" @click="exportarAlertasCsv" :disabled="!(store.alertas || []).length">Exportar CSV</button>
        </div>
        <div class="tabela-wrapper" v-if="alertasPaginados.length">
          <table class="tabela">
            <thead>
              <tr><th>Nivel</th><th>Codigo</th><th>Descricao</th><th>Indicador</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in alertasPaginados" :key="item.id">
                <td><span :class="['badge', `badge--${item.alerta_nivel || 'medio'}`]">{{ item.alerta_nivel }}</span></td>
                <td>{{ formatarSlugParaCliente(item.alerta_codigo) }}</td>
                <td>{{ formatarDescricaoCliente(item.alerta_descricao) }}</td>
                <td>{{ formatarPercentual(item.indicador_percentual) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="vazio">Sem alertas no periodo.</p>
        <div class="paginacao" v-if="totalPaginasAlertas > 1">
          <button @click="paginaAlertas = paginaAlertas - 1" :disabled="paginaAlertas <= 1">Anterior</button>
          <span>Pagina {{ paginaAlertas }} de {{ totalPaginasAlertas }}</span>
          <button @click="paginaAlertas = paginaAlertas + 1" :disabled="paginaAlertas >= totalPaginasAlertas">Proxima</button>
        </div>
      </article>

      <article class="bloco-card" v-if="store.relatorio">
        <h3>Despesas suspeitas ou invalidas</h3>
        <div class="tabela-wrapper" v-if="despesasNaoConformesPaginadas.length">
          <table class="tabela">
            <thead>
              <tr><th>Categoria</th><th>Valor</th><th>Classificacao</th><th>Motivo</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in despesasNaoConformesPaginadas" :key="item.id">
                <td>{{ formatarCategoriaCompleta(item.categoria, item.subcategoria) }}</td>
                <td>{{ formatarMoeda(item.valor) }}</td>
                <td>{{ item.classificacao_conformidade }}</td>
                <td>{{ item.conformidade_motivo || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="vazio">Nenhuma despesa nao conforme encontrada.</p>
        <div class="paginacao" v-if="totalPaginasNaoConformes > 1">
          <button @click="paginaNaoConformes = paginaNaoConformes - 1" :disabled="paginaNaoConformes <= 1">Anterior</button>
          <span>Pagina {{ paginaNaoConformes }} de {{ totalPaginasNaoConformes }}</span>
          <button @click="paginaNaoConformes = paginaNaoConformes + 1" :disabled="paginaNaoConformes >= totalPaginasNaoConformes">Proxima</button>
        </div>
      </article>

      <article class="bloco-card" v-if="rankingCategorias.length">
        <div class="periodo-rapido-ranking">
          <span>Periodo do ranking:</span>
          <button
            v-for="opcao in opcoesPeriodoRanking"
            :key="opcao.id"
            :class="['btn-mini', { 'btn-mini--ativo': filtroRapidoRanking === opcao.id }]"
            @click="filtroRapidoRanking = opcao.id"
          >
            {{ opcao.label }}
          </button>
        </div>
        <h3>Ranking visual das categorias (top gastos)</h3>
        <div class="ranking-lista">
          <div v-for="item in rankingCategorias" :key="item.categoria" class="ranking-item">
            <div class="ranking-topo">
              <strong>{{ formatarCategoria(item.categoria) }}</strong>
              <span>{{ formatarMoeda(item.total_categoria) }} ({{ formatarPercentual(item.percentual_uso) }})</span>
            </div>
            <div class="ranking-barra">
              <div class="ranking-barra-fill" :style="{ width: `${Number(item.percentual_uso || 0)}%` }"></div>
            </div>
          </div>
        </div>
      </article>

      <article class="bloco-card" v-if="rankingSubcategorias.length">
        <h3>Ranking visual das subcategorias (top gastos)</h3>
        <div class="ranking-lista">
          <div v-for="item in rankingSubcategorias" :key="`${item.categoria}-${item.subcategoria}`" class="ranking-item">
            <div class="ranking-topo">
              <strong>{{ formatarCategoriaCompleta(item.categoria, item.subcategoria) }}</strong>
              <span>{{ formatarMoeda(item.total_subcategoria) }} ({{ formatarPercentual(item.percentual_uso) }})</span>
            </div>
            <div class="ranking-barra">
              <div class="ranking-barra-fill ranking-barra-fill--sub" :style="{ width: `${Number(item.percentual_uso || 0)}%` }"></div>
            </div>
          </div>
        </div>
      </article>

      <article class="bloco-card" v-if="rankingSubcategoriasPorCategoria.length">
        <h3>Top subcategorias por categoria</h3>
        <div class="grupos-subcategoria">
          <div class="grupo-subcategoria" v-for="grupo in rankingSubcategoriasPorCategoria" :key="grupo.categoria">
            <h4>{{ formatarCategoria(grupo.categoria) }}</h4>
            <ul>
              <li v-for="item in grupo.itens" :key="`${grupo.categoria}-${item.subcategoria}`">
                <span>{{ formatarSubcategoria(item.subcategoria) }}</span>
                <span>{{ formatarMoeda(item.total_subcategoria) }} ({{ formatarPercentual(item.percentual_uso) }})</span>
              </li>
            </ul>
          </div>
        </div>
      </article>

      <article class="bloco-card" v-if="indicadoresEquipeCampanha.totalEquipe > 0">
        <h3>Equipe de campanha: salario de lider x restante</h3>
        <div class="resumo-equipe-grid">
          <div class="resumo-equipe-item resumo-equipe-item--total">
            <span>Total equipe</span>
            <strong>{{ formatarMoeda(indicadoresEquipeCampanha.totalEquipe) }}</strong>
          </div>
          <div class="resumo-equipe-item resumo-equipe-item--salario">
            <span>Salario de lider</span>
            <strong>{{ formatarMoeda(indicadoresEquipeCampanha.totalSalarioLider) }}</strong>
            <small>{{ formatarPercentual(indicadoresEquipeCampanha.percentualSalarioLider) }} do total da equipe</small>
          </div>
          <div class="resumo-equipe-item resumo-equipe-item--restante">
            <span>Restante da equipe</span>
            <strong>{{ formatarMoeda(indicadoresEquipeCampanha.totalRestanteEquipe) }}</strong>
            <small>{{ formatarPercentual(indicadoresEquipeCampanha.percentualRestanteEquipe) }} do total da equipe</small>
          </div>
        </div>

        <div class="periodo-rapido-ranking">
          <span>Filtro da equipe:</span>
          <button
            v-for="opcao in opcoesFiltroEquipe"
            :key="opcao.id"
            :class="['btn-mini', { 'btn-mini--ativo': filtroEquipeCampanha === opcao.id }]"
            @click="filtroEquipeCampanha = opcao.id"
          >
            {{ opcao.label }}
          </button>
        </div>

        <div class="tabela-wrapper" v-if="subcategoriasEquipeFiltradas.length">
          <table class="tabela">
            <thead>
              <tr><th>Subcategoria</th><th>Total</th><th>Participacao na equipe</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in subcategoriasEquipeFiltradas" :key="`equipe-${item.subcategoria}`">
                <td>{{ formatarSubcategoria(item.subcategoria) }}</td>
                <td>{{ formatarMoeda(item.total_subcategoria) }}</td>
                <td>{{ formatarPercentual(item.percentual_na_equipe) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="vazio">Sem despesas da equipe para o filtro selecionado.</p>
      </article>
    </section>

    <section v-if="abaAtiva === 'auditoria'" class="aba-conteudo">
      <article class="bloco-card">
        <div class="bloco-header-inline">
          <h3>Rastreabilidade receita -> despesa</h3>
          <button class="btn-mini" @click="exportarAuditoriaCsv" :disabled="!(store.auditoria || []).length">Exportar CSV</button>
        </div>
        <div class="tabela-wrapper" v-if="auditoriaPaginada.length">
          <table class="tabela">
            <thead>
              <tr>
                <th>Despesa</th><th>Categoria</th><th>Valor</th><th>Receita</th><th>Tipo recurso</th><th>Origem</th><th>Conformidade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in auditoriaPaginada" :key="item.despesa_id">
                <td>{{ formatarData(item.data_despesa) }}</td>
                <td>{{ formatarCategoriaCompleta(item.categoria, item.subcategoria) }}</td>
                <td>{{ formatarMoeda(item.despesa_valor) }}</td>
                <td>{{ item.receita_id }}</td>
                <td>{{ formatarSlugParaCliente(item.tipo_recurso) }}</td>
                <td>{{ item.receita_origem || '-' }}</td>
                <td>{{ formatarSlugParaCliente(item.classificacao_conformidade) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="vazio">Sem trilha de auditoria para os filtros aplicados.</p>
        <div class="paginacao" v-if="totalPaginasAuditoria > 1">
          <button @click="paginaAuditoria = paginaAuditoria - 1" :disabled="paginaAuditoria <= 1">Anterior</button>
          <span>Pagina {{ paginaAuditoria }} de {{ totalPaginasAuditoria }}</span>
          <button @click="paginaAuditoria = paginaAuditoria + 1" :disabled="paginaAuditoria >= totalPaginasAuditoria">Proxima</button>
        </div>
      </article>
    </section>

    <div v-if="mostrarModalFornecedor" class="modal-overlay" @click.self="fecharModalFornecedor">
      <article class="modal-card">
        <h3>{{ fornecedorEditId ? 'Editar fornecedor' : 'Novo fornecedor' }}</h3>
        <form @submit.prevent="enviarFornecedor">
          <input v-if="podeSelecionarCandidato" v-model.trim="fornecedorForm.candidato_id" type="text" placeholder="candidato_id" required />
          <input v-model.trim="fornecedorForm.nome" type="text" placeholder="Nome" required />
          <input v-model.trim="fornecedorForm.documento" type="text" placeholder="Documento (opcional)" />
          <input v-model.trim="fornecedorForm.tipo_fornecedor" type="text" placeholder="Tipo (opcional)" />
          <div class="modal-acoes">
            <button type="button" class="btn-mini" @click="fecharModalFornecedor">Cancelar</button>
            <button type="submit" class="btn-mini btn-mini--ativo">{{ fornecedorEditId ? 'Salvar' : 'Cadastrar' }}</button>
          </div>
        </form>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore.js'
import { useFinanceiroStore } from '@/stores/financeiroStore.js'
import financeiroServico from '@/services/financeiroServico.js'
import AlertaMensagem from '@/components/AlertaMensagem.vue'

const store = useFinanceiroStore()
const authStore = useAuthStore()

const abaAtiva = ref('lancamentos')
const abas = [
  { id: 'lancamentos', label: 'Lancamentos' },
  { id: 'alertas', label: 'Alertas e conformidade' },
  { id: 'auditoria', label: 'Auditoria' },
]

const limiteTabela = 10
const limiteCliente = 10

const paginaFornecedores = ref(1)
const paginaReceitas = ref(1)
const paginaDespesas = ref(1)

const paginaAlertas = ref(1)
const paginaNaoConformes = ref(1)
const paginaAuditoria = ref(1)
const filtroRapidoRanking = ref('todos')
const filtroEquipeCampanha = ref('todos')

const opcoesPeriodoRanking = [
  { id: '7d', label: 'Ultimos 7 dias' },
  { id: '30d', label: 'Ultimos 30 dias' },
  { id: 'todos', label: 'Campanha inteira' },
]

const opcoesFiltroEquipe = [
  { id: 'todos', label: 'Todos' },
  { id: 'salario_lider', label: 'Somente salario de lider' },
  { id: 'demais', label: 'Somente restante da equipe' },
]

const fornecedorEditId = ref(null)
const receitaEditId = ref(null)
const despesaEditId = ref(null)
const mostrarModalFornecedor = ref(false)
const opcoesFornecedoresDespesa = ref([])
const opcoesReceitasDespesa = ref([])

const filtros = reactive({
  candidatoId: '',
  dataInicio: '',
  dataFim: '',
})

const fornecedorForm = reactive({
  candidato_id: '',
  nome: '',
  documento: '',
  tipo_fornecedor: '',
})

const receitaForm = reactive({
  candidato_id: '',
  tipo_recurso: 'fundo_eleitoral',
  valor_total: null,
  data_recebimento: '',
  origem: '',
})

const despesaForm = reactive({
  candidato_id: '',
  receita_id: '',
  fornecedor_id: '',
  categoria: '',
  subcategoria: '',
  valor: null,
  data: '',
  descricao: '',
})

const podeSelecionarCandidato = computed(() => false)

const categoriasDespesa = [
  {
    valor: 'material_grafico',
    label: 'Material grafico',
    subcategorias: [
      { valor: 'santinhos', label: 'Santinhos' },
      { valor: 'panfletos', label: 'Panfletos' },
      { valor: 'adesivos', label: 'Adesivos' },
      { valor: 'bandeiras', label: 'Bandeiras' },
      { valor: 'cartazes', label: 'Cartazes' },
    ],
  },
  {
    valor: 'producao_conteudo',
    label: 'Producao de conteudo',
    subcategorias: [
      { valor: 'gravacao_videos', label: 'Gravacao de videos' },
      { valor: 'fotografia_profissional', label: 'Fotografia profissional' },
      { valor: 'edicao_video', label: 'Edicao de video' },
      { valor: 'design_grafico', label: 'Design grafico' },
    ],
  },
  {
    valor: 'marketing_digital',
    label: 'Marketing digital',
    subcategorias: [
      { valor: 'gestao_redes_sociais', label: 'Gestao de redes sociais' },
      { valor: 'trafego_pago', label: 'Trafego pago' },
      { valor: 'impulsionamento_posts', label: 'Impulsionamento de posts' },
    ],
  },
  {
    valor: 'equipe_campanha',
    label: 'Equipe de campanha',
    subcategorias: [
      { valor: 'salario_lider', label: 'Salario de lider' },
      { valor: 'coordenador_geral', label: 'Coordenador geral' },
      { valor: 'cabos_eleitorais', label: 'Cabos eleitorais' },
      { valor: 'equipe_rua', label: 'Equipe de rua' },
      { valor: 'social_media', label: 'Social media' },
      { valor: 'designers', label: 'Designers' },
      { valor: 'advogado_eleitoral', label: 'Advogado eleitoral' },
      { valor: 'contador', label: 'Contador' },
    ],
  },
  {
    valor: 'transporte_logistica',
    label: 'Transporte e logistica',
    subcategorias: [
      { valor: 'combustivel', label: 'Combustivel' },
      { valor: 'aluguel_veiculos', label: 'Aluguel de veiculos' },
      { valor: 'motoristas', label: 'Motoristas' },
      { valor: 'manutencao_basica', label: 'Manutencao basica' },
    ],
  },
  {
    valor: 'eventos_mobilizacao',
    label: 'Eventos e mobilizacao',
    subcategorias: [
      { valor: 'comicios', label: 'Comicios' },
      { valor: 'reunioes_comunitarias', label: 'Reunioes comunitarias' },
      { valor: 'aluguel_espaco', label: 'Aluguel de espaco' },
      { valor: 'som_estrutura', label: 'Som e estrutura' },
      { valor: 'alimentacao_eventos', label: 'Alimentacao em eventos' },
    ],
  },
  {
    valor: 'servicos_terceiros',
    label: 'Servicos de terceiros',
    subcategorias: [
      { valor: 'graficas', label: 'Graficas' },
      { valor: 'empresas_marketing', label: 'Empresas de marketing' },
      { valor: 'pesquisas_eleitorais', label: 'Pesquisas eleitorais' },
      { valor: 'consultorias', label: 'Consultorias' },
    ],
  },
  {
    valor: 'comunicacao_oficial',
    label: 'Comunicacao oficial de campanha',
    subcategorias: [
      { valor: 'jingles', label: 'Producao de jingles' },
      { valor: 'programas_radio_tv', label: 'Programas de radio/TV' },
      { valor: 'assessoria_imprensa', label: 'Assessoria de imprensa' },
    ],
  },
  {
    valor: 'custos_juridicos_contabeis',
    label: 'Custos juridicos e contabeis',
    subcategorias: [
      { valor: 'prestacao_contas', label: 'Prestacao de contas' },
      { valor: 'acompanhamento_juridico', label: 'Acompanhamento juridico eleitoral' },
    ],
  },
]

const categoriasDespesaMap = Object.fromEntries(categoriasDespesa.map((item) => [item.valor, item.label]))
const subcategoriasDespesaMap = Object.fromEntries(
  categoriasDespesa.flatMap((categoria) => categoria.subcategorias.map((sub) => [sub.valor, sub.label]))
)

const subcategoriasAtivasDespesa = computed(() => {
  const categoria = categoriasDespesa.find((item) => item.valor === despesaForm.categoria)
  return categoria?.subcategorias || []
})

const candidatoAtivoDespesa = computed(() => {
  if (podeSelecionarCandidato.value) {
    if (despesaForm.candidato_id) return String(despesaForm.candidato_id)
    if (filtros.candidatoId) return String(filtros.candidatoId)
  }

  // Em campanha unica, nao filtra localmente por candidato.
  return ''
})

onMounted(async () => {
  if (!podeSelecionarCandidato.value) {
    fornecedorForm.candidato_id = authStore.usuario?.id || ''
    receitaForm.candidato_id = authStore.usuario?.id || ''
    despesaForm.candidato_id = authStore.usuario?.id || ''
  }

  await carregarPainel()
})

async function carregarPainel() {
  await Promise.all([
    store.carregarFornecedores(paginaFornecedores.value, limiteTabela, ''),
    store.carregarReceitas(paginaReceitas.value, limiteTabela, ''),
    store.carregarDespesas(paginaDespesas.value, limiteTabela, ''),
    store.carregarSaldos(filtros.candidatoId || null),
    store.carregarAlertas(filtros.candidatoId || null),
    store.carregarRelatorio({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
    store.carregarAuditoria({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
    carregarOpcoesDespesa(),
  ])
}

async function carregarOpcoesDespesa() {
  try {
    const [fornecedoresResp, receitasResp] = await Promise.all([
      financeiroServico.listarFornecedores(1, 200, ''),
      financeiroServico.listarReceitas(1, 200, ''),
    ])

    opcoesFornecedoresDespesa.value = fornecedoresResp.dados || []
    opcoesReceitasDespesa.value = receitasResp.dados || []
  } catch {
    opcoesFornecedoresDespesa.value = []
    opcoesReceitasDespesa.value = []
  }
}

async function aplicarFiltros() {
  paginaAlertas.value = 1
  paginaNaoConformes.value = 1
  paginaAuditoria.value = 1

  if (podeSelecionarCandidato.value && filtros.candidatoId && !despesaEditId.value) {
    despesaForm.candidato_id = filtros.candidatoId
  }

  await carregarPainel()
}

async function carregarFornecedoresPagina(pagina) {
  const total = store.paginacaoFornecedores?.total_paginas || 1
  paginaFornecedores.value = Math.min(Math.max(1, pagina), total)
  await store.carregarFornecedores(paginaFornecedores.value, limiteTabela, '')
}

async function carregarReceitasPagina(pagina) {
  const total = store.paginacaoReceitas?.total_paginas || 1
  paginaReceitas.value = Math.min(Math.max(1, pagina), total)
  await store.carregarReceitas(paginaReceitas.value, limiteTabela, '')
}

async function carregarDespesasPagina(pagina) {
  const total = store.paginacaoDespesas?.total_paginas || 1
  paginaDespesas.value = Math.min(Math.max(1, pagina), total)
  await store.carregarDespesas(paginaDespesas.value, limiteTabela, '')
}

async function enviarFornecedor() {
  if (fornecedorEditId.value) {
    await store.atualizarFornecedor(fornecedorEditId.value, {
      nome: fornecedorForm.nome,
      documento: fornecedorForm.documento,
      tipo_fornecedor: fornecedorForm.tipo_fornecedor,
    })
  } else {
    await store.cadastrarFornecedor({ ...fornecedorForm })
  }

  fecharModalFornecedor()

  await Promise.all([
    store.carregarFornecedores(paginaFornecedores.value, limiteTabela, ''),
    store.carregarDespesas(paginaDespesas.value, limiteTabela, ''),
    carregarOpcoesDespesa(),
  ])
}

function editarFornecedor(item) {
  fornecedorEditId.value = item.id
  fornecedorForm.candidato_id = item.candidato_id || fornecedorForm.candidato_id
  fornecedorForm.nome = item.nome || ''
  fornecedorForm.documento = item.documento || ''
  fornecedorForm.tipo_fornecedor = item.tipo_fornecedor || ''
  mostrarModalFornecedor.value = true
}

function abrirNovoFornecedor() {
  fornecedorEditId.value = null
  fornecedorForm.nome = ''
  fornecedorForm.documento = ''
  fornecedorForm.tipo_fornecedor = ''
  mostrarModalFornecedor.value = true
}

function fecharModalFornecedor() {
  fornecedorEditId.value = null
  fornecedorForm.nome = ''
  fornecedorForm.documento = ''
  fornecedorForm.tipo_fornecedor = ''
  mostrarModalFornecedor.value = false
}

async function removerFornecedor(id) {
  if (!confirm('Confirma a remocao deste fornecedor?')) return

  await store.removerFornecedor(id)
  await Promise.all([
    store.carregarFornecedores(paginaFornecedores.value, limiteTabela, ''),
    carregarOpcoesDespesa(),
  ])
}

async function enviarReceita() {
  if (receitaEditId.value) {
    await store.atualizarReceita(receitaEditId.value, {
      tipo_recurso: receitaForm.tipo_recurso,
      valor_total: receitaForm.valor_total,
      data_recebimento: receitaForm.data_recebimento,
      origem: receitaForm.origem,
    })
  } else {
    await store.cadastrarReceita({ ...receitaForm })
  }

  cancelarEdicaoReceita()

  await Promise.all([
    store.carregarReceitas(paginaReceitas.value, limiteTabela, ''),
    store.carregarSaldos(filtros.candidatoId || null),
    carregarOpcoesDespesa(),
  ])
}

function editarReceita(item) {
  receitaEditId.value = item.id
  receitaForm.candidato_id = item.candidato_id || receitaForm.candidato_id
  receitaForm.tipo_recurso = item.tipo_recurso || 'fundo_eleitoral'
  receitaForm.valor_total = Number(item.valor_total || 0)
  receitaForm.data_recebimento = item.data_recebimento || ''
  receitaForm.origem = item.origem || ''
}

function cancelarEdicaoReceita() {
  receitaEditId.value = null
  receitaForm.tipo_recurso = 'fundo_eleitoral'
  receitaForm.valor_total = null
  receitaForm.data_recebimento = ''
  receitaForm.origem = ''
}

async function removerReceita(id) {
  if (!confirm('Confirma a remocao desta receita?')) return

  await store.removerReceita(id)
  await Promise.all([
    store.carregarReceitas(paginaReceitas.value, limiteTabela, ''),
    store.carregarSaldos(filtros.candidatoId || null),
    carregarOpcoesDespesa(),
  ])
}

const fornecedoresFiltradosDespesa = computed(() => {
  return opcoesFornecedoresDespesa.value.filter((item) => itemPertenceAoCandidato(item, candidatoAtivoDespesa.value))
})

const receitasFiltradasDespesa = computed(() => {
  return opcoesReceitasDespesa.value.filter((item) => itemPertenceAoCandidato(item, candidatoAtivoDespesa.value))
})

const fornecedoresOrdenadosDespesa = computed(() => {
  return [...fornecedoresFiltradosDespesa.value].sort((a, b) => {
    const nomeA = String(a.nome || '').toLowerCase()
    const nomeB = String(b.nome || '').toLowerCase()
    return nomeA.localeCompare(nomeB)
  })
})

const receitasOrdenadasDespesa = computed(() => {
  return [...receitasFiltradasDespesa.value].sort((a, b) => {
    const dataA = String(a.data_recebimento || '')
    const dataB = String(b.data_recebimento || '')
    return dataB.localeCompare(dataA)
  })
})

watch([candidatoAtivoDespesa, fornecedoresOrdenadosDespesa, receitasOrdenadasDespesa], () => {
  const fornecedoresPermitidos = new Set(fornecedoresOrdenadosDespesa.value.map((item) => String(item.id || '')))
  const receitasPermitidas = new Set(receitasOrdenadasDespesa.value.map((item) => String(item.id || '')))

  if (despesaForm.fornecedor_id && !fornecedoresPermitidos.has(String(despesaForm.fornecedor_id))) {
    despesaForm.fornecedor_id = ''
  }

  if (despesaForm.receita_id && !receitasPermitidas.has(String(despesaForm.receita_id))) {
    despesaForm.receita_id = ''
  }
})

watch(() => despesaForm.categoria, (novaCategoria, categoriaAnterior) => {
  if (novaCategoria === categoriaAnterior) return

  const subcategoriasValidas = new Set(subcategoriasAtivasDespesa.value.map((item) => item.valor))
  if (!subcategoriasValidas.has(despesaForm.subcategoria)) {
    despesaForm.subcategoria = ''
  }
})

async function enviarDespesa() {
  if (despesaEditId.value) {
    await store.atualizarDespesa(despesaEditId.value, {
      receita_id: despesaForm.receita_id,
      fornecedor_id: despesaForm.fornecedor_id,
      categoria: despesaForm.categoria,
      subcategoria: despesaForm.subcategoria,
      valor: despesaForm.valor,
      data: despesaForm.data,
      descricao: despesaForm.descricao,
    })
  } else {
    await store.cadastrarDespesa({ ...despesaForm })
  }

  cancelarEdicaoDespesa()

  await Promise.all([
    store.carregarDespesas(paginaDespesas.value, limiteTabela, ''),
    store.carregarSaldos(filtros.candidatoId || null),
    store.carregarAlertas(filtros.candidatoId || null),
    store.carregarRelatorio({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
    store.carregarAuditoria({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
  ])
}

function editarDespesa(item) {
  despesaEditId.value = item.id
  despesaForm.candidato_id = item.candidato_id || despesaForm.candidato_id
  despesaForm.receita_id = item.receita_id || ''
  despesaForm.fornecedor_id = item.fornecedor_id || ''
  despesaForm.categoria = item.categoria || ''
  despesaForm.subcategoria = item.subcategoria || ''
  despesaForm.valor = Number(item.valor || 0)
  despesaForm.data = item.data_despesa || ''
  despesaForm.descricao = item.descricao || ''
}

function cancelarEdicaoDespesa() {
  despesaEditId.value = null
  despesaForm.receita_id = ''
  despesaForm.fornecedor_id = ''
  despesaForm.categoria = ''
  despesaForm.subcategoria = ''
  despesaForm.valor = null
  despesaForm.data = ''
  despesaForm.descricao = ''
}

async function removerDespesa(id) {
  if (!confirm('Confirma a remocao desta despesa?')) return

  await store.removerDespesa(id)

  if (despesaEditId.value === id) {
    cancelarEdicaoDespesa()
  }

  await Promise.all([
    store.carregarDespesas(paginaDespesas.value, limiteTabela, ''),
    store.carregarSaldos(filtros.candidatoId || null),
    store.carregarAlertas(filtros.candidatoId || null),
    store.carregarRelatorio({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
    store.carregarAuditoria({
      candidatoId: filtros.candidatoId || null,
      dataInicio: filtros.dataInicio || null,
      dataFim: filtros.dataFim || null,
    }),
  ])
}

const alertasPaginados = computed(() => paginarArray(store.alertas || [], paginaAlertas.value, limiteCliente))
const totalPaginasAlertas = computed(() => totalPaginasArray(store.alertas || [], limiteCliente))

const despesasNaoConformes = computed(() => store.relatorio?.despesas_nao_conformes || [])
const despesasNaoConformesPaginadas = computed(() => paginarArray(despesasNaoConformes.value, paginaNaoConformes.value, limiteCliente))
const totalPaginasNaoConformes = computed(() => totalPaginasArray(despesasNaoConformes.value, limiteCliente))

const auditoriaPaginada = computed(() => paginarArray(store.auditoria || [], paginaAuditoria.value, limiteCliente))
const totalPaginasAuditoria = computed(() => totalPaginasArray(store.auditoria || [], limiteCliente))

const registrosRankingFiltrados = computed(() => {
  const lista = store.auditoria || []
  const inicio = obterInicioPeriodoRanking(filtroRapidoRanking.value)

  if (!inicio) return lista

  return lista.filter((item) => {
    if (!item?.data_despesa) return false
    const dataItem = new Date(`${item.data_despesa}T00:00:00`)
    return !Number.isNaN(dataItem.getTime()) && dataItem >= inicio
  })
})

const rankingCategorias = computed(() => {
  const porCategoria = new Map()
  let totalGeral = 0

  for (const item of registrosRankingFiltrados.value) {
    const categoria = String(item.categoria || '')
    const valor = Number(item.despesa_valor || 0)
    totalGeral += valor
    porCategoria.set(categoria, (porCategoria.get(categoria) || 0) + valor)
  }

  return [...porCategoria.entries()]
    .map(([categoria, total]) => ({
      categoria,
      total_categoria: total,
      percentual_uso: totalGeral > 0 ? (total / totalGeral) * 100 : 0,
    }))
    .sort((a, b) => b.total_categoria - a.total_categoria)
    .slice(0, 5)
})

const rankingSubcategorias = computed(() => {
  const porSubcategoria = new Map()
  let totalGeral = 0

  for (const item of registrosRankingFiltrados.value) {
    const categoria = String(item.categoria || '')
    const subcategoria = String(item.subcategoria || '')
    const chave = `${categoria}::${subcategoria}`
    const valor = Number(item.despesa_valor || 0)

    totalGeral += valor
    porSubcategoria.set(chave, {
      categoria,
      subcategoria,
      total_subcategoria: (porSubcategoria.get(chave)?.total_subcategoria || 0) + valor,
    })
  }

  return [...porSubcategoria.values()]
    .map((item) => ({
      ...item,
      percentual_uso: totalGeral > 0 ? (item.total_subcategoria / totalGeral) * 100 : 0,
    }))
    .sort((a, b) => b.total_subcategoria - a.total_subcategoria)
    .slice(0, 8)
})
const rankingSubcategoriasPorCategoria = computed(() => {
  const porCategoria = new Map()

  for (const item of (store.relatorio?.gastos_por_subcategoria || [])) {
    const categoria = String(item.categoria || '')
    const lista = porCategoria.get(categoria) || []
    lista.push(item)
    porCategoria.set(categoria, lista)
  }

  return [...porCategoria.entries()]
    .map(([categoria, itens]) => ({
      categoria,
      itens: [...itens]
        .sort((a, b) => Number(b.total_subcategoria || 0) - Number(a.total_subcategoria || 0))
        .slice(0, 3),
    }))
    .sort((a, b) => {
      const totalA = a.itens.reduce((acc, item) => acc + Number(item.total_subcategoria || 0), 0)
      const totalB = b.itens.reduce((acc, item) => acc + Number(item.total_subcategoria || 0), 0)
      return totalB - totalA
    })
    .slice(0, 6)
})

const subcategoriasEquipeCampanha = computed(() => {
  const itens = store.relatorio?.gastos_por_subcategoria || []
  const equipe = itens
    .filter((item) => String(item.categoria || '') === 'equipe_campanha')
    .map((item) => ({
      ...item,
      total_subcategoria: Number(item.total_subcategoria || 0),
    }))
    .sort((a, b) => b.total_subcategoria - a.total_subcategoria)

  const totalEquipe = equipe.reduce((acc, item) => acc + item.total_subcategoria, 0)

  return equipe.map((item) => ({
    ...item,
    percentual_na_equipe: totalEquipe > 0 ? (item.total_subcategoria / totalEquipe) * 100 : 0,
  }))
})

const indicadoresEquipeCampanha = computed(() => {
  const itens = subcategoriasEquipeCampanha.value
  const totalEquipe = itens.reduce((acc, item) => acc + Number(item.total_subcategoria || 0), 0)
  const totalSalarioLider = itens
    .filter((item) => String(item.subcategoria || '') === 'salario_lider')
    .reduce((acc, item) => acc + Number(item.total_subcategoria || 0), 0)
  const totalRestanteEquipe = Math.max(0, totalEquipe - totalSalarioLider)

  return {
    totalEquipe,
    totalSalarioLider,
    totalRestanteEquipe,
    percentualSalarioLider: totalEquipe > 0 ? (totalSalarioLider / totalEquipe) * 100 : 0,
    percentualRestanteEquipe: totalEquipe > 0 ? (totalRestanteEquipe / totalEquipe) * 100 : 0,
  }
})

const subcategoriasEquipeFiltradas = computed(() => {
  if (filtroEquipeCampanha.value === 'salario_lider') {
    return subcategoriasEquipeCampanha.value.filter((item) => String(item.subcategoria || '') === 'salario_lider')
  }

  if (filtroEquipeCampanha.value === 'demais') {
    return subcategoriasEquipeCampanha.value.filter((item) => String(item.subcategoria || '') !== 'salario_lider')
  }

  return subcategoriasEquipeCampanha.value
})

function paginarArray(lista, pagina, limite) {
  const inicio = (pagina - 1) * limite
  return lista.slice(inicio, inicio + limite)
}

function obterInicioPeriodoRanking(periodo) {
  const hoje = new Date()
  hoje.setHours(0, 0, 0, 0)

  if (periodo === '7d') {
    const data = new Date(hoje)
    data.setDate(data.getDate() - 7)
    return data
  }

  if (periodo === '30d') {
    const data = new Date(hoje)
    data.setDate(data.getDate() - 30)
    return data
  }

  return null
}

function totalPaginasArray(lista, limite) {
  return Math.max(1, Math.ceil(lista.length / limite))
}

function formatarMoeda(valor) {
  return Number(valor || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  })
}

function formatarData(valor) {
  if (!valor) return '-'
  return new Date(`${valor}T00:00:00`).toLocaleDateString('pt-BR')
}

function formatarPercentual(valor) {
  return `${Number(valor || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}%`
}

function formatarReceitaOpcao(item) {
  const tipo = formatarSlugParaCliente(item.tipo_recurso)
  const valor = formatarMoeda(item.valor_disponivel)
  const origem = item.origem ? ` - ${item.origem}` : ''
  return `${tipo} (${valor})${origem}`
}

function formatarSlugParaCliente(valor) {
  return String(valor || '-')
    .replaceAll('_', ' ')
    .split(' ')
    .filter(Boolean)
    .map((parte) => parte.charAt(0).toUpperCase() + parte.slice(1).toLowerCase())
    .join(' ')
}

function formatarDescricaoCliente(valor) {
  if (!valor) return '-'

  return String(valor).replace(/\b[a-z]+(?:_[a-z0-9]+)+\b/gi, (token) => token.replaceAll('_', ' '))
}

function formatarCategoria(valor) {
  return categoriasDespesaMap[String(valor || '')] || String(valor || '-')
}

function formatarSubcategoria(valor) {
  return subcategoriasDespesaMap[String(valor || '')] || String(valor || '-')
}

function formatarCategoriaCompleta(categoria, subcategoria) {
  const categoriaFormatada = formatarCategoria(categoria)
  if (!subcategoria) return categoriaFormatada
  return `${categoriaFormatada} > ${formatarSubcategoria(subcategoria)}`
}

function itemPertenceAoCandidato(item, candidatoId) {
  if (!candidatoId) return true
  return String(item?.candidato_id || '') === String(candidatoId)
}

function exportarAlertasCsv() {
  const linhas = store.alertas || []

  gerarCsv('financeiro_alertas', [
    { chave: 'alerta_nivel', titulo: 'nivel' },
    { chave: 'alerta_codigo', titulo: 'codigo' },
    { chave: 'alerta_titulo', titulo: 'titulo' },
    { chave: 'alerta_descricao', titulo: 'descricao' },
    { chave: 'indicador_percentual', titulo: 'indicador_percentual' },
    { chave: 'candidato_id', titulo: 'candidato_id' },
    { chave: 'receita_id', titulo: 'receita_id' },
    { chave: 'despesa_id', titulo: 'despesa_id' },
    { chave: 'gerado_em', titulo: 'gerado_em' },
  ], linhas)
}

function exportarAuditoriaCsv() {
  const linhas = store.auditoria || []

  gerarCsv('financeiro_auditoria', [
    { chave: 'despesa_id', titulo: 'despesa_id' },
    { chave: 'data_despesa', titulo: 'data_despesa' },
    { chave: 'categoria', titulo: 'categoria' },
    { chave: 'subcategoria', titulo: 'subcategoria' },
    { chave: 'despesa_valor', titulo: 'despesa_valor' },
    { chave: 'classificacao_conformidade', titulo: 'classificacao_conformidade' },
    { chave: 'conformidade_motivo', titulo: 'conformidade_motivo' },
    { chave: 'fornecedor_id', titulo: 'fornecedor_id' },
    { chave: 'fornecedor_nome', titulo: 'fornecedor_nome' },
    { chave: 'receita_id', titulo: 'receita_id' },
    { chave: 'tipo_recurso', titulo: 'tipo_recurso' },
    { chave: 'receita_origem', titulo: 'receita_origem' },
    { chave: 'receita_valor_total', titulo: 'receita_valor_total' },
    { chave: 'receita_valor_disponivel_atual', titulo: 'receita_valor_disponivel_atual' },
    { chave: 'receita_data_recebimento', titulo: 'receita_data_recebimento' },
  ], linhas)
}

function gerarCsv(prefixoArquivo, colunas, linhas) {
  if (!linhas.length) return

  const cabecalho = colunas.map((coluna) => coluna.titulo).join(';')
  const corpo = linhas.map((linha) => {
    return colunas
      .map((coluna) => escaparCsv(linha[coluna.chave]))
      .join(';')
  })

  const csv = [cabecalho, ...corpo].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  const data = new Date().toISOString().slice(0, 10)

  link.href = url
  link.download = `${prefixoArquivo}_${data}.csv`
  link.click()

  URL.revokeObjectURL(url)
}

function escaparCsv(valor) {
  if (valor === null || valor === undefined) return ''

  const texto = String(valor).replaceAll('"', '""')
  return `"${texto}"`
}
</script>

<style scoped>
.financeiro-page { display: flex; flex-direction: column; gap: 1rem; }
.hero-card { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; border-radius: 20px; padding: 1.4rem; background: linear-gradient(135deg, #0f172a, #164e63); color: #f8fafc; }
.hero-eyebrow { display: inline-block; padding: 0.3rem 0.7rem; border-radius: 999px; background: rgba(255, 255, 255, 0.14); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 800; }
.hero-titulo { margin-top: 0.6rem; font-size: 1.65rem; font-weight: 800; }
.hero-subtitulo { margin-top: 0.4rem; color: rgba(248, 250, 252, 0.86); }
.btn-principal, .btn-secundario { border: none; border-radius: 12px; padding: 0.7rem 1rem; font-weight: 700; cursor: pointer; }
.btn-principal { background: #ecfeff; color: #0f172a; }
.btn-secundario { background: #0f172a; color: #f8fafc; }
.filtros-card, .bloco-card, .form-card, .resumo-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05); }
.filtros-card { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.8rem; padding: 0.9rem; }
.filtro-item { display: flex; flex-direction: column; gap: 0.35rem; }
.filtro-item label { font-size: 0.74rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
.filtro-item input { border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.75rem; }
.filtro-acoes { display: flex; align-items: end; }
.resumo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.8rem; }
.resumo-card { padding: 0.95rem 1rem; }
.resumo-label { display: block; font-size: 0.74rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
.resumo-valor { display: block; margin-top: 0.2rem; font-size: 1.4rem; font-weight: 800; color: #0f172a; }
.resumo-card--total { background: linear-gradient(135deg, #f8fafc, #ecfeff); }
.resumo-card--uso { background: linear-gradient(135deg, #fff7ed, #ffedd5); }
.resumo-card--saldo { background: linear-gradient(135deg, #ecfdf5, #dcfce7); }
.abas-nav { display: flex; gap: 0.5rem; }
.aba-btn { border: 1px solid #cbd5e1; background: #fff; color: #0f172a; border-radius: 999px; padding: 0.45rem 0.9rem; font-weight: 700; cursor: pointer; }
.aba-btn--ativa { background: #0f172a; color: #fff; border-color: #0f172a; }
.aba-conteudo { display: flex; flex-direction: column; gap: 0.9rem; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.8rem; }
.form-card { padding: 0.9rem; }
.form-card h2 { font-size: 1rem; margin-bottom: 0.6rem; color: #0f172a; }
.form-card form { display: flex; flex-direction: column; gap: 0.55rem; }
.form-card input, .form-card select, .form-card textarea { border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.7rem; font-family: inherit; }
.ajuda-categoria { margin: 0; font-size: 0.74rem; color: #64748b; }
.form-card button { border: none; border-radius: 10px; padding: 0.6rem 0.7rem; font-weight: 700; background: #0f172a; color: #fff; cursor: pointer; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 1000; }
.modal-card { width: 100%; max-width: 430px; border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.25); padding: 0.9rem; }
.modal-card h3 { margin-bottom: 0.7rem; color: #0f172a; }
.modal-card form { display: flex; flex-direction: column; gap: 0.55rem; }
.modal-card input { border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.7rem; }
.modal-acoes { display: flex; justify-content: flex-end; gap: 0.45rem; margin-top: 0.2rem; }
.btn-link { margin-top: 0.2rem; border: none; background: transparent; color: #334155; text-decoration: underline; cursor: pointer; font-size: 0.82rem; }
.tabelas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }
.tabela-stack { grid-template-columns: 1fr; }
.bloco-card { padding: 0.9rem; }
.bloco-card h3 { font-size: 1rem; color: #0f172a; margin-bottom: 0.6rem; }
.bloco-header-inline { display: flex; align-items: center; justify-content: space-between; gap: 0.7rem; margin-bottom: 0.6rem; }
.bloco-header-inline h3 { margin-bottom: 0; }
.periodo-rapido-ranking { display: flex; align-items: center; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.6rem; }
.periodo-rapido-ranking span { font-size: 0.78rem; color: #64748b; font-weight: 700; }
.ranking-lista { display: flex; flex-direction: column; gap: 0.65rem; }
.ranking-item { display: flex; flex-direction: column; gap: 0.35rem; }
.ranking-topo { display: flex; align-items: center; justify-content: space-between; gap: 0.7rem; font-size: 0.82rem; }
.ranking-topo span { color: #334155; }
.ranking-barra { width: 100%; height: 10px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.ranking-barra-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #0ea5e9, #0284c7); }
.ranking-barra-fill--sub { background: linear-gradient(90deg, #22c55e, #15803d); }
.resumo-equipe-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 0.7rem; }
.resumo-equipe-item { display: flex; flex-direction: column; gap: 0.2rem; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.65rem; }
.resumo-equipe-item span { font-size: 0.74rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.04em; }
.resumo-equipe-item strong { font-size: 1rem; color: #0f172a; }
.resumo-equipe-item small { font-size: 0.75rem; color: #475569; }
.resumo-equipe-item--total { background: #f8fafc; }
.resumo-equipe-item--salario { background: #f0fdfa; }
.resumo-equipe-item--restante { background: #fff7ed; }
.grupos-subcategoria { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.8rem; }
.grupo-subcategoria { border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.7rem; background: #f8fafc; }
.grupo-subcategoria h4 { margin: 0 0 0.45rem 0; font-size: 0.84rem; color: #0f172a; }
.grupo-subcategoria ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem; }
.grupo-subcategoria li { display: flex; align-items: center; justify-content: space-between; gap: 0.7rem; font-size: 0.8rem; color: #334155; }
.tabela-wrapper { overflow-x: auto; }
.tabela { width: 100%; border-collapse: collapse; }
.tabela th, .tabela td { padding: 0.55rem 0.5rem; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 0.84rem; }
.tabela th { font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
.td-destaque { font-weight: 800; color: #0f766e; }
.badge { display: inline-flex; border-radius: 999px; padding: 0.2rem 0.55rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
.badge--alto, .badge--invalida { background: #fee2e2; color: #b91c1c; }
.badge--medio, .badge--suspeita { background: #ffedd5; color: #c2410c; }
.badge--valida { background: #dcfce7; color: #15803d; }
.acoes-cell { display: flex; gap: 0.4rem; }
.btn-mini { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 0.25rem 0.5rem; font-size: 0.78rem; cursor: pointer; }
.btn-mini--novo-fornecedor { background: #16a34a; color: #fff; border-color: #15803d; font-weight: 800; }
.btn-mini--novo-fornecedor:hover { background: #15803d; }
.btn-mini--ativo { background: #0f172a; color: #fff; border-color: #0f172a; }
.btn-mini:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-mini--danger { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }
.paginacao { display: flex; align-items: center; justify-content: flex-end; gap: 0.55rem; margin-top: 0.65rem; }
.paginacao button { border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; padding: 0.3rem 0.6rem; cursor: pointer; }
.paginacao button:disabled { opacity: 0.45; cursor: not-allowed; }
.paginacao span { font-size: 0.82rem; color: #475569; }
.vazio { color: #64748b; font-size: 0.9rem; }

@media (max-width: 1100px) {
  .filtros-card { grid-template-columns: 1fr 1fr; }
  .form-grid, .tabelas-grid { grid-template-columns: 1fr; }
  .resumo-grid { grid-template-columns: 1fr; }
  .resumo-equipe-grid { grid-template-columns: 1fr; }
  .grupos-subcategoria { grid-template-columns: 1fr; }
}
</style>
