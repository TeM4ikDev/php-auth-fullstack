import { PageContainer } from "@/components/layout/PageContainer";
import { Button } from "@/components/ui/Button";
import { cn } from "@/utils/cn";
import type { RouteKey } from "@/types/pagesConfig";
import { SearchX } from "lucide-react";

interface NotFoundPageProps {
    title?: string;
    description?: string;
    showButton?: boolean;
    buttonText?: string;
    buttonRouteKey?: RouteKey;
    className?: string;
}

const NotFound: React.FC<NotFoundPageProps> = ({
    title = "Page not found",
    description = "Sorry, the page you're looking for doesn't exist or has been moved.",
    showButton = true,
    buttonText = "Back to home",
    buttonRouteKey = "HOME",
    className = "",
}) => {
    return (
        <PageContainer className={cn("flex min-h-[70vh] flex-col items-center justify-center text-center", className)}>
            <div className="flex max-w-md flex-col items-center gap-6 p-6">
                <div className="flex h-24 w-24 items-center justify-center rounded-full border border-pablo-border bg-pablo-card">
                    <SearchX className="h-12 w-12 text-gold-500" />
                </div>
                <div className="space-y-2">
                    <h1 className="text-2xl font-bold text-white">{title}</h1>
                    <p className="text-gray-400">{description}</p>
                </div>
                {showButton && <Button text={buttonText} routeKey={buttonRouteKey} widthMin />}
            </div>
        </PageContainer>
    );
};

export default NotFound;
