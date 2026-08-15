"use client";
import React, { useState } from "react";
import Cookies from "js-cookie";
import { useRouter } from "next/navigation";
import Banner from "@/components/banner";
import styles from "@/scss/pages/login.module.scss";
import { useUser } from "@/app/(pages)/context/page";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import Link from "next/link";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

export default function Login() {
  const router = useRouter();
  const { guestSession } = useUser();
  const { setIsAuthenticated } = useUser();
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    cart_session: guestSession,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [hasRefreshed, setHasRefreshed] = useState(false);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const togglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      const response = await fetch(`${url}/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      if (response.ok) {
        const userDetails = await response.json();

        Cookies.set("userSession", JSON.stringify(userDetails.data), {
          secure: true,
          // httpOnly: true,
          sameSite: "Strict",
          expires: 730,
        });
        console.log("refresh");
        router.push("/");
        if (!hasRefreshed) {
          setTimeout(() => {
            router.refresh();
            setHasRefreshed(true);
          }, 10);
        }
        // setTimeout(() => router.refresh(), 10);

        if (Cookies.get("userSession")) {
          setIsAuthenticated(true);
        }
      } else {
        const errorDetails = await response.text();
        throw new Error(`Login failed: ${response.status} ${errorDetails}`);
      }
    } catch (error) {
      // console.error("Error:", response.message);
      setError("Failed to login. Please try again.");
    } finally {
      setLoading(false);
    }
  };
  return (
    <>
      <Banner title="Login" />
      <section className={`py-5 ${styles.login_sec}`}>
        <div className="container">
          <div className="row d-flex justify-content-center align-items-center">
            <div className="col-sm-6 col-md-5 col-lg-4">
              {/* <h4>Login Account</h4> */}
              <form onSubmit={handleSubmit}>
                <div className={styles.form_group}>
                  <label>Email</label>
                  <input
                    type="email"
                    className={`form-input ${styles.form_input}`}
                    onChange={handleChange}
                    name="email"
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    Password<sup className="mandatory">*</sup>
                  </label>
                  <div className={styles.password_wrapper}>
                    <input
                      type={showPassword ? "text" : "password"}
                      className={`form-input ${styles.form_input}`}
                      onChange={handleChange}
                      name="password"
                    />
                    <span
                      className={styles.eye_icon}
                      onClick={togglePasswordVisibility}
                    >
                      {showPassword ? <FaEyeSlash /> : <FaEye />}
                    </span>
                  </div>
                </div>
                {error && <p className={styles.error_message}>{error}</p>}
                <div className={`${styles.form_group} mb-3`}>
                  <span>
                    <Link href="/forgot-password">Forgot Password</Link>
                  </span>
                  <br />
                  <span>
                    Dont have an account?{" "}
                    <Link href={"/register"}>Register</Link>
                  </span>
                </div>
                <div className="form-button">
                  <button type="submit" className="blue-but" disabled={loading}>
                    {loading ? "Loading..." : "Login"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
