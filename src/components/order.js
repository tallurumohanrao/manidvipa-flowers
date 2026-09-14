import React, { useEffect, useState, useCallback } from "react";
import styles from "@/scss/components/orders.module.scss";
import { formatPrice, fetchListingData } from "../../hook/userCookie";
import Image from "next/image";
import Modal from "./modal";
import { formatDisplayDateTime } from "@/lib/date";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const Orders = ({ handleOrderActive, userToken }) => {
  const [orders, setOrders] = useState([]);

  const isCancelledOrder = (order) =>
    Number(order?.order_status_id) === 5 ||
    order?.order_status_name?.toLowerCase() === "cancelled";

  const fetchOrders = useCallback(async () => {
    if (userToken) {
      try {
        const ordersData = await fetchListingData("GET", `orders`, userToken);
        setOrders(ordersData?.data || []);
      } catch (error) {
        console.log("Error fetching data:", error);
      }
    }
  }, [userToken]);

  const handleOrderCancel = async (id) => {
    try {
      const response = await fetchListingData(
        "POST",
        `cancel-order?order_id=${id}`,
        userToken
      );
      if (response.success) {
        alert(response.message || "Your order is cancelled.");
        fetchOrders();
      } else {
        alert(response?.message || "Unable to cancel this order.");
      }
    } catch (error) {
      console.error("Error canceling order:", error);
      alert("Unable to cancel this order.");
    }
  };

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);

  return (
    <div className={styles.orders_page}>
      <h4>Your Orders</h4>
      <div className={styles.orders_list}>
        {orders.length > 0 ? (
          orders.map((order) => {
            return (
              <div key={order.order_id} className={` ${styles.order_card}`}>
                <div className={`container ${styles.order_header}`}>
                  <div className={` row`}>
                    <div className={`col-sm-9 ${styles.order_top}`}>
                      <div>
                        <p>
                          <strong>Order Placed:</strong>
                        </p>
                        <p>{formatDisplayDateTime(order.order_created_at)}</p>
                      </div>
                      <div>
                        <p>
                          <strong>Total:</strong>
                        </p>
                        <p> ₹{order.sub_total}</p>
                      </div>
                      {/* <ShipToDropdown shipTo={order.shipTo} /> */}
                      <div>
                        <p>
                          <strong>Order Status:</strong>
                        </p>
                        <p>{order.order_status_name || "N/A"}</p>
                      </div>
                      <div>
                        <p>
                          <strong>Delivery Status:</strong>
                        </p>
                        <p>{order.shipping_status || "N/A"}</p>
                      </div>
                    </div>
                    <div className="col-sm-3 text-md-end">
                      <strong>Order #:</strong> {order.order_id}
                      <button
                        onClick={() =>
                          handleOrderActive(order.order_encrypt_key)
                        }
                        className={`secondary-but ${styles.view_item_button}`}
                      >
                        Order Details
                      </button>
                      <Modal
                        buttonClass={
                          isCancelledOrder(order)
                            ? `${styles.custom_display}`
                            : `tyrian-purple ${styles.view_item_button}`
                        }
                        buttonName="Cancel Order"
                        onConfirm={() => handleOrderCancel(order.order_id)}
                      >
                        <span style={{ color: "black" }}>
                          Are you sure you want to cancel this order?
                        </span>
                      </Modal>
                      {/* {order.invoice && (
                      <a
                        href={order.invoice}
                        className={styles.invoice_link}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        Invoice
                      </a>
                    )} */}
                    </div>
                  </div>
                </div>

                <div className={styles.order_details}>
                  <div className="container">
                    {order.order_products.map((item, index) => (
                      <div key={index} className={`row ${styles.product}`}>
                        <div className={` col-3 ${styles.product_image}`}>
                          <Image
                            src={
                              item.image_name === null
                                ? "/assets/images/no-image.png"
                                : `${IMG_URL}/${item.image_name}`
                            }
                            alt={item.product_title}
                            width={0}
                            height={0}
                            sizes="100vw"
                            style={{ width: "100%", height: "100%" }}
                          />
                        </div>
                        <div className={`col-7 ${styles.product_info}`}>
                          <p>
                            <strong>{item.product_title}</strong>
                          </p>
                          <p className={styles.product_mobile_amount}>
                            {formatPrice(item.amount)}
                          </p>
                          {/* <Link href={`/orderDetails/${order.order_encrypt_key}`}>
                          <button
                            className={`primary-outlined-but ${styles.view_item_button}`}
                          >
                            View your item
                          </button>
                        </Link> */}
                        </div>
                        <div className={` col-2 ${styles.product_amount}`}>
                          <p>{formatPrice(item.amount)}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            );
          })
        ) : (
          <p>No orders found.</p>
        )}
      </div>
    </div>
  );
};

const ShipToDropdown = ({ shipTo }) => {
  const [isOpen, setIsOpen] = useState(false);

  const toggleDropdown = () => {
    setIsOpen(!isOpen);
  };

  return (
    <div className={styles.ship_to_dropdown}>
      <p>
        <strong>Ship To:</strong>
      </p>

      <div className={styles.dropdown_toggle} onClick={toggleDropdown}>
        <span>{isOpen ? "xxxxxxx" : "xxxxxxx"}</span>
        <span className={styles.icon}>{isOpen ? "▲" : "▼"}</span>
      </div>

      {isOpen && (
        <div className={styles.dropdown_content}>
          <p>{shipTo}</p>
        </div>
      )}
    </div>
  );
};

export default Orders;
