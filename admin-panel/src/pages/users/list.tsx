import { EditButton, List, ShowButton, useTable } from "@refinedev/antd";
import type { HttpError } from "@refinedev/core";
import { Form, Input, Space, Table, Tag } from "antd";
import { RoleOptions, type IUser } from "../../types";

const roleLabel = (role: string) =>
  RoleOptions.find((option) => option.value === role)?.label ?? role;

export const UserList = () => {
  const { tableProps, searchFormProps } = useTable<IUser, HttpError, { search: string }>({
    onSearch: (values) => [{ field: "search", operator: "contains", value: values.search }],
  });

  return (
    <List>
      <Form {...searchFormProps} layout="inline" style={{ marginBottom: 16 }}>
        <Form.Item name="search">
          <Input.Search
            placeholder="Search by name, email or phone"
            allowClear
            onSearch={() => searchFormProps.form?.submit()}
            style={{ width: 320 }}
          />
        </Form.Item>
      </Form>

      <Table {...tableProps} rowKey="id">
        <Table.Column dataIndex="name" title="Name" />
        <Table.Column dataIndex="phone" title="Phone" render={(value) => value ?? "—"} />
        <Table.Column dataIndex="email" title="Email" />
        <Table.Column
          dataIndex="role"
          title="Role"
          render={(value: string) => <Tag color={value === "ADMIN" ? "gold" : value === "ANALYST" ? "blue" : "default"}>{roleLabel(value)}</Tag>}
        />
        <Table.Column
          dataIndex="banned"
          title="Status"
          render={(value: boolean) => <Tag color={value ? "red" : "green"}>{value ? "Banned" : "Active"}</Tag>}
        />
        <Table.Column dataIndex="createdAt" title="Registered" render={(value) => new Date(value).toLocaleDateString()} />
        <Table.Column
          title="Actions"
          dataIndex="actions"
          render={(_, record: IUser) => (
            <Space>
              <ShowButton hideText size="small" recordItemId={record.id} />
              <EditButton hideText size="small" recordItemId={record.id} />
            </Space>
          )}
        />
      </Table>
    </List>
  );
};
