"use client";
import React, { useState } from "react";
import styles from "@/scss/pages/contactUs.module.scss";
import Banner from "@/components/banner";
import { FaLocationDot } from "react-icons/fa6";
import { FaWhatsapp } from "react-icons/fa";
import { TbPhoneCalling } from "react-icons/tb";
import { RiTimerFlashFill } from "react-icons/ri";
import { fetchListingData } from "../../../hook/userCookie";
import { useToast } from "@/app/(pages)/context/page";
import Link from "next/link";

export default function ContactUs({ contactDetails, produtTitles, userToken }) {
  const contactData = contactDetails?.data?.description;
  const { showToast } = useToast();
  const listingsTitles = produtTitles?.data;

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    mobile: "",
    subject: "",
    message: "",
  });

  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const Response = await fetchListingData(
        "POST",
        "contact-us",
        userToken,
        formData
      );
      if (Response) {
        showToast(Response.data);
        setFormData({
          name: "",
          email: "",
          mobile: "",
          subject: "",
          message: "",
        });
      } else {
        showToast("Failed to place the order. Please try again.");
      }
    } catch (error) {
      console.error("Error during order submission:", error);
      setMessage(
        "An error occurred during order submission. Please try again later."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      {/* BANNER SECTION START */}
      <Banner title="Contact Us" />
      {/* BANNER SECTION END */}
      <section className={`py-5 ${styles.register_sec}`}>
        <div className="container">
          <div className="row">
            <div className={`col-md-5 col-sm-6 my-2 ${styles.imformation_sec}`}>
              <div className="row mb-3">
                <div className={`col-sm-2 col-xs-3 ${styles.contact_icons}`}>
                  <div>
                    <FaLocationDot className={styles.user_icon} />
                  </div>
                </div>

                <div className="col-sm-10 col-xs-9">
                  <h6>Store Address</h6>
                  <Link
                    href="https://maps.app.goo.gl/9LNCrkoYU67LutX49"
                    target="_blank"
                  >
                    8-3-214/21,Srinivas Colony , Vengal Rao Nagar, SR Nagar,
                    Hyderabad-500038, Telangana, India.
                  </Link>
                </div>
              </div>

              <div className="row mb-3">
                <div className={`col-sm-2 col-xs-3 ${styles.contact_icons}`}>
                  <div>
                    <TbPhoneCalling className={styles.user_icon} />
                  </div>
                </div>

                <div className={`col-sm-10 col-xs-9 ${styles.contact_details}`}>
                  <h6>Call Us</h6>
                  <Link href="tel:8658991106">8658991106</Link>
                </div>
              </div>

              <div className="row mb-3">
                <div className={`col-sm-2 col-xs-3 ${styles.contact_icons}`}>
                  <div>
                    <FaWhatsapp className={styles.user_icon} />
                  </div>
                </div>

                <div className={`col-sm-10 col-xs-9 ${styles.contact_details}`}>
                  <h6>WhatsApp</h6>
                  <Link href="https://wa.me/8658991106" target="_blank">
                    8658991106
                  </Link>
                </div>
              </div>

              <div className="row mb-3">
                <div className={`col-sm-2 col-xs-3 ${styles.contact_icons}`}>
                  <div>
                    <RiTimerFlashFill className={styles.user_icon} />
                  </div>
                </div>

                <div className={`col-sm-10 col-xs-9 ${styles.contact_details}`}>
                  <h6>Store Hours</h6>
                  <p>9AM - 8PM</p>
                </div>
              </div>
            </div>
            <div className="col-md-7 col-sm-6 my-2">
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
                  <label>Contact No</label>
                  <input
                    type="tel"
                    name="mobile"
                    className={`form-input ${styles.form_input}`}
                    value={formData.mobile}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className={styles.form_group}>
                  <label>Topic</label>
                  <select
                    name="subject"
                    onChange={handleChange}
                    value={formData.subject}
                    required
                    className={`form-input ${styles.form_input}`}
                  >
                    <option value="">Select Topic</option>
                    {listingsTitles?.map((title, index) => (
                      <option key={index} value={title.title}>
                        {title.title}
                      </option>
                    ))}
                  </select>
                </div>
                <div className={styles.form_group}>
                  <label>Message</label>
                  <textarea
                    rows={5}
                    name="message"
                    className={`form-input ${styles.form_input}`}
                    value={formData.message}
                    onChange={handleChange}
                    required
                  ></textarea>
                </div>
                <div className="form-button">
                  <button type="submit" className="blue-but" disabled={loading}>
                    {loading ? "Submitting..." : "Submit"}
                  </button>
                </div>
              </form>

              {/* {message && <p>{showToast(message)}</p>} */}
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
