import type { UserRoles } from "./index";

export interface IUser {
    id: string;
    name: string;
    email: string;
    role: UserRoles;
    createdAt?: string;
}

export interface ILoginPayload {
    email: string;
    password: string;
}

export interface IRegisterPayload extends ILoginPayload {
    name: string;
}

export interface IAuthResponse {
    token: string;
    user: IUser;
}
