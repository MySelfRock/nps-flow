import { useState, useEffect } from 'react';
import {
  Download,
  Filter,
  Search,
  Calendar,
  FileText,
  AlertCircle,
  TrendingUp,
  MessageSquare,
} from 'lucide-react';
import { reportsAPI, campaignsAPI } from '../api';
import Loading from '../components/Loading';

const Reports = () => {
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState('');
  const [metrics, setMetrics] = useState(null);
  const [responses, setResponses] = useState([]);
  const [campaigns, setCampaigns] = useState([]);
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
  });

  // Filters
  const [filters, setFilters] = useState({
    campaign_id: '',
    score_min: '',
    score_max: '',
    date_from: '',
    date_to: '',
    search: '',
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    fetchResponses();
  }, [filters, pagination.current_page]);

  const fetchInitialData = async () => {
    setLoading(true);
    try {
      const [metricsResponse, campaignsResponse] = await Promise.all([
        reportsAPI.getNPSMetrics(),
        campaignsAPI.getAll(),
      ]);

      setMetrics(metricsResponse.data);
      setCampaigns(campaignsResponse.data.data || []);
    } catch (err) {
      setError('Erro ao carregar dados');
      console.error('Reports error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchResponses = async () => {
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        ...filters,
      };

      // Remove empty filters
      Object.keys(params).forEach((key) => {
        if (params[key] === '') delete params[key];
      });

      const response = await reportsAPI.getResponses(params);
      setResponses(response.data.data || []);
      setPagination({
        current_page: response.data.current_page,
        per_page: response.data.per_page,
        total: response.data.total,
      });
    } catch (err) {
      console.error('Fetch responses error:', err);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPagination((prev) => ({ ...prev, current_page: 1 }));
  };

  const handleExport = async (format) => {
    setExporting(true);
    setError('');

    try {
      const params = { ...filters, format };
      // Remove empty filters
      Object.keys(params).forEach((key) => {
        if (params[key] === '') delete params[key];
      });

      await reportsAPI.export(params);
    } catch (err) {
      setError('Erro ao exportar dados');
      console.error('Export error:', err);
    } finally {
      setExporting(false);
    }
  };

  const getScoreColor = (score) => {
    if (score >= 9) return 'bg-green-100 text-green-700';
    if (score >= 7) return 'bg-yellow-100 text-yellow-700';
    return 'bg-red-100 text-red-700';
  };

  const getScoreLabel = (score) => {
    if (score >= 9) return 'Promotor';
    if (score >= 7) return 'Neutro';
    return 'Detrator';
  };

  if (loading) {
    return <Loading message="Carregando relatórios..." />;
  }

  const npsScore = metrics?.nps_score || 0;
  const totalPages = Math.ceil(pagination.total / pagination.per_page);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Relatórios</h1>
          <p className="text-gray-600 mt-1">
            Análise detalhada das respostas de NPS
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <button
            onClick={() => handleExport('csv')}
            disabled={exporting}
            className="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center disabled:opacity-50"
          >
            <Download className="w-5 h-5 mr-2" />
            {exporting ? 'Exportando...' : 'Exportar CSV'}
          </button>
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
          <AlertCircle className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
          <div>
            <span className="text-sm">{error}</span>
            <button
              onClick={() => setError('')}
              className="ml-4 text-red-600 hover:text-red-800 underline text-sm"
            >
              Fechar
            </button>
          </div>
        </div>
      )}

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">NPS Score</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {npsScore}
              </p>
            </div>
            <div className="p-3 rounded-lg bg-primary-50">
              <TrendingUp className="w-8 h-8 text-primary-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Total Respostas</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {metrics?.total_responses || 0}
              </p>
            </div>
            <div className="p-3 rounded-lg bg-blue-50">
              <MessageSquare className="w-8 h-8 text-blue-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Promotores</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {metrics?.promoters_percentage || 0}%
              </p>
            </div>
            <div className="p-3 rounded-lg bg-green-50">
              <TrendingUp className="w-8 h-8 text-green-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Detratores</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {metrics?.detractors_percentage || 0}%
              </p>
            </div>
            <div className="p-3 rounded-lg bg-red-50">
              <TrendingUp className="w-8 h-8 text-red-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center mb-4">
          <Filter className="w-5 h-5 text-gray-500 mr-2" />
          <h3 className="text-lg font-semibold text-gray-900">Filtros</h3>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {/* Campaign Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Campanha
            </label>
            <select
              value={filters.campaign_id}
              onChange={(e) => handleFilterChange('campaign_id', e.target.value)}
              className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="">Todas as campanhas</option>
              {campaigns.map((campaign) => (
                <option key={campaign.id} value={campaign.id}>
                  {campaign.title}
                </option>
              ))}
            </select>
          </div>

          {/* Score Range */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Score Mínimo
            </label>
            <input
              type="number"
              min="0"
              max="10"
              value={filters.score_min}
              onChange={(e) => handleFilterChange('score_min', e.target.value)}
              className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="0"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Score Máximo
            </label>
            <input
              type="number"
              min="0"
              max="10"
              value={filters.score_max}
              onChange={(e) => handleFilterChange('score_max', e.target.value)}
              className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="10"
            />
          </div>

          {/* Date Range */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Data Inicial
            </label>
            <input
              type="date"
              value={filters.date_from}
              onChange={(e) => handleFilterChange('date_from', e.target.value)}
              className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Data Final
            </label>
            <input
              type="date"
              value={filters.date_to}
              onChange={(e) => handleFilterChange('date_to', e.target.value)}
              className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>

          {/* Search */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Buscar
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                value={filters.search}
                onChange={(e) => handleFilterChange('search', e.target.value)}
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                placeholder="Buscar por email ou comentário..."
              />
            </div>
          </div>

          {/* Reset Filters Button */}
          <div className="flex items-end">
            <button
              onClick={() => {
                setFilters({
                  campaign_id: '',
                  score_min: '',
                  score_max: '',
                  date_from: '',
                  date_to: '',
                  search: '',
                });
              }}
              className="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Limpar Filtros
            </button>
          </div>
        </div>
      </div>

      {/* Responses Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 border-b border-gray-100">
          <h3 className="text-lg font-semibold text-gray-900">
            Respostas ({pagination.total})
          </h3>
        </div>

        {responses.length === 0 ? (
          <div className="p-12 text-center">
            <FileText className="w-12 h-12 text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500">Nenhuma resposta encontrada</p>
            <p className="text-sm text-gray-400 mt-1">
              Ajuste os filtros ou aguarde novas respostas
            </p>
          </div>
        ) : (
          <>
            {/* Desktop Table */}
            <div className="hidden md:block overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b border-gray-100">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Score
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tipo
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Email
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Campanha
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Comentário
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Data
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-100">
                  {responses.map((response) => (
                    <tr key={response.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-semibold ${getScoreColor(
                            response.score
                          )}`}
                        >
                          {response.score}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {getScoreLabel(response.score)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {response.recipient?.email || 'Anônimo'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {response.campaign?.title || '-'}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500 max-w-xs">
                        {response.comment ? (
                          <span className="line-clamp-2">
                            {response.comment}
                          </span>
                        ) : (
                          <span className="text-gray-400">Sem comentário</span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(response.created_at).toLocaleDateString(
                          'pt-BR',
                          {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                          }
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Mobile Cards */}
            <div className="md:hidden divide-y divide-gray-100">
              {responses.map((response) => (
                <div key={response.id} className="p-4">
                  <div className="flex items-start justify-between mb-2">
                    <span
                      className={`inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-semibold ${getScoreColor(
                        response.score
                      )}`}
                    >
                      {response.score} - {getScoreLabel(response.score)}
                    </span>
                    <span className="text-xs text-gray-500">
                      {new Date(response.created_at).toLocaleDateString(
                        'pt-BR'
                      )}
                    </span>
                  </div>
                  <p className="text-sm text-gray-700 mb-1">
                    {response.recipient?.email || 'Anônimo'}
                  </p>
                  <p className="text-xs text-gray-500 mb-2">
                    {response.campaign?.title || '-'}
                  </p>
                  {response.comment && (
                    <p className="text-sm text-gray-600 line-clamp-3">
                      {response.comment}
                    </p>
                  )}
                </div>
              ))}
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div className="text-sm text-gray-500">
                  Página {pagination.current_page} de {totalPages}
                </div>
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() =>
                      setPagination((prev) => ({
                        ...prev,
                        current_page: prev.current_page - 1,
                      }))
                    }
                    disabled={pagination.current_page === 1}
                    className="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Anterior
                  </button>
                  <button
                    onClick={() =>
                      setPagination((prev) => ({
                        ...prev,
                        current_page: prev.current_page + 1,
                      }))
                    }
                    disabled={pagination.current_page === totalPages}
                    className="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Próxima
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default Reports;
