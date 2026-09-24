import { apiConfig } from "@/types/pagesConfig";
import type { IChangePasswordPayload, IUpdateProfilePayload, IUser } from "@/types/auth";

class profileService {
    private instance = apiConfig.profile.baseInstance;

    async update(payload: IUpdateProfilePayload): Promise<IUser> {
        const { data } = await this.instance.patch<IUser>(apiConfig.profile.root, payload);
        return data;
    }

    async changePassword(payload: IChangePasswordPayload): Promise<void> {
        await this.instance.post(apiConfig.profile.password, payload);
    }

    async remove(): Promise<void> {
        await this.instance.delete(apiConfig.profile.root);
    }
}

export const ProfileService = new profileService();
