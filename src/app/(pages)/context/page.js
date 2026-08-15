"use client";
import Cookies from "js-cookie";
import React, { createContext, useContext, useState, useEffect } from "react";
// import { fetchUser } from "../../../../hook/userCookie";
import { generateRandomString } from "../../../../hook/useUserToken";

const UserContext = createContext();
const ToastContext = createContext();
const CartCountContext = createContext();
const WatchlistContext = createContext();

export const useUser = () => useContext(UserContext);
export const useToast = () => useContext(ToastContext);
export const useCartCount = () => useContext(CartCountContext);
export const useWatchlistCount = () => useContext(WatchlistContext);

export const UserProvider = ({ children }) => {
  //guest token
  const guestSession = generateRandomString(16);
  //user token
  // const userToken = fetchUser();

  // Toast Related
  const [toast, setToast] = useState({ message: "", type: "", visible: false });
  const [cartCount, setCartCount] = useState(0);
  const [watchlistCount, setWatchlistCount] = useState(0);

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
  // const decreaseCartCount = () => {
  //   setCartCount((prev) => Math.max(0, prev - 1));
  // };

  const resetCartCount = () => {
    setCartCount(0);
  };
  const decreaseCartCount = () => {
    setCartCount((prev) => prev - 1);
  };

  const addWatchlistCount = () => {
    setWatchlistCount((prev) => prev + 1);
  };
  const decreaseWatchlistCount = () => {
    setWatchlistCount((prev) => prev - 1);
  };

  // const [userToken, setUserToken] = useState(null);
  // const [userData, setUserData] = useState(null);
  const [isAuthenticated, setIsAuthenticated] = useState("");

  const checkAuthStatus = () => {
    const userCookie = Cookies.get("userSession");
    if (userCookie) {
      setIsAuthenticated(true);
    } else {
      setIsAuthenticated(false);
    }
  };

  // useEffect(() => {
  //   if (userToken) {
  //     fetchBlogData(userToken)
  //       .then((blogData) => {
  //         if (blogData) {
  //           setUserData(blogData);
  //         } else {
  //           console.log("Failed to fetch blog data");
  //         }
  //       })
  //       .catch((error) => {
  //         console.error("Error fetching blog data:", error);
  //       });
  //   }
  // }, [userToken]);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const logout = () => {
    Cookies.remove("userSession");
    setIsAuthenticated(false);
  };

  return (
    <UserContext.Provider
      value={{
        isAuthenticated,
        setIsAuthenticated,
        logout,
        // userToken,
        // userData,
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
