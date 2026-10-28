"use client";

import React, { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import styles from "@/scss/pages/watchlist.module.scss";
import Link from "next/link";
import Toast from "@/components/Toast";
import { useCartCount, useToast, useUser } from "@/context/UserContext";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { MdDelete } from "react-icons/md";
import {
  fetchCartBySession,
  fetchListingData,
  formatPrice,
  getCartCount,
} from "../../../hook/userCookie";
import { useWatchlistCount } from "@/context/UserContext";
import { getPriceVisibility } from "@/lib/priceVisibility";
import { sortAvailableProductsFirst } from "@/lib/productStock";
import { getPurchaseQuantityMaximum } from "@/lib/purchaseQuantity";
import { getPerUnitPriceDetails } from "@/lib/unitPricing";

const Banner = dynamic(() => import("@/components/banner"), { ssr: false });

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

function formatQuantityNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? Number(number.toFixed(3)).toString() : "";
}

function formatQuantityUnit(unit, quantity) {
  const singular = Math.abs(Number(quantity) - 1) < 0.00001;
  const labels = {
    flower: singular ? "Flower" : "Flowers",
    piece: singular ? "Piece" : "Pieces",
    stem: singular ? "Stem" : "Stems",
    bunch: singular ? "Bunch" : "Bunches",
    gram: singular ? "Gram" : "Grams",
    kg: "KG",
    ml: "ml",
    liter: singular ? "Liter" : "Liters",
    packet: singular ? "Packet" : "Packets",
    box: singular ? "Box" : "Boxes",
    basket: singular ? "Basket" : "Baskets",
    set: singular ? "Set" : "Sets",
  };
  return labels[unit] || "Units";
}

const Watchlist = ({ watchlist, userToken, guestSession }) => {
  const router = useRouter();
  const [watchlistItems, setWatchlistItems] = useState(watchlist);
  const { guestSession: clientGuestSession } = useUser();
  const effectiveGuestSession = clientGuestSession || guestSession;

  const { showToast } = useToast();
  const { decreaseWatchlistCount } = useWatchlistCount();
  const [addingProductId, setAddingProductId] = useState(null);
  const [addedProductId, setAddedProductId] = useState(null);
  const { setCartCount } = useCartCount();

  const handleAddcart = async (e, item, quantity, weight, action) => {
    e.preventDefault();

    if (!effectiveGuestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    const usesCustomQuantity = Boolean(weight?.allow_custom_quantity);
    const customQuantity = usesCustomQuantity && quantity > 0 ? Number(quantity) : null;

    if (customQuantity !== null) {
      const minimum = Number(weight?.minimum_custom_quantity) || 1;
      const maximum = Number(weight?.maximum_custom_quantity) || null;
      const step = Number(weight?.custom_quantity_step) || 1;
      const stepsFromMinimum = (customQuantity - minimum) / step;
      const followsStep = Math.abs(stepsFromMinimum - Math.round(stepsFromMinimum)) < 0.00001;

      if (customQuantity < minimum || (maximum && customQuantity > maximum) || !followsStep) {
        showToast(`Enter a quantity from ${formatQuantityNumber(minimum)}${maximum ? ` to ${formatQuantityNumber(maximum)}` : ""} in steps of ${formatQuantityNumber(step)}.`, "error");
        return;
      }
    }

    setAddingProductId(item.product_id);
    setAddedProductId(null);

    const body1 = {
      cart_session: effectiveGuestSession,
      product_id: item.product_id,
      quantity: usesCustomQuantity ? 1 : quantity > 0 ? quantity : 1,
      weight_id: weight?.id || weight?.weight_id || item.weight_id,
      ...(customQuantity !== null ? { custom_quantity: customQuantity } : {}),
    };

    try {
      const cartData = await fetchListingData(
        "POST",
        "add-to-cart",
        userToken ? userToken : undefined,
        body1
      );

      if (!cartData?.success) {
        showToast(cartData.message || "Failed to add to cart", "error");
        return;
      }

      setAddedProductId(item.product_id);
      showToast(customQuantity !== null
        ? `${formatQuantityNumber(customQuantity)} ${formatQuantityUnit(weight?.quantity_unit, customQuantity)} added to cart.`
        : cartData.message || "Added to cart successfully", "success");

      const refreshedCart = await fetchCartBySession(
        effectiveGuestSession,
        userToken ? userToken : undefined
      );

      if (refreshedCart?.success) {
        setCartCount(getCartCount(refreshedCart));
      }

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
    } finally {
      setAddingProductId(null);
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
              {sortAvailableProductsFirst(watchlistItems.slice().reverse())
                .map((item) => (
                  <WatchlistCard
                    key={item.product_id}
                    item={item}
                    onDelete={handleDelete}
                    onCart={handleAddcart}
                    isAdding={addingProductId === item.product_id}
                    wasAdded={addedProductId === item.product_id}
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

const WatchlistCard = React.memo(({ item, onDelete, onCart, isAdding, wasAdded }) => {
  const priceVisibility = getPriceVisibility(item);
  const [quantity, setQuantity] = useState(0);
  const [selectedWeight, setSelectedWeight] = useState(null);
  const [isLoadingOption, setIsLoadingOption] = useState(true);
  const usesCustomQuantity = Boolean(selectedWeight?.allow_custom_quantity);
  const perUnitPrice = getPerUnitPriceDetails(selectedWeight);

  useEffect(() => {
    let isMounted = true;

    const loadSellingOption = async () => {
      setIsLoadingOption(true);
      try {
        const details = await fetchListingData(
          "GET",
          `product-details?product_slug=${encodeURIComponent(item.product_slug)}`
        );
        const weights = details?.weights || details?.data?.weights || [];
        const matchedWeight = weights.find((weight) =>
          String(weight?.id || weight?.weight_id) === String(item.weight_id)
        ) || weights[0] || null;
        if (isMounted) setSelectedWeight(matchedWeight);
      } catch (error) {
        console.error("Unable to load watchlist selling option:", error);
      } finally {
        if (isMounted) setIsLoadingOption(false);
      }
    };

    loadSellingOption();
    return () => {
      isMounted = false;
    };
  }, [item.product_slug, item.weight_id]);

  const updateQuantity = (nextQuantity) => {
    if (nextQuantity === "") {
      setQuantity("");
      return;
    }

    const numericQuantity = Number(nextQuantity);
    const maximum = getPurchaseQuantityMaximum(
      usesCustomQuantity,
      selectedWeight?.maximum_custom_quantity
    );
    const boundedQuantity = Math.min(
      maximum,
      Math.max(0, Number.isFinite(numericQuantity) ? numericQuantity : 0)
    );
    setQuantity(usesCustomQuantity
      ? Number(boundedQuantity.toFixed(3))
      : Math.floor(boundedQuantity));
  };

  const adjustQuantity = (direction) => {
    const currentQuantity = Number(quantity) || 0;

    if (!usesCustomQuantity) {
      updateQuantity(currentQuantity + direction);
      return;
    }

    const minimum = Number(selectedWeight?.minimum_custom_quantity) || 1;
    const step = Number(selectedWeight?.custom_quantity_step) || 1;
    updateQuantity(direction > 0
      ? currentQuantity < minimum ? minimum : currentQuantity + step
      : currentQuantity <= minimum ? 0 : Math.max(minimum, currentQuantity - step));
  };

  return (
  <div className={`row ${styles.card}`}>
    <div
      className={`col-sm-3 ${styles.title_price}`}
      style={{
        display: "flex",
        justifyContent: "start",
        height: "160px",
      }}
    >
      <Link href={`/flowers/${item.product_slug}`}>
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
        <Link href={`/flowers/${item.product_slug}`}>
          <li>
            <h5>{item.product_title}</h5>
          </li>
        </Link>
        {priceVisibility.showPrice ? <li>
          <span>
            {item.weight} - {formatPrice(item.sell_price)}
          </span>
        </li> : <li><strong>{priceVisibility.message}</strong></li>}
      </ul>
    </div>
    <div className={`col-sm-4 ${styles.cart_remove}`}>
      <div className={styles.cost}>
        <h6>{priceVisibility.showPrice ? <>
          <strong>{formatPrice(item.sell_price)}</strong>
          {perUnitPrice ? (
            <small>total · {formatPrice(perUnitPrice.unitPrice)} per {perUnitPrice.unitLabel}</small>
          ) : null}
        </> : priceVisibility.message}</h6>
        {priceVisibility.showPrice && priceVisibility.canPurchase ? (
          <div className={styles.quantityStepper}>
            <button
              type="button"
              onClick={() => adjustQuantity(-1)}
              disabled={quantity <= 0 || isAdding || isLoadingOption}
              aria-label={`Decrease quantity for ${item.product_title}`}
            >−</button>
            <input
              type="number"
              min="0"
              max={getPurchaseQuantityMaximum(usesCustomQuantity, selectedWeight?.maximum_custom_quantity)}
              step={usesCustomQuantity ? Number(selectedWeight?.custom_quantity_step) || 1 : 1}
              value={quantity}
              onChange={(event) => updateQuantity(event.target.value)}
              onBlur={(event) => {
                if (event.target.value === "") setQuantity(0);
              }}
              onFocus={(event) => event.currentTarget.select()}
              disabled={isAdding || isLoadingOption}
              inputMode={usesCustomQuantity ? "decimal" : "numeric"}
              aria-label={usesCustomQuantity
                ? `Custom ${formatQuantityUnit(selectedWeight?.quantity_unit, 2).toLowerCase()} for ${item.product_title}`
                : `Quantity for ${item.product_title}`}
            />
            <button
              type="button"
              onClick={() => adjustQuantity(1)}
              disabled={isAdding || isLoadingOption || quantity >= getPurchaseQuantityMaximum(
                usesCustomQuantity,
                selectedWeight?.maximum_custom_quantity
              )}
              aria-label={`Increase quantity for ${item.product_title}`}
            >+</button>
          </div>
        ) : null}
        <div className="d-flex align-items-center">
          {priceVisibility.showPrice && priceVisibility.canPurchase ? <p
            className="green-but"
            onClick={(e) => {
              if (!isAdding && !isLoadingOption) onCart(e, item, quantity, selectedWeight, "cart");
            }}
            role="button"
            aria-disabled={isAdding || isLoadingOption}
          >
            {isAdding ? "Adding..." : isLoadingOption ? "Loading..." : wasAdded ? "Added" : "Add To Cart"}
          </p> : <Link href={`/flowers/${item.product_slug}`} className="green-but">{priceVisibility.ctaLabel}</Link>}
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
  );
});

WatchlistCard.displayName = "WatchlistCard";

export default Watchlist;

