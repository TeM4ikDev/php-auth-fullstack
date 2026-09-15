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
 * Content of the popup message. The dark backdrop and animation come from ToastContainer
 * in main.tsx — this is just the icon and text, so every message looks the same.
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
 * Show a message. Lives outside the router, so it survives page transitions:
 * it can be called before navigate() without passing a flag through route state.
 */
export const showToast = (text: ReactNode, tone: ToastTone = "success", options?: ToastOptions) =>
    toast(<ToastMessage tone={tone} text={text} />, options);
