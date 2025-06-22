import api from "./api";

export const ipService = {
  getAll: () => api.get("/ip-addresses"),
  create: (data) => api.post("/ip-addresses", data),
  update: (id, data) => api.put(`/ip-addresses/${id}`, data),
  delete: (id) => api.delete(`/ip-addresses/${id}`),
};
