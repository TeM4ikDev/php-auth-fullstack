import { Modal } from "@/components/ui/Modal";
import { cn } from "@/utils/cn";
import { AlertTriangle, CheckCircle2 } from "lucide-react";
import { type ReactNode } from "react";

export type StatusTone = "success" | "error" | "neutral";

interface Props {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
    /** neutral — без иконки: состояние ожидания */
    tone?: StatusTone;
    title: string;
    description?: ReactNode;
    /** доп. строка под описанием, например «Раз и навсегда · 120$» */
    note?: ReactNode;
    /** кнопки */
    children?: ReactNode;
}

const ICONS: Record<Exclude<StatusTone, "neutral">, { Icon: typeof CheckCircle2; color: string }> = {
    success: { Icon: CheckCircle2, color: "text-success-secondary" },
    error: { Icon: AlertTriangle, color: "text-text-primary" },
};

export const StatusModal = ({ isOpen, setIsOpen, tone = "neutral", title, description, note, children }: Props) => {
    const icon = tone === "neutral" ? null : ICONS[tone];

    return (
        <Modal isOpen={isOpen} setIsOpen={setIsOpen}>
            <div className="flex flex-col items-center gap-2.5 text-center">
                {icon && <icon.Icon className={cn("h-10 w-10", icon.color)} strokeWidth={1.5} />}

                <h3 className="text-xl text-text-primary">{title}</h3>

                {description && (
                    <p className="text-sm font-normal leading-[1.25rem] text-text-secondary">{description}</p>
                )}

                {note && <p className="text-base text-text-primary">{note}</p>}
            </div>

            {children}
        </Modal>
    );
};
