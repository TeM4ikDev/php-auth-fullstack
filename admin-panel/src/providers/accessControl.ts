import type { AccessControlProvider } from "@refinedev/core";
import { authProvider } from "./auth";
import { UserRoles } from "../types";

const RESOURCE_ROLES: Record<string, string[]> = {
  users: [UserRoles.Admin],
  notifications: [UserRoles.Admin, UserRoles.Analyst],
};

export const accessControlProvider: AccessControlProvider = {
  can: async ({ resource }) => {
    const allowedRoles = resource ? RESOURCE_ROLES[resource] : undefined;

    if (!allowedRoles) {
      return { can: true };
    }

    const role = await authProvider.getPermissions?.();

    return { can: typeof role === "string" && allowedRoles.includes(role) };
  },
};
