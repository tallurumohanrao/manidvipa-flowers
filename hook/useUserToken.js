import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { fetchUser } from "./userCookie";
import Cookies from "js-cookie";

export const useUserToken = () => {
  const [userToken, setUserToken] = useState(null);
  const router = useRouter();

  useEffect(() => {
    const token = fetchUser();
    if (token) {
      setUserToken(token);
    }
  }, [router]);

  return userToken;
};

export function generateRandomString(length) {
  const cookieOptions = {
    secure:
      typeof window !== "undefined" && window.location.protocol === "https:",
    sameSite: "Lax",
    expires: 730,
    path: "/",
  };
  const guestSession = Cookies.get("guestSession");

  if (guestSession) {
    Cookies.set("guestSession", guestSession, cookieOptions);
    return guestSession;
  }

  const characters =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let result = "";

  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * characters.length);
    result += characters[randomIndex];
  }

  Cookies.set("guestSession", result, cookieOptions);

  return result;
}
