import { AuthService } from "@/services/auth.service";
import { UserService } from "@/services/user.service";
import type { ILoginPayload, IRegisterPayload, IUser } from "@/types/auth";
import {
    getTokenFromLocalStorage,
    removeTokenFromLocalStorage,
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
            console.error("Ошибка при получении профиля:", error);
            runInAction(() => this.logout());
        }
    };

    signIn = async (payload: ILoginPayload) => {
        const { token, user } = await AuthService.login(payload);
        setTokenToLocalStorage(token);
        runInAction(() => this.login(user));
    };

    signUp = async (payload: IRegisterPayload) => {
        const { token, user } = await AuthService.register(payload);
        setTokenToLocalStorage(token);
        runInAction(() => this.login(user));
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
