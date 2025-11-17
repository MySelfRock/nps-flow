import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const responsesAPI = {
  getByToken: async (token) => {
    const response = await axios.get(`${API_URL}/r/${token}`);
    return response.data;
  },

  submit: async (token, data) => {
    const response = await axios.post(`${API_URL}/r/${token}`, data);
    return response.data;
  },
};
