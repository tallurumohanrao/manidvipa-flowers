"use client";

import React, { useCallback, useEffect, useState } from "react";
import styles from "@/scss/pages/checkout.module.scss";
import { useToast } from "@/app/(pages)/context/page";
import { fetchListingData, formatPrice } from "../../hook/userCookie";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

const fetchData = async (guestSession, userToken, setCouponData) => {
  if (!guestSession) return;

  try {
    const res = await fetch(
      `${url}/get-cart-coupon?cart_session=${guestSession}`,
      {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          ...(userToken && { Authorization: `Bearer ${userToken}` }),
        },
        next: { revalidate: 60 },
        // cache: "no-store",
      }
    );

    if (!res.ok) {
      const errorData = await res.text();
      console.error("Fetch Error:", errorData);
      throw new Error(`Failed to fetch cart data: ${errorData}`);
    }

    const result = await res.json();
    setCouponData(result);
  } catch (error) {
    console.error("Error fetching cart data:", error);
  }
};

const CheckoutCoupon = ({ onAmountChange, guestSession, userToken }) => {
  const [couponCode, setCouponCode] = useState("");
  const [couponData, setCouponData] = useState({});
  const { showToast } = useToast();

  const debouncedFetchData = useCallback(fetchData, []);
  useEffect(() => {
    debouncedFetchData(guestSession, userToken, setCouponData);
  }, [guestSession, userToken, setCouponData, debouncedFetchData]);

  // useEffect(() => {
  //   userTokenRef.current = userToken;
  // }, [userToken]);

  // const fetchWithRetry = useCallback(
  //   async (
  //     endpoint,
  //     method = "GET",
  //     body = null,
  //     retries = 3,
  //     delay = 2000
  //   ) => {
  //     console.log("2");

  //     for (let i = 0; i < retries; i++) {
  //       const response = await fetchListingData(
  //         method,
  //         endpoint,
  //         userTokenRef.current,
  //         body
  //       );
  //       if (response.status === 429) {
  //         console.warn(`Rate limited. Retrying in ${delay}ms...`);
  //         await new Promise((res) => setTimeout(res, delay));
  //         delay *= 2; // Exponential backoff
  //       } else {
  //         return response;
  //       }
  //     }
  //     throw new Error("Too many retries, API still rate-limited.");
  //   },
  //   []
  // );

  // const fetchWatchlist = useCallback(async () => {
  //   console.log("1");
  //   if (isFetchingRef.current) return; // Stop duplicate calls
  //   isFetchingRef.current = true;

  //   try {
  //     const cachedData = localStorage.getItem("couponData");
  //     if (cachedData) {
  //       setCouponData(JSON.parse(cachedData));
  //     } else if (guestSession) {
  //       const response = await fetchWithRetry(
  //         `get-cart-coupon?cart_session=${guestSession}`
  //       );
  //       if (response.success) {
  //         setCouponData(response);
  //         localStorage.setItem("couponData", JSON.stringify(response));
  //         onAmountChange();
  //       }
  //     }
  //   } catch (error) {
  //     console.error("Failed to fetch watchlist:", error);
  //   } finally {
  //     isFetchingRef.current = false;
  //   }
  // }, [guestSession, onAmountChange, fetchWithRetry]);

  // useEffect(() => {
  //   if (!guestSession) return;
  //   fetchWatchlist();
  // }, [guestSession, fetchWatchlist]);

  // useEffect(() => {
  //   const cachedData = localStorage.getItem("couponData");
  //   if (!isFetching && (cachedData || couponCode)) {
  //     setCouponData(JSON.parse(cachedData));
  //     fetchWatchlist(); // This might be causing multiple calls
  //   }
  //   // if (cachedData || couponCode) {
  //   //   setCouponData(JSON.parse(cachedData));
  //   //   fetchWatchlist();
  //   // }
  // }, [couponCode, fetchWatchlist, isFetching]);

  const handleApplyCoupon = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch(`${url}/add-coupon`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: userToken ? `Bearer ${userToken}` : undefined,
        },
        body: JSON.stringify({
          coupon_code: couponCode,
          cart_session: guestSession,
        }),
      });

      const data = await response.json();

      if (data.success) {
        showToast(data.message);
        // localStorage.removeItem("couponData");
        debouncedFetchData(guestSession, userToken, setCouponData);
        onAmountChange();

        // fetchWatchlist();
      } else {
        showToast(data.message, "error");
      }
    } catch (error) {
      console.error("Error applying coupon:", error);
    }
  };

  const handleRemove = async () => {
    try {
      const response = await fetchListingData(
        "DELETE",
        `remove-coupon?cart_session=${guestSession}`
      );
      if (response.success) {
        showToast(response.message);
        setCouponData({});
        // localStorage.removeItem("couponData");
        onAmountChange();
      }
    } catch (error) {
      console.error("Failed to remove coupon:", error);
    }
  };

  return (
    <>
      <div className={`my-2 ${styles.couponCode}`}>
        <label htmlFor="couponCode">Coupon code</label>
        <form onSubmit={handleApplyCoupon} className={styles.input_group}>
          <input
            type="text"
            id="couponCode"
            className="form-control"
            placeholder="Enter your coupon code"
            value={couponCode}
            onChange={(e) => setCouponCode(e.target.value)}
            required
          />
          <button className="green-but" type="submit">
            Apply
          </button>
        </form>
        {couponData?.success && (
          <div className={styles.coupon_applied_msg}>
            <h6>
              Congrats! You Saved {formatPrice(couponData?.data?.discount)}
            </h6>
            <button
              type="button"
              className="primary-but"
              onClick={handleRemove}
            >
              Remove
            </button>
          </div>
        )}
      </div>
    </>
  );
};

export default CheckoutCoupon;
