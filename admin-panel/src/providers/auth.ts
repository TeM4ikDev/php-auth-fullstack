import type { AuthProvider } from "@refinedev/core";
import { REFRESH_TOKEN_KEY, TOKEN_KEY } from "./constants";
import { HttpError, request } from "./http";
import { UserRoles, type IUser } from "../types";

type LoginResponse = { accessToken: string; refreshToken: string; user: IUser };

const me = () => request<IUser>("users/me");

const clearTokens = () => {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(REFRESH_TOKEN_KEY);
};

export const authProvider: AuthProvider = {
  login: async ({ email, password }) => {
    try {
      const { accessToken, refreshToken, user } = await request<LoginResponse>("auth/login", {
        method: "POST",
        body: { email, password },
      });

      // Analyst видит только историю уведомлений, Customer в панель не допускается вовсе
      if (user.role !== UserRoles.Admin && user.role !== UserRoles.Analyst) {
        return {
          success: false,
          error: { name: "LoginError", message: "This panel is for administrators and analysts only" },
        };
      }

      localStorage.setItem(TOKEN_KEY, accessToken);
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);

      return { success: true, redirectTo: "/" };
    } catch (error) {
      return {
        success: false,
        error: {
          name: "LoginError",
          message: error instanceof Error ? error.message : "Invalid email or password",
        },
      };
    }
  },

  logout: async () => {
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);

    try {
      await request("auth/logout", { method: "POST", body: { refreshToken } });
    } catch {
      // Токен уже мог истечь — это не должно мешать локальному разлогину
    }

    clearTokens();

    return { success: true, redirectTo: "/login" };
  },

  check: async () => {
    if (!localStorage.getItem(TOKEN_KEY)) {
      return { authenticated: false, redirectTo: "/login" };
    }

    try {
      const user = await me();

      if (user.role !== UserRoles.Admin && user.role !== UserRoles.Analyst) {
        clearTokens();

        return { authenticated: false, redirectTo: "/login", error: new Error("Administrators and analysts only") };
      }

      return { authenticated: true };
    } catch {
      // Истёкший токен или бан: оба случая означают, что сессии больше нет
      clearTokens();

      return { authenticated: false, redirectTo: "/login" };
    }
  },

  getPermissions: async () => {
    try {
      return (await me()).role;
    } catch {
      return null;
    }
  },

  getIdentity: async () => {
    try {
      const user = await me();

      return { id: user.id, name: user.name, email: user.email };
    } catch {
      return null;
    }
  },

  onError: async (error) => {
    if (error instanceof HttpError && (error.statusCode === 401 || error.statusCode === 403)) {
      clearTokens();

      return { logout: true, redirectTo: "/login", error };
    }

    return { error };
  },
};
