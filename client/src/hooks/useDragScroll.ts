import { useEffect, useRef } from "react";

/**
 * Horizontal scrolling by dragging with the mouse.
 * Touch devices scroll natively, so the hook does nothing there.
 */
export const useDragScroll = <T extends HTMLElement>() => {
    const ref = useRef<T>(null);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        let isDown = false;
        let startX = 0;
        let startScroll = 0;

        const onPointerDown = (e: PointerEvent) => {
            if (e.pointerType !== "mouse") return;
            isDown = true;
            startX = e.clientX;
            startScroll = el.scrollLeft;
        };

        const onPointerMove = (e: PointerEvent) => {
            if (!isDown) return;
            const delta = e.clientX - startX;
            // only change the cursor once the user has actually dragged
            if (Math.abs(delta) > 3) el.style.cursor = "grabbing";
            el.scrollLeft = startScroll - delta;
        };

        const stop = () => {
            isDown = false;
            el.style.cursor = "";
        };

        el.addEventListener("pointerdown", onPointerDown);
        window.addEventListener("pointermove", onPointerMove);
        window.addEventListener("pointerup", stop);
        window.addEventListener("pointercancel", stop);

        return () => {
            el.removeEventListener("pointerdown", onPointerDown);
            window.removeEventListener("pointermove", onPointerMove);
            window.removeEventListener("pointerup", stop);
            window.removeEventListener("pointercancel", stop);
        };
    }, []);

    return ref;
};
