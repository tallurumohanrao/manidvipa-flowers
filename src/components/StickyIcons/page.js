"use client";
import React, { useEffect, useState } from "react";
import styles from "@/scss/components/stickyIcons.module.scss";
import { FaWhatsapp, FaArrowUp } from "react-icons/fa";
import { IoCallSharp } from "react-icons/io5";

const StickyIcons = ({ siteSettings }) => {
  const contactUs = siteSettings?.data;

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
            <div className={`col-6 py-2 ${styles.faSearch} `}>
              <a
                href="tel:+919491747624"
                rel="noopener noreferrer"
                aria-label="mobile instagram"
              >
                <IoCallSharp className="fs-2" title="Call" />
              </a>
            </div>
            <div className="col-6 py-2 ">
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={`https://wa.me/${contactUs?.SITE_WHATSAPP}`}
                aria-label="mobile whatsapp"
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
