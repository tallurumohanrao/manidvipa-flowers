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

  useEffect(() => {
    if (userData?.user) setUserDetails(userData.user);
  }, [userData]);

  const handleChange = ({ target: { name, value } }) =>
    setUserDetails((prev) => ({ ...prev, [name]: value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const response = await fetchListingData(
        "POST",
        "update-profile",
        userToken,
        userDetails
      );
      response?.success && alert(`Profile updated: ${response.message}`);
    } catch (error) {
      console.error("Error updating profile:", error);
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
          <div className={`form-button ${styles.customButton}`}>
            <button disabled={!editable} type="submit" className="blue-but">
              Save
            </button>
          </div>
        </form>
      </div>
    </section>
  );
};

export default MyProfile;
