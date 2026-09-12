import React, { createContext, useContext } from 'react';
import userStore from './user.store';

const store = {
    userStore,
};

export const StoreContext = createContext(store);

export const useStore = () => {
    return useContext(StoreContext);
};

interface IStoreProviderProps {
    children: React.ReactNode;
}

export const StoreProvider: React.FC<IStoreProviderProps> = ({ children }) => {
    return <StoreContext.Provider value={store}>{children}</StoreContext.Provider>;
};
