"use client";
import React, { useState, useEffect, useCallback, useRef } from "react";
import Banner from "@/components/banner";
import styles from "@/scss/pages/productDetails.module.scss";
import styles2 from "@/scss/pages/testimonials.module.scss";
import { useParams, usePathname, useRouter } from "next/navigation";
import Tabs from "@/components/Tabs";
import { FaRegHeart } from "react-icons/fa";
import { FaStar } from "react-icons/fa";
import QuantityComponent from "@/components/quantityComponent";
import {
  useCartCount,
  useWatchlistCount,
  useToast,
} from "@/app/(pages)/context/page";
import { fetchListingData, formatPrice } from "../../../hook/userCookie";
import Toast from "@/components/Toast";
import Image from "next/image";
import { FaHeart } from "react-icons/fa";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

export default function ProductDetails({
  guestSession,
  userToken,
  produtsDetails,
  produtsReviews,
  siteSettings,
}) {
  const { showToast } = useToast();
  const contactUs = siteSettings?.data;
  const pathname = usePathname();
  const fullUrl = `https://manidvipaflowers.com${pathname}`;

  const handleShareClick = (event) => {
    const target = event.target.closest("a[data-share-url]");
    if (target) {
      const shareUrl = target.getAttribute("data-share-url");
      window.open(shareUrl, "_blank", "noopener,noreferrer");
    }
  };

  const [productDetails, setProductDetails] = useState({});
  const [formData, setFormData] = useState({
    full_name: "",
    email: "",
    comment: "",
    star_rating: 0,
  });
  const [hoverRating, setHoverRating] = useState(0);
  const router = useRouter();
  const { id } = useParams();
  const userToken1 = userToken;
  // const { guestSession } = useUser();
  const [customerReview, setCustomerReview] = useState([]);
  // const [productCategory, setProductCategory] = useState([]);
  const [selectedImage, setSelectedImage] = useState(null);
  const [selectedPrice, setSelectedPrice] = useState(0);
  const [selectedWeightId, setSelectedWeightId] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [message, setMessage] = useState("");
  const { addCartCount } = useCartCount();
  const totalPrice = selectedPrice * quantity;
  const { addWatchlistCount, decreaseWatchlistCount } = useWatchlistCount();

  const priceOptions = productDetails?.weights;
  const defaultPrice = priceOptions?.[0]?.sell_price || 0;
  const defaultWeightId = priceOptions?.find(
    (option) => option.sell_price === defaultPrice
  )?.id;

  useEffect(() => {
    if (priceOptions?.length) {
      handlePriceChange(defaultPrice, defaultWeightId);
    }
  }, [priceOptions, defaultPrice, defaultWeightId]);

  const handlePriceChange = (price, weightId) => {
    setSelectedPrice(price);
    setSelectedWeightId(weightId);
  };
  const handleQuantityChange = (newQuantity) => {
    const value = Math.max(0, newQuantity);
    setQuantity(value);
  };

  const handleImageClick = (image) => {
    setSelectedImage(`${IMG_URL}/${image}`);
  };

  const handleAddcart = async (e, action) => {
    e.preventDefault();

    // Prevent multiple clicks using useRef
    if (handleAddcart.timer) {
      clearTimeout(handleAddcart.timer);
    }

    handleAddcart.timer = setTimeout(async () => {
      const body1 = {
        cart_session: guestSession,
        product_id: productDetails?.data?.id,
        quantity,
        weight_id: selectedWeightId,
      };

      try {
        const cartData = await fetchListingData(
          "POST",
          "add-to-cart",
          userToken ? userToken : null,
          body1
        );
        // const cartResponse = await fetch("/api", {
        //   method: "POST",
        //   body: JSON.stringify({
        //     req_method: "POST",
        //     endpoint: "add-to-cart",
        //     userToken: userToken ? userToken : undefined,
        //     formData: body1,
        //   }),
        // });

        // const cartData = await cartResponse.json();

        if (!cartData.success) {
          showToast(cartData.message || "Failed to add to cart", "error");
          return;
        }

        showToast(cartData.message || "Added to cart successfully", "success");
        addCartCount();

        if (cartData.message?.toLowerCase().includes("out of stock")) {
          return;
        }

        if (action) {
          router.push(`/${action}`);
        }
      } catch (error) {
        console.error("Error during ADD cart:", error);
        showToast(
          "An unexpected error occurred. Please try again later.",
          "error"
        );
      }
    }, 1000);
  };

  const fetchData = useCallback(async () => {
    const data = await fetchListingData(
      "GET",
      `product-details?product_slug=${id}`,
      userToken ? userToken : undefined
    );
    if (data) {
      setProductDetails(data);
      if (data?.images?.[0]?.name) {
        setSelectedImage(`${IMG_URL}/${data.images[0].name}`);
      }
    } else {
      return <>No Data Loading in this Movement</>;
    }
  }, [userToken, id]);
  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const fetchDataReviews = useCallback(async () => {
    const data = await fetchListingData(
      "GET",
      // `reviews?product_id=1}`,
      `reviews?product_id=${productDetails?.data?.id}`,
      userToken ? userToken : undefined
    );
    if (data.success) {
      setCustomerReview(data?.data);
    } else {
      showToast(data.message);
    }
  }, [userToken, productDetails?.data?.id, showToast]);

  useEffect(() => {
    fetchDataReviews();
  }, [fetchDataReviews]);

  const handleWishlistClick = async (productId) => {
    if (!userToken) {
      console.warn("User not authenticated");
      return;
    }

    try {
      const response = await fetchListingData(
        "POST",
        "add-wishlist",
        userToken,
        { product_id: productId }
      );

      if (response) {
        showToast(response.message);
        addWatchlistCount();
      }
    } catch (error) {
      console.error("Error adding to wishlist:", error);
    }
    fetchData();
  };

  const handleDelete = async (wishlistId) => {
    if (!userToken) return;

    try {
      const response = await fetchListingData(
        "DELETE",
        "delete-wishlist",
        userToken,
        { wishlist_id: wishlistId }
      );
      if (response?.success) {
        showToast("Item removed successfully!");
        decreaseWatchlistCount();
      } else {
        showToast("Failed to remove item.", "error");
      }
      fetchData();
    } catch (error) {
      console.error("Failed to delete item:", error);
      showToast("An error occurred while removing the item.", "error");
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleStarClick = (rating) => {
    setFormData((prev) => ({
      ...prev,
      star_rating: rating, // Update the star rating
    }));
  };

  const handleStarHover = (rating) => {
    setHoverRating(rating); // Update the hover rating
  };

  const handleStarLeave = () => {
    setHoverRating(0); // Reset hover rating when mouse leaves
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const response = await fetchListingData(
        "GET",
        `add-review?product_id=${productDetails?.data?.id}&name=${formData.full_name}&email=${formData.email}
        &comment=${formData.comment}&star_rating=${formData.star_rating}`,
        userToken ? userToken : undefined
      );
      if (response.success) {
        showToast(response.message);
        fetchDataReviews();
      } else {
        showToast(response.message);
      }
    } catch (err) {
      console.error(err);
    }
  };
  const tabsContent = [
    {
      category: "Description",
      content: (
        <>
          <p
            dangerouslySetInnerHTML={{
              __html: productDetails?.data?.description,
            }}
          />
        </>
      ),
    },
    {
      category: "Write a Review",
      content: (
        <form onSubmit={handleSubmit} className={styles.review_form}>
          <div>
            <label>Star Rating:</label>
            <div
              style={{
                display: "flex",
                gap: "5px",
                cursor: "pointer",
                marginBottom: "10px",
              }}
              onMouseLeave={handleStarLeave}
            >
              {[...Array(5)].map((_, i) => {
                const starValue = i + 1;
                return (
                  <FaStar
                    key={i}
                    size={24}
                    color={
                      starValue <= (hoverRating || formData.star_rating)
                        ? "gold"
                        : "gray"
                    }
                    onClick={() => handleStarClick(starValue)}
                    onMouseEnter={() => handleStarHover(starValue)}
                  />
                );
              })}
            </div>
          </div>
          <div>
            <label htmlFor="full_name">
              Full Name <span>*</span>
            </label>
            <input
              id="full_name"
              type="full_name"
              className={`form-input ${styles.form_input}`}
              onChange={handleChange}
              name="full_name"
              placeholder="Enter Your Full Name"
              required
            />
          </div>
          <div>
            <label htmlFor="email">
              Email <span>*</span>
            </label>
            <input
              id="email"
              type="email"
              className={`form-input ${styles.form_input}`}
              onChange={handleChange}
              name="email"
              placeholder="Enter Your Email"
              required
            />
          </div>
          <div>
            <label htmlFor="comment">
              Comment <span>*</span>
            </label>
            <textarea
              className={`form-input ${styles.form_input}`}
              rows={6}
              onChange={handleChange}
              id="comment"
              name="comment"
              placeholder="Enter Your Comment"
              required
            />
          </div>

          <div className="form-button">
            <button type="submit" className="blue-but">
              Submit
            </button>
          </div>
        </form>
      ),
    },
  ];
  return (
    <>
      {/*BANNER SECTION START */}
      <Banner title="Product Details" />
      {/* BANNER SECTION END  */}
      {/* PRODUCT DETAILS SECTION START  */}
      <section className="my-5">
        <div className={`container ${styles.product_details_cart_sec}`}>
          <h3 className={styles.tanjore_painting_head}>
            {productDetails?.data?.title}
          </h3>
          <hr className={styles.head_horizontal_line} />
          <div className="row justify-content-between">
            <div className="col-sm-5">
              <div className={styles.product_image}>
                <div className={styles.selected_image}>
                  <Image
                    alt="Selected Product"
                    width={0}
                    height={0}
                    sizes="100vw"
                    style={{ width: "100%", height: "100%" }}
                    src={selectedImage || "/assets/images/no-image.png"}
                    priority
                  />
                </div>
                <div className={`mt-3 ${styles.item_related_images}`}>
                  {productDetails?.images?.map((image, index) => (
                    <Image
                      key={index}
                      alt={`Product ${index + 1}`}
                      width={0}
                      height={0}
                      sizes="100vw"
                      src={`${IMG_URL}/${image?.name}`}
                      onClick={() => handleImageClick(image?.name)}
                      priority
                    />
                  ))}
                </div>
              </div>
            </div>
            <div className="col-sm-7 col-md-6">
              <div className={styles.item_price_options}>
                <h3 className={styles.price}>Rs {totalPrice}</h3>
                <h5 className={styles.available_options}>Available Options</h5>
                <hr className={styles.horizontal_line} />
                <div className={styles.painting_price_details}>
                  <h6 className={styles.painting_price_head}>
                    * Flat Flowers Price *
                  </h6>
                  <form action="">
                    {priceOptions?.length > 0 ? (
                      <>
                        {priceOptions?.map((option) => (
                          <div
                            className={styles.check_box_prices}
                            key={option.id}
                          >
                            <input
                              type="radio"
                              id={option.id}
                              name="price"
                              value={option.sell_price}
                              onChange={() =>
                                handlePriceChange(option.sell_price, option.id)
                              }
                              defaultChecked={
                                option.sell_price === defaultPrice
                              }
                            />

                            <label htmlFor={option.id}>
                              {option.name}{" "}
                              <span>{formatPrice(option.sell_price)}</span>
                              {"  "}
                              <span className={styles.price_strike}>
                                {formatPrice(option.list_price)}
                              </span>
                            </label>
                          </div>
                        ))}
                      </>
                    ) : (
                      <>
                        <div style={{ color: "red" }}>Out of Stock</div>
                      </>
                    )}
                  </form>
                </div>
                <div className={styles.buy_or_cart_sec}>
                  <div className="d-flex flex-row order-0 order-xs-0 order-sm-0 order-md-0">
                    <QuantityComponent
                      quantity={quantity}
                      onQuantityChange={handleQuantityChange}
                    />
                  </div>
                  <button
                    className={`primary-but ${styles.add_to_cart_btn}`}
                    onClick={(e) => handleAddcart(e)}
                  >
                    Add To Cart
                  </button>
                  <button
                    className={`green-but ${styles.buy_now_btn}`}
                    onClick={(e) => handleAddcart(e, "checkout")}
                  >
                    BUY NOW
                  </button>
                </div>
                {message && <p>{message}</p>}
                <hr className={styles.cart_horizontal_line} />
                <div className={`mt-2 ${styles.wish_list_compare}`}>
                  <div className={styles.wish_list}>
                    {userToken1 &&
                      (productDetails?.data?.wishlist_id !== null ||
                      undefined ? (
                        <div
                          onClick={() =>
                            handleDelete(productDetails?.data?.wishlist_id)
                          }
                        >
                          <FaHeart
                            style={{ color: "red", marginRight: "7px" }}
                          />
                          <span className={styles.wish_list_btn}>
                            Added in Wish List
                          </span>
                        </div>
                      ) : (
                        <div
                          onClick={() =>
                            handleWishlistClick(productDetails?.data?.id)
                          }
                        >
                          <span className={styles.heart_symbol}>
                            <FaRegHeart />
                          </span>
                          <span className={styles.wish_list_btn}>
                            Add to Wish List
                          </span>
                        </div>
                      ))}
                  </div>
                </div>
                <div className={styles.share_icons}>
                  <h5>Share:</h5>
                  <ul onClick={handleShareClick}>
                    <li>
                      <a
                        data-share-url={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
                          fullUrl
                        )}`}
                        alt="facebook"
                      >
                        <Image
                          alt="facebook"
                          width={32}
                          height={32}
                          style={{ width: "100%", height: "100%" }}
                          src="/assets/icons/facebook.png"
                          priority
                        />
                      </a>
                    </li>
                    <li>
                      <a
                        data-share-url={`https://twitter.com/intent/tweet?text=Check%20this%20out!%20${encodeURIComponent(
                          fullUrl
                        )}`}
                        alt="twitter"
                      >
                        <Image
                          alt="twitter"
                          width={32}
                          height={32}
                          style={{ width: "100%", height: "100%" }}
                          src="/assets/icons/twitter.png"
                        />
                      </a>
                    </li>
                    <li>
                      <a
                        data-share-url={`https://api.whatsapp.com/send?text=Check%20this%20out!%20${encodeURIComponent(
                          fullUrl
                        )}`}
                        alt="whatsapp"
                      >
                        <Image
                          alt="whatsapp"
                          width={32}
                          height={32}
                          style={{ width: "100%", height: "100%" }}
                          src="/assets/icons/whatsapp.png"
                        />
                      </a>
                    </li>
                  </ul>
                </div>
                <div className={styles.assisted_checkout}>
                  <h6>ASSISTED CHECKOUT</h6>
                  <p>
                    Lorem ipsum dolor sit, amet consectetur adipisicing elit.
                    Asperiores maxime vitae provident tempora eum alias, ut
                    similique, ex earum aperiam nesciunt, odit quisquam
                    temporibus optio labore illo esse obcaecati? Accusantium.
                  </p>
                  <div className={styles.contact_btns}>
                    <a
                      className={`green-but ${styles.whatsapp_btn}`}
                      target="_blank"
                      href={`https://wa.me/${contactUs?.SITE_WHATSAPP}`}
                    >
                      <span className="me-2">
                        <Image
                          alt="whatsapp-white"
                          width={0}
                          height={0}
                          sizes="100vw"
                          style={{
                            width: "100%",
                            height: "100%",
                          }}
                          src="/assets/icons/whatsapp-white.png"
                        />
                      </span>
                      <span>WhatsApp</span>
                    </a>
                    <a
                      className={`blue-but ${styles.call_btn}`}
                      target="_blank"
                      href="tel:+919491747624"
                    >
                      <span className="me-2">
                        <Image
                          alt="telephone-call-white"
                          width={0}
                          height={0}
                          sizes="100vw"
                          style={{ width: "100%", height: "100%" }}
                          src="/assets/icons/telephone-call-white.png"
                        />
                      </span>
                      9012345678
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      {/*  PRODUCT DETAILS SECTION END */}
      {/* TAB SECTION START  */}
      <section className="mt-5">
        <div className={`${styles.item_spcl_descriptions}`}>
          <Tabs
            tabsContent={tabsContent}
            renderContent={(item) => (
              <div
                className={styles.tab_pane}
                id={item.category}
                key={item.category}
              >
                {item.content}
              </div>
            )}
            customClasses={{
              controls: styles.tab_headings,
              active: styles.active,
              contentContainer: styles.tab_content,
            }}
          />
        </div>
      </section>
      {/* TAB SECTION END  */}
      {/* CUSTOMER REVIEW SECTION START  */}
      <section className="my-5">
        <div className={`container ${styles.product_customer_review_sec}`}>
          <h5 className={styles.head}>Customer Reviews</h5>
          <div className="row">
            {customerReview.map((review, index) => (
              <div key={index} className={styles.customer_review}>
                <div>
                  <h6>{review.name}</h6>
                  <div className={styles2.review_rating}>
                    {[...Array(review.star_rating)].map((_, i) => (
                      <FaStar key={i} />
                    ))}
                  </div>
                </div>
                <p>{review.comment}</p>
              </div>
            ))}
          </div>
          <Toast />
        </div>
      </section>
    </>
  );
}
