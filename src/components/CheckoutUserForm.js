"use client";
import React, { useState } from "react";
import styles from "@/scss/pages/checkout.module.scss";
import styles1 from "@/scss/components/billingForm.module.scss";
import Link from "next/link";
import { FaThumbsUp } from "react-icons/fa6";

const formFields = [
  { label: "Full Name", name: "name", type: "text" },
  { label: "Email", name: "email", type: "email" },
  { label: "Phone Number", name: "contact_number", type: "tel" },
];

const CheckoutUserForm = ({
  userToken,
  guestSession,
  userData,
  formData: parentSelectedAddress,
  onUserChange,
  formErrors,
}) => {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    contact_number: "",
    cart_session: guestSession,
    parentSelectedAddress,
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    onUserChange({ ...formData, [name]: value });
  };

  return (
    <>
      <div className="mb-3">
        <div className={styles.payment_section}>
          <div className="p-3">
            {userToken ? (
              <div>
                <div className="d-flex flex-row justify-content-between">
                  <div>
                    <h5 className={styles.logged_in_text}>
                      LOGGED IN <FaThumbsUp />
                    </h5>
                  </div>
                  <div>
                    <Link
                      style={{
                        whiteSpace: "nowrap",
                        textDecoration: "underline",
                      }}
                      href="/my-account"
                      className={styles.new_address}
                    >
                      + Edit Profile
                    </Link>
                  </div>
                </div>
                <p className={styles.user_name_mobile}>
                  {userData?.user?.name} +{userData?.user?.mobile}
                </p>
              </div>
            ) : (
              <>
                <div
                  className={`${styles1.billingform}`}
                  style={{ width: "100%" }}
                >
                  <div>
                    <h5>Login or Register</h5>
                  </div>
                  <div className=" d-flex flex-row">
                    <Link href={"/login"}>
                      <div className={`mx-2 ${styles.payment_type}`}>
                        <input
                          className={styles.radio_input}
                          type="radio"
                          id="Login"
                        />
                        <label className="form-check-label" htmlFor={`Login`}>
                          Login
                        </label>
                      </div>
                    </Link>
                    <Link href={"/register"}>
                      <div className={`mx-2 ${styles.payment_type}`}>
                        <input
                          className={styles.radio_input}
                          type="radio"
                          id="Login"
                        />
                        <label className="form-check-label" htmlFor={`Login`}>
                          Register
                        </label>
                      </div>
                    </Link>
                  </div>
                </div>
                <div
                  className={`${styles1.billingform}`}
                  style={{ width: "100%" }}
                >
                  <div className={`${styles1.title}`}>
                    <h6>Buy Now Without an Account</h6>
                  </div>
                  <div className={`${styles1.formContent}`}>
                    {formFields.map(({ label, name, type }) => (
                      <div key={name} className={`row ${styles1.input_group}`}>
                        <div className="col-md-5">
                          <label htmlFor={name}>
                            {label}
                            <span>*</span>
                          </label>
                        </div>
                        <div className="col-md-7">
                          <input
                            type={type}
                            id={name}
                            name={name}
                            value={formData[name]}
                            onChange={handleChange}
                            placeholder={label}
                          />
                          {formErrors[name] && (
                            <div className={`${styles1.error} mt-1`}>
                              {formErrors[name]}
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </>
  );
};
export default CheckoutUserForm;
