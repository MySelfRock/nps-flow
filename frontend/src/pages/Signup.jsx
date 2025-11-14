import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { UserPlus, Mail, Lock, User, Building, FileText, AlertCircle } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const Signup = () => {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const { signup } = useAuth();
  const navigate = useNavigate();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm();

  const password = watch('password');

  const onSubmit = async (data) => {
    setIsLoading(true);
    setError('');

    try {
      const result = await signup(data);

      if (result.success) {
        navigate('/');
      } else {
        setError(result.error || 'Falha ao criar conta');
      }
    } catch (err) {
      setError('Erro ao criar conta. Tente novamente.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        {/* Logo */}
        <div className="text-center">
          <div className="flex justify-center">
            <div className="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
              <span className="text-white font-bold text-2xl">N</span>
            </div>
          </div>
          <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
            NPSFlow
          </h2>
          <p className="mt-2 text-sm text-gray-600">
            Crie sua conta e comece a medir NPS
          </p>
        </div>

        {/* Signup Form */}
        <div className="bg-white py-8 px-4 shadow-xl rounded-xl sm:px-10">
          <form className="space-y-5" onSubmit={handleSubmit(onSubmit)}>
            {/* Error Alert */}
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                <AlertCircle className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
                <span className="text-sm">{error}</span>
              </div>
            )}

            {/* Tenant Name */}
            <div>
              <label
                htmlFor="tenant_name"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Nome da Empresa
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Building className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="tenant_name"
                  type="text"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.tenant_name ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="Sua Empresa Ltda"
                  {...register('tenant_name', {
                    required: 'Nome da empresa é obrigatório',
                    minLength: {
                      value: 3,
                      message: 'Nome deve ter no mínimo 3 caracteres',
                    },
                  })}
                />
              </div>
              {errors.tenant_name && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.tenant_name.message}
                </p>
              )}
            </div>

            {/* CNPJ */}
            <div>
              <label
                htmlFor="cnpj"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                CNPJ
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FileText className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="cnpj"
                  type="text"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.cnpj ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="00.000.000/0000-00"
                  {...register('cnpj', {
                    required: 'CNPJ é obrigatório',
                    pattern: {
                      value: /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/,
                      message: 'CNPJ inválido (formato: 00.000.000/0000-00)',
                    },
                  })}
                />
              </div>
              {errors.cnpj && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.cnpj.message}
                </p>
              )}
              <p className="mt-1 text-xs text-gray-500">
                Formato: 00.000.000/0000-00
              </p>
            </div>

            {/* User Name */}
            <div>
              <label
                htmlFor="name"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Seu Nome
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <User className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="name"
                  type="text"
                  autoComplete="name"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.name ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="João Silva"
                  {...register('name', {
                    required: 'Nome é obrigatório',
                    minLength: {
                      value: 3,
                      message: 'Nome deve ter no mínimo 3 caracteres',
                    },
                  })}
                />
              </div>
              {errors.name && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.name.message}
                </p>
              )}
            </div>

            {/* Email */}
            <div>
              <label
                htmlFor="email"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Email
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Mail className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="email"
                  type="email"
                  autoComplete="email"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.email ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="seu@email.com"
                  {...register('email', {
                    required: 'Email é obrigatório',
                    pattern: {
                      value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                      message: 'Email inválido',
                    },
                  })}
                />
              </div>
              {errors.email && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.email.message}
                </p>
              )}
            </div>

            {/* Password */}
            <div>
              <label
                htmlFor="password"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Senha
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="password"
                  type="password"
                  autoComplete="new-password"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.password ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="••••••••"
                  {...register('password', {
                    required: 'Senha é obrigatória',
                    minLength: {
                      value: 6,
                      message: 'Senha deve ter no mínimo 6 caracteres',
                    },
                  })}
                />
              </div>
              {errors.password && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.password.message}
                </p>
              )}
            </div>

            {/* Password Confirmation */}
            <div>
              <label
                htmlFor="password_confirmation"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Confirmar Senha
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="password_confirmation"
                  type="password"
                  autoComplete="new-password"
                  className={`block w-full pl-10 pr-3 py-2 border ${
                    errors.password_confirmation ? 'border-red-300' : 'border-gray-300'
                  } rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent`}
                  placeholder="••••••••"
                  {...register('password_confirmation', {
                    required: 'Confirmação de senha é obrigatória',
                    validate: (value) =>
                      value === password || 'As senhas não coincidem',
                  })}
                />
              </div>
              {errors.password_confirmation && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.password_confirmation.message}
                </p>
              )}
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={isLoading}
              className="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {isLoading ? (
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
                  Criando conta...
                </>
              ) : (
                <>
                  <UserPlus className="w-5 h-5 mr-2" />
                  Criar Conta
                </>
              )}
            </button>
          </form>

          {/* Login link */}
          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">
                  Já tem uma conta?
                </span>
              </div>
            </div>

            <div className="mt-6">
              <Link
                to="/login"
                className="w-full flex justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
              >
                Fazer Login
              </Link>
            </div>
          </div>
        </div>

        {/* Footer */}
        <p className="text-center text-xs text-gray-500">
          &copy; 2025 NPSFlow. Todos os direitos reservados.
        </p>
      </div>
    </div>
  );
};

export default Signup;
