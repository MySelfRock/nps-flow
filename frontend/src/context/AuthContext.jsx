import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [tenant, setTenant] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    const token = localStorage.getItem('token');
    if (token) {
      try {
        const response = await authAPI.me();
        setUser(response.data.user);
        setTenant(response.data.tenant);
        setIsAuthenticated(true);
      } catch (error) {
        console.error('Auth check failed:', error);
        logout();
      }
    }
    setLoading(false);
  };

  const login = async (email, password) => {
    try {
      const response = await authAPI.login(email, password);
      const { user, access_token } = response.data;

      localStorage.setItem('token', access_token);
      localStorage.setItem('user', JSON.stringify(user));

      setUser(user);
      setIsAuthenticated(true);

      // Fetch full user data including tenant
      await checkAuth();

      return { success: true };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Login failed',
      };
    }
  };

  const signup = async (data) => {
    try {
      const response = await authAPI.signup(data);
      const { user, tenant, access_token } = response.data;

      localStorage.setItem('token', access_token);
      localStorage.setItem('user', JSON.stringify(user));

      setUser(user);
      setTenant(tenant);
      setIsAuthenticated(true);

      return { success: true };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Signup failed',
      };
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      setTenant(null);
      setIsAuthenticated(false);
    }
  };

  const value = {
    user,
    tenant,
    loading,
    isAuthenticated,
    login,
    signup,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
