import React from "react";
// import Breadcrumbs from "@/components/breadcrumb";
import styles from "@/scss/components/banner.module.scss";

const Banner = ({ title, breadcrumbs }) => {
  return (
    <section>
      <div className={`container-fluid ${styles.all_banner_sec}`}>
        <div className="container">
          {/* <Breadcrumbs breadcrumbs={breadcrumbs} /> */}
          <h1 className={styles.banner_head}>{title}</h1>
        </div>
      </div>
    </section>
  );
};

export default Banner;
