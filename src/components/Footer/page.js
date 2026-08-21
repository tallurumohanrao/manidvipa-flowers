import React from "react";
import styles from "@/scss/components/footer.module.scss";
import {
  BsFacebook,
  BsInstagram,
  BsLinkedin,
  BsPinterest,
  BsTwitterX,
  BsWhatsapp,
  BsYoutube,
} from "react-icons/bs";
import Link from "next/link";
import Image from "next/image";

const Footer = ({ categories, siteSettings }) => {
  const categoriesData = categories;
  const footerCategories = Array.isArray(categoriesData)
    ? categoriesData.slice(0, 5)
    : [];

  const contactUs = siteSettings?.data;

  const socialIcons = [
    {
      icon: <BsFacebook />,
      href: `${contactUs?.FACEBOOK_LINK}`,
    },
    {
      icon: <BsTwitterX />,
      href: `${contactUs?.TWITTER_LINK}`,
    },
    {
      icon: <BsInstagram />,
      href: `${contactUs?.INSTAGRAM_LINK}`,
    },
    {
      icon: <BsYoutube />,
      href: `${contactUs?.YOUTUBE_LINK}`,
    },
    {
      icon: <BsWhatsapp />,
      href: `${contactUs?.SITE_WHATSAPP}`,
    },
    {
      icon: <BsPinterest />,
      href: `${contactUs?.PINTEREST_LINK}`,
    },
    {
      icon: <BsLinkedin />,
      href: `${contactUs?.LINKED_LINK}`,
    },
  ];

  const validSocialIcons = socialIcons.filter(
    (iconItem) => iconItem.href && iconItem.href !== "null"
  );

  // console.log("validSocialIcons", validSocialIcons);

  return (
    <div>
      {/* footer section start her */}
      <footer className={`${styles.footer_sec} pt-5 pt-md-3 pt-sm-3 pt-xs-2`}>
        <div className={styles.top_img}></div>
        <div className="container mt-5">
          <div className="row my-5 my-md-2 my-sm-2 my-xs-2">
            <div className={`col-md-4 col-sm-6 ${styles.bord} my-5`}>
              <Link href="/" as={"logo"}>
                <Image
                  src={
                    siteSettings?.data?.SITE_LOGO2 || "/assets/images/logo2.png"
                  }
                  alt="logo2"
                  width={0}
                  height={0}
                  priority
                  sizes="100vw"
                  as=""
                />
              </Link>
            </div>
            <div className="col-md-2 col-sm-6 col-xs-6 mb-3">
              <h6>Quick Links</h6>
              <ul>
                <li>
                  <Link href="/about">About Us</Link>
                </li>
                <li>
                  <Link href="/testimonials">Testimonials</Link>
                </li>
                <li>
                  <Link href="/contact-us">Contact Us</Link>
                </li>
                <li>
                  <Link href="/refund-cancellation">Refund Cancellation</Link>
                </li>
              </ul>
            </div>
            <div className="col-md-2 col-sm-6 col-xs-6 mb-3">
              <h6>Categories</h6>
              <ul>
                {footerCategories.map((item, index) => (
                  <li key={index}>
                    <Link href={`/products/${item.slug}`}>{item.title}</Link>
                  </li>
                ))}
                <li>
                  <Link href="/flowers">More Flowers</Link>
                </li>
              </ul>
            </div>
            <div className="col-md-4 col-sm-6 mb-3">
              <h6>Contact Us</h6>
              <ul>
                <li>
                  <Link
                    target="_blank"
                    href="https://maps.app.goo.gl/WwTnCyEAzv1n28mf8"
                  >
                    {contactUs?.SITE_ADDRESS}
                  </Link>
                </li>
                <li>
                  <Link href="">{contactUs?.SITE_EMAIL}</Link>
                </li>
                <li>
                  <Link href="">{contactUs?.SITE_PHONE}</Link>
                </li>
                {validSocialIcons.length > 0 && (
                  <div className="d-xs-flex justify-content-center flex-column">
                    <span>Follow us on</span>
                    <ul className="d-flex mt-2">
                      {validSocialIcons.map((iconItem, iconIndex) => (
                        <li key={iconIndex}>
                          <Link href={iconItem.href} target="_blank">
                            {iconItem.icon}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </ul>
            </div>
          </div>
          <div
            className={`${styles["copyrights"]} ${styles["d-flex"]} ${styles["justify-content-center"]}`}
          >
            <p>
              {contactUs?.SITE_COPYRIGHTS}, All Rights Reserved |
              <Link href="/privacy-policy"> Privacy Policy</Link> |
              <Link href="/terms-conditions"> Terms & Conditions</Link>
            </p>
          </div>
        </div>
      </footer>
      {/* footer section end her */}
    </div>
  );
};

export default Footer;
