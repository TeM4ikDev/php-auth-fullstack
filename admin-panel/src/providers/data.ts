import type { BaseRecord, DataProvider } from "@refinedev/core";
import { API_URL } from "./constants";
import { request } from "./http";
import type { IUser } from "../types";

type ListResponse<TData> = { data: TData[]; total: number };

const unsupported = (operation: string) => () => {
  throw new Error(`[dataProvider] ${operation} is not supported by this API.`);
};

/**
 * Ресурс один — users, он лежит под /api/admin/users.
 * Бэкенд уже отдаёт список в форме {data, total}, которую ждёт Refine.
 */
export const dataProvider: DataProvider = {
  getApiUrl: () => API_URL,

  getList: async <TData extends BaseRecord = BaseRecord>({
    pagination,
    filters,
  }: Parameters<NonNullable<DataProvider["getList"]>>[0]) => {
    const search = filters?.find((filter) => "field" in filter && filter.field === "search");

    return request<ListResponse<TData>>("admin/users", {
      query: {
        page: pagination?.currentPage ?? 1,
        perPage: pagination?.pageSize ?? 20,
        search: search && "value" in search ? (search.value as string) : undefined,
      },
    });
  },

  getOne: async <TData extends BaseRecord = BaseRecord>({
    id,
  }: Parameters<NonNullable<DataProvider["getOne"]>>[0]) => {
    return { data: await request<TData>(`admin/users/${id}`) };
  },

  update: async <TData extends BaseRecord = BaseRecord>({
    id,
    variables,
  }: Parameters<NonNullable<DataProvider["update"]>>[0]) => {
    return { data: await request<TData>(`admin/users/${id}`, { method: "PATCH", body: variables }) };
  },

  create: unsupported("create"),
  deleteOne: unsupported("deleteOne"),
};

/** Бан и смена роли — отдельные операции: они защищены собственными проверками на бэкенде. */
export const setUserBanned = (id: string, banned: boolean) =>
  request<IUser>(`admin/users/${id}/ban`, { method: "POST", body: { banned } });

export const setUserRole = (id: string, role: string) =>
  request<IUser>(`admin/users/${id}/role`, { method: "POST", body: { role } });

/**
 * Ресурс notifications лежит под /api/notifications — на nginx-уровне (и в dev-проксировании
 * Vite) этот префикс уезжает в notification-service, а не в auth-service. Read-only: история
 * уведомлений не редактируется из панели.
 */
export const notificationsDataProvider: DataProvider = {
  getApiUrl: () => API_URL,

  getList: async <TData extends BaseRecord = BaseRecord>({
    pagination,
  }: Parameters<NonNullable<DataProvider["getList"]>>[0]) => {
    return request<ListResponse<TData>>("notifications", {
      query: {
        page: pagination?.currentPage ?? 1,
        perPage: pagination?.pageSize ?? 20,
      },
    });
  },

  getOne: async <TData extends BaseRecord = BaseRecord>({
    id,
  }: Parameters<NonNullable<DataProvider["getOne"]>>[0]) => {
    return { data: await request<TData>(`notifications/${id}`) };
  },

  create: unsupported("create"),
  update: unsupported("update"),
  deleteOne: unsupported("deleteOne"),
};
