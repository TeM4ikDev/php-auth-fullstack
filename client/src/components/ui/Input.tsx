import { cn } from "@/utils/cn";
import { X } from "lucide-react";
import { type ReactNode } from "react";

type Props = {
  placeholder?: string;
  name?: string;
  type?: string;
  error?: string;
  value?: string | number;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  onBlur?: (e: React.FocusEvent<HTMLInputElement>) => void;
  onClear?: () => void;
  className?: string;
  min?: number;
  max?: number;
  step?: number;
  isRequired?: boolean;
  disabled?: boolean;
  showClearButton?: boolean;
  icon?: ReactNode;
};

export const Input = ({
  placeholder,
  name,
  type = "text",
  step,
  error,
  value,
  min,
  max,
  onChange,
  onBlur,
  onClear,
  className = "",
  isRequired = false,
  disabled = false,
  showClearButton = false,
  icon,
}: Props) => {
  const getStep = () => (type === "number" ? step ?? "any" : step);

  return (
    <div className="flex w-full flex-col">
      <div className="relative">
        {icon && <span className="absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">{icon}</span>}
        <input
          type={type}
          name={name}
          value={value ?? ""}
          onChange={onChange}
          onBlur={onBlur}
          disabled={disabled}
          placeholder={placeholder}
          required={isRequired}
          min={min}
          max={max}
          step={getStep()}
          inputMode={type === "number" ? "decimal" : "text"}
          className={cn(
            "w-full rounded-xl border border-border-secon bg-blackSabath-900 px-3 py-3 text-base text-text-primary placeholder:text-blackSabath-700 transition-colors",
            "focus:border-border-brended focus:outline-none",
            icon ? "pl-11" : "",
            showClearButton && value ? "pr-10" : "",
            error ? "border-red-500/60 focus:border-red-500" : "",
            disabled ? "cursor-not-allowed opacity-60" : "",
            className
          )}
        />
        {showClearButton && value && onClear && (
          <button
            type="button"
            onClick={onClear}
            className="absolute right-3 top-1/2 -translate-y-1/2 flex h-5 w-5 items-center justify-center rounded-full text-text-secondary hover:text-text-primary"
            title="Clear"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>
      {error && <p className="mt-1 px-1 text-xs text-red-400">{error}</p>}
    </div>
  );
};
