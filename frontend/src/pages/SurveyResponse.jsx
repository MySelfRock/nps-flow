import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { CheckCircle, AlertCircle, MessageSquare, Send } from 'lucide-react';
import { responsesAPI } from '../api';

const SurveyResponse = () => {
  const { token } = useParams();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [survey, setSurvey] = useState(null);
  const [selectedScore, setSelectedScore] = useState(null);
  const [comment, setComment] = useState('');
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    fetchSurvey();
  }, [token]);

  const fetchSurvey = async () => {
    setLoading(true);
    setError('');

    try {
      const response = await responsesAPI.getByToken(token);
      setSurvey(response.data);

      // Check if already submitted
      if (response.data.status === 'completed') {
        setSubmitted(true);
      }
    } catch (err) {
      if (err.response?.status === 404) {
        setError('Pesquisa não encontrada ou token inválido');
      } else if (err.response?.status === 410) {
        setError('Esta pesquisa já foi respondida ou expirou');
      } else {
        setError('Erro ao carregar pesquisa');
      }
      console.error('Survey error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (selectedScore === null) {
      setError('Por favor, selecione uma pontuação');
      return;
    }

    setSubmitting(true);
    setError('');

    try {
      await responsesAPI.submit(token, {
        score: selectedScore,
        comment: comment.trim() || null,
      });

      setSubmitted(true);
    } catch (err) {
      setError(
        err.response?.data?.message || 'Erro ao enviar resposta. Tente novamente.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  const getScoreColor = (score) => {
    if (selectedScore === score) {
      if (score >= 9) return 'bg-green-600 text-white border-green-600';
      if (score >= 7) return 'bg-yellow-600 text-white border-yellow-600';
      return 'bg-red-600 text-white border-red-600';
    }
    return 'bg-white text-gray-700 border-gray-300 hover:border-primary-500';
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
          <p className="text-gray-600 mt-4">Carregando pesquisa...</p>
        </div>
      </div>
    );
  }

  if (error && !survey) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <AlertCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">
            Ops! Algo deu errado
          </h2>
          <p className="text-gray-600">{error}</p>
        </div>
      </div>
    );
  }

  if (submitted) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">
            Obrigado pela sua resposta!
          </h2>
          <p className="text-gray-600 mb-6">
            Seu feedback é muito importante para nós e nos ajuda a melhorar
            continuamente nossos serviços.
          </p>
          {survey?.campaign?.tenant && (
            <p className="text-sm text-gray-500">
              {survey.campaign.tenant.name}
            </p>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-50 flex items-center justify-center p-4">
      <div className="max-w-2xl w-full">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="flex justify-center mb-4">
            <div className="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
              <span className="text-white font-bold text-2xl">N</span>
            </div>
          </div>
          {survey?.campaign?.tenant && (
            <h1 className="text-3xl font-bold text-gray-900 mb-2">
              {survey.campaign.tenant.name}
            </h1>
          )}
          <p className="text-gray-600">Pesquisa de Satisfação</p>
        </div>

        {/* Survey Form */}
        <div className="bg-white rounded-2xl shadow-xl p-8">
          {survey?.campaign && (
            <div className="mb-8">
              <h2 className="text-2xl font-bold text-gray-900 mb-2">
                {survey.campaign.title}
              </h2>
              {survey.campaign.description && (
                <p className="text-gray-600">{survey.campaign.description}</p>
              )}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-8">
            {/* Error Alert */}
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                <AlertCircle className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
                <span className="text-sm">{error}</span>
              </div>
            )}

            {/* NPS Question */}
            <div>
              <label className="block text-lg font-semibold text-gray-900 mb-2">
                Em uma escala de 0 a 10, qual a probabilidade de você
                recomendar nossos serviços a um amigo ou colega?
              </label>
              <p className="text-sm text-gray-500 mb-4">
                0 = Nada provável | 10 = Extremamente provável
              </p>

              {/* Score Buttons */}
              <div className="grid grid-cols-11 gap-2">
                {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((score) => (
                  <button
                    key={score}
                    type="button"
                    onClick={() => {
                      setSelectedScore(score);
                      setError('');
                    }}
                    className={`aspect-square flex items-center justify-center text-lg font-semibold border-2 rounded-lg transition-all transform hover:scale-105 ${getScoreColor(
                      score
                    )}`}
                  >
                    {score}
                  </button>
                ))}
              </div>

              {/* Score Labels */}
              <div className="flex justify-between mt-2 text-xs text-gray-500">
                <span>Nada provável</span>
                <span>Extremamente provável</span>
              </div>
            </div>

            {/* Comment Field */}
            <div>
              <label
                htmlFor="comment"
                className="flex items-center text-lg font-semibold text-gray-900 mb-2"
              >
                <MessageSquare className="w-5 h-5 mr-2" />
                Comentários (opcional)
              </label>
              <p className="text-sm text-gray-500 mb-3">
                Compartilhe mais sobre sua experiência
              </p>
              <textarea
                id="comment"
                rows={4}
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                className="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                placeholder="Conte-nos mais sobre sua experiência..."
              />
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={submitting || selectedScore === null}
              className="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-[1.02]"
            >
              {submitting ? (
                <>
                  <svg
                    className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      className="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      strokeWidth="4"
                    ></circle>
                    <path
                      className="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  Enviando...
                </>
              ) : (
                <>
                  <Send className="w-5 h-5 mr-2" />
                  Enviar Resposta
                </>
              )}
            </button>
          </form>

          {/* Privacy Notice */}
          <div className="mt-6 pt-6 border-t border-gray-200">
            <p className="text-xs text-gray-500 text-center">
              Suas respostas são confidenciais e serão usadas apenas para
              melhorar nossos serviços.
            </p>
          </div>
        </div>

        {/* Footer */}
        <p className="text-center text-xs text-gray-500 mt-6">
          Powered by NPSFlow &copy; 2025
        </p>
      </div>
    </div>
  );
};

export default SurveyResponse;
