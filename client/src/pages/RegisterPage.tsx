import { PageContainer } from "@/components/layout/PageContainer";
import { Block } from "@/components/ui/Block";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { useStore } from "@/store/root.store";
import { RoutesConfig } from "@/types/pagesConfig";
import { getErrorMessage } from "@/utils/handleError";
import { UserPlus } from "lucide-react";
import { observer } from "mobx-react-lite";
import { useState, type FormEvent } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import { toast } from "react-toastify";

const MIN_PASSWORD_LENGTH = 8;

export const RegisterPage = observer(() => {
    const { userStore } = useStore();
    const navigate = useNavigate();

    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);

    const passwordError =
        password && password.length < MIN_PASSWORD_LENGTH
            ? `At least ${MIN_PASSWORD_LENGTH} characters`
            : undefined;

    const canSubmit = !!name.trim() && !!email.trim() && !!password && !passwordError;

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!canSubmit) return;

        setLoading(true);

        try {
            await userStore.signUp({ name: name.trim(), email: email.trim(), password });
            navigate(RoutesConfig.PROFILE.path, { replace: true });
            toast.success("Registered successfully!")

        } catch (error) {
            toast.error(getErrorMessage(error, "Failed to register"));
        } finally {
            setLoading(false);
        }
    };

    return (
        <PageContainer>
            <Block className="max-w-110! w-full p-5 gap-5" icons={[<UserPlus />]} title="Sign up">
                <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                    <Input
                        name="name"
                        placeholder="Name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        isRequired
                    />
                    <Input
                        type="email"
                        name="email"
                        placeholder="Email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        isRequired
                    />
                    <Input
                        type="password"
                        name="password"
                        placeholder="Password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        error={passwordError}
                        isRequired
                    />

                    <Button text="Sign up" formSubmit loading={loading} disabled={!canSubmit} />
                </form>

                <p className="text-center text-sm text-text-secondary">
                    Already have an account?{" "}
                    <NavLink to={RoutesConfig.LOGIN.path} className="text-brand-500">
                        Sign in
                    </NavLink>
                </p>
            </Block>
        </PageContainer>
    );
});
