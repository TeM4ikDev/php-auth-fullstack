// Объект вместо enum: шаблон Vite включает erasableSyntaxOnly, где enum запрещён.
// Использование не меняется — UserRoles.Admin как значение, UserRoles как тип.
export const UserRoles = {
    Admin: 'ADMIN',
    User: 'USER',
} as const;

export type UserRoles = (typeof UserRoles)[keyof typeof UserRoles];

/** Гасит ошибку запроса и отдаёт null — чтобы вызывающий код не оборачивал всё в try/catch. */
export async function onRequest<T>(request: Promise<T>): Promise<T | null> {
    try {
        return await request;
    } catch (err) {
        console.error(err);
        return null;
    }
}
