"use client";

import React, { useState } from "react";
import dynamic from "next/dynamic";
import styles from "@/scss/pages/watchlist.module.scss";
import Link from "next/link";
import Toast from "@/components/Toast";
import { useCartCount, useToast } from "@/app/(pages)/context/page";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { MdDelete } from "react-icons/md";
import { fetchListingData, formatPrice } from "../../../hook/userCookie";
import { useWatchlistCount } from "@/app/(pages)/context/page";

const Banner = dynamic(() => import("@/components/banner"), { ssr: false });

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const Watchlist = ({ watchlist, userToken, guestSession }) => {
  const router = useRouter();
  const [watchlistItems, setWatchlistItems] = useState(watchlist);

  const { showToast } = useToast();
  const { decreaseWatchlistCount } = useWatchlistCount();
  //default quantity
  const [quantity, setQuantity] = useState(1);
  const { addCartCount } = useCartCount();

  const handleAddcart = async (e, product_id, weight_id, action) => {
    e.preventDefault();
    const body1 = {
      cart_session: guestSession,
      product_id: product_id,
      quantity,
      weight_id: weight_id,
    };
    try {
      const cartResponse = await fetch("/api", {
        method: "POST",

        body: JSON.stringify({
          req_method: "POST",
          endpoint: "add-to-cart",
          userToken: userToken ? userToken : undefined,
          formData: body1,
        }),
      });

      const cartData = cartResponse;
      if (!cartResponse.ok) {
        showToast(cartData.message || "Failed to add to cart", "error");
        addCartCount();
        return;
      } else {
        showToast(cartData.message || "Added to cart successfully", "success");
        addCartCount();
      }
      showToast(cartData.message || "Added to cart successfully", "success");
      if (cartData.message?.toLowerCase().includes("out of stock")) {
        return;
      }

      router.push(`/${action}`);
    } catch (error) {
      console.error("Error during ADD cart:", error);
      showToast(
        "An unexpected error occurred. Please try again later.",
        "error"
      );
    }
  };

  const handleDelete = async (wishlistId) => {
    if (!userToken) return;

    try {
      const response = await fetchListingData(
        "DELETE",
        "delete-wishlist",
        userToken,
        { wishlist_id: wishlistId }
      );
      if (response?.success) {
        setWatchlistItems((prevItems) =>
          prevItems.filter((item) => item.wishlist_id !== wishlistId)
        );
        showToast("Item removed successfully!");
        decreaseWatchlistCount();
      } else {
        showToast("Failed to remove item.", "error");
      }
    } catch (error) {
      console.error("Failed to delete item:", error);
      showToast("An error occurred while removing the item.", "error");
    }
  };

  if (!watchlist || watchlist.length === 0)
    return (
      <>
        <Banner title="Watchlist" />
        <div className="container">
          <section className="my-5">
            <div className={styles.empty_shopping_cart_sec}>
              <div className={styles.cart_image}>
                <Image
                  src="/assets/images/cart-empty-1.png"
                  alt={""}
                  width={399}
                  height={226}
                  priority
                  sizes="100vw"
                />
              </div>
              <h5>Please Login to see your Watchlist.</h5>
              <button className={`primary-but ${styles.primary_but}`}>
                keep shopping
              </button>
            </div>
          </section>
        </div>
      </>
    );

  if (!userToken || watchlistItems.length === 0) {
    return (
      <>
        <Banner title="Watchlist" />
        <section className={`py-5 ${styles.watch_list_sec}`}>
          <div className="container">
            <h3>No items in your watchlist</h3>
          </div>
        </section>
        <Toast />
      </>
    );
  }

  return (
    <>
      <Banner title="Watchlist" />
      <section className={`py-5 ${styles.watch_list_sec}`}>
        <div className="container">
          <div className="row justify-content-center">
            <div className={`col-lg-7 ${styles.watch_list}`}>
              {/* <h3>Watchlist</h3> */}
              <h5 className={styles.list_head}>
                <span>{watchlistItems.length}</span> Items In Watchlist
              </h5>
              {/* Latest Items on Top of Watch List */}
              {watchlistItems
                .slice()
                .reverse()
                .map((item) => (
                  <WatchlistCard
                    key={item.product_id}
                    item={item}
                    onDelete={handleDelete}
                    onCart={handleAddcart}
                  />
                ))}
            </div>
          </div>
        </div>
        <Toast />
      </section>
    </>
  );
};

const WatchlistCard = React.memo(({ item, onDelete, onCart }) => (
  <div className={`row ${styles.card}`}>
    <div
      className={`col-sm-3 ${styles.title_price}`}
      style={{
        display: "flex",
        justifyContent: "start",
        height: "160px",
      }}
    >
      <Link href={`/product-details/${item.product_slug}`}>
        <Image
          src={
            item.image_name === null
              ? "/assets/images/no-image.png"
              : `${IMG_URL}/${item.image_name}`
          }
          alt={item.product_title || "Product image"}
          width={0}
          height={200}
          priority
          sizes="100vw"
        />
      </Link>
    </div>
    <div className={`col-sm-5 ${styles.title_price}`}>
      <ul className={styles.list}>
        <Link href={`/product-details/${item.product_slug}`}>
          <li>
            <h5>{item.product_title}</h5>
          </li>
        </Link>
        <li>
          <span>
            {item.weight} - {formatPrice(item.sell_price)}
          </span>
        </li>
      </ul>
    </div>
    <div className={`col-sm-4 ${styles.cart_remove}`}>
      <div className={styles.cost}>
        <h6>{formatPrice(item.sell_price)}/-</h6>
        <div className="d-flex align-items-center">
          <p
            className="green-but"
            onClick={(e) => onCart(e, item.product_id, item.weight_id, "cart")}
          >
            Add To Cart
          </p>
          <span
            className={styles.close_icon}
            onClick={() => onDelete(item.wishlist_id)}
            role="button"
            aria-label="Remove from watchlist"
          >
            <MdDelete />
          </span>
        </div>
      </div>
    </div>
  </div>
));

WatchlistCard.displayName = "WatchlistCard";

export default Watchlist;
