import { Edit, useForm } from "@refinedev/antd";
import { useGetIdentity } from "@refinedev/core";
import { Form, Input, Select, Switch } from "antd";
import { RoleOptions, type IUser } from "../../types";

// Backend's PATCH /api/admin/users/{id} validates the full AdminUpdateUserDto,
// so role and banned travel with the form even though only name/phone/email are usually touched.
export const UserEdit = () => {
  const { formProps, saveButtonProps, query } = useForm<IUser>();
  const record = query?.data?.data;

  const { data: identity } = useGetIdentity<IUser>();
  const isSelf = !!identity && !!record && identity.id === record.id;

  return (
    <Edit saveButtonProps={saveButtonProps}>
      <Form {...formProps} layout="vertical">
        <Form.Item label="Name" name="name" rules={[{ required: true }]}>
          <Input />
        </Form.Item>
        <Form.Item label="Phone" name="phone">
          <Input />
        </Form.Item>
        <Form.Item label="Email" name="email" rules={[{ required: true, type: "email" }]}>
          <Input />
        </Form.Item>
        <Form.Item label="Role" name="role" rules={[{ required: true }]}>
          <Select options={RoleOptions} disabled={isSelf} />
        </Form.Item>
        <Form.Item
          label="Banned"
          name="banned"
          valuePropName="checked"
          extra={isSelf ? "You cannot ban yourself." : undefined}
        >
          <Switch disabled={isSelf} />
        </Form.Item>
      </Form>
    </Edit>
  );
};
