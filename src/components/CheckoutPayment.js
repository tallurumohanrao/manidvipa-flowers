import React, { useState } from "react";
import styles from "@/scss/pages/checkout.module.scss";
import Image from "next/image";

const CheckoutPayment = ({
  PaymentMethods,
  selectedPaymentMethod: parentSelectedAddress,
  onAddressChange,
  selectError,
}) => {
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(
    parentSelectedAddress
  );

  // Handle payment method selection
  const handlePaymentMethodChange = (methodName) => {
    setSelectedPaymentMethod(methodName);
    if (onAddressChange) {
      onAddressChange(methodName);
    }
  };

  return (
    <>
      <div className={styles.payment_section}>
        <h6 className="p-3">SELECT A PAYMENT METHOD</h6>
        <hr />
        <div className="p-3">
          {PaymentMethods?.data?.map((method, index) => (
            <div key={index} className={styles.payment_type}>
              <input
                className={styles.radio_input}
                type="radio"
                name="paymentMethod"
                // value={method.name}
                id={`payment-${index}`}
                checked={selectedPaymentMethod === method.name}
                onChange={() => handlePaymentMethodChange(method.name)}
              />
              <label className="form-check-label" htmlFor={`payment-${index}`}>
                {method.title}
              </label>
            </div>
          ))}
          <div className={styles.card_img}>
            <Image
              src="/assets/images/razor-pay.png"
              alt="Visa"
              className={styles.payment_icon}
              width={0}
              height={0}
              sizes="100vw"
              style={{
                height: "10%",
                width: "10%",
              }}
            />
            <Image
              src="/assets/images/upi.png"
              alt="MasterCard"
              className={styles.payment_icon}
              width={0}
              height={0}
              sizes="100vw"
              style={{
                height: "10%",
                width: "10%",
              }}
            />
          </div>
        </div>
      </div>
      {selectError.payment && (
        <div className={`${styles.error} mt-1`}>{selectError.payment} </div>
      )}
    </>
  );
};

export default CheckoutPayment;
