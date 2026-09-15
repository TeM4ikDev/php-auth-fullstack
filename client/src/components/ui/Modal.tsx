import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { AnimatePresence, motion, useDragControls, type PanInfo } from 'framer-motion';
import { X } from 'lucide-react';
import { type ReactNode, memo, useEffect, useState } from 'react';

interface Props {
  title?: string | ReactNode;
  description?: string | ReactNode;
  buttonFC?: () => void;
  children?: React.ReactNode;
  isOpen: boolean;
  setIsOpen: (arg: boolean) => void;
}

const EASE: [number, number, number, number] = [0.22, 1, 0.36, 1];

const SWIPE_CLOSE_OFFSET = 110;
const SWIPE_CLOSE_VELOCITY = 600;

export const Modal = memo(function Modal({ title, description, children, buttonFC, isOpen, setIsOpen }: Props) {
  const [isMobile, setIsMobile] = useState(() => typeof window !== 'undefined' && window.innerWidth < 768);
  const dragControls = useDragControls();

  useEffect(() => {
    const onResize = () => setIsMobile(window.innerWidth < 768);
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  const close = () => {
    setIsOpen(false);
    buttonFC?.();
  };

  const onDragEnd = (_: MouseEvent | TouchEvent | PointerEvent, info: PanInfo) => {
    if (info.offset.y > SWIPE_CLOSE_OFFSET || info.velocity.y > SWIPE_CLOSE_VELOCITY) close();
  };

  const panelVariants = isMobile
    ? { initial: { y: '100%' }, animate: { y: 0 }, exit: { y: '100%' } }
    : { initial: { opacity: 0, scale: 0.96, y: 8 }, animate: { opacity: 1, scale: 1, y: 0 }, exit: { opacity: 0, scale: 0.96, y: 8 } };

  return (
    <AnimatePresence>
      {isOpen && (
        <Dialog static open onClose={close} className="relative z-50 font-bold">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2, ease: 'linear' }}
            className="fixed inset-0 bg-black/70"
            style={{ willChange: 'opacity' }}
            aria-hidden="true"
          />

          <div className="fixed inset-0 flex items-end justify-center pointer-events-none md:items-center md:px-4">
            <DialogPanel className="pointer-events-auto w-full max-w-md">
              <motion.div
                initial={panelVariants.initial}
                animate={panelVariants.animate}
                exit={panelVariants.exit}
                transition={{ duration: isMobile ? 0.34 : 0.22, ease: EASE }}
                drag={isMobile ? 'y' : false}
                dragControls={dragControls}
                dragListener={false}
                dragConstraints={{ top: 0, bottom: 0 }}
                dragElastic={{ top: 0, bottom: 0.6 }}
                dragMomentum={false}
                onDragEnd={onDragEnd}
                style={{ willChange: 'transform, opacity' }}
                className="flex max-h-[90vh] w-full flex-col overflow-hidden rounded-t-2xl bg-blackSabath-900 shadow-2xl md:rounded-2xl"
              >

                <div
                  onPointerDown={(e) => isMobile && dragControls.start(e)}
                  className="relative flex h-[3.75rem] shrink-0 touch-none items-center justify-center py-2"
                >
                  <span aria-hidden className="h-1 w-9 rounded-full bg-white/[0.16]" />
                  <button
                    type="button"
                    onClick={close}
                    onPointerDown={(e) => e.stopPropagation()}
                    className="absolute right-4 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-back-alpha-xs text-white/60 transition-colors hover:text-white"
                    aria-label="Close"
                  >
                    <X className="h-[1.125rem] w-[1.125rem]" strokeWidth={2.5} />
                  </button>
                </div>

                {/* min-h-0 is required: without it a flex child won't shrink below its content and scroll won't kick in */}
                <div className='flex min-h-0 flex-1 flex-col gap-2 px-4'>
                  {(title || description) && (
                    <div className="flex shrink-0 flex-col gap-2.5">
                      {title && (
                        <DialogTitle as="h3" className="text-xl text-text-primary">
                          {title}
                        </DialogTitle>
                      )}

                      {description && (
                        <p className="text-sm font-normal leading-[1.125rem] text-text-secondary">{description}</p>
                      )}
                    </div>
                  )}

                  <div
                    className="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto overscroll-contain pt-3"
                    style={{ paddingBottom: 'calc(3.125rem + var(--tg-safe-area-inset-bottom, 0px))' }}
                  >
                    {children}
                  </div>
                </div>

              </motion.div>
            </DialogPanel>
          </div>
        </Dialog>
      )}
    </AnimatePresence>
  );
});
