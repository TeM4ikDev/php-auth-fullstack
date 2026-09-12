import {Button} from "@/components/ui/Button";
import {useStore} from "@/store/root.store";
import {RoutesConfig} from "@/types/pagesConfig";
import {LogOut} from "lucide-react";
import {observer} from "mobx-react-lite";
import {useNavigate} from "react-router-dom";
import {ProfileHeader} from "@/components/layout/ProfileHeader.tsx";
import {Skeleton} from "@/components/ui/Skeleton.tsx";
import {toast} from "react-toastify";

const HeaderSkeleton = () => (
    <div role="status" aria-busy="true" className="flex w-full items-center gap-4">
        <div className="flex items-center gap-3">
            <Skeleton className="h-14 w-14 rounded-full"/>
            <div className="flex flex-col gap-1.5">
                <Skeleton className="h-4 w-28 rounded-md"/>
                <Skeleton className="h-3 w-32 rounded-md"/>
            </div>
        </div>

        <div className="ml-auto flex items-center gap-2">
            <Skeleton className="h-10 w-24 rounded-xl"/>
            <Skeleton className="h-10 w-24 rounded-xl"/>
        </div>

        <span className="sr-only">Загрузка профиля</span>
    </div>
);

export const Header = observer(() => {
    const {userStore} = useStore();
    const {isAuth, isLoading} = userStore;
    const navigate = useNavigate();

    const handleLogout = () => {
        userStore.logout();
        navigate(RoutesConfig.LOGIN.path, {replace: true});
        toast.success("Вы вышли")
    };

    return (
        <header className="sticky justify-between top-0 z-40 flex w-full shrink-0 items-center gap-4 border-b border-white/[0.12] bg-back-primary/85 px-4 py-2 backdrop-blur-sm">
            {isLoading ? (
                <HeaderSkeleton />
            ) : isAuth ? (
                <>
                    <ProfileHeader />
                    <Button
                        text="Выйти"
                        color="gray"
                        widthMin
                        className="h-min"
                        icon={<LogOut className="h-4 w-4" />}
                        onClick={handleLogout}
                    />
                </>
            ) : (
                <div className="ml-auto flex items-center gap-2">
                    <Button text="Войти" color="transparent" widthMin routeKey="LOGIN" />
                    <Button text="Регистрация" widthMin routeKey="REGISTER" />
                </div>
            )}
        </header>
    );
});
