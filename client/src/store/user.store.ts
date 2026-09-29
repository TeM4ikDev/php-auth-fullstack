import { AuthService } from "@/services/auth.service";
import { ProfileService } from "@/services/profile.service";
import { UserService } from "@/services/user.service";
import type {
    IChangePasswordPayload,
    ILoginPayload,
    IRegisterPayload,
    IUpdateProfilePayload,
    IUser,
} from "@/types/auth";
import {
    getRefreshTokenFromLocalStorage,
    getTokenFromLocalStorage,
    removeTokenFromLocalStorage,
    setRefreshTokenToLocalStorage,
    setTokenToLocalStorage,
} from "@/utils/localstorage";
import { makeAutoObservable, runInAction } from "mobx";

class UserStore {
    user: IUser | null = null;
    isAuth: boolean = false;
    isLoading: boolean = true;
    updateTrigger: boolean = false;

    constructor() {
        makeAutoObservable(this);
        this.checkAuth();
    }

    checkAuth = async () => {
        if (!getTokenFromLocalStorage()) {
            this.logout();
            return;
        }

        this.isLoading = true;

        try {
            const user = await UserService.getMe();
            runInAction(() => this.login(user));
        } catch (error) {
            console.error("Failed to fetch profile:", error);
            runInAction(() => this.logout());
        }
    };

    signIn = async (payload: ILoginPayload) => {
        const { accessToken, refreshToken, user } = await AuthService.login(payload);
        setTokenToLocalStorage(accessToken);
        setRefreshTokenToLocalStorage(refreshToken);
        runInAction(() => this.login(user));
    };

    // Регистрация не логинит: сначала нужно подтвердить email по ссылке из письма
    signUp = async (payload: IRegisterPayload) => {
        await AuthService.register(payload);
    };

    updateProfile = async (payload: IUpdateProfilePayload) => {
        const user = await ProfileService.update(payload);
        runInAction(() => {
            this.user = user;
        });
    };

    changePassword = async (payload: IChangePasswordPayload) => {
        await ProfileService.changePassword(payload);
    };

    deleteAccount = async () => {
        await ProfileService.remove();
        runInAction(() => this.logout());
    };

    /** Пользовательский "выйти" — в отличие от logout(), ещё и отзывает сессию на бэкенде. */
    signOut = async () => {
        try {
            await AuthService.logout(getRefreshTokenFromLocalStorage());
        } catch (error) {
            console.error("Failed to revoke the session on the server:", error);
        } finally {
            this.logout();
        }
    };

    login(userData: IUser) {
        this.user = userData;
        this.isAuth = true;
        this.isLoading = false;
    }

    logout() {
        removeTokenFromLocalStorage();
        this.isAuth = false;
        this.user = null;
        this.isLoading = false;
    }

    setLoading(loading: boolean) {
        this.isLoading = loading;
    }

    updateData() {
        this.updateTrigger = !this.updateTrigger;
    }

    get userRole() {
        return this.user?.role;
    }
}

export default new UserStore();
