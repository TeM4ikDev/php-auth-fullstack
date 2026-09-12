import { cn } from "@/utils/cn";
import { Switch as HeadlessSwitch } from '@headlessui/react';
import React from 'react';

interface SwitchProps {
  value: boolean;
  onToggle: () => void;
  showSwitch?: boolean;
  text1?: string;
  text2?: string;
  disabled?: boolean;
}

export const Switch: React.FC<SwitchProps> = ({
  value,
  onToggle,
  showSwitch = true,
  disabled = false,
  text1 = '',
  text2 = '',
}) => {
  const label = value ? text1 : text2;

  if (!showSwitch) {
    return (
      <span className={cn("px-2 py-1 text-xs font-semibold rounded-full", value ? 'bg-gold-500/20 text-gold-400' : 'bg-red-900 text-red-300')}>
        {label}
      </span>
    );
  }

  return (
    <div className="flex items-center gap-2 w-min justify-center">
      <HeadlessSwitch
        disabled={disabled}
        checked={value}
        onChange={onToggle}
        className={cn(
          "relative inline-flex h-6 min-w-11 w-11 items-center rounded-full transition-colors focus:outline-none",
          value ? 'bg-blackSabath-700' : 'bg-blackSabath-900',
          disabled && 'opacity-40 cursor-not-allowed'
        )}
      >
        <span className={cn("inline-block h-4 w-4 transform rounded-full bg-white transition-transform", value ? 'translate-x-6' : 'translate-x-1')} />
      </HeadlessSwitch>
      {label && (
        <span className={cn("text-xs font-semibold", value ? 'text-gold-400' : 'text-gray-400')}>{label}</span>
      )}
    </div>
  );
};
