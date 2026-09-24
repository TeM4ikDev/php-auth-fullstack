import { Header } from "@/components/layout/Header";
import { Loader } from "@/components/layout/Loader";
import { NavFooter } from "@/components/layout/NavFooter";
import NotFound from "@/components/layout/NotFound";
import { ProtectedRoute } from "@/components/layout/ProtectedRoute";
import { LoginPage } from "@/pages/LoginPage";
import { MainPage } from "@/pages/MainPage";
import { ProfilePage } from "@/pages/ProfilePage";
import { RegisterPage } from "@/pages/RegisterPage";
import { VerifyEmailPage } from "@/pages/VerifyEmailPage";
import { useStore } from "@/store/root.store";
import { UserRoles } from "@/types";
import { RoutesConfig } from "@/types/pagesConfig";
import { observer } from "mobx-react-lite";
import { BrowserRouter, Route, Routes } from "react-router-dom";

const AppContent = observer(() => {
    const { userStore: { isLoading } } = useStore();

    return (
        <div className="relative flex h-svh flex-col overflow-y-auto bg-back-primary">
            <Header />

            <div className="flex flex-1 flex-col">
                {isLoading ? (
                    <div className="flex flex-1 items-center justify-center">
                        <Loader />
                    </div>
                ) : (
                    <Routes>
                        <Route path={RoutesConfig.HOME.path} element={<MainPage />} />
                        <Route path={RoutesConfig.LOGIN.path} element={<LoginPage />} />
                        <Route path={RoutesConfig.REGISTER.path} element={<RegisterPage />} />
                        <Route path={RoutesConfig.VERIFY_EMAIL.path} element={<VerifyEmailPage />} />

                        <Route
                            path={RoutesConfig.PROFILE.path}
                            element={
                                <ProtectedRoute
                                    allowedRoles={[UserRoles.Customer, UserRoles.Analyst, UserRoles.Admin]}
                                >
                                    <ProfilePage />
                                </ProtectedRoute>
                            }
                        />

                        <Route path="*" element={<NotFound />} />
                    </Routes>
                )}
            </div>

            <NavFooter />
        </div>
    );
});

function App() {
    return (
        <BrowserRouter>
            <AppContent />
        </BrowserRouter>
    );
}

export default App;
