import axios from "axios";

const api = axios.create({
  baseURL: "/api",
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("access_token");
  const refreshToken = localStorage.getItem("refresh_token");
  const sessionId = localStorage.getItem("session_id");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  if (refreshToken) {
    config.headers["X-Refresh-Token"] = refreshToken;
  }
  if (sessionId) {
    config.headers["X-Session-ID"] = sessionId;
  }

  return config;
});

// Handle token refresh
api.interceptors.response.use(
  (response) => {
    const newToken = response.headers["x-new-access-token"];
    if (newToken) {
      localStorage.setItem("access_token", newToken);
    }
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.clear();
      window.location.href = "/login";
    }
    
    // Standardize error response format
    if (error.response?.data) {
      const errorData = error.response.data;
      // If it's our standardized error format, keep it as is
      if (errorData.success === false) {
        error.response.data = errorData;
      }
    }
    
    return Promise.reject(error);
  }
);

export default api;
