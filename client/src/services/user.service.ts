import { apiConfig } from "@/types/pagesConfig";
import type { IUser } from "@/types/auth";

class userService {
    private instance = apiConfig.users.baseInstance;

    async getMe(): Promise<IUser> {
        const { data } = await this.instance.get<IUser>(apiConfig.users.me);
        return data;
    }
}

export const UserService = new userService();
