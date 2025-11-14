import axios from './axios';

export const campaignsAPI = {
  getAll: async (params) => {
    const response = await axios.get('/campaigns', { params });
    return response.data;
  },

  getById: async (id) => {
    const response = await axios.get(`/campaigns/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await axios.post('/campaigns', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await axios.put(`/campaigns/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await axios.delete(`/campaigns/${id}`);
    return response.data;
  },

  start: async (id) => {
    const response = await axios.post(`/campaigns/${id}/start`);
    return response.data;
  },

  stop: async (id) => {
    const response = await axios.post(`/campaigns/${id}/stop`);
    return response.data;
  },
};
