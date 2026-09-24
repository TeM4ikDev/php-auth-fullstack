import { EditButton, Show } from "@refinedev/antd";
import { useGetIdentity, useInvalidate, useShow } from "@refinedev/core";
import { Button, Descriptions, Select, Space, Tag, Typography, message } from "antd";
import { useState } from "react";
import { setUserBanned, setUserRole } from "../../providers/data";
import { RoleOptions, type IUser } from "../../types";

const { Text } = Typography;

export const UserShow = () => {
  const { query } = useShow<IUser>();
  const { data, isLoading, refetch } = query;
  const record = data?.data;

  const { data: identity } = useGetIdentity<IUser>();
  const invalidate = useInvalidate();

  const [banning, setBanning] = useState(false);
  const [changingRole, setChangingRole] = useState(false);

  const isSelf = !!identity && !!record && identity.id === record.id;

  const afterMutate = () => {
    refetch();
    invalidate({ resource: "users", invalidates: ["list"] });
  };

  const toggleBan = async () => {
    if (!record) return;

    setBanning(true);

    try {
      await setUserBanned(record.id, !record.banned);
      message.success(record.banned ? "User unbanned" : "User banned");
      afterMutate();
    } catch (error) {
      message.error(error instanceof Error ? error.message : "Failed to update ban status");
    } finally {
      setBanning(false);
    }
  };

  const changeRole = async (role: string) => {
    if (!record) return;

    setChangingRole(true);

    try {
      await setUserRole(record.id, role);
      message.success("Role updated");
      afterMutate();
    } catch (error) {
      message.error(error instanceof Error ? error.message : "Failed to update role");
    } finally {
      setChangingRole(false);
    }
  };

  return (
    <Show isLoading={isLoading} headerButtons={record ? <EditButton recordItemId={record.id} /> : undefined}>
      {record && (
        <>
          <Descriptions column={1} bordered size="small">
            <Descriptions.Item label="Name">{record.name}</Descriptions.Item>
            <Descriptions.Item label="Phone">{record.phone ?? "—"}</Descriptions.Item>
            <Descriptions.Item label="Email">{record.email}</Descriptions.Item>
            <Descriptions.Item label="Status">
              <Tag color={record.banned ? "red" : "green"}>{record.banned ? "Banned" : "Active"}</Tag>
            </Descriptions.Item>
            <Descriptions.Item label="Registered">{new Date(record.createdAt).toLocaleString()}</Descriptions.Item>
            {record.deletedAt && (
              <Descriptions.Item label="Deleted">{new Date(record.deletedAt).toLocaleString()}</Descriptions.Item>
            )}
          </Descriptions>

          <Space direction="vertical" style={{ marginTop: 24 }}>
            <Text strong>Role</Text>
            <Select
              value={record.role}
              options={RoleOptions}
              style={{ width: 240 }}
              loading={changingRole}
              disabled={isSelf}
              onChange={changeRole}
            />
            {isSelf && <Text type="secondary">You cannot change your own role.</Text>}
          </Space>

          <Space direction="vertical" style={{ marginTop: 24 }}>
            <Text strong>Access</Text>
            <Button danger={!record.banned} loading={banning} disabled={isSelf} onClick={toggleBan}>
              {record.banned ? "Unban user" : "Ban user"}
            </Button>
            {isSelf && <Text type="secondary">You cannot ban yourself.</Text>}
          </Space>
        </>
      )}
    </Show>
  );
};
