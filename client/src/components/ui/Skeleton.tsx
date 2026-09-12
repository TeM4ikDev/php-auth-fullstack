import { cn } from "@/utils/cn";

interface Props {
    className?: string;
}

export const Skeleton = ({ className }: Props) => (
    <span aria-hidden className={cn("relative block overflow-hidden rounded-3xl bg-white/[0.06]", className)}>
        <span className="absolute inset-0 -translate-x-full animate-shimmer bg-gradient-to-r from-transparent via-white/[0.09] to-transparent motion-reduce:hidden" />
    </span>
);
