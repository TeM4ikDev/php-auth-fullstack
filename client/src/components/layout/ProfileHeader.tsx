import {useStore} from "@/store/root.store";
import {User} from "lucide-react";
import {observer} from "mobx-react-lite";
import {NavLink} from "react-router-dom";

export const ProfileHeader = observer(() => {
    const {userStore: {user }} = useStore();
    const name = user?.name || "User";



    return (
        <div className="flex items-start gap-2 justify-center">
            <NavLink className="flex flex-1 items-center gap-3" to="/profile">
                <div className="h-14 w-14 shrink-0 overflow-hidden rounded-full border border-border-brended">
                    <div className="flex h-full w-full items-center justify-center bg-pablo-card">
                        <User className="h-6 w-6 text-text-secondary"/>
                    </div>
                </div>
                <div className="flex flex-col gap-1">
                    <p className="text-sm leading-[1.125rem] text-text-primary">{name}</p>
                    {user?.email && (
                        <p className="text-[0.625rem] leading-[0.875rem] text-text-secondary">
                            {user.email}
                        </p>
                    )}
                </div>
            </NavLink>
        </div>
    );
});
