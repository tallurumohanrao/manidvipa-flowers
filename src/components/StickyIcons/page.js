"use client";
import React, { useEffect, useState } from "react";
import Link from "next/link";
import styles from "@/scss/components/stickyIcons.module.scss";
import { FaWhatsapp, FaArrowUp, FaShoppingCart } from "react-icons/fa";
import { IoCallSharp } from "react-icons/io5";
import { useCartCount } from "@/context/UserContext";

const StickyIcons = ({ siteSettings }) => {
  const contactUs = siteSettings?.data;
  const phoneNumber = contactUs?.SITE_PHONE || "+91 73375 25445";
  const callHref = `tel:${String(phoneNumber).replace(/\s+/g, "")}`;
  const whatsappNumber = String(contactUs?.SITE_WHATSAPP || phoneNumber).replace(/\D/g, "");
  const whatsappHref = whatsappNumber ? `https://wa.me/${whatsappNumber}` : "/contact-us";
  const { cartCount } = useCartCount();

  const [showScrollBtn, setShowScrollBtn] = useState(false);

  // Show the button only when user scrolls down
  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 20) {
        setShowScrollBtn(true);
      } else {
        setShowScrollBtn(false);
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  // Scroll to top function
  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  };

  return (
    <>
      <section
        className={`${styles.bottom_panel_section} fixed-bottom d-block d-sm-none`}
      >
        <div className="container-fluid">
          <div className="row">
            <div className={`col-4 py-2 ${styles.bottomPanelItem}`}>
              <a
                href={callHref}
                rel="noopener noreferrer"
                aria-label="Call Manidvipa Flowers"
              >
                <IoCallSharp className="fs-2" title="Call" />
              </a>
            </div>
            <div className={`col-4 py-2 ${styles.bottomPanelItem}`}>
              <Link href="/cart" aria-label={`Open cart${cartCount > 0 ? `, ${cartCount} items` : ""}`} className={styles.mobileCartLink}>
                <FaShoppingCart className="fs-2" title="Cart" />
                {cartCount > 0 ? (
                  <span className={styles.mobileCartCount}>{cartCount}</span>
                ) : null}
              </Link>
            </div>
            <div className="col-4 py-2">
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={whatsappHref}
                aria-label="WhatsApp Manidvipa Flowers"
              >
                <FaWhatsapp className="fs-2" title="whatsapp" />
              </a>
            </div>
          </div>
        </div>
      </section>

      {showScrollBtn && (
        <button
          id="scrollTopBtn"
          className={styles.scroll_top_btn}
          title="Go to Top"
          onClick={scrollToTop}
        >
          <FaArrowUp />
        </button>
      )}
    </>
  );
};

export default StickyIcons;
