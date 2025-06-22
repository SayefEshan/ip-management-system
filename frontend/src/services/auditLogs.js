import api from "./api";

export const auditService = {
  getSessionLogs: () => api.get("/audit-logs/session"),
  getUserLogs: () => api.get("/audit-logs/user"),
  getIpLogs: (ipId) => api.get(`/audit-logs/ip-address/${ipId}`),
  getIpSessionLogs: (ipId) => api.get(`/audit-logs/ip-address/${ipId}/session`),
  getAllLogs: () => api.get("/audit-logs/all"),
};
