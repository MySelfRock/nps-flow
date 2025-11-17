import axios from './axios';

export const authAPI = {
  login: async (email, password) => {
    const response = await axios.post('/auth/login', { email, password });
    return response.data;
  },

  signup: async (data) => {
    const response = await axios.post('/auth/signup', data);
    return response.data;
  },

  logout: async () => {
    const response = await axios.post('/auth/logout');
    return response.data;
  },

  me: async () => {
    const response = await axios.get('/auth/me');
    return response.data;
  },

  refresh: async () => {
    const response = await axios.post('/auth/refresh');
    return response.data;
  },
};
