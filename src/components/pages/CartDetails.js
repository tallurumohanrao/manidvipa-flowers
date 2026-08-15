"use client";
import React, { useState } from "react";
import styles from "@/scss/pages/cartDetails.module.scss";
import Banner from "@/components/banner";
import QuantityComponent from "@/components/quantityComponent";
import { formatPrice } from "../../../hook/userCookie";
import { useToast, useCartCount } from "@/app/(pages)/context/page";
import Cart from "@/components/Cart";
import Toast from "@/components/Toast";
import Image from "next/image";
import Link from "next/link";
import { MdDelete } from "react-icons/md";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

export default function CartDetails({
  CartDetailsData,
  guestSession,
  userToken,
}) {
  const { showToast } = useToast();

  const [data, setData] = useState({
    data: CartDetailsData?.data,
    subTotal: CartDetailsData?.totals?.sub_total?.amount,
  });

  const [message, setMessage] = useState("");
  const { decreaseCartCount } = useCartCount();

  const calculateTotals = (cartData) => {
    const subTotal = cartData.reduce(
      (acc, item) => acc + item.sell_price * item.quantity,
      0
    );
    return { subTotal };
  };

  const handleQuantityChange = (newQuantity, index) => {
    const updatedData = [...data.data];
    updatedData[index].quantity = newQuantity;
    const { subTotal } = calculateTotals(updatedData);
    setData({
      data: updatedData,
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
      console.log("result", result);
      console.log("response", response);

      if (!response.ok) {
        showToast(result.message || "Failed to delete the item", "error");
        console.error("Server Error:", response.status);
        return;
      } else {
        showToast(result.message || "Item deleted successfully", "success");
        decreaseCartCount();
      }

      showToast(result.message || "Item deleted successfully", "success");

      if (result.success) {
        const updatedData = data.data.filter((item) => item.cart_id !== cartId);
        const { subTotal } = calculateTotals(updatedData);

        setData({
          data: updatedData,
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

  return (
    <>
      {data?.data?.length > 0 ? (
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
                  </h5>

                  {data.data?.map((value, index) => (
                    <div className={styles.card} key={index}>
                      <div className="row w-100">
                        <div
                          className="col-xs-5 col-sm-3 my-auto"
                          style={{
                            display: "flex",
                            justifyContent: "start",
                            height: "160px",
                          }}
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
                        <div className="col-xs-7 col-sm-6 my-auto h-100">
                          <ul className={styles.list}>
                            <li>
                              <h5>{value.product_title}</h5>
                            </li>
                            <li>
                              <span>{value.weight}</span>
                            </li>
                            <li
                              className={`d-block d-sm-none`}
                              style={{ color: "#cc0000" }}
                            >
                              {formatPrice(value?.sell_price * value?.quantity)}
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
                                  <div className={styles.close_icon}>
                                    <MdDelete
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
                        <div className="col-sm-3 d-md-flex align-items-center justify-content-center d-none d-sm-block my-auto">
                          <div className={styles.cost}>
                            <h6>
                              {formatPrice(value?.sell_price * value?.quantity)}
                            </h6>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="col-lg-4 col-md-4 col-sm-12">
                  <div className={styles.cart_total_price_sec}>
                    <h5>Total</h5>
                    <div className={`mt-3 ${styles.cart_totals}`}>
                      <p>
                        <span className={styles.key}>
                          <strong>Subtotal:</strong>
                        </span>
                        <span className={styles.value}>
                          <strong>{formatPrice(data?.subTotal)}</strong>
                        </span>
                      </p>
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
