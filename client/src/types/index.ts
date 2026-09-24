// Object instead of an enum: the Vite template enables erasableSyntaxOnly, which forbids enums.
// Usage stays the same — UserRoles.Admin as a value, UserRoles as a type.
export const UserRoles = {
    Customer: 'CUSTOMER',
    Analyst: 'ANALYST',
    Admin: 'ADMIN',
} as const;

export type UserRoles = (typeof UserRoles)[keyof typeof UserRoles];

export const RoleLabels: Record<UserRoles, string> = {
    [UserRoles.Customer]: 'Customer',
    [UserRoles.Analyst]: 'Analyst',
    [UserRoles.Admin]: 'Administrator',
};

/** Swallows a request error and returns null so the caller doesn't need its own try/catch. */
export async function onRequest<T>(request: Promise<T>): Promise<T | null> {
    try {
        return await request;
    } catch (err) {
        console.error(err);
        return null;
    }
}
