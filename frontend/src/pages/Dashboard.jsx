import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  TrendingUp,
  Users,
  MessageSquare,
  Activity,
  ChevronRight,
  AlertCircle,
} from 'lucide-react';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { reportsAPI } from '../api';
import Loading from '../components/Loading';

const Dashboard = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [metrics, setMetrics] = useState(null);
  const [responses, setResponses] = useState([]);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    setError('');

    try {
      const [metricsResponse, responsesResponse] = await Promise.all([
        reportsAPI.getNPSMetrics(),
        reportsAPI.getResponses({ limit: 5 }),
      ]);

      setMetrics(metricsResponse.data);
      setResponses(responsesResponse.data.data || []);
    } catch (err) {
      setError('Erro ao carregar dados do dashboard');
      console.error('Dashboard error:', err);
    } finally {
      setLoading(false);
    }
  };

  const getNPSColor = (score) => {
    if (score >= 75) return 'text-green-600 bg-green-50';
    if (score >= 50) return 'text-yellow-600 bg-yellow-50';
    if (score >= 0) return 'text-orange-600 bg-orange-50';
    return 'text-red-600 bg-red-50';
  };

  const getNPSLabel = (score) => {
    if (score >= 75) return 'Excelente';
    if (score >= 50) return 'Bom';
    if (score >= 0) return 'Razoável';
    return 'Ruim';
  };

  if (loading) {
    return <Loading message="Carregando dashboard..." />;
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-center">
          <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <p className="text-gray-600">{error}</p>
          <button
            onClick={fetchDashboardData}
            className="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          >
            Tentar Novamente
          </button>
        </div>
      </div>
    );
  }

  const npsScore = metrics?.nps_score || 0;
  const promoters = metrics?.promoters_percentage || 0;
  const passives = metrics?.passives_percentage || 0;
  const detractors = metrics?.detractors_percentage || 0;
  const totalResponses = metrics?.total_responses || 0;

  // Data for pie chart
  const pieData = [
    { name: 'Promotores', value: promoters, color: '#22c55e' },
    { name: 'Neutros', value: passives, color: '#eab308' },
    { name: 'Detratores', value: detractors, color: '#ef4444' },
  ];

  // Mock data for trend chart (in real app, this would come from API)
  const trendData = [
    { month: 'Jan', nps: 45 },
    { month: 'Fev', nps: 52 },
    { month: 'Mar', nps: 48 },
    { month: 'Abr', nps: 61 },
    { month: 'Mai', nps: 55 },
    { month: 'Jun', nps: npsScore },
  ];

  // Data for distribution chart
  const distributionData = [
    { score: '0-6', count: Math.round((detractors / 100) * totalResponses) },
    { score: '7-8', count: Math.round((passives / 100) * totalResponses) },
    { score: '9-10', count: Math.round((promoters / 100) * totalResponses) },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-600 mt-1">
            Visão geral das métricas de NPS
          </p>
        </div>
        <Link
          to="/campaigns"
          className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center"
        >
          Nova Campanha
          <ChevronRight className="w-4 h-4 ml-1" />
        </Link>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* NPS Score Card */}
        <div className={`bg-white rounded-xl shadow-sm border border-gray-100 p-6 ${getNPSColor(npsScore)}`}>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium opacity-80">NPS Score</p>
              <p className="text-3xl font-bold mt-2">{npsScore}</p>
              <p className="text-xs mt-1 opacity-70">{getNPSLabel(npsScore)}</p>
            </div>
            <div className="p-3 rounded-lg bg-white bg-opacity-50">
              <TrendingUp className="w-8 h-8" />
            </div>
          </div>
        </div>

        {/* Promoters Card */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Promotores</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {promoters}%
              </p>
              <p className="text-xs text-gray-500 mt-1">Score 9-10</p>
            </div>
            <div className="p-3 rounded-lg bg-green-50">
              <Users className="w-8 h-8 text-green-600" />
            </div>
          </div>
        </div>

        {/* Passives Card */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Neutros</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {passives}%
              </p>
              <p className="text-xs text-gray-500 mt-1">Score 7-8</p>
            </div>
            <div className="p-3 rounded-lg bg-yellow-50">
              <Activity className="w-8 h-8 text-yellow-600" />
            </div>
          </div>
        </div>

        {/* Detractors Card */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Detratores</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">
                {detractors}%
              </p>
              <p className="text-xs text-gray-500 mt-1">Score 0-6</p>
            </div>
            <div className="p-3 rounded-lg bg-red-50">
              <Users className="w-8 h-8 text-red-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* NPS Trend Chart */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Tendência NPS
          </h3>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={trendData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis domain={[-100, 100]} />
              <Tooltip />
              <Legend />
              <Line
                type="monotone"
                dataKey="nps"
                stroke="#7c3aed"
                strokeWidth={2}
                dot={{ fill: '#7c3aed', r: 4 }}
                activeDot={{ r: 6 }}
              />
            </LineChart>
          </ResponsiveContainer>
        </div>

        {/* Distribution Pie Chart */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Distribuição de Respostas
          </h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={pieData}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, value }) => `${name}: ${value}%`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {pieData.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Score Distribution Bar Chart */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          Distribuição por Faixa de Score
        </h3>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={distributionData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="score" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="count" fill="#7c3aed" name="Respostas" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Recent Responses */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 border-b border-gray-100">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-gray-900">
              Respostas Recentes
            </h3>
            <Link
              to="/reports"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center"
            >
              Ver todas
              <ChevronRight className="w-4 h-4 ml-1" />
            </Link>
          </div>
        </div>

        {responses.length === 0 ? (
          <div className="p-12 text-center">
            <MessageSquare className="w-12 h-12 text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500">Nenhuma resposta ainda</p>
            <p className="text-sm text-gray-400 mt-1">
              Crie uma campanha para começar a coletar feedback
            </p>
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {responses.map((response) => (
              <div
                key={response.id}
                className="p-6 hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <span
                        className={`inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-semibold ${
                          response.score >= 9
                            ? 'bg-green-100 text-green-700'
                            : response.score >= 7
                            ? 'bg-yellow-100 text-yellow-700'
                            : 'bg-red-100 text-red-700'
                        }`}
                      >
                        {response.score}
                      </span>
                      <span className="text-sm text-gray-500">
                        {response.recipient?.email || 'Anônimo'}
                      </span>
                    </div>
                    {response.comment && (
                      <p className="mt-2 text-sm text-gray-700 line-clamp-2">
                        {response.comment}
                      </p>
                    )}
                    <div className="mt-2 flex items-center text-xs text-gray-500">
                      <span>{response.campaign?.title || 'Campanha'}</span>
                      <span className="mx-2">•</span>
                      <span>
                        {new Date(response.created_at).toLocaleDateString(
                          'pt-BR'
                        )}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;
