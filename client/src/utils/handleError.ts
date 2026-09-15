import { isAxiosError } from "axios";

export function getErrorMessage(e: unknown, fallback = "Something went wrong"): string {
    if (isAxiosError<{ error?: string; message?: string }>(e)) {
        const data = e.response?.data;
        if (data?.error) return data.error;
        if (data?.message) return data.message;
        if (!e.response) return "Server is unavailable";
        return `Request failed with status ${e.response.status}`;
    }

    if (e instanceof Error && e.message) return e.message;

    return fallback;
}
