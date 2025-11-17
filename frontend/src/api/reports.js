import axios from './axios';

export const reportsAPI = {
  getNPSMetrics: async (params) => {
    const response = await axios.get('/reports/nps', { params });
    return response.data;
  },

  getResponses: async (params) => {
    const response = await axios.get('/reports/responses', { params });
    return response.data;
  },

  export: async (params) => {
    const response = await axios.get('/reports/export', {
      params,
      responseType: params.format === 'csv' ? 'blob' : 'json',
    });

    if (params.format === 'csv') {
      // Create download link for CSV
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `export_${Date.now()}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      return { success: true };
    }

    return response.data;
  },
};
