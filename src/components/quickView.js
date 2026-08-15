import React from "react";
import styles from "@/scss/components/quickView.module.scss";
import Image from "next/image";

const QuickView = ({ product }) => {
  return (
    <div className={`container ${styles.product_details_cart_sec}`}>
      <h3 className={`ms-5 ms-xs-1 ${styles.tanjore_painting_head}`}>
        {product.title}
      </h3>
      <hr className={`ms-5 ms-xs-1 ${styles.head_horizontal_line}`} />
      <div className="row justify-content-center">
        <div className="col-md-5">
          <div className={styles.product_image}>
            <div className={styles.selected_image}>
              <Image
                src={product.images.selected}
                alt={product.title}
                width={0}
                height={0}
                priority
                sizes="100vw"
              />
            </div>
            <div className={`mt-3 ${styles.item_related_images}`}>
              {product.images.related.map((image, index) => (
                <Image
                  key={index}
                  src={image}
                  alt={`${product.title} model ${index + 1}`}
                  width={0}
                  height={0}
                  className="w-100"
                  priority
                  sizes="100vw"
                />
              ))}
            </div>
            <div className="d-block">
              <div className={styles.buy_or_cart_sec}>
                <div className="d-flex flex-row order-0">
                  <input
                    type="number"
                    className={styles.number_input}
                    defaultValue="1"
                  />
                  <div>
                    <div className={styles.arrow1}>⌵</div>
                    <div className={styles.arrow2}>⌵</div>
                  </div>
                </div>
                <button
                  className={` order-0 primary-but ${styles.add_to_cart_btn}`}
                >
                  Add To Cart
                </button>
                <button className={` order-0 ${styles.buy_now_btn} green-but`}>
                  BUY NOW
                </button>
              </div>
              <hr className={styles.cart_horizontal_line} />
              <div className={`mt-2 ${styles.wish_list_compare}`}>
                <div className={styles.wish_list}>
                  <span className={styles.heart_symbol}>♡</span>
                  <span className={styles.wish_list_btn}>Add to Wish List</span>
                </div>
                <div className={styles.wish_list}>
                  <span className={styles.left_right_symbol}>⇆</span>
                  <span className={styles.wish_list_btn}>
                    Compare this Product
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="col-md-7 col-lg-6">
          <div className={styles.item_price_opions}>
            <h3 className={styles.price}>{product.price}</h3>
            <h5 className={styles.available_options}>Available Options</h5>
            <hr className={styles.horizontal_line} />
            <div className={styles.painting_price_details}>
              <h6 className={styles.painting_price_head}>
                * Flat Painting Price *
              </h6>
              <form>
                {product.options.map((option, index) => (
                  <div
                    className={styles.check_box_prices}
                    key={`${option.id}-${index}`}
                  >
                    <input type="radio" id={option.id} name="painting-price" />
                    <label htmlFor={option.id}>{option.label}</label>
                  </div>
                ))}
              </form>
              <h6 className={`mt-3 ${styles.painting_price_head}`}>
                * Frames *
              </h6>
              <select
                className={styles.form_select}
                aria-label="Frame selection"
              >
                {product.frames.map((frame, index) => (
                  <option key={index} value={frame.value}>
                    {frame.label}
                  </option>
                ))}
              </select>
            </div>
            <div className={styles.share_icons}>
              <h5>Share:</h5>
              <ul>
                {product.shareLinks.map((link) => (
                  <li key={link.platform}>
                    <a href={link.url}>
                      <Image
                        src={link.icon}
                        alt={link.platform}
                        width={0}
                        height={0}
                        priority
                        sizes="100vw"
                      />
                    </a>
                  </li>
                ))}
              </ul>
            </div>
            <div className={styles.assisted_checkout}>
              <h6>ASSISTED CHECKOUT</h6>
              <p>{product.assistedCheckoutText}</p>
              <div className={styles.contact_btns}>
                <button
                  className={`green-but ${styles.whatsapp_btn}`}
                  onClick={() =>
                    (window.location.href = `https://wa.me/${product.whatsappNumber}`)
                  }
                >
                  <Image
                    src="/assets/icons/whatsapp-white.png"
                    alt="WhatsApp"
                    width={30}
                    height={30}
                    priority
                    sizes="100vw"
                  />
                  WhatsApp
                </button>
                <button
                  className={`blue-but ${styles.call_btn}`}
                  onClick={() =>
                    (window.location.href = `tel:${product.callNumber}`)
                  }
                >
                  <Image
                    src="/assets/images/telephone.svg"
                    alt="Call"
                    width={25}
                    height={25}
                    priority
                    sizes="100vw"
                  />
                  {product.callNumber}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default QuickView;
