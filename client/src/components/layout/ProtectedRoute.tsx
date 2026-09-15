import { Loader } from "@/components/layout/Loader";
import { useStore } from "@/store/root.store";
import { RoutesConfig } from "@/types/pagesConfig";
import type { UserRoles } from "@/types";
import { observer } from "mobx-react-lite";
import { useEffect, type ReactNode } from "react";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";

interface Props {
    children: ReactNode;
    allowedRoles: UserRoles[];
}

export const ProtectedRoute = observer(({ children, allowedRoles }: Props) => {
    const { userStore: { isLoading, userRole } } = useStore();
    const allowed = !!userRole && allowedRoles.includes(userRole);

    // Toast in an effect, not in the render body — otherwise StrictMode shows it twice
    useEffect(() => {
        if (!isLoading && !allowed) {
            toast.error('Insufficient permissions');
        }
    }, [isLoading, allowed]);

    if (isLoading) {
        return <Loader />;
    }

    if (!allowed) {
        // Once a sign-in page exists, change this redirect to RoutesConfig.LOGIN.path
        return <Navigate to={RoutesConfig.HOME.path} replace />;
    }

    return <>{children}</>;
});
