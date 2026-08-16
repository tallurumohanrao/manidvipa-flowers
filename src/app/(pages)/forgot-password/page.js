"use client";
import React, { useState } from "react";
import { useToast } from "@/context/UserContext";
import Toast from "@/components/Toast";
import styles from "@/scss/pages/forgotPassword.module.scss";
import Banner from "@/components/banner";
import Link from "next/link";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

export default function Page() {
  const [formData, setFormData] = useState({
    email: "",
  });
  const { showToast } = useToast();
  const [message, setMessage] = useState(null);

  const validateForm = () => {
    if (!formData.email) {
      setMessage("email required");
      return false;
    }
    return true;
  };

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    try {
      const response = await fetch(`${url}/send-password-reset-notification`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email: formData.email }),
      });
      const result = await response.json();
      if (!result.status) {
        showToast(result.message, "error");
        setMessage(result.message);
      } else {
        showToast(result.message);
        setMessage(result.message);
      }
    } catch (error) {
      showToast(error.message, "error");
    }
  };

  return (
    <>
      {/* BANNER SECTION START  */}
      <Banner title="Forgot Password" />
      {/* BANNER SECTION END  */}
      <section className={`py-5 ${styles.forgotPassword_sec}`}>
        <div className="container">
          <div className="row d-flex justify-content-center align-items-center">
            <div className="col-sm-6 col-md-5 col-lg-4">
              {/* <h4>Forgot Password</h4> */}
              <form onSubmit={handleSubmit}>
                <div className={styles.form_group}>
                  <label>
                    Email <span>*</span>
                  </label>
                  <input
                    id="email"
                    type="email"
                    className={`form-input ${styles.form_input}`}
                    onChange={handleChange}
                    name="email"
                  />
                </div>
                {message && <p className={`${styles.message}`}>{message}</p>}
                <div className={`mb-3 ${styles.form_group}`}>
                  <span>
                    <Link href={"/register"}>Register</Link>
                  </span>
                  <br />
                  <span>
                    Already have an account?&nbsp;
                    <Link href="/login">Login</Link>
                  </span>
                </div>

                <div className="form-button">
                  <button type="submit" className="blue-but">
                    Continue
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>
      <Toast />
    </>
  );
}
