"use client";
import styles from "@/scss/pages/register.module.scss";
import { useRouter } from "next/navigation";
import Cookies from "js-cookie";
import { useState } from "react";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

const ChangePassword = ({ userToken }) => {
  const router = useRouter();
  const [formData, setFormData] = useState({
    current_password: "",
    new_password: "",
    confirm_new_password: "",
  });

  const [message, setMessage] = useState("");

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const validateForm = () => {
    if (!formData.new_password || !formData.current_password) {
      setMessage("Password fields Should not be Empty");
      return false;
    }
    if (formData.new_password.length < 8) {
      setMessage("New password must be at least 8 characters long");
      return false;
    }
    if (formData.new_password !== formData.confirm_new_password) {
      setMessage("New Password and Confirm Password are not Matching");
      return false;
    }
    if (formData.new_password == formData.current_password) {
      setMessage("New Password and Current Password Should not be Same");
      return false;
    }
    return true;
  };
  const handleUpdate = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    if (!userToken) {
      setMessage("Please login again before changing your password");
      return;
    }
    const data = {
      current_password: formData.current_password,
      new_password: formData.new_password,
      new_password_confirmation: formData.confirm_new_password,
    };
    try {
      const response = await fetch(`${url}/update-user-password`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${userToken}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });
      const result = await response.json();

      if (response.ok && result.success) {
        setMessage(result.message || "Password changed successfully.");
        Cookies.remove("userSession", { sameSite: "Strict", path: "/" });
        router.push("/login");
      } else {
        setMessage(result.message || "Unable to update password.");
      }
    } catch (error) {
      console.error("Error:", error.message);
      setMessage(error.message);
    }
  };
  return (
    <>
      <section className={`${styles.register_sec}`}>
        <div className="container">
          <div className="row d-flex justify-content-center align-items-center">
            <div className="col-12">
              <form onSubmit={handleUpdate}>
                <div className={styles.form_group}>
                  <label>
                    Current Password <span>*</span>
                  </label>
                  <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    className={`form-input ${styles.form_input}`}
                    value={formData.current_password || ""}
                    onChange={handleChange}
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    New Password <span>*</span>
                  </label>
                  <input
                    id="new_password"
                    name="new_password"
                    type="password"
                    className={`form-input ${styles.form_input}`}
                    value={formData.new_password}
                    onChange={handleChange}
                  />
                </div>
                <div className={styles.form_group}>
                  <label>
                    Confirm New Password <span>*</span>
                  </label>
                  <input
                    id="confirm_new_password"
                    name="confirm_new_password"
                    type="password"
                    className={`form-input ${styles.form_input}`}
                    value={formData.confirm_new_password}
                    onChange={handleChange}
                  />
                </div>
                <div className="form-button">
                  <button type="submit" className="blue-but">
                    Update
                  </button>
                </div>
              </form>
              {message && <p className={styles.errorMessage}>{message}</p>}
            </div>
          </div>
        </div>
      </section>
    </>
  );
};

export default ChangePassword;
