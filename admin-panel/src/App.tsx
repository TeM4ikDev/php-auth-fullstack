import { Authenticated, CanAccess, Refine, useCan } from "@refinedev/core";
import { DevtoolsPanel, DevtoolsProvider } from "@refinedev/devtools";
import { RefineKbar, RefineKbarProvider } from "@refinedev/kbar";

import { ErrorComponent, ThemedLayout, ThemedSider, useNotificationProvider } from "@refinedev/antd";
import "@refinedev/antd/dist/reset.css";

import routerProvider, {
  CatchAllNavigate,
  DocumentTitleHandler,
  NavigateToResource,
  UnsavedChangesNotifier,
} from "@refinedev/react-router";
import { App as AntdApp } from "antd";
import { BrowserRouter, Outlet, Route, Routes } from "react-router";
import { Header } from "./components/header";
import { ColorModeContextProvider } from "./contexts/color-mode";
import { Login } from "./pages/login";
import { NotificationList } from "./pages/notifications";
import { UserEdit, UserList, UserShow } from "./pages/users";
import { accessControlProvider } from "./providers/accessControl";
import { authProvider } from "./providers/auth";
import { dataProvider, notificationsDataProvider } from "./providers/data";

/** Analyst не видит users — первый доступный ему ресурс это notifications. */
const IndexRedirect = () => {
  const { data, isLoading } = useCan({ resource: "users", action: "list" });

  if (isLoading) {
    return null;
  }

  return <NavigateToResource resource={data?.can ? "users" : "notifications"} />;
};

function App() {
  return (
    <BrowserRouter>
      <RefineKbarProvider>
        <ColorModeContextProvider>
          <AntdApp>
            <DevtoolsProvider>
              <Refine
                dataProvider={{ default: dataProvider, notifications: notificationsDataProvider }}
                notificationProvider={useNotificationProvider}
                routerProvider={routerProvider}
                authProvider={authProvider}
                accessControlProvider={accessControlProvider}
                resources={[
                  {
                    name: "users",
                    list: "/users",
                    show: "/users/show/:id",
                    edit: "/users/edit/:id",
                    meta: {
                      canDelete: false,
                    },
                  },
                  {
                    name: "notifications",
                    list: "/notifications",
                    meta: {
                      dataProviderName: "notifications",
                    },
                  },
                ]}
                options={{
                  syncWithLocation: true,
                  warnWhenUnsavedChanges: true,
                  reactQuery: {
                    clientConfig: {
                      defaultOptions: {
                        queries: {
                          staleTime: 60_000,
                        },
                      },
                    },
                  },
                }}
              >
                <Routes>
                  <Route
                    element={
                      <Authenticated key="authenticated-inner" fallback={<CatchAllNavigate to="/login" />}>
                        <ThemedLayout Header={Header} Sider={(props) => <ThemedSider {...props} fixed />}>
                          <Outlet />
                        </ThemedLayout>
                      </Authenticated>
                    }
                  >
                    <Route index element={<IndexRedirect />} />
                    <Route
                      path="/users"
                      element={
                        <CanAccess resource="users" action="list" fallback={<ErrorComponent />}>
                          <Outlet />
                        </CanAccess>
                      }
                    >
                      <Route index element={<UserList />} />
                      <Route path="show/:id" element={<UserShow />} />
                      <Route path="edit/:id" element={<UserEdit />} />
                    </Route>
                    <Route path="/notifications">
                      <Route index element={<NotificationList />} />
                    </Route>
                    <Route path="*" element={<ErrorComponent />} />
                  </Route>
                  <Route
                    element={
                      <Authenticated key="authenticated-outer" fallback={<Outlet />}>
                        <NavigateToResource />
                      </Authenticated>
                    }
                  >
                    <Route path="/login" element={<Login />} />
                  </Route>
                </Routes>

                <RefineKbar />
                <UnsavedChangesNotifier />
                <DocumentTitleHandler />
              </Refine>
              <DevtoolsPanel />
            </DevtoolsProvider>
          </AntdApp>
        </ColorModeContextProvider>
      </RefineKbarProvider>
    </BrowserRouter>
  );
}

export default App;
