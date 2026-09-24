export const UserRoles = {
  Customer: "CUSTOMER",
  Analyst: "ANALYST",
  Admin: "ADMIN",
} as const;

export type UserRole = (typeof UserRoles)[keyof typeof UserRoles];

export const RoleOptions: { label: string; value: UserRole }[] = [
  { label: "Customer", value: UserRoles.Customer },
  { label: "Analyst", value: UserRoles.Analyst },
  { label: "Administrator", value: UserRoles.Admin },
];

export interface IUser {
  id: string;
  name: string;
  phone: string | null;
  email: string;
  role: UserRole;
  banned: boolean;
  deletedAt: string | null;
  createdAt: string;
  updatedAt: string | null;
}

export const NotificationChannels = {
  Email: "email",
} as const;

export type NotificationChannel = (typeof NotificationChannels)[keyof typeof NotificationChannels];

export const NotificationStatuses = {
  Pending: "pending",
  Sent: "sent",
  Failed: "failed",
} as const;

export type NotificationStatus = (typeof NotificationStatuses)[keyof typeof NotificationStatuses];

export interface INotification {
  id: string;
  event: string;
  channel: NotificationChannel;
  recipient: string;
  payload: Record<string, unknown>;
  status: NotificationStatus;
  attempts: number;
  createdAt: string;
  updatedAt: string;
  sentAt: string | null;
}
