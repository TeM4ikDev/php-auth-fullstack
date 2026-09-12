import { PageContainer } from "@/components/layout/PageContainer";
import { Block } from "@/components/ui/Block";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { useStore } from "@/store/root.store";
import { RoutesConfig } from "@/types/pagesConfig";
import { getErrorMessage } from "@/utils/handleError";
import { LogIn } from "lucide-react";
import { observer } from "mobx-react-lite";
import { useState, type FormEvent } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import { toast } from "react-toastify";

export const LoginPage = observer(() => {
    const { userStore } = useStore();
    const navigate = useNavigate();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setLoading(true);

        try {
            await userStore.signIn({ email: email.trim(), password });
            navigate(RoutesConfig.PROFILE.path, { replace: true });
            toast.success("Успешный вход!")
        } catch (error) {
            toast.error(getErrorMessage(error, "Не удалось войти"));
        } finally {
            setLoading(false);
        }
    };

    return (
        <PageContainer>
            <Block className="max-w-110! w-full p-5 gap-5" icons={[<LogIn />]} title="Вход">
                <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                    <Input
                        type="email"
                        name="email"
                        placeholder="Почта"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        isRequired
                    />
                    <Input
                        type="password"
                        name="password"
                        placeholder="Пароль"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        isRequired
                    />

                    <Button
                        text="Войти"
                        formSubmit
                        loading={loading}
                        disabled={!email.trim() || !password}
                    />
                </form>

                <p className="text-center text-sm text-text-secondary">
                    Нет аккаунта?{" "}
                    <NavLink to={RoutesConfig.REGISTER.path} className="text-brand-500">
                        Зарегистрироваться
                    </NavLink>
                </p>
            </Block>
        </PageContainer>
    );
});
