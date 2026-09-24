import { PageContainer } from "@/components/layout/PageContainer";
import { Badge } from "@/components/ui/Badge";
import { Block } from "@/components/ui/Block";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { useStore } from "@/store/root.store";
import { RoleLabels, UserRoles } from "@/types";
import { RoutesConfig } from "@/types/pagesConfig";
import { getErrorMessage } from "@/utils/handleError";
import { KeyRound, LogOut, Trash2, UserRound } from "lucide-react";
import { observer } from "mobx-react-lite";
import { useEffect, useState, type FormEvent } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { toast } from "react-toastify";

const MIN_PASSWORD_LENGTH = 8;

export const ProfilePage = observer(() => {
    const { userStore } = useStore();
    const { user } = userStore;
    const navigate = useNavigate();

    const [name, setName] = useState("");
    const [phone, setPhone] = useState("");
    const [email, setEmail] = useState("");
    const [savingProfile, setSavingProfile] = useState(false);

    const [currentPassword, setCurrentPassword] = useState("");
    const [newPassword, setNewPassword] = useState("");
    const [savingPassword, setSavingPassword] = useState(false);

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        if (!user) return;

        setName(user.name);
        setPhone(user.phone ?? "");
        setEmail(user.email);
    }, [user]);

    if (!user) {
        return <Navigate to={RoutesConfig.LOGIN.path} replace />;
    }

    const profileChanged =
        name.trim() !== user.name || phone.trim() !== (user.phone ?? "") || email.trim() !== user.email;
    const canSaveProfile = !!name.trim() && !!email.trim() && profileChanged;

    const passwordError =
        newPassword && newPassword.length < MIN_PASSWORD_LENGTH
            ? `At least ${MIN_PASSWORD_LENGTH} characters`
            : undefined;
    const canSavePassword = !!currentPassword && !!newPassword && !passwordError;

    const handleSaveProfile = async (e: FormEvent) => {
        e.preventDefault();
        if (!canSaveProfile) return;

        setSavingProfile(true);

        try {
            await userStore.updateProfile({
                name: name.trim(),
                phone: phone.trim() || null,
                email: email.trim(),
            });
            toast.success("Profile updated");
        } catch (error) {
            toast.error(getErrorMessage(error, "Failed to update profile"));
        } finally {
            setSavingProfile(false);
        }
    };

    const handleChangePassword = async (e: FormEvent) => {
        e.preventDefault();
        if (!canSavePassword) return;

        setSavingPassword(true);

        try {
            await userStore.changePassword({ currentPassword, newPassword });
            setCurrentPassword("");
            setNewPassword("");
            toast.success("Password changed");
        } catch (error) {
            toast.error(getErrorMessage(error, "Failed to change password"));
        } finally {
            setSavingPassword(false);
        }
    };

    const handleDelete = async () => {
        setDeleting(true);

        try {
            await userStore.deleteAccount();
            setDeleteOpen(false);
            navigate(RoutesConfig.REGISTER.path, { replace: true });
            toast.success("Account deleted");
        } catch (error) {
            toast.error(getErrorMessage(error, "Failed to delete account"));
        } finally {
            setDeleting(false);
        }
    };

    const handleLogout = () => {
        userStore.logout();
        navigate(RoutesConfig.LOGIN.path, { replace: true });
    };

    return (
        <PageContainer itemsStart className="gap-5">
            <Block
                className="max-w-140! w-full p-5 gap-5"
                icons={[<UserRound />]}
                title="Profile"
                subtitle={<Badge text={RoleLabels[user.role] ?? user.role} tone={user.role === UserRoles.Admin ? "brand" : "neutral"} />}
            >
                <form onSubmit={handleSaveProfile} className="flex flex-col gap-3">
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
                        placeholder="Phone"
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

                    <Button
                        text="Save changes"
                        formSubmit
                        loading={savingProfile}
                        disabled={!canSaveProfile}
                    />
                </form>
            </Block>

            <Block className="max-w-140! w-full p-5 gap-5" icons={[<KeyRound />]} title="Change password">
                <form onSubmit={handleChangePassword} className="flex flex-col gap-3">
                    <Input
                        type="password"
                        name="currentPassword"
                        placeholder="Current password"
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        isRequired
                    />
                    <Input
                        type="password"
                        name="newPassword"
                        placeholder="New password"
                        value={newPassword}
                        onChange={(e) => setNewPassword(e.target.value)}
                        error={passwordError}
                        isRequired
                    />

                    <Button
                        text="Change password"
                        formSubmit
                        loading={savingPassword}
                        disabled={!canSavePassword}
                    />
                </form>
            </Block>

            <div className="flex max-w-140! w-full flex-col gap-2 sm:flex-row">
                <Button
                    text="Log out"
                    color="transparent"
                    icon={<LogOut className="h-4 w-4" />}
                    onClick={handleLogout}
                />
                <Button
                    text="Delete account"
                    color="red"
                    icon={<Trash2 className="h-4 w-4" />}
                    onClick={() => setDeleteOpen(true)}
                />
            </div>

            <Modal
                isOpen={deleteOpen}
                setIsOpen={setDeleteOpen}
                title="Delete account?"
                description="Your profile will be deactivated and you will not be able to sign in with this email again."
            >
                <Button text="Delete" color="red" loading={deleting} onClick={handleDelete} />
                <Button text="Cancel" color="transparent" onClick={() => setDeleteOpen(false)} />
            </Modal>
        </PageContainer>
    );
});
