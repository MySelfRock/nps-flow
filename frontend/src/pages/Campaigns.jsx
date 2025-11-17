import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import {
  Plus,
  Edit,
  Trash2,
  Play,
  Pause,
  X,
  Calendar,
  Users,
  AlertCircle,
  CheckCircle,
  Clock,
} from 'lucide-react';
import { campaignsAPI } from '../api';
import Loading from '../components/Loading';

const Campaigns = () => {
  const [loading, setLoading] = useState(true);
  const [campaigns, setCampaigns] = useState([]);
  const [error, setError] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCampaign, setEditingCampaign] = useState(null);
  const [actionLoading, setActionLoading] = useState(null);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm();

  useEffect(() => {
    fetchCampaigns();
  }, []);

  const fetchCampaigns = async () => {
    setLoading(true);
    setError('');

    try {
      const response = await campaignsAPI.getAll();
      setCampaigns(response.data.data || []);
    } catch (err) {
      setError('Erro ao carregar campanhas');
      console.error('Campaigns error:', err);
    } finally {
      setLoading(false);
    }
  };

  const openModal = (campaign = null) => {
    setEditingCampaign(campaign);
    if (campaign) {
      reset({
        title: campaign.title,
        description: campaign.description,
        starts_at: campaign.starts_at?.split('T')[0],
        ends_at: campaign.ends_at?.split('T')[0],
      });
    } else {
      reset({
        title: '',
        description: '',
        starts_at: '',
        ends_at: '',
      });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingCampaign(null);
    reset();
  };

  const onSubmit = async (data) => {
    try {
      if (editingCampaign) {
        await campaignsAPI.update(editingCampaign.id, data);
      } else {
        await campaignsAPI.create(data);
      }
      await fetchCampaigns();
      closeModal();
    } catch (err) {
      setError(
        err.response?.data?.message ||
          `Erro ao ${editingCampaign ? 'atualizar' : 'criar'} campanha`
      );
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Tem certeza que deseja excluir esta campanha?')) {
      return;
    }

    setActionLoading(id);
    try {
      await campaignsAPI.delete(id);
      await fetchCampaigns();
    } catch (err) {
      setError('Erro ao excluir campanha');
    } finally {
      setActionLoading(null);
    }
  };

  const handleStart = async (id) => {
    if (!window.confirm('Tem certeza que deseja iniciar esta campanha?')) {
      return;
    }

    setActionLoading(id);
    try {
      await campaignsAPI.start(id);
      await fetchCampaigns();
    } catch (err) {
      setError('Erro ao iniciar campanha');
    } finally {
      setActionLoading(null);
    }
  };

  const handleStop = async (id) => {
    if (!window.confirm('Tem certeza que deseja parar esta campanha?')) {
      return;
    }

    setActionLoading(id);
    try {
      await campaignsAPI.stop(id);
      await fetchCampaigns();
    } catch (err) {
      setError('Erro ao parar campanha');
    } finally {
      setActionLoading(null);
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      draft: {
        label: 'Rascunho',
        className: 'bg-gray-100 text-gray-700',
        icon: Clock,
      },
      scheduled: {
        label: 'Agendada',
        className: 'bg-blue-100 text-blue-700',
        icon: Calendar,
      },
      active: {
        label: 'Ativa',
        className: 'bg-green-100 text-green-700',
        icon: CheckCircle,
      },
      completed: {
        label: 'Concluída',
        className: 'bg-purple-100 text-purple-700',
        icon: CheckCircle,
      },
      paused: {
        label: 'Pausada',
        className: 'bg-yellow-100 text-yellow-700',
        icon: Pause,
      },
    };

    const badge = badges[status] || badges.draft;
    const Icon = badge.icon;

    return (
      <span
        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.className}`}
      >
        <Icon className="w-3 h-3 mr-1" />
        {badge.label}
      </span>
    );
  };

  if (loading) {
    return <Loading message="Carregando campanhas..." />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Campanhas</h1>
          <p className="text-gray-600 mt-1">
            Gerencie suas campanhas de NPS
          </p>
        </div>
        <button
          onClick={() => openModal()}
          className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Nova Campanha
        </button>
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

      {/* Campaigns List */}
      {campaigns.length === 0 ? (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
          <Users className="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <p className="text-gray-500">Nenhuma campanha criada</p>
          <p className="text-sm text-gray-400 mt-1">
            Crie sua primeira campanha para começar a coletar feedback
          </p>
          <button
            onClick={() => openModal()}
            className="mt-4 px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            Criar Primeira Campanha
          </button>
        </div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          {/* Desktop Table */}
          <div className="hidden md:block overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Campanha
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Período
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Destinatários
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ações
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-100">
                {campaigns.map((campaign) => (
                  <tr key={campaign.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">
                          {campaign.title}
                        </div>
                        {campaign.description && (
                          <div className="text-sm text-gray-500 truncate max-w-xs">
                            {campaign.description}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(campaign.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center">
                        <Calendar className="w-4 h-4 mr-1" />
                        {campaign.starts_at
                          ? new Date(campaign.starts_at).toLocaleDateString(
                              'pt-BR'
                            )
                          : '-'}
                        {' até '}
                        {campaign.ends_at
                          ? new Date(campaign.ends_at).toLocaleDateString(
                              'pt-BR'
                            )
                          : '-'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center">
                        <Users className="w-4 h-4 mr-1" />
                        {campaign.recipients_count || 0}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end space-x-2">
                        {campaign.status === 'draft' && (
                          <button
                            onClick={() => handleStart(campaign.id)}
                            disabled={actionLoading === campaign.id}
                            className="text-green-600 hover:text-green-900 disabled:opacity-50"
                            title="Iniciar"
                          >
                            <Play className="w-5 h-5" />
                          </button>
                        )}
                        {campaign.status === 'active' && (
                          <button
                            onClick={() => handleStop(campaign.id)}
                            disabled={actionLoading === campaign.id}
                            className="text-yellow-600 hover:text-yellow-900 disabled:opacity-50"
                            title="Parar"
                          >
                            <Pause className="w-5 h-5" />
                          </button>
                        )}
                        <button
                          onClick={() => openModal(campaign)}
                          className="text-primary-600 hover:text-primary-900"
                          title="Editar"
                        >
                          <Edit className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => handleDelete(campaign.id)}
                          disabled={actionLoading === campaign.id}
                          className="text-red-600 hover:text-red-900 disabled:opacity-50"
                          title="Excluir"
                        >
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Mobile Cards */}
          <div className="md:hidden divide-y divide-gray-100">
            {campaigns.map((campaign) => (
              <div key={campaign.id} className="p-4">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex-1">
                    <h3 className="text-sm font-medium text-gray-900">
                      {campaign.title}
                    </h3>
                    {campaign.description && (
                      <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                        {campaign.description}
                      </p>
                    )}
                  </div>
                  {getStatusBadge(campaign.status)}
                </div>
                <div className="space-y-2 text-sm text-gray-500">
                  <div className="flex items-center">
                    <Calendar className="w-4 h-4 mr-2" />
                    {campaign.starts_at
                      ? new Date(campaign.starts_at).toLocaleDateString('pt-BR')
                      : '-'}
                    {' até '}
                    {campaign.ends_at
                      ? new Date(campaign.ends_at).toLocaleDateString('pt-BR')
                      : '-'}
                  </div>
                  <div className="flex items-center">
                    <Users className="w-4 h-4 mr-2" />
                    {campaign.recipients_count || 0} destinatários
                  </div>
                </div>
                <div className="mt-3 flex items-center space-x-2">
                  {campaign.status === 'draft' && (
                    <button
                      onClick={() => handleStart(campaign.id)}
                      disabled={actionLoading === campaign.id}
                      className="flex-1 px-3 py-2 text-sm text-green-600 border border-green-600 rounded-lg hover:bg-green-50"
                    >
                      <Play className="w-4 h-4 inline mr-1" />
                      Iniciar
                    </button>
                  )}
                  {campaign.status === 'active' && (
                    <button
                      onClick={() => handleStop(campaign.id)}
                      disabled={actionLoading === campaign.id}
                      className="flex-1 px-3 py-2 text-sm text-yellow-600 border border-yellow-600 rounded-lg hover:bg-yellow-50"
                    >
                      <Pause className="w-4 h-4 inline mr-1" />
                      Parar
                    </button>
                  )}
                  <button
                    onClick={() => openModal(campaign)}
                    className="flex-1 px-3 py-2 text-sm text-primary-600 border border-primary-600 rounded-lg hover:bg-primary-50"
                  >
                    <Edit className="w-4 h-4 inline mr-1" />
                    Editar
                  </button>
                  <button
                    onClick={() => handleDelete(campaign.id)}
                    disabled={actionLoading === campaign.id}
                    className="px-3 py-2 text-sm text-red-600 border border-red-600 rounded-lg hover:bg-red-50"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Create/Edit Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {/* Background overlay */}
            <div
              className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
              onClick={closeModal}
            ></div>

            {/* Modal panel */}
            <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <form onSubmit={handleSubmit(onSubmit)}>
                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-medium text-gray-900">
                      {editingCampaign ? 'Editar Campanha' : 'Nova Campanha'}
                    </h3>
                    <button
                      type="button"
                      onClick={closeModal}
                      className="text-gray-400 hover:text-gray-500"
                    >
                      <X className="w-6 h-6" />
                    </button>
                  </div>

                  <div className="space-y-4">
                    {/* Title */}
                    <div>
                      <label
                        htmlFor="title"
                        className="block text-sm font-medium text-gray-700 mb-1"
                      >
                        Título
                      </label>
                      <input
                        id="title"
                        type="text"
                        className={`block w-full px-3 py-2 border ${
                          errors.title ? 'border-red-300' : 'border-gray-300'
                        } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500`}
                        {...register('title', {
                          required: 'Título é obrigatório',
                        })}
                      />
                      {errors.title && (
                        <p className="mt-1 text-sm text-red-600">
                          {errors.title.message}
                        </p>
                      )}
                    </div>

                    {/* Description */}
                    <div>
                      <label
                        htmlFor="description"
                        className="block text-sm font-medium text-gray-700 mb-1"
                      >
                        Descrição
                      </label>
                      <textarea
                        id="description"
                        rows={3}
                        className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                        {...register('description')}
                      />
                    </div>

                    {/* Dates */}
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label
                          htmlFor="starts_at"
                          className="block text-sm font-medium text-gray-700 mb-1"
                        >
                          Data de Início
                        </label>
                        <input
                          id="starts_at"
                          type="date"
                          className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                          {...register('starts_at')}
                        />
                      </div>
                      <div>
                        <label
                          htmlFor="ends_at"
                          className="block text-sm font-medium text-gray-700 mb-1"
                        >
                          Data de Término
                        </label>
                        <input
                          id="ends_at"
                          type="date"
                          className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                          {...register('ends_at')}
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button
                    type="submit"
                    className="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                  >
                    {editingCampaign ? 'Atualizar' : 'Criar'}
                  </button>
                  <button
                    type="button"
                    onClick={closeModal}
                    className="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm"
                  >
                    Cancelar
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Campaigns;
