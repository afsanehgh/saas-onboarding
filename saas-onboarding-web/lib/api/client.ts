import axios, { AxiosError } from 'axios';
import { useAuthStore } from '@/lib/store/authStore';

export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Attach the bearer token from the auth store to every request.
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// If the token has expired or been revoked, clear it and send the user to log in.
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const isAuthCall = error.config?.url?.startsWith('/auth/');
    if (error.response?.status === 401 && !isAuthCall) {
      useAuthStore.getState().logout();
      if (typeof window !== 'undefined') {
        // Runs outside React, so a full navigation is the simplest reliable redirect.
        // eslint-disable-next-line @next/next/no-location-assign-relative-destination
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  },
);

interface ApiErrorBody {
  message?: string;
  errors?: Record<string, string[]>;
}

/** Turn an API or network error into a message suitable for showing to the user. */
export function getErrorMessage(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
  if (axios.isAxiosError<ApiErrorBody>(error)) {
    if (!error.response) {
      return `Cannot reach the API at ${API_URL}. Is the backend running?`;
    }
    const firstFieldError = error.response.data?.errors && Object.values(error.response.data.errors)[0]?.[0];
    return firstFieldError || error.response.data?.message || fallback;
  }
  return error instanceof Error ? error.message : fallback;
}
