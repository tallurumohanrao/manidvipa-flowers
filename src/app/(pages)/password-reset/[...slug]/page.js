"use client";
import React, { useState } from "react";
import { useToast } from "@/context/UserContext";
import Toast from "@/components/Toast";
import { useParams, useSearchParams, useRouter } from "next/navigation";
import styles from "@/scss/pages/forgotPassword.module.scss";
import Banner from "@/components/banner";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

export default function Page() {
  const [formData, setFormData] = useState({
    new_password: "",
    confirmation_password: "",
  });
  const { showToast } = useToast();
  const router = useRouter();
  const [message, setMessage] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const params = useParams();
  const searchParams = useSearchParams();
  const slug = Array.isArray(params.slug)
    ? params.slug.join("/")
    : params.slug;
  const email = searchParams.get("email");

  const validateForm = () => {
    if (!formData.new_password || !formData.confirmation_password) {
      setMessage("Password Fields should not be Empty");
      return false;
    }
    if (formData.new_password.length < 8) {
      setMessage("New password must be at least 8 characters long");
      return false;
    }
    if (formData.new_password !== formData.confirmation_password) {
      setMessage("Passwords are not Matching");
      return false;
    }
    if (!slug || !email) {
      setMessage("Password reset link is invalid. Please request a new link.");
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
    setIsSubmitting(true);
    try {
      const response = await fetch(`${url}/password/update`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          token: slug,
          email,
          password: formData.new_password,
          password_confirmation: formData.confirmation_password,
        }),
      });
      const result = await response.json();
      const nextMessage = result.message || "Failed to change password.";

      if (response.ok && result.success) {
        showToast(nextMessage);
        setMessage(nextMessage);
        setTimeout(() => {
          router.push("/login");
        }, 400);
      } else {
        showToast(nextMessage, "error");
        setMessage(nextMessage);
      }
    } catch (error) {
      showToast(error.message, "error");
      setMessage(error.message);
    } finally {
      setIsSubmitting(false);
    }
  };
  return (
    <>
      {/* BANNER SECTION START  */}
      <Banner title="Reset Password" />
      {/* BANNER SECTION END  */}
      <section className={`py-5 ${styles.forgotPassword_sec}`}>
        <div className="container">
          <div className="row d-flex justify-content-center align-items-center">
            <div className="col-sm-6 col-md-5 col-lg-4">
              {/* <h4>Reset Password</h4> */}
              <form onSubmit={handleSubmit}>
                <div className={styles.form_group}>
                  <label>
                    New Password <span>*</span>
                  </label>
                  <input
                    id="new_password"
                    type="password"
                    className={`form-input ${styles.form_input}`}
                    onChange={handleChange}
                    name="new_password"
                    value={formData.new_password}
                    autoComplete="new-password"
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    Confirm New Password <span>*</span>
                  </label>
                  <input
                    id="confirmation_password"
                    type="password"
                    className={`form-input ${styles.form_input}`}
                    onChange={handleChange}
                    name="confirmation_password"
                    value={formData.confirmation_password}
                    autoComplete="new-password"
                    required
                  />
                </div>
                {message && <p className={`${styles.message}`}>{message}</p>}
                <div className="form-button">
                  <button type="submit" className="blue-but" disabled={isSubmitting}>
                    {isSubmitting ? "Updating..." : "Update"}
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
