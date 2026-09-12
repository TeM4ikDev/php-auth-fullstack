import { cn } from "@/utils/cn";
import { CircleCheck, Info, TriangleAlert } from "lucide-react";
import type { ReactNode } from "react";
import { toast, type ToastOptions } from "react-toastify";

export type ToastTone = "success" | "error" | "info";

const TONES: Record<ToastTone, { Icon: typeof CircleCheck; color: string }> = {
    success: { Icon: CircleCheck, color: "text-success-secondary" },
    error: { Icon: TriangleAlert, color: "text-error-primary" },
    info: { Icon: Info, color: "text-gold-500" },
};

/**
 * Содержимое всплывающего сообщения. Тёмную подложку и анимацию даёт ToastContainer
 * из main.tsx, здесь только иконка и текст — поэтому вид у всех сообщений один.
 */
const ToastMessage = ({ tone, text }: { tone: ToastTone; text: ReactNode }) => {
    const { Icon, color } = TONES[tone];

    return (
        <div className="flex w-full items-center gap-2.5">
            <Icon className={cn("h-5 w-5 shrink-0", color)} strokeWidth={2} />
            <span className="min-w-0 flex-1 text-sm leading-[1.125rem]">{text}</span>
        </div>
    );
};

/**
 * Показать сообщение. Живёт вне роутера, поэтому переживает переход между страницами:
 * можно вызвать перед navigate и не передавать флаг через state маршрута.
 */
export const showToast = (text: ReactNode, tone: ToastTone = "success", options?: ToastOptions) =>
    toast(<ToastMessage tone={tone} text={text} />, options);
