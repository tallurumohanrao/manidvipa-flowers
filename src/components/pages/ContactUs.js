"use client";

import React, { useMemo, useState } from "react";
import Link from "next/link";
import {
  FaClock,
  FaEnvelope,
  FaFacebookF,
  FaInstagram,
  FaLock,
  FaMapMarkerAlt,
  FaPhoneAlt,
  FaRegClock,
  FaShoppingBag,
  FaTruck,
  FaWhatsapp,
  FaYoutube,
} from "react-icons/fa";
import { fetchListingData } from "../../../hook/userCookie";
import { useToast } from "@/context/UserContext";
import styles from "@/scss/pages/contactUs.module.scss";

const FALLBACK_PHONE = "+91 73375 25445";
const FALLBACK_EMAIL = "support@manidvipaflowers.com";
const FALLBACK_ADDRESS = "Hyderabad, Telangana, India";
const FALLBACK_HOURS = "Mon - Sun 6AM - 10PM";

const defaultTopics = [
  "Fresh Flowers",
  "Puja Flowers",
  "Decorations",
  "Subscriptions",
  "Delivery Support",
];

function getSettings(siteSettings) {
  return siteSettings?.data || siteSettings || {};
}

function stripHtml(value) {
  return String(value || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function normalizePhone(value) {
  return String(value || "").replace(/\D/g, "");
}

function buildTelHref(value) {
  const phone = normalizePhone(value);
  return phone ? `tel:+${phone}` : `tel:${normalizePhone(FALLBACK_PHONE)}`;
}

function buildWhatsAppHref(value) {
  const phone = normalizePhone(value) || normalizePhone(FALLBACK_PHONE);
  return `https://wa.me/${phone}`;
}

function buildMapHref(address) {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

function buildMapEmbedSrc(address) {
  return `https://www.google.com/maps?q=${encodeURIComponent(address)}&output=embed`;
}

function normalizeExternalLink(value) {
  if (!value || value === "#") return "#";
  return String(value).startsWith("http") ? value : `https://${value}`;
}

function ContactInfoItem({ icon, title, children }) {
  return (
    <div className={styles.infoItem}>
      <span className={styles.infoIcon}>{icon}</span>
      <div>
        <strong>{title}</strong>
        {children}
      </div>
    </div>
  );
}

function SupportItem({ icon, title, subtitle }) {
  return (
    <div className={styles.supportItem}>
      <span>{icon}</span>
      <div>
        <strong>{title}</strong>
        <small>{subtitle}</small>
      </div>
    </div>
  );
}

export default function ContactUs({
  contactDetails,
  produtTitles,
  userToken,
  siteSettings,
}) {
  const { showToast } = useToast();
  const settings = getSettings(siteSettings);
  const staticDescription = stripHtml(contactDetails?.data?.description);
  const phone = settings?.SITE_PHONE || FALLBACK_PHONE;
  const whatsapp = settings?.SITE_WHATSAPP || phone;
  const email = settings?.SITE_EMAIL || FALLBACK_EMAIL;
  const address = settings?.SITE_ADDRESS || FALLBACK_ADDRESS;
  const hours = settings?.SITE_HOURS || settings?.STORE_HOURS || FALLBACK_HOURS;
  const topics = useMemo(() => {
    const categoryTopics = Array.isArray(produtTitles?.data)
      ? produtTitles.data
          .map((item) => item?.title)
          .filter(Boolean)
          .slice(0, 8)
      : [];

    return categoryTopics.length ? categoryTopics : defaultTopics;
  }, [produtTitles]);

  const socialLinks = [
    {
      label: "Instagram",
      href: normalizeExternalLink(settings?.INSTAGRAM_LINK),
      icon: <FaInstagram />,
    },
    {
      label: "Facebook",
      href: normalizeExternalLink(settings?.FACEBOOK_LINK),
      icon: <FaFacebookF />,
    },
    {
      label: "WhatsApp",
      href: buildWhatsAppHref(whatsapp),
      icon: <FaWhatsapp />,
    },
    {
      label: "YouTube",
      href: normalizeExternalLink(settings?.YOUTUBE_LINK),
      icon: <FaYoutube />,
    },
  ];

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    mobile: "",
    subject: "",
    message: "",
  });
  const [loading, setLoading] = useState(false);

  const handleChange = (event) => {
    const { name, value } = event.target;
    setFormData((currentData) => ({ ...currentData, [name]: value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);

    try {
      const response = await fetchListingData(
        "POST",
        "contact-us",
        userToken,
        formData
      );

      if (response?.success) {
        showToast(response.data || response.message || "Message sent successfully.", "success");
        setFormData({
          name: "",
          email: "",
          mobile: "",
          subject: "",
          message: "",
        });
      } else {
        showToast(response?.message || "Unable to send message right now.", "error");
      }
    } catch (error) {
      console.error("Error submitting contact form:", error);
      showToast("An unexpected error occurred. Please try again later.", "error");
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className={styles.contactPage}>
      <div className="container">
        <nav className={styles.breadcrumbs} aria-label="Breadcrumb">
          <Link href="/">Home</Link>
          <span>›</span>
          <strong>Contact Us</strong>
        </nav>

        <div className={styles.contactCard}>
          <aside className={styles.infoPanel}>
            <h1>Get in Touch</h1>
            <p>
              {staticDescription ||
                "We're here to help you with your flower needs."}
            </p>

            <div className={styles.infoList}>
              <ContactInfoItem icon={<FaPhoneAlt />} title={phone}>
                <Link href={buildTelHref(phone)}>Call or WhatsApp</Link>
              </ContactInfoItem>

              <ContactInfoItem icon={<FaEnvelope />} title={email}>
                <Link href={`mailto:${email}`}>We reply within 24 hours</Link>
              </ContactInfoItem>

              <ContactInfoItem icon={<FaClock />} title={hours}>
                <span>We are open all days</span>
              </ContactInfoItem>

              <ContactInfoItem icon={<FaMapMarkerAlt />} title={address}>
                <Link href={buildMapHref(address)} target="_blank" rel="noopener noreferrer">
                  We deliver across Hyderabad
                </Link>
              </ContactInfoItem>
            </div>

            <div className={styles.socialBlock}>
              <span>Follow Us</span>
              <div className={styles.socialLinks}>
                {socialLinks.map((social) => (
                  <Link
                    href={social.href}
                    key={social.label}
                    aria-label={social.label}
                    target={social.href === "#" ? undefined : "_blank"}
                    rel={social.href === "#" ? undefined : "noopener noreferrer"}
                  >
                    {social.icon}
                  </Link>
                ))}
              </div>
            </div>
          </aside>

          <div className={styles.formPanel}>
            <h2>Send us a Message</h2>
            <form onSubmit={handleSubmit}>
              <div className={styles.formGrid}>
                <input
                  name="name"
                  type="text"
                  placeholder="Your Name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                />
                <input
                  name="mobile"
                  type="tel"
                  placeholder="Your Phone Number"
                  value={formData.mobile}
                  onChange={handleChange}
                  required
                />
                <input
                  name="email"
                  type="email"
                  placeholder="Your Email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                />
                <select
                  name="subject"
                  value={formData.subject}
                  onChange={handleChange}
                  required
                >
                  <option value="">Subject</option>
                  {topics.map((topic) => (
                    <option value={topic} key={topic}>
                      {topic}
                    </option>
                  ))}
                </select>
              </div>
              <textarea
                name="message"
                placeholder="Your Message"
                value={formData.message}
                onChange={handleChange}
                required
              />
              <button type="submit" disabled={loading}>
                {loading ? "SENDING..." : "SEND MESSAGE"}
              </button>
            </form>
          </div>

          <div className={styles.mapPanel}>
            <iframe
              src={buildMapEmbedSrc(address)}
              title="Manidvipa Flowers location map"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
            />
            <div className={styles.mapLabel}>
              <FaMapMarkerAlt />
              <div>
                <strong>Manidvipa Flowers</strong>
                <span>Hyderabad, Telangana</span>
              </div>
            </div>
          </div>
        </div>

        <div className={styles.supportStrip}>
          <SupportItem icon={<FaRegClock />} title="Quick Response" subtitle="We reply quickly" />
          <SupportItem icon={<FaWhatsapp />} title="WhatsApp Support" subtitle="Easy & fast" />
          <SupportItem icon={<FaLock />} title="Safe Payments" subtitle="100% Secure" />
          <SupportItem icon={<FaTruck />} title="On-Time Delivery" subtitle="Across Hyderabad" />
          <SupportItem icon={<FaShoppingBag />} title="Fresh Flowers" subtitle="Sourced daily" />
        </div>
      </div>
    </section>
  );
}
