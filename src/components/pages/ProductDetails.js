"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import Image from "next/image";
import { useParams, useRouter } from "next/navigation";
import {
  FaChevronDown,
  FaGift,
  FaHeart,
  FaLeaf,
  FaMapMarkerAlt,
  FaRegHeart,
  FaShieldAlt,
  FaShoppingCart,
  FaStar,
  FaTruck,
  FaWhatsapp,
} from "react-icons/fa";
import styles from "@/scss/pages/productDetails.module.scss";
import {
  useCartCount,
  useWatchlistCount,
  useToast,
  useUser,
} from "@/context/UserContext";
import {
  fetchCartBySession,
  fetchListingData,
  getCartCount,
} from "../../../hook/userCookie";
import Toast from "@/components/Toast";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const addonOptions = [
  { key: "jasmine", label: "Add Jasmine", price: 50 },
  { key: "tulasi", label: "Add Tulasi Leaves", price: 20 },
  { key: "mango", label: "Add Mango Leaves", price: 20 },
  { key: "gift", label: "Gift Wrap", price: 30 },
];

const deliverySlotOptions = [
  { value: "6-9", label: "6 AM - 9 AM" },
  { value: "9-12", label: "9 AM - 12 PM" },
  { value: "12-3", label: "12 PM - 3 PM" },
  { value: "3-6", label: "3 PM - 6 PM" },
];

function parsePriceValue(value) {
  const numericValue = Number(String(value || "").replace(/[^\d.]/g, ""));
  return Number.isFinite(numericValue) ? numericValue : 0;
}

function formatFlowerPrice(value) {
  const price = parsePriceValue(value);
  return `₹${new Intl.NumberFormat("en-IN", {
    maximumFractionDigits: price % 1 === 0 ? 0 : 2,
  }).format(price)}`;
}

function getDateInputValue(daysFromToday = 1) {
  const date = new Date();
  date.setDate(date.getDate() + daysFromToday);
  return date.toISOString().slice(0, 10);
}

function parseWeightInGrams(label) {
  const text = String(label || "").toLowerCase().replace(/\s+/g, "");
  const value = parsePriceValue(text);

  if (!value) return Number.POSITIVE_INFINITY;
  if (text.includes("kg") || text.includes("kilo")) return value * 1000;
  if (text.includes("gm") || text.includes("gram") || text.includes("g")) return value;
  if (text.includes("piece") || text.includes("pc")) return value * 100000;
  return value;
}

function formatWeightLabel(label) {
  const grams = parseWeightInGrams(label);

  if (grams === Number.POSITIVE_INFINITY) return label || "Default";
  if (grams < 1000) return `${grams}g`;
  if (grams % 1000 === 0) return `${grams / 1000}kg`;
  return `${grams / 1000}kg`;
}

function getProductDataPrice(data) {
  return parsePriceValue(
    data?.sell_price || data?.offer_price || data?.price || data?.starting_price
  );
}

function getProductDataListPrice(data) {
  return parsePriceValue(
    data?.list_price || data?.original_price || data?.mrp || data?.regular_price
  );
}

function getWeightId(weight, productData) {
  return (
    weight?.id ||
    weight?.weight_id ||
    productData?.weight_id ||
    productData?.default_weight_id ||
    null
  );
}

function buildPriceOptions(productDetails) {
  const productData = productDetails?.data || {};
  const weights = Array.isArray(productDetails?.weights) ? productDetails.weights : [];

  if (weights.length) {
    return weights.map((weight, index) => {
      const weightName = weight?.name || weight?.weight || weight?.title || `Option ${index + 1}`;
      const sellPrice = parsePriceValue(weight?.sell_price || weight?.price);
      const listPrice = parsePriceValue(weight?.list_price || weight?.mrp);
      const weightId = getWeightId(weight, productData);

      return {
        key: String(weightId || weight?.id || index),
        id: weightId,
        name: formatWeightLabel(weightName),
        originalName: weightName,
        weightGrams: parseWeightInGrams(weightName),
        sell_price: sellPrice,
        list_price: listPrice,
      };
    }).sort((a, b) => {
      if (a.weightGrams !== b.weightGrams) return a.weightGrams - b.weightGrams;
      return a.sell_price - b.sell_price;
    });
  }

  const fallbackSellPrice = getProductDataPrice(productData);
  if (!fallbackSellPrice) return [];

  const fallbackWeightId = getWeightId(null, productData);

  return [
    {
      key: String(fallbackWeightId || "default-price"),
      id: fallbackWeightId,
      name: productData?.unit || productData?.units ? `1 ${productData.unit || productData.units}` : "Default",
      sell_price: fallbackSellPrice,
      list_price: getProductDataListPrice(productData),
    },
  ];
}

function resolveProductImageSrc(imageName) {
  if (!imageName) return "/assets/images/no-image.png";

  const src = String(imageName);
  if (src.startsWith("http") || src.startsWith("/")) return src;

  return `${IMG_URL}/${src}`;
}

function normalizePhone(value) {
  return String(value || "").replace(/\D/g, "");
}

function buildWhatsAppHref(value, productTitle) {
  const phone = normalizePhone(value) || "917337525445";
  const message = `Hello Manidvipa Flowers, I want to order ${productTitle}.`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function getReviewSummary(reviews = []) {
  const validReviews = Array.isArray(reviews) ? reviews : [];
  const count = validReviews.length;

  if (!count) {
    return { rating: "4.8", count: 120 };
  }

  const total = validReviews.reduce(
    (sum, review) => sum + parsePriceValue(review?.star_rating || review?.rating),
    0
  );

  return {
    rating: (total / count).toFixed(1),
    count,
  };
}

function openDatePicker(event) {
  try {
    event.currentTarget.showPicker?.();
  } catch {
    // Browser can reject showPicker outside a direct user gesture; native input still works.
  }
}

export default function ProductDetails({
  guestSession,
  userToken,
  produtsDetails,
  produtsReviews,
  siteSettings,
}) {
  const { showToast } = useToast();
  const { guestSession: clientGuestSession } = useUser();
  const effectiveGuestSession = clientGuestSession || guestSession;
  const contactUs = siteSettings?.data || {};
  const router = useRouter();
  const { id } = useParams();
  const initialPriceOption = buildPriceOptions(produtsDetails || {})[0];

  const [productDetails, setProductDetails] = useState(produtsDetails || {});
  const [formData, setFormData] = useState({
    full_name: "",
    email: "",
    comment: "",
    star_rating: 0,
  });
  const [hoverRating, setHoverRating] = useState(0);
  const [customerReview, setCustomerReview] = useState(produtsReviews?.data || []);
  const [selectedImage, setSelectedImage] = useState(() =>
    resolveProductImageSrc(produtsDetails?.images?.[0]?.name)
  );
  const [selectedPrice, setSelectedPrice] = useState(() =>
    parsePriceValue(initialPriceOption?.sell_price)
  );
  const [selectedWeightId, setSelectedWeightId] = useState(
    initialPriceOption?.id || null
  );
  const [selectedOptionKey, setSelectedOptionKey] = useState(
    initialPriceOption?.key || null
  );
  const [quantity, setQuantity] = useState(0);
  const [cartAction, setCartAction] = useState("");
  const [wasAddedToCart, setWasAddedToCart] = useState(false);
  const [selectedAddonKeys, setSelectedAddonKeys] = useState([]);
  const [selectedDeliveryDate, setSelectedDeliveryDate] = useState(() =>
    getDateInputValue(1)
  );
  const [selectedDeliverySlot, setSelectedDeliverySlot] = useState("6-9");
  const { setCartCount } = useCartCount();
  const { addWatchlistCount, decreaseWatchlistCount } = useWatchlistCount();

  const productData = productDetails?.data || {};
  const productTitle = productData?.title || "Fresh Flowers";
  const priceOptions = useMemo(() => buildPriceOptions(productDetails), [productDetails]);
  const selectedPriceOption =
    priceOptions.find((option) => option.key === selectedOptionKey) || priceOptions?.[0];
  const productImages = productDetails?.images?.length
    ? productDetails.images
    : [{ name: selectedImage || "/assets/images/no-image.png" }];
  const displayQuantity = quantity > 0 ? quantity : 1;
  const totalPrice = selectedPrice * displayQuantity;
  const reviewSummary = getReviewSummary(customerReview);
  const productDescription =
    productData?.description ||
    "Beautiful, fresh and fragrant red roses. Perfect for pooja, decoration, gifting and special occasions.";
  const whatsappHref = buildWhatsAppHref(
    contactUs?.SITE_WHATSAPP || contactUs?.SITE_PHONE,
    productTitle
  );

  const handlePriceChange = (option) => {
    setSelectedPrice(parsePriceValue(option?.sell_price));
    setSelectedWeightId(option?.id || null);
    setSelectedOptionKey(option?.key || null);
    setWasAddedToCart(false);
  };

  const handleQuantityChange = (newQuantity) => {
    const parsedQuantity = Number.parseInt(newQuantity, 10);
    setQuantity(Math.min(99, Math.max(0, Number.isNaN(parsedQuantity) ? 0 : parsedQuantity)));
    setWasAddedToCart(false);
  };

  const handleImageClick = (image) => {
    setSelectedImage(resolveProductImageSrc(image));
  };

  const toggleAddon = (addonKey) => {
    setSelectedAddonKeys((currentKeys) =>
      currentKeys.includes(addonKey)
        ? currentKeys.filter((key) => key !== addonKey)
        : [...currentKeys, addonKey]
    );
  };

  const handleAddcart = async (e, action) => {
    e.preventDefault();

    if (!productDetails?.data?.id || !selectedWeightId) {
      showToast("Price option is not available for cart. Please contact us to order this item.", "error");
      return;
    }

    if (!effectiveGuestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    const nextCartAction = action || "cart";
    const cartQuantity = quantity > 0 ? quantity : 1;
    const body = {
      cart_session: effectiveGuestSession,
      product_id: productDetails?.data?.id,
      quantity: cartQuantity,
      weight_id: selectedWeightId,
    };

    setCartAction(nextCartAction);
    setWasAddedToCart(false);

    try {
      const cartData = await fetchListingData(
        "POST",
        "add-to-cart",
        userToken ? userToken : null,
        body
      );

      if (!cartData?.success) {
        showToast(cartData?.message || "Failed to add to cart", "error");
        return;
      }

      setQuantity(cartQuantity);
      if (!action) {
        setWasAddedToCart(true);
      }
      showToast(cartData.message || "Added to cart successfully", "success");

      const refreshedCart = await fetchCartBySession(
        effectiveGuestSession,
        userToken ? userToken : undefined
      );

      if (refreshedCart?.success) {
        setCartCount(getCartCount(refreshedCart));
      }

      if (cartData.message?.toLowerCase().includes("out of stock")) {
        return;
      }

      if (action) {
        router.push(`/${action}`);
      }
    } catch (error) {
      console.error("Error during ADD cart:", error);
      showToast("An unexpected error occurred. Please try again later.", "error");
    } finally {
      setCartAction("");
    }
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
        setSelectedImage(resolveProductImageSrc(data.images[0].name));
      }
    } else if (produtsDetails) {
      setProductDetails(produtsDetails);
      if (produtsDetails?.images?.[0]?.name) {
        setSelectedImage(resolveProductImageSrc(produtsDetails.images[0].name));
      }
    }
  }, [produtsDetails, userToken, id]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  useEffect(() => {
    const defaultPriceOption = priceOptions?.[0];
    const hasSelectedOption = priceOptions.some(
      (option) => option.key === selectedOptionKey
    );

    if (defaultPriceOption && (!selectedOptionKey || !hasSelectedOption)) {
      handlePriceChange(defaultPriceOption);
    }
  }, [priceOptions, selectedOptionKey]);

  const fetchDataReviews = useCallback(async () => {
    if (!productDetails?.data?.id) {
      setCustomerReview([]);
      return;
    }

    const data = await fetchListingData(
      "GET",
      `reviews?product_id=${productDetails?.data?.id}`,
      userToken ? userToken : undefined
    );

    if (data?.success) {
      setCustomerReview(data?.data || []);
    }
  }, [userToken, productDetails?.data?.id]);

  useEffect(() => {
    fetchDataReviews();
  }, [fetchDataReviews]);

  const handleWishlistClick = async (productId) => {
    if (!userToken) {
      showToast("Please login to add this product to wishlist.", "error");
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
      star_rating: rating,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await fetchListingData(
        "POST",
        "add-review",
        userToken ? userToken : undefined,
        {
          product_id: productDetails?.data?.id,
          name: formData.full_name,
          email: formData.email,
          comment: formData.comment,
          star_rating: formData.star_rating,
        }
      );

      if (response?.success) {
        showToast(response.message);
        fetchDataReviews();
      } else {
        showToast(response?.message || "Unable to submit review.", "error");
      }
    } catch (err) {
      console.error(err);
      showToast("Unable to submit review.", "error");
    }
  };

  return (
    <>
      <section className={styles.productPage}>
        <div className="container">
          <nav className={styles.breadcrumbs} aria-label="Breadcrumb">
            <button type="button" onClick={() => router.push("/")}>Home</button>
            <span>/</span>
            <button type="button" onClick={() => router.push("/flowers")}>Flowers</button>
            <span>/</span>
            <strong>{productTitle}</strong>
          </nav>

          <div className={styles.productCard}>
            <div className={styles.galleryColumn}>
              <div className={styles.mainImageWrap}>
                <Image
                  alt={productTitle}
                  width={640}
                  height={640}
                  sizes="(max-width: 768px) 100vw, 48vw"
                  className={styles.mainImage}
                  src={selectedImage || "/assets/images/no-image.png"}
                  priority
                />
              </div>

              <div className={styles.thumbnailRow}>
                {productImages.slice(0, 5).map((image, index) => {
                  const imageSrc = resolveProductImageSrc(image?.name);
                  return (
                    <button
                      type="button"
                      key={`${imageSrc}-${index}`}
                      className={selectedImage === imageSrc ? styles.activeThumbnail : ""}
                      onClick={() => handleImageClick(image?.name)}
                      aria-label={`View ${productTitle} image ${index + 1}`}
                    >
                      <Image
                        alt={`${productTitle} ${index + 1}`}
                        width={96}
                        height={96}
                        src={imageSrc}
                      />
                    </button>
                  );
                })}
              </div>
            </div>

            <div className={styles.detailsColumn}>
              <div className={styles.titleRow}>
                <div>
                  <h1>{productTitle}</h1>
                  <div className={styles.ratingLine}>
                    <span className={styles.stars}>
                      {[...Array(5)].map((_, index) => (
                        <FaStar key={index} aria-hidden="true" />
                      ))}
                    </span>
                    <strong>{reviewSummary.rating}</strong>
                    <span>({reviewSummary.count} reviews)</span>
                  </div>
                </div>

                {userToken &&
                  (productDetails?.data?.wishlist_id ? (
                    <button
                      type="button"
                      className={`${styles.wishlistButton} ${styles.wishlistActive}`}
                      onClick={() => handleDelete(productDetails?.data?.wishlist_id)}
                      aria-label="Remove from wishlist"
                    >
                      <FaHeart />
                    </button>
                  ) : (
                    <button
                      type="button"
                      className={styles.wishlistButton}
                      onClick={() => handleWishlistClick(productDetails?.data?.id)}
                      aria-label="Add to wishlist"
                    >
                      <FaRegHeart />
                    </button>
                  ))}
              </div>

              <p className={styles.priceLine}>
                <strong>{formatFlowerPrice(totalPrice || selectedPrice)}</strong>
                <span>/ {selectedPriceOption?.name || productData?.unit || "kg"}</span>
              </p>

              <ul className={styles.productHighlights}>
                <li><FaLeaf /> Fresh Every Morning</li>
                <li><FaShieldAlt /> Pesticide Free</li>
                <li><FaGift /> Handpicked</li>
                <li><FaTruck /> Perfect for Puja & Gifting</li>
              </ul>

              <div className={styles.optionBlock}>
                <h2>Select Quantity</h2>
                {priceOptions?.length > 0 ? (
                  <div className={styles.weightOptions} role="radiogroup" aria-label="Select quantity">
                    {priceOptions.map((option) => (
                      <button
                        type="button"
                        key={option.key}
                        className={selectedOptionKey === option.key ? styles.activeWeight : ""}
                        onClick={() => handlePriceChange(option)}
                      >
                        {option.name}
                      </button>
                    ))}
                  </div>
                ) : (
                  <p className={styles.optionError}>Price details are not available for this product.</p>
                )}
              </div>

              <div className={styles.optionBlock}>
                <h2>Add-ons <span>(Optional)</span></h2>
                <div className={styles.addonList}>
                  {addonOptions.map((addon) => (
                    <label key={addon.key}>
                      <input
                        type="checkbox"
                        checked={selectedAddonKeys.includes(addon.key)}
                        onChange={() => toggleAddon(addon.key)}
                      />
                      <span>{addon.label} ({formatFlowerPrice(addon.price)})</span>
                    </label>
                  ))}
                </div>
              </div>

              <div className={styles.deliveryGrid}>
                <label>
                  <span>Delivery Date</span>
                  <input
                    type="date"
                    value={selectedDeliveryDate}
                    min={getDateInputValue(0)}
                    onChange={(event) => setSelectedDeliveryDate(event.target.value)}
                    onFocus={openDatePicker}
                    onClick={openDatePicker}
                  />
                </label>

                <label>
                  <span>Delivery Time Slot</span>
                  <select
                    value={selectedDeliverySlot}
                    onChange={(event) => setSelectedDeliverySlot(event.target.value)}
                  >
                    {deliverySlotOptions.map((option) => (
                      <option value={option.value} key={option.value}>{option.label}</option>
                    ))}
                  </select>
                </label>
              </div>

              <div className={styles.actionRow}>
                <div className={styles.quantityStepper}>
                  <button type="button" onClick={() => handleQuantityChange(quantity - 1)} disabled={quantity <= 0 || Boolean(cartAction)}>−</button>
                  <input
                    type="number"
                    min="0"
                    max="99"
                    value={quantity}
                    onChange={(event) => handleQuantityChange(event.target.value)}
                    disabled={Boolean(cartAction)}
                    aria-label={`Quantity for ${productTitle}`}
                  />
                  <button type="button" onClick={() => handleQuantityChange(quantity + 1)} disabled={Boolean(cartAction)}>+</button>
                </div>

                <button
                  type="button"
                  className={`${styles.addToCartButton} ${wasAddedToCart ? styles.addedToCartButton : ""}`}
                  onClick={(event) => handleAddcart(event)}
                  disabled={Boolean(cartAction)}
                >
                  <FaShoppingCart />
                  {cartAction === "cart" ? "Adding..." : wasAddedToCart ? "Added" : "Add to Cart"}
                </button>

                <button
                  type="button"
                  className={styles.buyNowButton}
                  onClick={(event) => handleAddcart(event, "checkout")}
                  disabled={Boolean(cartAction)}
                >
                  {cartAction === "checkout" ? "Please wait..." : "Buy Now"}
                </button>
              </div>

              <a href={whatsappHref} target="_blank" rel="noopener noreferrer" className={styles.whatsappButton}>
                <FaWhatsapp />
                Order on WhatsApp
              </a>
            </div>
          </div>

          <div className={styles.promiseStrip}>
            <div><FaMapMarkerAlt /><strong>Same-Day Delivery</strong><span>Across Hyderabad</span></div>
            <div><FaShieldAlt /><strong>Secure Payment</strong><span>100% Safe</span></div>
            <div><FaGift /><strong>Easy Returns</strong><span>Hassle Free</span></div>
            <div><FaLeaf /><strong>Fresh & Quality</strong><span>Guaranteed</span></div>
          </div>

          <div className={styles.accordionList}>
            <details open className={styles.accordionItem}>
              <summary>
                <span>Product Description</span>
                <FaChevronDown />
              </summary>
              <div dangerouslySetInnerHTML={{ __html: productDescription }} />
            </details>

            <details className={styles.accordionItem}>
              <summary>
                <span>Care Instructions</span>
                <FaChevronDown />
              </summary>
              <p>Keep flowers in a cool place, sprinkle light water if needed, and use them on the same day for best freshness.</p>
            </details>

            <details className={styles.accordionItem}>
              <summary>
                <span>Reviews ({reviewSummary.count})</span>
                <FaChevronDown />
              </summary>
              <div className={styles.reviewList}>
                {customerReview?.length ? (
                  customerReview.map((review, index) => (
                    <article key={`${review?.name || "review"}-${index}`}>
                      <h3>{review.name}</h3>
                      <div className={styles.stars}>
                        {[...Array(Number(review.star_rating) || 0)].map((_, star) => (
                          <FaStar key={star} />
                        ))}
                      </div>
                      <p>{review.comment}</p>
                    </article>
                  ))
                ) : (
                  <p>No customer reviews yet.</p>
                )}
              </div>

              <form onSubmit={handleSubmit} className={styles.reviewForm}>
                <h3>Write a Review</h3>
                <div className={styles.reviewStars} onMouseLeave={() => setHoverRating(0)}>
                  {[...Array(5)].map((_, index) => {
                    const starValue = index + 1;
                    return (
                      <button
                        type="button"
                        key={starValue}
                        onClick={() => handleStarClick(starValue)}
                        onMouseEnter={() => setHoverRating(starValue)}
                        className={starValue <= (hoverRating || formData.star_rating) ? styles.activeReviewStar : ""}
                      >
                        <FaStar />
                      </button>
                    );
                  })}
                </div>
                <input name="full_name" placeholder="Full name" onChange={handleChange} required />
                <input name="email" type="email" placeholder="Email" onChange={handleChange} required />
                <textarea name="comment" rows={4} placeholder="Your review" onChange={handleChange} required />
                <button type="submit">Submit Review</button>
              </form>
            </details>
          </div>
        </div>
      </section>
      <Toast />
    </>
  );
}
