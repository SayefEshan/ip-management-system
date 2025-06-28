import React, { useState, useEffect } from "react";
import { auditService } from "../services/auditLogs";
import { authService } from "../services/auth";

function AuditLogs() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [logType, setLogType] = useState("user");
  const [ipAddress, setIpAddress] = useState("");

  const user = authService.getUser();

  useEffect(() => {
    if (logType !== "ip") {
      fetchLogs();
    }
  }, [logType]);

  const fetchLogs = async () => {
    setLoading(true);
    try {
      let response;
      switch (logType) {
        case "user":
          response = await auditService.getUserLogs();
          break;
        case "session":
          response = await auditService.getSessionLogs();
          break;
        case "all":
          response = await auditService.getAllLogs();
          break;
      }
      setLogs(response.data.data || []);
    } catch (err) {
      console.error("Failed to fetch logs:", err);
      setLogs([]);
    } finally {
      setLoading(false);
    }
  };

  const fetchIpLogs = async (sessionOnly = false) => {
    if (!ipAddress) {
      alert("Please enter an IP address");
      return;
    }
    setLoading(true);
    try {
      const response = sessionOnly
        ? await auditService.getIpSessionLogs(ipAddress)
        : await auditService.getIpLogs(ipAddress);
      setLogs(response.data.data || []);
    } catch (err) {
      console.error("Failed to fetch IP logs:", err);
      alert("Failed to fetch logs for this IP");
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  const getActionBadgeColor = (action) => {
    const colors = {
      LOGIN: "bg-green-100 text-green-800",
      LOGOUT: "bg-gray-100 text-gray-800",
      CREATE: "bg-blue-100 text-blue-800",
      UPDATE: "bg-yellow-100 text-yellow-800",
      DELETE: "bg-red-100 text-red-800",
      FAILED_LOGIN: "bg-red-100 text-red-800",
    };
    return colors[action] || "bg-gray-100 text-gray-800";
  };

  const renderChanges = (log) => {
    if (log.action === "CREATE" && log.new_values) {
      const values =
        typeof log.new_values === "string"
          ? JSON.parse(log.new_values)
          : log.new_values;

      return (
        <div className="text-xs space-y-1">
          <div className="font-medium text-gray-700">Created:</div>
          {Object.entries(values).map(([key, value]) => (
            <div key={key} className="pl-2">
              <span className="text-gray-600">{key}:</span>{" "}
              <span className="text-gray-900">{value || "-"}</span>
            </div>
          ))}
        </div>
      );
    }

    if (log.action === "UPDATE" && log.metadata?.changes) {
      return (
        <div className="text-xs space-y-1">
          <div className="font-medium text-gray-700">Updated:</div>
          {Object.entries(log.metadata.changes).map(
            ([field, [oldVal, newVal]]) => (
              <div key={field} className="pl-2">
                <span className="text-gray-600">{field}:</span>
                <span className="text-red-600 line-through"> {oldVal}</span>
                <span> → </span>
                <span className="text-green-600">{newVal}</span>
              </div>
            )
          )}
        </div>
      );
    }

    if (log.action === "DELETE" && log.old_values) {
      const values =
        typeof log.old_values === "string"
          ? JSON.parse(log.old_values)
          : log.old_values;

      return (
        <div className="text-xs space-y-1">
          <div className="font-medium text-gray-700">Deleted:</div>
          {Object.entries(values).map(([key, value]) => (
            <div key={key} className="pl-2">
              <span className="text-gray-600">{key}:</span>{" "}
              <span className="text-gray-900">{value || "-"}</span>
            </div>
          ))}
        </div>
      );
    }

    if (log.metadata) {
      const metadata =
        typeof log.metadata === "string"
          ? JSON.parse(log.metadata)
          : log.metadata;

      if (metadata.email || metadata.attempted_email) {
        return (
          <div className="text-xs">
            <span className="text-gray-600">Attempted email:</span>{" "}
            <span className="text-gray-900">
              {metadata.email || metadata.attempted_email}
            </span>
          </div>
        );
      }
    }

    return <span className="text-gray-400 text-xs">-</span>;
  };

  return (
    <div className="px-4 sm:px-0">
      <div className="sm:flex sm:items-center">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900">Audit Logs</h1>
          <p className="mt-2 text-sm text-gray-700">
            View system activity and changes
          </p>
        </div>
      </div>

      <div className="mt-6 bg-white shadow rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Filter Logs</h3>
        <div className="space-y-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="sm:w-64">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Log Type
              </label>
              <select
                value={logType}
                onChange={(e) => setLogType(e.target.value)}
                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="user">My Lifetime Logs</option>
                <option value="session">Current Session</option>
                {user.is_super_admin && (
                  <option value="all">All Logs (Admin)</option>
                )}
                <option value="ip">IP Address Logs</option>
              </select>
            </div>

            {logType === "ip" && (
              <div className="flex gap-2 flex-1">
                <div className="flex-1">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    IP Address
                  </label>
                  <input
                    type="text"
                    placeholder="Enter IP Address (e.g., 192.168.1.1)"
                    value={ipAddress}
                    onChange={(e) => setIpAddress(e.target.value)}
                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                </div>
                <div className="flex items-end gap-2">
                  <button
                    onClick={() => fetchIpLogs(true)}
                    className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Session Logs
                  </button>
                  <button
                    onClick={() => fetchIpLogs(false)}
                    className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    All Logs
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              {loading ? (
                <div className="flex justify-center items-center h-64 bg-white">
                  <div className="text-gray-500">Loading...</div>
                </div>
              ) : (
                <table className="min-w-full divide-y divide-gray-300">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date/Time
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Entity
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Changes
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        IP Address
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {logs.map((log) => (
                      <tr key={log.id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {formatDate(log.created_at)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getActionBadgeColor(
                              log.action
                            )}`}
                          >
                            {log.action}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {log.user_email}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {log.entity_type === "ip_address" && log.entity_ip
                            ? log.entity_ip
                            : "-"}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-500">
                          {renderChanges(log)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {log.ip_address || "-"}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
              {!loading && logs.length === 0 && (
                <div className="text-center py-12 bg-white">
                  <p className="text-sm text-gray-500">No logs found</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default AuditLogs;
