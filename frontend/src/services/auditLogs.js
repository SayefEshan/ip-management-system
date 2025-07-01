import api from "./api";

export const auditService = {
  getSessionLogs: async () => {
    const response = await api.get("/audit-logs/session");
    return response;
  },
  
  getUserLogs: async () => {
    const response = await api.get("/audit-logs/user");
    return response;
  },
  
  getIpLogs: async (ipAddress) => {
    const response = await api.get(`/audit-logs/ip-address/${ipAddress}`);
    return response;
  },
  
  getIpSessionLogs: async (ipAddress) => {
    const response = await api.get(`/audit-logs/ip-address/${ipAddress}/session`);
    return response;
  },
  
  getAllLogs: async () => {
    const response = await api.get("/audit-logs/all");
    return response;
  },
};