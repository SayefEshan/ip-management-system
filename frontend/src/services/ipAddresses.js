import api from "./api";

export const ipService = {
  getAll: async () => {
    const response = await api.get("/ip-addresses");
    // The new API returns data in response.data.data format
    return response;
  },
  
  create: async (data) => {
    const response = await api.post("/ip-addresses", data);
    return response;
  },
  
  update: async (id, data) => {
    const response = await api.put(`/ip-addresses/${id}`, data);
    return response;
  },
  
  delete: async (id) => {
    const response = await api.delete(`/ip-addresses/${id}`);
    return response;
  },
};