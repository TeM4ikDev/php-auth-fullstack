import { cn } from "@/utils/cn";
import { type ReactNode } from "react";

interface SectionProps {
  children: ReactNode;
  className?: string;
}

export const Section = ({ children, className }: SectionProps) => {
  return (
    <section className={cn("flex min-h-screen flex-col z-[0] w-full items-center transition-all duration-300", className)}>
      {children}
    </section>
  );
};
