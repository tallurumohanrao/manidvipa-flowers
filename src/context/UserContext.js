"use client";

import Cookies from "js-cookie";
import React, { createContext, useContext, useEffect, useState } from "react";
import { generateRandomString } from "../../hook/useUserToken";

const UserContext = createContext();
const ToastContext = createContext();
const CartCountContext = createContext();
const WatchlistContext = createContext();

export const useUser = () => useContext(UserContext);
export const useToast = () => useContext(ToastContext);
export const useCartCount = () => useContext(CartCountContext);
export const useWatchlistCount = () => useContext(WatchlistContext);

export const UserProvider = ({ children }) => {
  const [guestSession, setGuestSession] = useState("");
  const [toast, setToast] = useState({ message: "", type: "", visible: false });
  const [cartCount, setCartCount] = useState(0);
  const [watchlistCount, setWatchlistCount] = useState(0);
  const [isAuthenticated, setIsAuthenticated] = useState("");

  const showToast = (message, type = "success") => {
    setToast({ message, type, visible: true });
    setTimeout(() => {
      setToast({ message: "", type: "", visible: false });
    }, 1500);
  };

  const closeToast = () => {
    setToast({ message: "", type: "", visible: false });
  };

  const addCartCount = () => {
    setCartCount((prev) => prev + 1);
  };

  const resetCartCount = () => {
    setCartCount(0);
  };

  const decreaseCartCount = () => {
    setCartCount((prev) => Math.max(0, prev - 1));
  };

  const addWatchlistCount = () => {
    setWatchlistCount((prev) => prev + 1);
  };

  const decreaseWatchlistCount = () => {
    setWatchlistCount((prev) => prev - 1);
  };

  useEffect(() => {
    setGuestSession(generateRandomString(16));
    setIsAuthenticated(Boolean(Cookies.get("userSession")));
  }, []);

  const logout = () => {
    Cookies.remove("userSession", { path: "/" });
    setIsAuthenticated(false);
  };

  return (
    <UserContext.Provider
      value={{
        isAuthenticated,
        setIsAuthenticated,
        logout,
        guestSession,
      }}
    >
      <ToastContext.Provider value={{ toast, showToast, closeToast }}>
        <WatchlistContext.Provider
          value={{
            watchlistCount,
            setWatchlistCount,
            addWatchlistCount,
            decreaseWatchlistCount,
          }}
        >
          <CartCountContext.Provider
            value={{
              cartCount,
              setCartCount,
              addCartCount,
              decreaseCartCount,
              resetCartCount,
            }}
          >
            {children}
          </CartCountContext.Provider>
        </WatchlistContext.Provider>
      </ToastContext.Provider>
    </UserContext.Provider>
  );
};
