"use client";
import React, { useCallback, useEffect, useState } from "react";
import styles from "@/scss/pages/orderDetails.module.scss";
import { fetchListingData, formatPrice } from "../../../../hook/userCookie";
// import { useUser } from "@/context/UserContext";
import Image from "next/image";
import Link from "next/link";
import Modal from "@/components/modal";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

export default function OrderDetails({ id, handleBack, userData, userToken }) {
  // const { userToken } = useUser();
  const [orderDetails, setOrderDetails] = useState([]);
  const [orderedProducts, setOrderedProducts] = useState([]);
  const [orderlineitems, setOrderlineitems] = useState([]);
  const [ordershipping, setOrderShipping] = useState("");

  const getColor = (status) => {
    switch (status) {
      case 1:
        return "#ff9900";
      case 2:
        return "#ff9900";
      case 3:
        return "#28a745";
      case 4:
        return "#28a745";
      case 5:
        return "#cc0000";
      default:
        return "gray";
    }
  };

  const handleOrderCancel = async (id) => {
    const response = await fetchListingData(
      "POST",
      `cancel-order?order_id=${id}`,
      userToken
    );
    if (response.success) {
      handleBack();
    }
  };

  const fetchAddresses = useCallback(async () => {
    if (userToken) {
      try {
        const orderDetails = await fetchListingData(
          "GET",
          `order-details?order_encrypt_key=${id}`,
          userToken
        );
        setOrderDetails(orderDetails || []);
        setOrderedProducts(orderDetails?.products);
        setOrderlineitems(orderDetails?.orderlineitems);
        setOrderShipping(orderDetails?.shipping);
      } catch (error) {
        console.log("Error fetching addresses:", error);
      }
    }
  }, [userToken, id]);

  useEffect(() => {
    fetchAddresses();
  }, [fetchAddresses]);

  return (
    <div className={styles.orderDetails_mains}>
      <div className="my-4 my-md-0">
        <div className={`${styles.order_header}`}>
          <h4>Order Details</h4>
          <p onClick={handleBack} className={`${styles.orders_back}`}>
            View All Orders
          </p>
        </div>
        <div className={`${styles.order_details}`}>
          <div className={`${styles.order_header}`}>
            <h6>
              Order Status :{" "}
              <span
              // style={{
              //   color: "#fff",
              //   backgroundColor: getColor(
              //     orderDetails?.order?.order_status_id
              //   ),
              //   padding: "5px 8px",
              //   fontSize: "16px",
              //   borderRadius: "3px",
              // }}
              >
                {orderDetails?.order?.order_status_name}
              </span>
            </h6>
            <Modal
              buttonClass={
                orderDetails?.order?.order_status_id === 5
                  ? `${styles.custom_display}`
                  : `primary-but `
              }
              buttonName="Cancel Order"
              onConfirm={() => handleOrderCancel(orderDetails?.order?.id)}
            >
              <p>Are you sure you want to delete this Order?</p>
            </Modal>
          </div>
          <p className="mb-3">
            Order Id # {orderDetails?.order?.id}
            <br></br>
            Ordered on {orderDetails?.order?.created_at}
          </p>
          <div className={`row mb-3 ${styles.order_info}`}>
            <div className="col-sm-6">
              <h6 className="">User Information</h6>
              <ul className={`${styles.user_info}`}>
                <li>
                  <span>{userData?.user?.name}</span>
                </li>
                <li>
                  <span>{userData?.user?.email}</span>
                </li>
              </ul>
            </div>
            <div className="col-sm-6">
              <h6 className="">Delivery Address</h6>
              <ul className={`${styles.shipping_address}`}>
                <li>{orderDetails?.shipping_address?.full_name},</li>
                <li>{orderDetails?.shipping_address?.address_line1},</li>
                <li>{orderDetails?.shipping_address?.address_line2},</li>
                <li>{orderDetails?.shipping_address?.landmark},</li>
                <li>{orderDetails?.shipping_address?.city},</li>
                <li>{orderDetails?.shipping_address?.state},</li>
                <li>{orderDetails?.shipping_address?.pincode},</li>
                <li>+91-{orderDetails?.shipping_address?.phone_number}</li>
              </ul>
            </div>
            <div className="col-sm-6">
              <h6 className="">Payment Information</h6>
              <p>
                Payment Status :
                <span> {orderDetails?.payment?.payment_status}</span>
              </p>
              <p>Payment Method : {orderDetails?.payment?.payment_method}</p>
              <p>Amount Paid : {orderDetails?.payment?.payment_amount}</p>
              <p>Last Updated Date : {orderDetails?.payment?.updated_at}</p>
            </div>
            <div className="col-sm-6">
              <h6 className="">Delivery Information</h6>
              <p>
                Delivery Status :{" "}
                <span
                // style={{
                //   color: "#fff",
                //   backgroundColor: getColor(
                //     ordershipping?.shipping_status_id
                //   ),
                //   padding: "5px 8px",
                //   fontSize: "16px",
                //   borderRadius: "3px",
                // }}
                >
                  {ordershipping?.shipping_status_name}
                </span>
              </p>
              <p>
                Delivery Charges : <span>₹ {ordershipping?.amount}</span>
              </p>
              <p>Last Updated Date : {ordershipping?.updated_at}</p>
            </div>
          </div>
          <div className={styles.table_wrapper}>
            <table className={`${styles.table_custom}`}>
              <thead>
                <tr>
                  <th>
                    <p>S.No.</p>
                  </th>
                  <th>
                    <p>Image</p>
                  </th>
                  <th>
                    <p>Title</p>
                  </th>
                  <th>
                    <p>Weight</p>
                  </th>
                  <th>
                    <p>Quantity</p>
                  </th>
                  <th>
                    <p>Price</p>
                  </th>
                  <th>
                    <p>Total</p>
                  </th>
                </tr>
              </thead>
              <tbody>
                {orderedProducts?.map((item, index) => (
                  <tr key={index}>
                    <td>{index + 1}</td>
                    <td>
                      <Image
                        src={
                          item?.image_url === null
                            ? "/assets/images/no-image.png"
                            : `${IMG_URL}/${item?.image_url}`
                        }
                        alt={item?.product_title}
                        width={50} // Adjust the size based on your requirements
                        height={50}
                        className="img-fluid" // Ensures the image is responsive
                      />
                    </td>
                    <td>
                      <Link href={`/product-details/${item.product_slug}`}>
                        {item?.product_title}
                      </Link>
                    </td>
                    <td>
                      <p>{item?.weight}</p>
                    </td>
                    <td>
                      <p>{item?.quantity}</p>
                    </td>
                    <td>
                      <p>{formatPrice(item.sell_price)}</p>
                    </td>
                    <td>
                      <p>₹ {item.amount}</p>
                    </td>
                  </tr>
                ))}
                {orderlineitems?.map((item, index) => (
                  <tr key={index}>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                      <p
                        className="text-end"
                        style={{
                          fontSize: item.title === "Total" && "20px",
                          fontWeight: item.title === "Total" && "800",
                        }}
                      >
                        {item.title} :
                      </p>
                    </td>
                    <td>
                      <p>₹ {item.amount}</p>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
