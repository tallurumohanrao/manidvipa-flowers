import React from "react";
import styles from "@/scss/pages/checkout.module.scss";
import Image from "next/image";
import { formatPrice } from "../../hook/userCookie";
import { RxCross1 } from "react-icons/rx";
import { getPerUnitPriceDetails } from "@/lib/unitPricing";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const CheckoutCartDetails = ({ CartDetailsData }) => {
  const data = CartDetailsData;

  return (
    <>
      <div className={`row ${styles.card} py-3`}>
        {data?.data?.map((value, index) => {
          const selectedWeight = Array.isArray(value?.available_weights)
            ? value.available_weights.find((weight) =>
              String(weight?.id || weight?.weight_id) === String(value?.weight_id)
            )
            : null;
          const perUnitPrice = getPerUnitPriceDetails(
            selectedWeight,
            value?.is_custom_quantity ? value?.custom_quantity : null
          );

          return (
          <div className="col-12" key={index}>
            <div className="row">
              <div
                className="col-xs-4 col-sm-3 col-md-3"
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
              <div className="col-xs-8 col-sm-6 col-md-6 my-auto">
                <ul className={`${styles.list}`}>
                  <li>
                    <h5>{value.product_title}</h5>
                  </li>
                  <li>
                    {/* <span>{value.weight}</span> */}
                    <p>
                      {value.weight}{" "}
                      <RxCross1
                        // className="pt-3"
                        style={{ paddingTop: "5px", fontWeight: "bold" }}
                      />{" "}
                      {value.quantity}
                    </p>
                  </li>
                  <li
                    className={`d-block d-sm-none`}
                    style={{ color: "#cc0000" }}
                  >
                    {formatPrice(value?.totalPrice)}
                    {perUnitPrice ? (
                      <small className={styles.unitPrice}>
                        {formatPrice(perUnitPrice.unitPrice)} per {perUnitPrice.unitLabel}
                      </small>
                    ) : null}
                  </li>
                </ul>
              </div>
              <div className="d-none d-sm-block col-xs-12 col-sm-3 col-md-3 d-md-flex align-items-center justify-content-center my-auto">
                <div className={styles.cost}>
                  <h6> {formatPrice(value?.sell_price * value?.quantity)}</h6>
                  {perUnitPrice ? (
                    <small className={styles.unitPrice}>
                      {formatPrice(perUnitPrice.unitPrice)} per {perUnitPrice.unitLabel}
                    </small>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
          );
        })}
        {data?.data?.length <= 0 && (
          <div className={styles.iconContainer}>Your Cart is Empty</div>
        )}
      </div>
    </>
  );
};

export default CheckoutCartDetails;
