import React from "react";
import styles from "@/scss/pages/testimonials.module.scss";
import Banner from "@/components/banner";
import Image from "next/image";

export default function Page() {
  return (
    <>
      {/* BANNER SECTION START  */}
      <Banner title="Testimoials" />
      {/* BANNER SECTION END  */}
      <div className="container py-5">
        <div className="row">
          <div className="col-sm-6 col-md-4">
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on
                StyleSeat.I can focus on my craft I can focus on my craft and
                have clients book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                Everything I need to run and market my business is on StyleSeat.
                I can focus on my craft and have clients book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                I can focus and have clients book themselves. Everything I need
                to run and market my business is on StyleSeat. I can focus and
                have clients book themselves.Everything I need to run and market
                my business is on StyleSeat. I can focus and have clients book
                themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
          </div>
          <div className="col-sm-6 col-md-4">
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                I can focus and have clients book themselves. Everything I need
                to run and market my business is on StyleSeat. I can focus and
                have clients book themselves.Everything I need to run and market
                my business is on StyleSeat. I can focus and have clients book
                themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                I can focus on my craft I can focus on my craft and have clients
                book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                I can focus on my craft I can focus on my craft and have clients
                book themselves.I can focus on my craft I can focus on my craft
                and have clients book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
          </div>
          <div className="col-sm-6 col-md-4">
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on StyleSeat.
                I can focus on my craft and have clients book
                themselves.Everything I need to run and market my business is on
                StyleSeat. I can focus on my craft and have clients book
                themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on
                StyleSeat.I can focus on my craftI can focus on my craft I can
                focus on my craft and have clients book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
            <div className={styles.testimonial}>
              <p className={styles.testimonialContent}>
                Everything I need to run and market my business is on
                StyleSeat.I can focus on my craftI can focus on my craft I can
                focus on my craft and have clients book themselves.
              </p>
              <div className={styles.testimonial_name}>
                <Image
                  src="/assets/images/brass-3.png"
                  alt="Kat Park"
                  height={0}
                  width={0}
                  sizes="100vw"
                />
                <div>
                  <h6>Kat Park</h6>
                  <span>Salon Goalee</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
