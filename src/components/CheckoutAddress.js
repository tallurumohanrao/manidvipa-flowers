"use client";

import React, { useState, useCallback, useEffect } from "react";
import styles from "@/scss/pages/checkout.module.scss";
import { fetchListingData } from "../../hook/userCookie";
import Toast from "./Toast";
import Modal from "./modal";
import BillingForm from "./billingForm";
import Cookies from "js-cookie";

const CheckoutAddress = ({
  selectedAddress: parentSelectedAddress,
  onAddressChange,
  selectError,
  userToken,
  guestSession,
}) => {
  const [userAddress, setUserAddress] = useState([]);
  const [editAddress, setEditAddress] = useState([]);
  const [showAll, setShowAll] = useState(false);
  const [selectedAddress, setSelectedAddress] = useState(parentSelectedAddress);
  const [guestAddressId, setGuestAddressId] = useState("");

  const handleAddressChange = (addressId) => {
    setSelectedAddress(addressId);
    if (onAddressChange) {
      onAddressChange(addressId);
    }
  };
  const handleSelect = () => {};
  // TOGGLE FOR SHOW MORE ADDRESSES
  const toggleShowAll = () => {
    setShowAll((prev) => !prev);
  };

  // FETCH ALL ADDRESSES
  const fetchAddresses = useCallback(async () => {
    const guestAddressId = Cookies.get("guestAddressId");
    setGuestAddressId(guestAddressId);
    const endpoint = userToken
      ? "addresses"
      : guestAddressId
      ? `checkout-get-address-by-id?address_id=${guestAddressId}`
      : null;

    if (!endpoint) {
      setUserAddress([]);
      return;
    }

    try {
      const data = await fetchListingData("GET", endpoint, userToken || null);
      setUserAddress(userToken ? data?.data : [data?.data] || []);
    } catch (error) {
      console.error("Error fetching addresses:", error);
    }
  }, [userToken]);

  const getEditedAddresss = () => {
    fetchAddresses();
  };

  useEffect(() => {
    fetchAddresses();
  }, [fetchAddresses]);

  useEffect(() => {
    if (userAddress?.length > 0 && !selectedAddress) {
      const defaultAddress = userAddress.find((item) => item?.is_default === 1);
      if (defaultAddress) {
        setSelectedAddress(defaultAddress.id);
        if (onAddressChange) {
          onAddressChange(defaultAddress.id);
        }
      }
    }
  }, [userAddress, selectedAddress, onAddressChange]);

  const editById = async (id) => {
    const endpoint = userToken
      ? "get-address-by-id"
      : "checkout-get-address-by-id";

    try {
      const data = await fetchListingData(
        "GET",
        `${endpoint}?address_id=${id}`,
        userToken || null
      );

      if (data) {
        setEditAddress(data);
        fetchAddresses();
      } else {
        console.error("No data received for the given ID.");
      }
    } catch (error) {
      console.error("Error fetching address by ID:", error);
    }
  };

  const displayedAddresses = showAll
    ? userAddress
    : userAddress
        ?.filter(
          (address) =>
            address?.id === selectedAddress || selectedAddress === null
        )
        .slice(0, 1);

  // modal details

  return (
    <>
      <div className={`my-3 ${styles.address_section}`}>
        <div className={styles.address_top}>
          <h6 className="p-3">DELIVERY ADDRESS</h6>

          {userToken === null ? (
            userAddress?.[0]?.id ? (
              <Modal
                buttonClass="green-but"
                buttonName="Edit"
                onOpen={() => editById(guestAddressId)}
              >
                <BillingForm
                  userToken={userToken}
                  guestSession={guestSession}
                  editAddress={editAddress}
                  onSuccess={() => getEditedAddresss()}
                />
              </Modal>
            ) : (
              <>
                <Modal buttonClass="green-but" buttonName={`+ Add Address`}>
                  <BillingForm
                    userToken={userToken}
                    guestSession={guestSession}
                    onSuccess={() => getEditedAddresss()}
                  />
                </Modal>
              </>
            )
          ) : (
            <Modal buttonClass="green-but" buttonName={`+ Add Address`}>
              <BillingForm
                userToken={userToken}
                guestSession={guestSession}
                onSuccess={() => getEditedAddresss()}
              />
            </Modal>
          )}
        </div>
        <hr />
        {/* <form onSubmit={handleCheckoutSubmit}> */}
        {displayedAddresses?.length > 0 && displayedAddresses[0] !== null ? (
          displayedAddresses?.map((item, index) => (
            <div key={index} className="px-3 pb-1">
              <div className={styles.select_address}>
                <input
                  className={styles.radio_input}
                  type="radio"
                  name="address"
                  onSelect={handleSelect()}
                  id={`address${item?.id}`}
                  checked={selectedAddress === item?.id}
                  onChange={() => handleAddressChange(item?.id)}
                />
                <label htmlFor={`address${item?.id}`}>
                  <strong>
                    {item?.full_name} (
                    {item?.address_type === 1 ? "Home" : "Office"}) -{" "}
                    {item?.phone_number}
                  </strong>
                  <p className="mb-1">
                    {item?.address_line1}, {item?.address_line2},{" "}
                    {item?.landmark}, {item?.city}, {item?.state},{" "}
                    {item?.pincode}
                  </p>
                </label>
              </div>
            </div>
          ))
        ) : (
          <p className="px-3">No addresses found.</p>
        )}
        <div className="p-3">
          {userAddress?.length > 1 && (
            <button
              type="button"
              onClick={toggleShowAll}
              className="primary-but"
            >
              {showAll ? "Show Less" : "View all addresses"}
            </button>
          )}
        </div>
        {/* </form> */}
      </div>
      {selectError.address && (
        <div className={`m-2 ${styles.error} mt-1`}>{selectError.address} </div>
      )}

      <Toast />
    </>
  );
};

export default CheckoutAddress;
