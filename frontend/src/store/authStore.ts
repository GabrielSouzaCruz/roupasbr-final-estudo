import { create } from 'zustand';
import { authApi } from '@/lib/api';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: true,
  login: async (email, password) => {
    const response = await authApi.login({ email, password });
    const { token, user } = response.data;
    localStorage.setItem('auth_token', token);
    set({ user, token, isAuthenticated: true, isLoading: false });
  },
  register: async (data: any) => {
    const response = await authApi.register(data);
    const { token, user } = response.data;
    localStorage.setItem('auth_token', token);
    set({ user, token, isAuthenticated: true, isLoading: false });
  },
  logout: async () => {
    try {
      await authApi.logout();
    } finally {
      localStorage.removeItem('auth_token');
      set({ user: null, token: null, isAuthenticated: false, isLoading: false });
    }
  },
  fetchUser: async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      set({ isLoading: false });
      return;
    }
    try {
      const response = await authApi.getUser();
      set({ user: response.data, token, isAuthenticated: true, isLoading: false });
    } catch {
      localStorage.removeItem('auth_token');
      set({ user: null, token: null, isAuthenticated: false, isLoading: false });
    }
  },
}));
