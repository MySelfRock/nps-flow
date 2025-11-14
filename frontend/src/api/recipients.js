import axios from './axios';

export const recipientsAPI = {
  getAll: async (campaignId, params) => {
    const response = await axios.get(`/campaigns/${campaignId}/recipients`, { params });
    return response.data;
  },

  getById: async (campaignId, id) => {
    const response = await axios.get(`/campaigns/${campaignId}/recipients/${id}`);
    return response.data;
  },

  create: async (campaignId, data) => {
    const response = await axios.post(`/campaigns/${campaignId}/recipients`, data);
    return response.data;
  },

  update: async (campaignId, id, data) => {
    const response = await axios.put(`/campaigns/${campaignId}/recipients/${id}`, data);
    return response.data;
  },

  delete: async (campaignId, id) => {
    const response = await axios.delete(`/campaigns/${campaignId}/recipients/${id}`);
    return response.data;
  },

  uploadCSV: async (campaignId, file) => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await axios.post(
      `/campaigns/${campaignId}/recipients/upload`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  },

  getTemplate: async () => {
    const response = await axios.get('/campaigns/0/recipients/template');
    return response.data;
  },
};
