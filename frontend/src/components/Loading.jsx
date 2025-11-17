import { Loader2 } from 'lucide-react';

const Loading = ({ message = 'Carregando...' }) => {
  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <Loader2 className="w-12 h-12 text-primary-600 animate-spin mx-auto mb-4" />
        <p className="text-gray-600">{message}</p>
      </div>
    </div>
  );
};

export default Loading;
