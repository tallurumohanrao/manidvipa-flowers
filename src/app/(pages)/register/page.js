"use client";
import React, { useState } from "react";
import styles from "@/scss/pages/register.module.scss";
import Banner from "@/components/banner";
import { useRouter } from "next/navigation";
import { useUser } from "../context/page";
import Link from "next/link";

export default function RegisterPage() {
  const router = useRouter();
  const { guestSession } = useUser();

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    mobile: "",
    password: "",
    c_password: "",
    cart_session: guestSession,
  });

  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false); // Add loading state

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const validateForm = () => {
    if (formData.password !== formData.c_password) {
      setMessage("Passwords do not match.");
      return false;
    }
    if (!/^\d{10}$/.test(formData.mobile)) {
      setMessage("Please enter a valid 10-digit mobile number.");
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) return;

    setLoading(true); // Show loading indicator
    setMessage("");

    try {
      // Register the user
      const registerResponse = await fetch("/api/auth/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const registerData = await registerResponse.json();

      if (!registerResponse.ok) {
        setMessage(registerData.message || "Registration failed!");
        setLoading(false);
        return;
      }
      router.push("/login");
    } catch (error) {
      console.error("Error during registration:", error);
      setMessage("An error occurred during registration or login.");
    } finally {
      setLoading(false); // Hide loading indicator
    }
  };

  return (
    <>
      {/* BANNER SECTION START */}
      <Banner title="Register" />
      {/* BANNER SECTION END */}
      <section className={`py-5 ${styles.register_sec}`}>
        <div className="container">
          <div className="row d-flex justify-content-center align-items-center">
            <div className="col-sm-6 col-md-5 col-lg-4">
              {/* <h4>Register Account</h4> */}
              <form onSubmit={handleSubmit}>
                <div className={styles.form_group}>
                  <label>Name</label>
                  <input
                    name="name"
                    type="text"
                    className={`form-input ${styles.form_input}`}
                    value={formData.name}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>Email</label>
                  <input
                    type="email"
                    name="email"
                    className={`form-input ${styles.form_input}`}
                    value={formData.email}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    Password<sup className="mandatory">*</sup>
                  </label>
                  <input
                    type="password"
                    name="password"
                    className={`form-input ${styles.form_input}`}
                    value={formData.password}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    Confirm Password<sup className="mandatory">*</sup>
                  </label>
                  <input
                    type="password"
                    name="c_password"
                    className={`form-input ${styles.form_input}`}
                    value={formData.c_password}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>Phone No</label>
                  <input
                    type="tel"
                    name="mobile"
                    className={`form-input ${styles.form_input}`}
                    value={formData.mobile}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={` ${styles.form_group} mb-3`}>
                  <span>
                    Already have an account?&nbsp;
                    <Link href="/login" color="#00aaf3">
                      Login
                    </Link>
                  </span>
                </div>
                <div className="form-button">
                  <button type="submit" className="blue-but" disabled={loading}>
                    {loading ? "Submitting..." : "Continue"}
                  </button>
                </div>
              </form>
              {message && <p>{message}</p>}
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
