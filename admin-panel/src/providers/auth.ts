import type { AuthProvider } from "@refinedev/core";
import { TOKEN_KEY } from "./constants";
import { HttpError, request } from "./http";
import { UserRoles, type IUser } from "../types";

type LoginResponse = { token: string; user: IUser };

const me = () => request<IUser>("users/me");

export const authProvider: AuthProvider = {
  login: async ({ email, password }) => {
    try {
      const { token, user } = await request<LoginResponse>("auth/login", {
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

      localStorage.setItem(TOKEN_KEY, token);

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
    localStorage.removeItem(TOKEN_KEY);

    return { success: true, redirectTo: "/login" };
  },

  check: async () => {
    if (!localStorage.getItem(TOKEN_KEY)) {
      return { authenticated: false, redirectTo: "/login" };
    }

    try {
      const user = await me();

      if (user.role !== UserRoles.Admin && user.role !== UserRoles.Analyst) {
        localStorage.removeItem(TOKEN_KEY);

        return { authenticated: false, redirectTo: "/login", error: new Error("Administrators and analysts only") };
      }

      return { authenticated: true };
    } catch {
      // Истёкший токен или бан: оба случая означают, что сессии больше нет
      localStorage.removeItem(TOKEN_KEY);

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
      localStorage.removeItem(TOKEN_KEY);

      return { logout: true, redirectTo: "/login", error };
    }

    return { error };
  },
};
