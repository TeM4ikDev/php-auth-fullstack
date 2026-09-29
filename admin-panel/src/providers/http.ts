import { API_URL, REFRESH_TOKEN_KEY, TOKEN_KEY } from "./constants";

export class HttpError extends Error {
  constructor(
    message: string,
    public readonly statusCode: number,
  ) {
    super(message);
    this.name = "HttpError";
  }
}

type RequestOptions = {
  method?: string;
  body?: unknown;
  query?: Record<string, string | number | undefined>;
};

type RefreshResponse = { accessToken: string; refreshToken: string };

const isAuthPath = (path: string) => /^auth\/(login|register|refresh)$/.test(path);

// Конкурентные 401 не должны запускать несколько параллельных refresh — только один запрос,
// остальные ждут его результата и повторяют исходный запрос с новым access-токеном
let refreshPromise: Promise<string> | null = null;

const buildUrl = (path: string, query?: RequestOptions["query"]): string => {
  const url = new URL(`${API_URL}/api/${path}`, window.location.origin);

  Object.entries(query ?? {}).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      url.searchParams.set(key, String(value));
    }
  });

  return url.toString();
};

const performRefresh = async (): Promise<string> => {
  const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);

  if (!refreshToken) {
    throw new HttpError("No refresh token available", 401);
  }

  const response = await fetch(buildUrl("auth/refresh"), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ refreshToken }),
  });

  if (!response.ok) {
    throw new HttpError("Failed to refresh the session", response.status);
  }

  const data = (await response.json()) as RefreshResponse;

  localStorage.setItem(TOKEN_KEY, data.accessToken);
  localStorage.setItem(REFRESH_TOKEN_KEY, data.refreshToken);

  return data.accessToken;
};

const refreshAccessToken = (): Promise<string> => {
  refreshPromise ??= performRefresh().finally(() => {
    refreshPromise = null;
  });

  return refreshPromise;
};

const performRequest = async <T>(path: string, options: RequestOptions, token: string | null): Promise<T> => {
  const { method = "GET", body } = options;

  const response = await fetch(buildUrl(path, options.query), {
    method,
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const message =
      payload && typeof payload === "object" && "error" in payload
        ? String(payload.error)
        : `Request failed with status ${response.status}`;

    throw new HttpError(message, response.status);
  }

  return payload as T;
};

export const request = async <T>(path: string, options: RequestOptions = {}): Promise<T> => {
  const token = localStorage.getItem(TOKEN_KEY);

  try {
    return await performRequest<T>(path, options, token);
  } catch (error) {
    if (!(error instanceof HttpError) || error.statusCode !== 401 || isAuthPath(path)) {
      throw error;
    }

    // Протухший access-токен — один раз пробуем обновить пару и повторить исходный запрос
    const newToken = await refreshAccessToken();
    return performRequest<T>(path, options, newToken);
  }
};
