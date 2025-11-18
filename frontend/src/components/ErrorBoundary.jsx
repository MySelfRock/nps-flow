import React from 'react';
import { AlertTriangle } from 'lucide-react';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    // Log the error to an error reporting service
    console.error('Error caught by ErrorBoundary:', error, errorInfo);

    this.setState({
      error,
      errorInfo,
    });

    // You could also log to an external service here
    // Example: logErrorToService(error, errorInfo);
  }

  handleReset = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
    });
  };

  render() {
    if (this.state.hasError) {
      // Fallback UI
      return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
          <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div className="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full">
              <AlertTriangle className="w-8 h-8 text-red-600" />
            </div>

            <h1 className="mt-6 text-2xl font-bold text-gray-900 text-center">
              Algo deu errado
            </h1>

            <p className="mt-4 text-gray-600 text-center">
              Desculpe, ocorreu um erro inesperado. Por favor, tente recarregar a página.
            </p>

            {process.env.NODE_ENV === 'development' && this.state.error && (
              <div className="mt-6 p-4 bg-gray-100 rounded-lg">
                <p className="text-sm font-semibold text-gray-700 mb-2">
                  Detalhes do erro (visível apenas em desenvolvimento):
                </p>
                <pre className="text-xs text-red-600 overflow-auto max-h-40">
                  {this.state.error.toString()}
                  {this.state.errorInfo?.componentStack}
                </pre>
              </div>
            )}

            <div className="mt-6 flex gap-3">
              <button
                onClick={this.handleReset}
                className="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Tentar novamente
              </button>
              <button
                onClick={() => window.location.href = '/'}
                className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
              >
                Voltar ao início
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
