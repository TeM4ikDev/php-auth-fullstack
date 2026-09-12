import { apiConfig } from "@/types/pagesConfig";
import type { IAuthResponse, ILoginPayload, IRegisterPayload, IUser } from "@/types/auth";

class authService {
    private instance;
    private baseUrl;

    constructor() {
        this.instance = apiConfig.auth.baseInstance;
        this.baseUrl = apiConfig.auth;
    }

    async login(payload: ILoginPayload): Promise<IAuthResponse> {
        const { data } = await this.instance.post<IAuthResponse>(this.baseUrl.login, payload);
        return data;
    }

    async register(payload: IRegisterPayload): Promise<IAuthResponse> {
        const { data } = await this.instance.post<IAuthResponse>(this.baseUrl.register, payload);
        return data;
    }

    async getProfile(): Promise<IUser | null> {
        try {
            const { data } = await this.instance.get<IUser>(this.baseUrl.profile);
            return data;
        } catch {
            return null;
        }
    }
}

export const AuthService = new authService();
