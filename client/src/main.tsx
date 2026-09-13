import {StrictMode} from 'react'
import {createRoot} from 'react-dom/client'
import {StoreProvider} from '@/store/root.store'
import {ToastContainer} from 'react-toastify'
import { GoogleOAuthProvider } from '@react-oauth/google'

import './index.css'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <StoreProvider>
            <GoogleOAuthProvider clientId={'1026970301384-bi5knnci1e5ngs3ga2au87squ9p3f2mv.apps.googleusercontent.com'}>
                <App />
            </GoogleOAuthProvider>

            <ToastContainer
                position="top-right"
                autoClose={1500}
                className="flex flex-col gap-1 my-2 min-w-50 p-2 "
                toastClassName={(props) =>
                    (props?.defaultClassName ?? "") +
                    "!backdrop-blur-sm !bg-[#232323] !mb-0 !w-full !rounded-xl !h-auto !p-2 !text-white !flex !items-center !gap-0.5 !text-base !font-bold !select-none !min-h-12"
                }
                hideProgressBar={true}
                closeButton={false}
                draggable
                draggableDirection="x"
            />
        </StoreProvider>


    </StrictMode>,
)
