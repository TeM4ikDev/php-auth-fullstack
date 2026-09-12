import { isAxiosError } from "axios";

export function getErrorMessage(e: unknown, fallback = "Что-то пошло не так"): string {
    if (isAxiosError<{ error?: string; message?: string }>(e)) {
        const data = e.response?.data;
        if (data?.error) return data.error;
        if (data?.message) return data.message;
        if (!e.response) return "Сервер недоступен";
        return `Запрос завершился с кодом ${e.response.status}`;
    }

    if (e instanceof Error && e.message) return e.message;

    return fallback;
}
