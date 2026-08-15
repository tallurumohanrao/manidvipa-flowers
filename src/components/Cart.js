import React from "react";
import styles from "@/scss/pages/cart.module.scss";
import Banner from "@/components/banner";
import Image from "next/image";

export default function Cart () {
  return (
    <>
      {/*BANNER SECTION START */}
      <Banner title="Shopping Cart" />
      {/* BANNER SECTION END  */}
      {/* EMPTY SHOPPING CART SECTION START  */}
      <section className="my-5">
        <div className={styles.empty_shopping_cart_sec}>
          <div className={styles.cart_image}>
            <Image width={0}
                    height={0}
                    sizes="100vw"
                    style={{ width: "100%", height: "100%" }} src="/assets/images/cart-empty-1.png" alt="" />
          </div>
          <h5>Your cart is empty.</h5>
          <button className={`primary-but ${styles.primary_but}`}>
            keep shopping
          </button>
        </div>
      </section>
      {/* EMPTY SHOPPING CART SECTION END  */}
    </>
  );
}
