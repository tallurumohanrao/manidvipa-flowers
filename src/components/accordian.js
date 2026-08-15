"use client";
import React, { useState } from "react";
import styles from "@/scss/components/accordian.module.scss";

const Accordion = ({
  items,
  headerClass,
  itemClass,
  arrowDiv,
  arrow,
  accordionContent,
  activeClass,
  children,
}) => {
  const [openIndex, setOpenIndex] = useState(null);

  const handleToggle = (index) => {
    setOpenIndex(openIndex === index ? null : index);
  };

  return (
    <div className={styles.samagri_faq_sec}>
      {items.map((item, index) => (
        <div key={index} className={`${styles.accordion_item} ${itemClass}`}>
          <button
            className={`${styles.accordion_header} ${headerClass} ${
              openIndex === index ? styles.active : ""
            }`}
            onClick={() => handleToggle(index)}>
            {item.title}
            <div className={`${styles.arrow_div} ${arrowDiv}`}>
              <span
                className={`${styles.arrow} ${arrow} ${
                  openIndex === index ? styles.active : ""
                }`}></span>
            </div>
          </button>
          <div
            className={`${styles.accordion_content} ${accordionContent} ${
              openIndex === index ? `${styles.active} ${activeClass}` : ""
            }`}>
            <div className="p-3">
              {item.content ? <p>{item.content}</p> : children}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default Accordion;
