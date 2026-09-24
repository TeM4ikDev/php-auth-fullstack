import { PageContainer } from "@/components/layout/PageContainer";
import { Block } from "@/components/ui/Block";
import { Button } from "@/components/ui/Button";
import { AuthService } from "@/services/auth.service";
import { getErrorMessage } from "@/utils/handleError";
import { CircleCheck, CircleX, MailCheck } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useSearchParams } from "react-router-dom";

type Status = "loading" | "success" | "error";

export const VerifyEmailPage = () => {
    const [searchParams] = useSearchParams();
    const token = searchParams.get("token");

    const [status, setStatus] = useState<Status>("loading");
    const [error, setError] = useState<string>("");
    const calledRef = useRef(false);

    useEffect(() => {
        if (calledRef.current) return;
        calledRef.current = true;

        if (!token) {
            setStatus("error");
            setError("Verification link is missing a token.");
            return;
        }

        AuthService.verifyEmail(token)
            .then(() => setStatus("success"))
            .catch((err) => {
                setStatus("error");
                setError(getErrorMessage(err, "Failed to verify email"));
            });
    }, [token]);

    return (
        <PageContainer>
            <Block
                className="max-w-110! w-full p-5 gap-5"
                icons={[status === "success" ? <CircleCheck /> : status === "error" ? <CircleX /> : <MailCheck />]}
                title={status === "success" ? "Email confirmed" : status === "error" ? "Verification failed" : "Confirming your email..."}
            >
                {status === "success" && (
                    <p className="text-center text-sm text-text-secondary">
                        Your email has been confirmed. You can now sign in.
                    </p>
                )}

                {status === "error" && (
                    <p className="text-center text-sm text-text-secondary">{error}</p>
                )}

                {status !== "loading" && (
                    <Button text="Go to sign in" routeKey="LOGIN" />
                )}
            </Block>
        </PageContainer>
    );
};
