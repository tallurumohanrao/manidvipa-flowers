"use client";

import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

import Banner from "@/components/banner";
import styles from "@/scss/pages/checkout.module.scss";
import React, { useCallback, useEffect, useState } from "react";
import {
  fetchCartSessionData,
  fetchListingData,
  formatPrice,
} from "../../../hook/userCookie";
import { useCartCount, useToast } from "@/app/(pages)/context/page";
import Toast from "@/components/Toast";
import { useRouter } from "next/navigation";
import CheckoutAddress from "@/components/CheckoutAddress";
import CheckoutPayment from "@/components/CheckoutPayment";
import CheckoutCartDetails from "@/components/CheckoutCartDetails";
import CheckoutUserForm from "@/components/CheckoutUserForm";
import CheckoutCoupon from "@/components/CheckoutCoupon";

export default function OrderCheckout({
  CartDetailsData,
  PaymentMethods,
  userData,
  userToken,
  guestSession,
}) {
  const router = useRouter();
  const { showToast } = useToast();

  const [startDate, setStartDate] = useState(null);

  // Calculate tomorrow's date
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
  const [selectedAddress, setSelectedAddress] = useState(null);
  const [couponData, setCouponData] = useState([]);
  const [formErrors, setFormErrors] = useState({});
  const [selectError, setSelectError] = useState({});
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    contact_number: "",
    cart_session: guestSession,
  });

  // CART RELATED FUCTIONALITY
  const { resetCartCount } = useCartCount();
  const [data, setData] = useState({
    data: [],
    totals: [],
  });

  const isDisabled = (date) => {
    const now = new Date();
    const today = now.toDateString() === date.toDateString();
    const after4PM = now.getHours() >= 16; // 4 PM (16:00)
    return today && after4PM;
  };

  const dataArrayTotals = Object.values(data?.totals);

  const fetchData = useCallback(async () => {
    try {
      const result = await fetchCartSessionData(
        `get-cart?cart_session=${guestSession}`,
        userToken ? userToken : undefined
      );

      // setMessage(result.message);

      if (result?.data) {
        const updatedData = result.data.map((item) => ({
          ...item,
          totalPrice: item.sell_price * item.quantity, // Precompute discounted price
        }));

        // Calculate totals

        const updatedState = {
          data: updatedData,
          totals: result.totals,
        };

        setData(updatedState);
      }
    } catch (error) {
      console.error("Error fetching cart data:", error);
    }
  }, [guestSession, userToken]);

  const handleAddressSelection = (addressId) => {
    setSelectedAddress(addressId);
  };
  const handlePaymentSelection = (addressId) => {
    setSelectedPaymentMethod(addressId);
  };

  // const handleAmountSelection = useCallback((data) => {
  //   setData(data);
  // }, []);
  const handleCouponData = () => {
    fetchData();
  };

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleUserForm = (data) => {
    setFormData(data);
  };

  // HANDLE SUBMIT
  const handleCheckoutSubmit = async (e) => {
    e.preventDefault();

    // Initialize errors array
    let validationErrors = {
      address: "",
      payment: "",
      date: "",
    };
    let isFormValid = true;

    // Validate userToken-related fields
    if (!userToken) {
      const errors = {};
      Object.keys(formData).forEach((field) => {
        if (!formData[field]) {
          errors[field] = `${field} is Required`;
          isFormValid = false;
        }
        if (field === "contact_number" && !/^\d{10}$/.test(formData[field])) {
          errors[field] = "Mobile number must be 10 digits";
          isFormValid = false;
        }
      });
      setFormErrors(errors);
    }

    // Validate address selection
    if (!selectedAddress) {
      validationErrors = {
        ...validationErrors,
        address: "Please select an address.",
      };
      setSelectError(validationErrors);
      isFormValid = false;
    }

    // Validate payment method selection
    if (!selectedPaymentMethod) {
      validationErrors = {
        ...validationErrors,
        payment: "Please select an Payment Method",
      };
      setSelectError(validationErrors);
      isFormValid = false;
    }

    // Validate serve_date
    const today = new Date();
    const serveDate = new Date(startDate);
    if (!startDate || serveDate.toDateString() === today.toDateString()) {
      validationErrors = {
        ...validationErrors,
        date: "Please select Your Preferred Delivery Day that is not today.",
      };
      setSelectError(validationErrors);
      isFormValid = false;
    }

    // Display validation errors
    if (!isFormValid) {
      return;
    }

    let queryString = "";
    if (userToken) {
      queryString = new URLSearchParams({
        address_id: selectedAddress,
        cart_session: formData.cart_session,
        payment_method: selectedPaymentMethod,
        serve_date: startDate,
      }).toString();
    } else {
      queryString = new URLSearchParams({
        name: formData.name,
        email: formData.email,
        contact_number: formData.contact_number,
        address_id: selectedAddress,
        cart_session: formData.cart_session,
        payment_method: selectedPaymentMethod,
        serve_date: startDate,
      }).toString();
    }

    try {
      let response;
      if (userToken) {
        response = await fetchListingData(
          "POST",
          `store-order?${queryString}`,
          userToken
        );
      } else {
        response = await fetchListingData("POST", `store-order?${queryString}`);
      }

      if (!response) {
        throw new Error("Error submitting the form");
      }

      if (response.success) {
        resetCartCount();
        showToast(response.message || "Order Placed successfully", "success");
        router.push(`/thank-you/${response?.data?.order_encrypt_key}`);
      } else {
        showToast(response.message, "error");
      }
    } catch (error) {
      console.error("Error:", error);
      setSubmitError("An error occurred while submitting the form.");
    }
  };

  return (
    <>
      {/* BANNER SECTION START */}
      <Banner title="Checkout" />
      {/* BANNER SECTION END */}

      {/* CHECKOUT SECTION START */}
      <div className="container my-3">
        <div className="row">
          {/* LEFT COLUMN */}
          <div className="col-md-7">
            {/* USER DETAILS SECTION */}
            <CheckoutUserForm
              formData={formData}
              onUserChange={handleUserForm}
              formErrors={formErrors}
              userData={userData}
              userToken={userToken}
              guestSession={guestSession}
            />
            <div className="d-block d-md-none">
              <CheckoutCartDetails CartDetailsData={CartDetailsData} />
            </div>
            {/* DELIVERY ADDRESS SECTION */}
            {/* <div className="mb-3"> */}
            <CheckoutAddress
              selectedAddress={selectedAddress}
              onAddressChange={handleAddressSelection}
              selectError={selectError}
              userToken={userToken}
              guestSession={guestSession}
            />
            {/* </div> */}
            <div className={`mb-3`}>
              <div className={`p-3 ${styles.payment_section}`}>
                <h6 className="mb-2">
                  Please Select{" "}
                  <span style={{ color: "red", textDecoration: "underline" }}>
                    Your
                  </span>{" "}
                  Preferred Delivery Day
                </h6>
                <DatePicker
                  selected={startDate}
                  onChange={(date) => setStartDate(date)}
                  filterDate={(date) => !isDisabled(date)}
                  minDate={new Date()} // Disable past dates
                  dateFormat="dd-MM-yyyy"
                  className="p-2"
                />
              </div>
              {selectError.date && (
                <div className={`${styles.error} mt-2`}>
                  {selectError.date}{" "}
                </div>
              )}
            </div>
            <div className="mb-3">
              <CheckoutPayment
                selectedPaymentMethod={selectedPaymentMethod}
                onAddressChange={handlePaymentSelection}
                selectError={selectError}
                PaymentMethods={PaymentMethods}
              />
            </div>
          </div>

          {/* RIGHT COLUMN */}
          <div className="col-md-5">
            <div className="d-none d-md-block">
              <CheckoutCartDetails CartDetailsData={CartDetailsData} />
            </div>
            <CheckoutCoupon
              onAmountChange={handleCouponData}
              userToken={userToken}
              guestSession={guestSession}
            />
            <hr />
            <div className={`mt-3 ${styles.cart_totals}`}>
              {dataArrayTotals?.map((item, index) => (
                <p key={index}>
                  <span className={styles.key}>{item?.title}:</span>
                  <span className={styles.value}>
                    {formatPrice(item?.amount)}
                  </span>
                </p>
              ))}
            </div>
            <form onSubmit={handleCheckoutSubmit}>
              <button
                type="submit"
                className={`${styles.order_button} primary-but w-100 mt-3`}
              >
                Place Order
              </button>
            </form>
          </div>
        </div>
        <Toast />
      </div>
      {/* CHECKOUT SECTION ENDS */}
    </>
  );
}
