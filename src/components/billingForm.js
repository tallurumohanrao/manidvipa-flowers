"use client";
import { useEffect, useState } from "react";
import styles from "@/scss/components/billingForm.module.scss";
import { fetchListingData } from "../../hook/userCookie";
import Toast from "@/components/Toast";
import Cookies from "js-cookie";
import { useToast } from "@/context/UserContext";

const formFields = [
  { label: "Full Name", name: "full_name", type: "text" },
  { label: "Email", name: "email", type: "email" },
  { label: "Phone Number", name: "phone_number", type: "tel" },
  {
    label: "Flat, House no., Building, Company, Apartment",
    name: "address_line1",
    type: "text",
  },
  {
    label: "Street, Sector, Village, Area",
    name: "address_line2",
    type: "text",
  },
  { label: "Landmark", name: "landmark", type: "text" },
  // { label: "PIN Code", name: "pincode", type: "text" },
];

export default function BillingForm({
  userToken,
  guestSession,
  editAddress,
  closeModal,
  onSuccess = () => {},
}) {
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
    address_type: "",
    is_default: "",
    cart_session: guestSession,
  });
  const [errors, setErrors] = useState({});

  const [submitError, setSubmitError] = useState("");
  const { showToast } = useToast();

  const [cities, setCities] = useState();
  const [states, setStates] = useState();

  // Fetch user token and ID

  useEffect(() => {
    if (editAddress && editAddress.data) {
      setFormData({
        address_id: editAddress.data.id,
        full_name: editAddress.data.full_name || "",
        email: editAddress.data.email || "",
        phone_number: editAddress.data.phone_number || "",
        address_line1: editAddress.data.address_line1 || "",
        address_line2: editAddress.data.address_line2 || "",
        landmark: editAddress.data.landmark || "",
        city: editAddress.data.city || "",
        state: editAddress.data.state || "",
        pincode: editAddress.data.pincode || "",
        address_type: editAddress.data.address_type || "",
        is_default: editAddress.data.is_default || "",
      });
    }
  }, [editAddress]);

  const validateField = (name, value) => {
    if (name === "landmark" || name === "address_type" || name === "is_default")
      return "";
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

  useEffect(() => {
    const fetchData = async () => {
      try {
        const citiesRes = await fetchListingData("GET", "cities", userToken);
        const statesRes = await fetchListingData("GET", "states", userToken);

        if (citiesRes) {
          setCities(citiesRes?.data);
        }
        if (statesRes) {
          setStates(statesRes?.data);
        }
      } catch (error) {
        console.error("Failed to fetch data:", error);
      }
    };

    fetchData();
  }, [userToken]);

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
      const endpoint = editAddress ? "edit-address" : "store-address";
      let response;
      if (userToken) {
        response = await fetchListingData("POST", endpoint, userToken, payload);
      } else {
        const checkEndpoint = editAddress
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
        });
      }

      if (response) {
        onSuccess();
        showToast(response.message);
        closeModal(false);
      } else {
        showToast("Failed to store the address.", "error");
      }
    } catch (error) {
      console.error("Error:", error);
      setSubmitError("An error occurred while submitting the form.");
    }
  };

  const handleClose = () => {
    if (closeModal) closeModal(false);
  };

  return (
    <div className="">
      <div className="row">
        <div className="col-12">
          <div className={`${styles.billingform}`}>
            {/* <div className={`${styles.title}`}>
              <h3>
                <span className={`${styles.highlight}`}>
                  {editAddress ? "Edit" : "Store"}
                </span>{" "}
                Address
              </h3>
            </div> */}
            <div className={`${styles.formContent}`}>
              <form onSubmit={handleSubmit}>
                <div className={`row ${styles.input_group}`}>
                  {formFields.map(({ label, name, type }) => (
                    <div key={name} className={`${styles.group_label} col-12`}>
                      {name !== "landmark" ? (
                        <label htmlFor={name}>
                          {label}
                          <span>*</span>
                        </label>
                      ) : (
                        <label htmlFor={name}>{label}</label>
                      )}
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
                  ))}
                  <div className={`${styles.group_label} col-md-6`}>
                    <label htmlFor="pincode">
                      PIN Code <span>*</span>
                    </label>
                    <input
                      type="number"
                      id="pincode"
                      name="pincode"
                      value={formData.pincode}
                      onChange={handleChange}
                      placeholder="Enter PIN Code"
                    ></input>
                    {errors?.pincode && (
                      <div className={`${styles.error}`}>{errors?.pincode}</div>
                    )}
                  </div>
                  <div className={`${styles.group_label} col-md-6`}>
                    <label htmlFor="city">
                      City <span>*</span>
                    </label>
                    <select
                      id="city"
                      name="city"
                      value={formData.city}
                      onChange={handleChange}
                    >
                      <option value="">Select</option>
                      {cities?.map((item, index) => (
                        <option key={index} value={item?.name}>
                          {item?.name}
                        </option>
                      ))}
                    </select>
                    {errors?.city && (
                      <div className={`${styles.error}`}>{errors?.city}</div>
                    )}
                  </div>
                  <div className={`${styles.group_label} col-md-6`}>
                    <label htmlFor="state">
                      State <span>*</span>
                    </label>
                    <select
                      id="state"
                      name="state"
                      value={formData.state}
                      onChange={handleChange}
                    >
                      <option value="">Select</option>
                      {states?.map((item, index) => (
                        <option key={index} value={item?.name}>
                          {item?.name}
                        </option>
                      ))}
                    </select>
                    {errors?.state && (
                      <div className={`${styles.error}`}>{errors?.state}</div>
                    )}
                  </div>
                  <div className={`${styles.group_label} col-md-6`}>
                    <label className="py-1">Address Type</label>
                    <div className="d-flex flex-start mt-2">
                      <div className="d-flex flex-row align-items-center">
                        <input
                          type="radio"
                          id="address_type_home"
                          name="address_type"
                          value="1"
                          checked={formData.address_type === "1"}
                          onChange={handleChange}
                        />
                        <label htmlFor="address_type_home" className="p-2">
                          Home
                        </label>
                      </div>
                      <div className="d-flex flex-row align-items-center">
                        <input
                          type="radio"
                          id="address_type_office"
                          name="address_type"
                          value="2"
                          checked={formData.address_type === "2"}
                          onChange={handleChange}
                        />
                        <label htmlFor="address_type_office" className="p-2">
                          Office
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div className={`row ${styles.input_group}`}>
                  <div className={`${styles.group_label} col-12`}>
                    <label htmlFor="is_default">
                      Make this Default Address
                    </label>
                  </div>
                  <div className="col-12" style={{ width: "100% !important" }}>
                    <div className="d-flex flex-row">
                      <input
                        style={{ width: "max-content" }}
                        type="checkbox"
                        id="is_default"
                        name="is_default"
                        checked={formData.is_default === "1"}
                        onChange={(e) =>
                          handleChange({
                            target: {
                              name: "is_default",
                              value: e.target.checked ? "1" : "0",
                            },
                          })
                        }
                      />
                      <label htmlFor="is_default" className="p-2">
                        Default Address
                      </label>
                    </div>
                  </div>
                </div>

                <div className="d-flex">
                  <button
                    type="button"
                    className="tyrian-purple"
                    onClick={handleClose}
                  >
                    Close
                  </button>

                  <button
                    className="green-but"
                    type="submit"
                    onClick={handleSubmit}
                  >
                    {editAddress ? "Update" : "Submit"}
                  </button>
                </div>
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
