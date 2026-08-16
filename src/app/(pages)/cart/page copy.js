"use client";
import React, { useCallback, useEffect, useState } from "react";
import styles from "@/scss/pages/cartDetails.module.scss";
import Banner from "@/components/banner";
import { FaRegHeart } from "react-icons/fa";
import { HiOutlineSwitchHorizontal } from "react-icons/hi";
import { IoClose } from "react-icons/io5";
import QuantityComponent from "@/components/quantityComponent";
import {
  fetchListingData,
  fetchUser,
  formatPrice,
} from "../../../../hook/userCookie";
import { useToast, useUser, useWatchlistCount } from "@/context/UserContext";
import Cart from "@/components/Cart";
import Toast from "@/components/Toast";
import { useCartCount } from "@/context/UserContext";
import Image from "next/image";
import Link from "next/link";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

export default function CartDetails() {
  const { guestSession, userToken } = useUser();
  const { showToast } = useToast();
  const [data, setData] = useState({
    data: [],
    total: 0, // Without offer
    subTotal: 0, // With offer
  });
  const [message, setMessage] = useState("");
  const { decreaseCartCount } = useCartCount();
  const { addWatchlistCount } = useWatchlistCount();

  // Helper function to calculate totals
  const calculateTotals = (cartData) => {
    const total = cartData.reduce(
      (acc, item) => acc + item.list_price * item.quantity,
      0
    ); // Without offer
    const subTotal = cartData.reduce(
      (acc, item) => acc + item.sell_price * item.quantity,
      0
    ); // With offer
    return { total, subTotal };
  };

  // Fetch cart data when userDetails is available
  const fetchData = useCallback(async () => {
    try {
      const res = await fetch(`${url}/get-cart?cart_session=${guestSession}`, {
        headers: {
          "Content-Type": "application/json",
          Authorization: userToken ? `Bearer ${userToken}` : undefined,
        },
      });

      // Check if the response is JSON
      const contentType = res.headers.get("Content-Type");
      if (contentType && contentType.includes("application/json")) {
        const result = await res.json();
        setMessage(result.message);

        if (result?.data) {
          const updatedData = result.data.map((item) => ({
            ...item,
            totalPrice: item.sell_price * item.quantity, // Precompute discounted price
          }));

          // Calculate totals
          const { total, subTotal } = calculateTotals(result.data);

          setData({
            data: updatedData,
            total,
            subTotal,
          });
        }
      } else {
        const html = await res.text(); // Get the raw HTML content for debugging
        console.error("Unexpected response type:", html);
      }
    } catch (error) {
      console.error("Error fetching cart data:", error);
    }
  }, [guestSession, userToken]);
  useEffect(() => {
    fetchData();
  }, [fetchData]);
  const handleQuantityChange = (newQuantity, index) => {
    const updatedData = [...data.data];
    updatedData[index].quantity = newQuantity;
    updatedData[index].totalPrice = updatedData[index].sell_price * newQuantity;
    const { total, subTotal } = calculateTotals(updatedData);

    setData({
      data: updatedData,
      total,
      subTotal,
    });
  };

  const handleUpdate = async () => {
    try {
      const payload = {
        cart_session: guestSession,
        products: data.data?.map((item) => ({
          cart_id: item.cart_id,
          quantity: item.quantity,
        })),
      };

      const response = await fetch(`${url}/update-cart`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: userToken ? `Bearer ${userToken}` : undefined,
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const errorResult = await response.json();
        showToast(errorResult.message || "Failed to update cart", "error");
        return;
      }

      const contentType = response.headers.get("Content-Type");
      if (contentType && contentType.includes("application/json")) {
        const result = await response.json();

        if (result.success) {
          showToast(result.message || "Cart updated successfully", "success");
          setMessage(result.message);
        } else {
          showToast(result.message || "Failed to update cart", "error");
          setMessage(result.message);
        }
      } else {
        console.error("Unexpected response type:", response);
        showToast(
          "Unexpected server response. Please try again later.",
          "error"
        );
      }
    } catch (error) {
      console.error("Error during cart update:", error);
      showToast(
        "An unexpected error occurred. Please try again later.",
        "error"
      );
    }
  };

  const handleDelete = async (e, cartId) => {
    e.preventDefault();

    try {
      const response = await fetch(`${url}/delete-cart`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          Authorization: userToken ? `Bearer ${userToken}` : undefined,
        },
        body: JSON.stringify({
          cart_id: cartId,
          cart_session: guestSession,
        }),
      });

      const result = await response.json();

      if (!response.ok) {
        showToast(result.message || "Failed to delete the item", "error");
        console.error("Server Error:", response.status);
        return;
      } else {
        showToast(result.message || "Item deleted successfully", "error");
        decreaseCartCount();
      }

      showToast(result.message || "Item deleted successfully", "error");

      if (result.success) {
        const updatedData = data.data.filter((item) => item.cart_id !== cartId);
        const { total, subTotal } = calculateTotals(updatedData);

        setData({
          data: updatedData,
          total,
          subTotal,
        });
      } else {
        console.warn("Deletion failed:", result.message);
      }
    } catch (error) {
      console.error("Error deleting item:", error);
      showToast(
        "An unexpected error occurred. Please try again later.",
        "error"
      );
    }
  };

  const handleWishlistClick = async (productId) => {
    if (!userToken) {
      console.warn("User not authenticated");
      return;
    }

    try {
      const response = await fetchListingData(
        "POST",
        "add-wishlist",
        userToken,
        { product_id: productId }
      );

      if (response) {
        showToast(response.message);
        addWatchlistCount();
      }
    } catch (error) {
      console.error("Error adding to wishlist:", error);
    }
  };

  return (
    <>
      {data.data?.length > 0 ? (
        <>
          {/* BANNER SECTION START */}
          <Banner title="Shopping Cart" />
          {/* BANNER SECTION END */}
          {/* CART DETAILS SECTION START */}
          <section className="my-5">
            <div className={`container ${styles.cart_details_sec}`}>
              <h3 className={styles.cart_det_head}>Cart Details</h3>
              <div className={`row justify-content-between`}>
                <div className={`col-lg-7 col-md-8 ${styles.cart_list}`}>
                  <h5 className={styles.list_head}>
                    <span>{data.data?.length || 0} Items In Cart </span>
                    {/* <button
                      type="button"
                      className="green-but"
                      // onClick={handleUpdate}
                    >
                      Update Quantity
                    </button> */}
                  </h5>

                  {data.data?.map((value, index) => (
                    <div className={styles.card} key={index}>
                      <div className="row w-100">
                        <div
                          className="col-sm-3"
                          style={{ display: "flex", justifyContent: "start" }}
                        >
                          <Image
                            src={
                              value.image === null
                                ? "/assets/images/no-image.png"
                                : `${IMG_URL}/${value.image}`
                            }
                            alt={value.product_title}
                            width={0}
                            height={0}
                            sizes="100vw"
                            style={{
                              height: "100%",
                              width: "100%",
                            }}
                          />
                        </div>
                        <div className="col-sm-6">
                          <ul className={styles.list}>
                            <li>
                              <h5>{value.product_title}</h5>
                            </li>
                            <li>
                              <span>{value.weight}</span>
                            </li>
                            <li>
                              <ul className={styles.favourites}>
                                {Array(5)
                                  .fill()
                                  .map((_, i) => (
                                    <li key={i}>
                                      <Image
                                        src="/assets/icons/star-yellow.png"
                                        alt="star"
                                        width={0}
                                        height={0}
                                        sizes="100vw"
                                        style={{
                                          height: "100%",
                                          width: "100%",
                                        }}
                                      />
                                    </li>
                                  ))}
                              </ul>
                            </li>
                            <li>
                              <ul className={styles.actions}>
                                <li>
                                  <QuantityComponent
                                    quantity={value.quantity}
                                    onQuantityChange={(newQuantity) =>
                                      handleQuantityChange(newQuantity, index)
                                    }
                                  />
                                </li>
                                <li>
                                  <div
                                    onClick={() =>
                                      handleWishlistClick(value.product_id)
                                    }
                                  >
                                    <FaRegHeart />
                                  </div>
                                </li>
                                <li>
                                  <div className={styles.close_icon}>
                                    <IoClose
                                      onClick={(e) =>
                                        handleDelete(e, value.cart_id)
                                      }
                                    />
                                  </div>
                                </li>
                              </ul>
                              <span> {message && <p>{message}</p>}</span>
                            </li>
                          </ul>
                        </div>
                        <div className="col-sm-3 mb-xs-3 d-flex align-items-center justify-content-center">
                          <div className={styles.cost}>
                            <h6>{formatPrice(value?.totalPrice)}</h6>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="col-lg-4 col-md-4 col-sm-12">
                  <div className={styles.cart_total_price_sec}>
                    <h5>Total</h5>
                    <div className="row">
                      <div className="col-6 d-flex justify-content-end">
                        <p>Total :</p>
                      </div>
                      <div className="col-4">
                        <p className="text-center">
                          {" "}
                          {formatPrice(data?.total)}
                        </p>
                      </div>
                      <div className="col-6 d-flex justify-content-end">
                        <p>Coupon Applied :</p>
                      </div>
                      <div className="col-4">
                        <p className={styles.coupon_applied_amt}>₹ 2,199</p>
                      </div>
                      <div className="col-6 d-flex justify-content-end">
                        <p>Sub Total :</p>
                      </div>
                      <div className="col-4">
                        <p className="text-center">
                          {formatPrice(data?.subTotal)}
                        </p>
                      </div>
                    </div>
                    <div className={styles.continue_or_check_out}>
                      <Link href="/">
                        <button className={`blue-but ${styles.blue_but}`}>
                          Continue Shopping
                        </button>
                      </Link>
                      <Link href="/checkout">
                        <button
                          type="button"
                          onClick={handleUpdate}
                          className={`green-but ${styles.green_but}`}
                        >
                          Check Out
                        </button>
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
              <Toast />
            </div>
          </section>
          {/* CART DETAILS SECTION END */}{" "}
        </>
      ) : (
        <Cart />
      )}
      <Toast />
    </>
  );
}
