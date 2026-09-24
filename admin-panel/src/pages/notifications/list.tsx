import { List, useTable } from "@refinedev/antd";
import type { HttpError } from "@refinedev/core";
import { Table, Tag } from "antd";
import type { INotification } from "../../types";

const statusColor = (status: string) =>
  status === "sent" ? "green" : status === "failed" ? "red" : "gold";

export const NotificationList = () => {
  const { tableProps } = useTable<INotification, HttpError>({
    resource: "notifications",
    meta: { dataProviderName: "notifications" },
  });

  return (
    <List>
      <Table {...tableProps} rowKey="id">
        <Table.Column dataIndex="event" title="Event" />
        <Table.Column dataIndex="channel" title="Channel" />
        <Table.Column dataIndex="recipient" title="Recipient" />
        <Table.Column
          dataIndex="status"
          title="Status"
          render={(value: string) => <Tag color={statusColor(value)}>{value}</Tag>}
        />
        <Table.Column dataIndex="attempts" title="Attempts" />
        <Table.Column dataIndex="createdAt" title="Created" render={(value) => new Date(value).toLocaleString()} />
      </Table>
    </List>
  );
};
