"use client";

import React, { useState, useCallback, useEffect } from "react";
import styles from "@/scss/pages/userAddresses.module.scss";
import { fetchListingData } from "../../hook/userCookie";
import { useToast } from "@/app/(pages)/context/page";
import Toast from "./Toast";
import Modal from "./modal";
import BillingForm from "./billingForm";

const MyAddresses = ({ userToken, guestSession }) => {
  const [selectedAddressId, setSelectedAddressId] = useState(null);
  const [userAddress, setUserAddress] = useState([]);
  const [editAddress, setEditAddress] = useState([]);
  const { showToast } = useToast();

  const fetchAddresses = useCallback(async () => {
    if (userToken) {
      try {
        const data = await fetchListingData("GET", "addresses", userToken);
        setUserAddress(data || []);
      } catch (error) {
        console.error("Error fetching addresses:", error);
      }
    }
  }, [userToken]);

  useEffect(() => {
    fetchAddresses();
  }, [fetchAddresses]);

  const handleDelete = async (addressId) => {
    try {
      const formData = { address_id: addressId };
      const response = await fetchListingData(
        "DELETE",
        "delete-address-by-id",
        userToken,
        formData
      );

      if (response) {
        showToast(response.message, "error");
        await fetchAddresses();
      } else {
        showToast("Failed to delete the address", "error");
      }
    } catch (error) {
      alert("Failed to delete address:");
      alert("An error occurred while deleting the address.");
    }
  };

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
      } else {
        console.error("No data received for the given ID.");
      }
    } catch (error) {
      console.error("Error fetching address by ID:", error);
    }
  };

  const getEditedAddresss = () => {
    fetchAddresses();
  };

  // modal details

  return (
    <div className="container">
      <div className="row">
        <div className="col-12">
          <div className="mb-3 mt-4 mt-sm-1 text-end">
            {/* <Link
              href="/address?redirect=myAccount?tab=2"
            > */}
            <Modal
              buttonClass="green-but"
              title={`Add Address`}
              buttonName={`Add Address`}
            >
              <BillingForm
                userToken={userToken}
                guestSession={guestSession}
                onSuccess={() => getEditedAddresss()}
              />
            </Modal>
          </div>
          {userAddress?.data?.length > 0 ? (
            userAddress.data.map((item, index) => (
              <div key={index} className={`row ${styles.addresses}`}>
                <div className="col-sm-10">
                  <p style={{ fontWeight: "bold", marginBottom: "8px" }}>
                    {item.full_name}{" "}
                    {item.is_default !== null || 0 ? (
                      <span
                        className="green-but"
                        style={{ width: "auto", margin: "0", padding: "0" }}
                      >
                        Default
                      </span>
                    ) : (
                      <></>
                    )}
                  </p>
                  <p>
                    {item.address_line1}, {item.address_line2}, {item.landmark},{" "}
                    {item.city}, {item.state}, {item.phone_number}
                  </p>
                  {/* <p>{item.address_line2}</p>
                  <p>{item.landmark}</p>
                  <p>{item.city}</p>
                  <p>{item.state}</p>
                  <p>{item.phone_number}</p> */}
                </div>
                <div className={`col-sm-2 ${styles.actionsButtons}`}>
                  <Modal
                    buttonClass="green-but"
                    buttonName="Edit"
                    title={`Edit Address`}
                    onOpen={() => editById(item.id)}
                  >
                    <BillingForm
                      userToken={userToken}
                      guestSession={guestSession}
                      editAddress={editAddress}
                      onSuccess={() => getEditedAddresss()}
                    />
                  </Modal>
                  <Modal
                    buttonClass="primary-but"
                    buttonName="Delete"
                    onOpen={() => setSelectedAddressId(item.id)}
                    onConfirm={() => handleDelete(selectedAddressId)}
                  >
                    <p>Are you sure you want to delete this address?</p>
                  </Modal>
                </div>
              </div>
            ))
          ) : (
            <p>No addresses found.</p>
          )}
        </div>
      </div>
      <Toast />
    </div>
  );
};

export default MyAddresses;
