"use client";
import { useEffect, useState } from "react";
import styles from "@/scss/components/billingForm.module.scss";
import { fetchListingData } from "../../../../hook/userCookie";
// import { fetchBlogData } from "../../../../hook/loginAuth";
import { useRouter } from "next/navigation";
import { useToast, useUser } from "@/context/UserContext";
import Toast from "@/components/Toast";
import Cookies from "js-cookie";
// import { useRouter } from "next/router";

const formFields = [
  { label: "Full Name", name: "full_name", type: "text" },
  { label: "Email", name: "email", type: "email" },
  { label: "Phone Number", name: "phone_number", type: "tel" },
  { label: "Address Line 1", name: "address_line1", type: "text" },
  { label: "Address Line 2", name: "address_line2", type: "text" },
  { label: "Landmark", name: "landmark", type: "text" },
  { label: "City", name: "city", type: "text" },
  { label: "State", name: "state", type: "text" },
  { label: "PIN Code", name: "pincode", type: "text" },
];

export default function Address({ userAddress }) {
  const router = useRouter();
  // const searchParams = useSearchParams();

  const { userToken, guestSession } = useUser();
  const [formData, setFormData] = useState({
    full_name: "",
    email: "",
    phone_number: "",
    address_line1: "",
    address_line2: "",
    landmark: "",
    city: "",
    state: "",
    pincode: "",
    address_type: "1",
    is_default: "1",
    cart_session: guestSession,
  });
  const [errors, setErrors] = useState({});
  const [submitError, setSubmitError] = useState("");
  // const [userId, setUserId] = useState(null);
  const { showToast } = useToast();
  const [redirectPage, setRedirectPage] = useState(0);

  // Fetch user token and ID
  // useEffect(() => {
  //   if (userToken) {
  //     fetchBlogData(userToken)
  //       .then((response) => {
  //         if (response?.success && response.user) {
  //           setUserId(response.user.id);
  //         } else {
  //           console.error("Failed to fetch user data or user not found.");
  //         }
  //       })
  //       .catch((error) => console.error("Error fetching user data:", error));
  //   }
  // }, [userToken]);

  useEffect(() => {
    if (userAddress && userAddress.data) {
      setFormData({
        address_id: userAddress.data.id,
        full_name: userAddress.data.full_name || "",
        email: userAddress.data.email || "",
        phone_number: userAddress.data.phone_number || "",
        address_line1: userAddress.data.address_line1 || "",
        address_line2: userAddress.data.address_line2 || "",
        landmark: userAddress.data.landmark || "",
        city: userAddress.data.city || "",
        state: userAddress.data.state || "",
        pincode: userAddress.data.pincode || "",
        address_type: userAddress.data.address_type || "1",
        is_default: userAddress.data.is_default || "1",
      });
    }
  }, [userAddress]);

  const validateField = (name, value) => {
    if (!value) return "This field is required";
    if (name === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return "Please enter a valid email address";
    }
    if (name === "phone_number" && !/^\d{10}$/.test(value)) {
      return "Please enter a valid 10-digit phone number";
    }
    if (name === "pincode" && !/^\d{6}$/.test(value)) {
      return "Please enter a valid 6-digit PIN code";
    }
    return "";
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => ({ ...prev, [name]: "" }));
  };

  const handleBlur = (e) => {
    const { name, value } = e.target;
    const error = validateField(name, value);
    if (error) {
      setErrors((prev) => ({ ...prev, [name]: error }));
    }
  };

  // useEffect(() => {
  //   const tab = searchParams.get("redirect");
  //   setRedirectPage(tab);
  // }, [searchParams]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    const validationErrors = Object.keys(formData).reduce((acc, name) => {
      const error = validateField(name, formData[name]);
      if (error) acc[name] = error;
      return acc;
    }, {});

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setSubmitError("Please fix the errors before submitting");
      return;
    }

    setSubmitError("");
    const payload = { ...formData };
    try {
      const endpoint = userAddress ? "edit-address" : "store-address";
      let response;
      if (userToken) {
        response = await fetchListingData("POST", endpoint, userToken, payload);
      } else {
        const checkEndpoint = userAddress
          ? "checkout-edit-address"
          : "checkout-store-address";

        if (!userToken) {
          response = await fetchListingData(
            "POST",
            checkEndpoint,
            null,
            payload
          );
        }
      }

      if (!userToken) {
        const adressId = response?.data?.address_id;
        const guestAddressId = Cookies.get("guestAddressId");
        if (guestAddressId) {
          Cookies.remove("guestAddressId", {
            secure: true,
            sameSite: "Strict",
          });
        }
        Cookies.set("guestAddressId", adressId, {
          secure: true,
          sameSite: "Strict",
          expires: 730,
        });
      }

      if (response) {
        showToast(response.message);
        // if (!userToken) {
        //   router.back();
        // }
        // router.push(`/${redirectPage}`);
        router.back();
      } else {
        showToast("Failed to store the address.", "error");
      }
    } catch (error) {
      console.error("Error:", error);
      setSubmitError("An error occurred while submitting the form.");
    }
  };

  return (
    <div className="container my-5">
      <div className="row">
        <div className="col-7">
          <div className={`${styles.billingform}`}>
            <div className={`${styles.title}`}>
              <h1>
                <span className={`${styles.highlight}`}>
                  {userAddress ? "Edit" : "Store"}
                </span>{" "}
                Address
              </h1>
            </div>
            <div className={`${styles.formContent}`}>
              <form onSubmit={handleSubmit}>
                {formFields.map(({ label, name, type }) => (
                  <div key={name} className={`row ${styles.input_group}`}>
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
                        onBlur={handleBlur}
                        placeholder={label}
                      />
                      {errors[name] && (
                        <div className={`${styles.error}`}>{errors[name]}</div>
                      )}
                    </div>
                  </div>
                ))}
                {/* Address Type and Default Options */}
                <div className={`row ${styles.input_group}`}>
                  <div className="col-md-5">
                    <label>Address Type</label>
                  </div>
                  <div className="col-md-7">
                    <select
                      name="address_type"
                      value={formData.address_type}
                      onChange={handleChange}
                    >
                      <option value="1">Home</option>
                      <option value="2">Office</option>
                    </select>
                  </div>
                </div>
                <div className={`row ${styles.input_group}`}>
                  <div className="col-md-5">
                    <label>Is Default</label>
                  </div>
                  <div className="col-md-7">
                    <select
                      name="is_default"
                      value={formData.is_default}
                      onChange={handleChange}
                    >
                      <option value="1">Yes</option>
                      <option value="0">No</option>
                    </select>
                  </div>
                </div>
                <button className={"primary-outlined-but"} type="submit">
                  {userAddress ? "Update" : "Submit"}
                </button>
                {submitError && (
                  <div className={`${styles.error}`}>{submitError}</div>
                )}
              </form>
            </div>
          </div>
        </div>
      </div>
      <Toast />
    </div>
  );
}
