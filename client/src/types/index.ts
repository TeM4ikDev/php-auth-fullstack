// Object instead of enum: the Vite template enables erasableSyntaxOnly, which forbids enum.
// Usage stays the same — UserRoles.Admin as a value, UserRoles as a type.
export const UserRoles = {
    Admin: 'ADMIN',
    User: 'USER',
} as const;

export type UserRoles = (typeof UserRoles)[keyof typeof UserRoles];

/** Swallows a request error and returns null so the caller doesn't need its own try/catch. */
export async function onRequest<T>(request: Promise<T>): Promise<T | null> {
    try {
        return await request;
    } catch (err) {
        console.error(err);
        return null;
    }
}
