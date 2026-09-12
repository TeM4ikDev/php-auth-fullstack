import {PageContainer} from "@/components/layout/PageContainer";
import {Button} from "@/components/ui/Button";
import {useStore} from "@/store/root.store";
import {RoutesConfig} from "@/types/pagesConfig";
import {LogOut} from "lucide-react";
import {observer} from "mobx-react-lite";
import {Navigate, useNavigate} from "react-router-dom";

export const ProfilePage = observer(() => {
    const {userStore} = useStore();
    const {user} = userStore;
    const navigate = useNavigate();

    const handleLogout = () => {
        userStore.logout();
        navigate(RoutesConfig.LOGIN.path, {replace: true});
    };

    // navigate() в теле рендера — побочный эффект; редирект делаем элементом
    if (!user) {
        return <Navigate to={RoutesConfig.LOGIN.path} replace/>;
    }

    return (
        <PageContainer itemsStart>

            <dl className="flex flex-col gap-3 rounded-xl border border-border-secon bg-back-secondary p-4 text-sm">
                <div className="flex justify-between gap-4">
                    <dt className="text-text-secondary">Имя</dt>
                    <dd className="text-text-primary">{user?.name ?? '—'}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-text-secondary">Почта</dt>
                    <dd className="text-text-primary">{user?.email ?? '—'}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-text-secondary">Роль</dt>
                    <dd className="text-text-primary">{user?.role ?? '—'}</dd>
                </div>
            </dl>

            <div>
                <Button
                    text="Выйти"
                    color="transparent"
                    icon={<LogOut className="h-4 w-4"/>}
                    onClick={handleLogout}
                />
            </div>
        </PageContainer>
    );
});
