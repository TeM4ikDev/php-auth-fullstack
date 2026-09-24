import { PageContainer } from "@/components/layout/PageContainer";
import { Block } from "@/components/ui/Block";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { useStore } from "@/store/root.store";
import { RoutesConfig } from "@/types/pagesConfig";
import { getErrorMessage } from "@/utils/handleError";
import { MailCheck, UserPlus } from "lucide-react";
import { observer } from "mobx-react-lite";
import { useState, type FormEvent } from "react";
import { NavLink } from "react-router-dom";
import { toast } from "react-toastify";

const MIN_PASSWORD_LENGTH = 8;

export const RegisterPage = observer(() => {
    const { userStore } = useStore();

    const [name, setName] = useState("");
    const [phone, setPhone] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [registered, setRegistered] = useState(false);

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
            await userStore.signUp({
                name: name.trim(),
                phone: phone.trim() || undefined,
                email: email.trim(),
                password,
            });
            setRegistered(true);
        } catch (error) {
            toast.error(getErrorMessage(error, "Failed to register"));
        } finally {
            setLoading(false);
        }
    };

    if (registered) {
        return (
            <PageContainer>
                <Block className="max-w-110! w-full p-5 gap-5" icons={[<MailCheck />]} title="Check your email">
                    <p className="text-center text-sm text-text-secondary">
                        We sent a confirmation link to <b>{email.trim()}</b>. Open it to activate your account,
                        then sign in.
                    </p>

                    <NavLink to={RoutesConfig.LOGIN.path} className="text-brand-500 text-center text-sm">
                        Go to sign in
                    </NavLink>
                </Block>
            </PageContainer>
        );
    }

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
                        type="tel"
                        name="phone"
                        placeholder="Phone (optional)"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
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
