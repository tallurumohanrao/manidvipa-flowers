import styles from "@/scss/pages/thankYou.module.scss";
import React from "react";

import { FaCircleCheck } from "react-icons/fa6";
import Image from "next/image";
import { formatPrice } from "../../../hook/userCookie";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

export default function ThankYou({ orderData }) {
  const orderSucces = orderData;
  const shippingAddress = orderData?.shipping_address;
  const deliveryTimeLine = orderData?.orderlineitems?.find((item) =>
    String(item?.title || "").startsWith("Delivery Time Slot:")
  );
  const deliveryTime = deliveryTimeLine?.title
    ? deliveryTimeLine.title.replace("Delivery Time Slot:", "").trim()
    : "";

  return (
    <div className={`container ${styles.order_success_page}`}>
      <div className="row">
        <div className="col-12 text-center my-4">
          <FaCircleCheck className={`my-4 ${styles.success_icon}`} />
          <h3 className={styles.text_success}>
            Your order has been placed successfully .
          </h3>
        </div>
      </div>

      <div className="row mx-1">
        <div className={`col-sm-6 ${styles.ship_address}`}>
          <h5 className={styles.shipping_title}>Shipping Address</h5>
          <p className={styles.shipping_info}>
            <strong>{shippingAddress?.full_name}</strong>
            <br />
            {shippingAddress?.address_line1}
            <br />
            {shippingAddress?.address_line2}
            <br />
            {shippingAddress?.city}
            <br />
            {shippingAddress?.phone_number}
            <br />
            {shippingAddress?.pincode}
            <br />
            {shippingAddress?.state}
          </p>
        </div>
        <div className={`col-sm-6 text-md-end ${styles.ship_address}`}>
          <p className={styles.order_id}>
            Order Id:{" "}
            <span className={styles.text_danger}>
              #{shippingAddress?.order_id}
            </span>
          </p>
          <p>
            Date:{" "}
            <span className={styles.text_danger}>
              {shippingAddress?.created_at}
            </span>
          </p>
          {orderSucces?.order?.serve_date ? (
            <p>
              Preferred Delivery Date:{" "}
              <span className={styles.text_danger}>
                {orderSucces.order.serve_date}
              </span>
            </p>
          ) : null}
          {deliveryTime ? (
            <p>
              Preferred Delivery Time:{" "}
              <span className={styles.text_danger}>{deliveryTime}</span>
            </p>
          ) : null}
        </div>
      </div>

      <div className="row my-5">
        <div className="col-12">
          <div className={styles.table_responsive}>
            <table className={`table ${styles.table_bordered}`}>
              <thead>
                <tr>
                  <th>IMAGE</th>
                  <th>PRODUCT TITLE</th>
                  <th>SKU</th>
                  <th>SIZE</th>
                  <th>QTY</th>
                  <th>PRICE</th>
                  <th>TOTAL</th>
                </tr>
              </thead>
              <tbody>
                {orderSucces?.products?.map((item, index) => (
                  <tr key={index}>
                    <td>
                      <Image
                        src={
                          item.image_url === null
                            ? "/assets/images/no-image.png"
                            : `${IMG_URL}/${item.image_url}`
                        }
                        alt={item.product_title}
                        className={styles.product_image}
                        width={0}
                        height={0}
                        sizes="100vw"
                        priority
                      />
                    </td>
                    <td>
                      <p>{item.product_title}</p>
                    </td>
                    <td>
                      <p>{item.sku}</p>
                    </td>
                    <td>
                      <p>{item.weight}</p>
                    </td>
                    <td>
                      <p>{item.quantity}</p>
                    </td>
                    <td>
                      <p>{formatPrice(item.sell_price)}</p>
                    </td>
                    <td>
                      <p>{formatPrice(item.sell_price * item.quantity)}</p>
                    </td>
                  </tr>
                ))}

                <tr>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td>
                    <p>Sub Total</p>
                  </td>
                  <td>
                    <p>{formatPrice(orderSucces?.order?.sub_total)}</p>
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td>
                    <p>Shipping Amount</p>
                  </td>
                  <td>
                    <p>{formatPrice(orderSucces?.shipping?.amount)}</p>
                  </td>
                </tr>
                <tr className={styles.total_row}>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <th>
                    <p>Total</p>
                  </th>
                  <th>
                    {" "}
                    <p>{formatPrice(orderSucces?.order?.amount)}</p>
                  </th>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
