"use client";

import React, { useEffect, useState } from "react";
import { fetchListingData } from "../../hook/userCookie";
import styles from "@/scss/pages/register.module.scss";

const MyProfile = ({ userData, userToken }) => {
  const [userDetails, setUserDetails] = useState({
    name: "",
    email: "",
    mobile: "",
  });
  const [editable, setEditable] = useState(false);
  const [message, setMessage] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (userData?.user) setUserDetails(userData.user);
  }, [userData]);

  const handleChange = ({ target: { name, value } }) =>
    setUserDetails((prev) => ({ ...prev, [name]: value }));

  const validateForm = () => {
    if (!userDetails.name?.trim()) {
      setMessage("Name is required");
      return false;
    }
    if (!userDetails.email?.trim()) {
      setMessage("Email is required");
      return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userDetails.email)) {
      setMessage("Please enter a valid email address");
      return false;
    }
    if (!/^\d{10}$/.test(String(userDetails.mobile || ""))) {
      setMessage("Mobile number must be 10 digits");
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!editable || !validateForm()) return;
    setIsSaving(true);
    try {
      const response = await fetchListingData(
        "POST",
        "update-profile",
        userToken,
        userDetails
      );
      if (response?.success) {
        setMessage(response.message || "Profile updated successfully.");
        setEditable(false);
      } else {
        setMessage(response?.message || "Unable to update profile.");
      }
    } catch (error) {
      console.error("Error updating profile:", error);
      setMessage(error.message);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <section className={styles.register_sec}>
      <div className="container">
        <form onSubmit={handleSubmit}>
          <div className={styles.form_group}>
            <div className={`${styles.changeButton}`}>
              <button
                type="button"
                onClick={() => setEditable(true)}
                className="primary-but"
                disabled={editable}
              >
                Edit Details
              </button>
            </div>
            {["name", "email", "mobile"].map((field) => (
              <div key={field} className={styles.form_group}>
                <label htmlFor={field}>
                  {field.charAt(0).toUpperCase() + field.slice(1)}
                </label>
                <input
                  type={field === "email" ? "email" : "text"}
                  id={field}
                  name={field}
                  value={userDetails[field] || ""}
                  readOnly={!editable}
                  onChange={handleChange}
                  className={`form-input ${styles.form_input}`}
                />
              </div>
            ))}
          </div>
          {message && <p className={styles.errorMessage}>{message}</p>}
          <div className={`form-button ${styles.customButton}`}>
            <button disabled={!editable || isSaving} type="submit" className="blue-but">
              {isSaving ? "Saving..." : "Save"}
            </button>
          </div>
        </form>
      </div>
    </section>
  );
};

export default MyProfile;
