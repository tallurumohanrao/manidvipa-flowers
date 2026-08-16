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
import { useCartCount, useToast } from "@/context/UserContext";
import Toast from "@/components/Toast";
import { useRouter } from "next/navigation";
import CheckoutAddress from "@/components/CheckoutAddress";
import CheckoutPayment from "@/components/CheckoutPayment";
import CheckoutCartDetails from "@/components/CheckoutCartDetails";
import CheckoutUserForm from "@/components/CheckoutUserForm";
import CheckoutCoupon from "@/components/CheckoutCoupon";

const DELIVERY_PREFERENCE_STORAGE_KEY = "manidvipaDeliveryPreference";

const deliverySlotOptions = [
  { value: "6-9", label: "6 AM - 9 AM" },
  { value: "9-12", label: "9 AM - 12 PM" },
  { value: "12-3", label: "12 PM - 3 PM" },
  { value: "3-6", label: "3 PM - 6 PM" },
];

function getTomorrowDate() {
  const date = new Date();
  date.setDate(date.getDate() + 1);
  date.setHours(0, 0, 0, 0);
  return date;
}

function parseDateInputValue(value) {
  const [year, month, day] = String(value || "").split("-").map(Number);
  const date = year && month && day ? new Date(year, month - 1, day) : null;

  return date && !Number.isNaN(date.getTime()) ? date : null;
}

function getDeliverySlotLabel(value) {
  return deliverySlotOptions.find((option) => option.value === value)?.label || value;
}

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
  const [selectedDeliverySlot, setSelectedDeliverySlot] = useState("6-9");
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
  const [selectedAddress, setSelectedAddress] = useState(null);
  const [formErrors, setFormErrors] = useState({});
  const [selectError, setSelectError] = useState({});
  const [submitError, setSubmitError] = useState("");
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

  const dataArrayTotals = Object.values(data?.totals);

  const formatDateForApi = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

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

  useEffect(() => {
    if (typeof window === "undefined") return;

    try {
      const savedPreference = JSON.parse(
        window.sessionStorage.getItem(DELIVERY_PREFERENCE_STORAGE_KEY) || "{}"
      );
      const savedDate = parseDateInputValue(savedPreference.delivery_date);
      const savedSlot = savedPreference.delivery_slot;

      if (savedDate) {
        setStartDate(savedDate);
      }

      if (deliverySlotOptions.some((option) => option.value === savedSlot)) {
        setSelectedDeliverySlot(savedSlot);
      }
    } catch (error) {
      console.error("Unable to read delivery preference:", error);
    }
  }, []);

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

    setSubmitError("");
    const apiServeDate = formatDateForApi(startDate);
    let queryString = "";
    if (userToken) {
      queryString = new URLSearchParams({
        address_id: selectedAddress,
        cart_session: formData.cart_session,
        payment_method: selectedPaymentMethod,
        serve_date: apiServeDate,
        serve_time_slot: selectedDeliverySlot,
        serve_time_slot_label: getDeliverySlotLabel(selectedDeliverySlot),
      }).toString();
    } else {
      queryString = new URLSearchParams({
        name: formData.name,
        email: formData.email,
        contact_number: formData.contact_number,
        address_id: selectedAddress,
        cart_session: formData.cart_session,
        payment_method: selectedPaymentMethod,
        serve_date: apiServeDate,
        serve_time_slot: selectedDeliverySlot,
        serve_time_slot_label: getDeliverySlotLabel(selectedDeliverySlot),
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
        if (typeof window !== "undefined") {
          window.sessionStorage.removeItem(DELIVERY_PREFERENCE_STORAGE_KEY);
        }
        resetCartCount();
        showToast(response.message || "Order Placed successfully", "success");
        router.push(`/thank-you/${response?.data?.order_encrypt_key}`);
      } else {
        showToast(response.message, "error");
      }
    } catch (error) {
      console.error("Error:", error);
      setSubmitError("An error occurred while submitting the form.");
      showToast("An error occurred while submitting the form.", "error");
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
                  Preferred Delivery Date & Time
                </h6>
                <div className={styles.deliveryPickerGrid}>
                  <label>
                    <span>Delivery Date</span>
                    <DatePicker
                      selected={startDate}
                      onChange={(date) => setStartDate(date)}
                      minDate={getTomorrowDate()}
                      dateFormat="dd-MM-yyyy"
                      className="p-2"
                    />
                  </label>
                  <label>
                    <span>Delivery Time Slot</span>
                    <select
                      value={selectedDeliverySlot}
                      onChange={(event) => setSelectedDeliverySlot(event.target.value)}
                    >
                      {deliverySlotOptions.map((option) => (
                        <option value={option.value} key={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </label>
                </div>
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
              {submitError && (
                <div className={`${styles.error} mb-2`}>{submitError}</div>
              )}
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
