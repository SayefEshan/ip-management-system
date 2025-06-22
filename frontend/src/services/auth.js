import api from "./api";

export const authService = {
  login: async (email, password) => {
    const response = await api.post("/auth/login", { email, password });
    const data = response.data;

    localStorage.setItem("access_token", data.access_token);
    localStorage.setItem("refresh_token", data.refresh_token);
    localStorage.setItem("session_id", data.session_id);
    localStorage.setItem("user", JSON.stringify(data.user));

    return data;
  },

  logout: async () => {
    await api.post("/auth/logout");
    localStorage.clear();
  },

  getUser: () => {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
  },

  isAuthenticated: () => {
    return !!localStorage.getItem("access_token");
  },
};
