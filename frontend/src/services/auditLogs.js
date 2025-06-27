import api from "./api";

export const auditService = {
  getSessionLogs: () => api.get("/audit-logs/session"),
  getUserLogs: () => api.get("/audit-logs/user"),
  getIpLogs: (ipAddress) => api.get(`/audit-logs/ip-address/${ipAddress}`),
  getIpSessionLogs: (ipAddress) => api.get(`/audit-logs/ip-address/${ipAddress}/session`),
  getAllLogs: () => api.get("/audit-logs/all"),
};
