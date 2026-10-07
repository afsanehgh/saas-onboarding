import { apiClient } from './client';
import type { User } from '@/lib/store/authStore';

interface AuthResponse {
  success: boolean;
  data: { user: User; token: string };
}

export const AuthService = {
  async login(email: string, password: string) {
    const { data } = await apiClient.post<AuthResponse>('/auth/login', { email, password });
    return data.data;
  },

  async register(input: { name: string; email: string; password: string; company?: string }) {
    const { data } = await apiClient.post<AuthResponse>('/auth/register', input);
    return data.data;
  },

  async me() {
    const { data } = await apiClient.get<{ success: boolean; data: User }>('/auth/me');
    return data.data;
  },

  async logout() {
    await apiClient.post('/auth/logout');
  },
};
