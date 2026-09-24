import type { UserRoles } from "./index";

export interface IUser {
    id: string;
    name: string;
    phone: string | null;
    email: string;
    role: UserRoles;
    banned: boolean;
    deletedAt?: string | null;
    createdAt?: string;
    updatedAt?: string | null;
}

export interface ILoginPayload {
    email: string;
    password: string;
}

export interface IRegisterPayload extends ILoginPayload {
    name: string;
    phone?: string;
}

export interface IUpdateProfilePayload {
    name: string;
    phone?: string | null;
    email: string;
}

export interface IChangePasswordPayload {
    currentPassword: string;
    newPassword: string;
}

export interface IAuthResponse {
    token: string;
    user: IUser;
}
