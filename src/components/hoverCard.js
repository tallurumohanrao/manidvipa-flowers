// "use client";
import Image from "next/image";
import Link from "next/link";
import { FaStar, FaRegHeart } from "react-icons/fa6";
import { PiShareFat } from "react-icons/pi";
import styles from "@/scss/components/hoverCard.module.scss";
import { fetchListingData, formatPrice } from "../../hook/userCookie";
import { usePathname } from "next/navigation";
import Toast from "./Toast";
import { useToast } from "@/context/UserContext";
import { useWatchlistCount } from "@/context/UserContext";
import { FaHeart } from "react-icons/fa6";
import { useState, useRef } from "react";
import { IoLogoWhatsapp } from "react-icons/io";
import { FaFacebook } from "react-icons/fa";
import { RiTwitterXFill } from "react-icons/ri";

const HoverCard = ({
  imageSrc,
  hoverImageSrc,
  title,
  rating,
  originalPrice,
  salePrice,
  slug,
  productId,
  handleClick,
  wishlist_id,
  userToken,
  fetchData = () => {},
}) => {
  const pathname = usePathname();
  const fullUrl = `https://manidvipaflowers.com${pathname}`;
  const { showToast } = useToast();
  const { addWatchlistCount, decreaseWatchlistCount } = useWatchlistCount();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const deleteTimeout = useRef(null);
  const wishlistTimeout = useRef(null);

  const handleDelete = async (wishlist_id) => {
    if (!userToken) return;

    // Prevent multiple rapid clicks
    if (deleteTimeout.current) {
      clearTimeout(deleteTimeout.current);
    }

    deleteTimeout.current = setTimeout(async () => {
      try {
        const response = await fetchListingData(
          "DELETE",
          "delete-wishlist",
          userToken,
          { wishlist_id: wishlist_id }
        );

        if (response?.success) {
          fetchData();
          showToast("Item removed successfully!", "error");
          decreaseWatchlistCount();
        } else {
          showToast("Failed to remove item.", "error");
        }
      } catch (error) {
        console.error("Failed to delete item:", error);
        showToast("An error occurred while removing the item.", "error");
      }
    }, 1000); // Delay execution by 1 second
  };

  const handleWishlistClick = async (productId) => {
    if (!userToken) {
      console.warn("User not authenticated");
      return;
    }

    // if (!category_slug) {
    //   console.error("category_slug is missing");
    //   return;
    // }

    // Prevent multiple rapid clicks
    if (wishlistTimeout.current) {
      clearTimeout(wishlistTimeout.current);
    }

    wishlistTimeout.current = setTimeout(async () => {
      try {
        const wishlistResponse = await fetchListingData(
          "POST",
          "add-wishlist",
          userToken,
          { product_id: productId }
        );

        if (wishlistResponse?.success) {
          showToast(wishlistResponse.message);
          addWatchlistCount();
        }
        fetchData();
        // ✅ Ensure the revalidate API is called with the correct path
        // const revalidateResponse = await fetch(
        //   `/api/revalidate?path=/flower-category/${category_slug}`,
        //   { method: "GET" }
        // );

        // router.refresh();

        // const result = await revalidateResponse.json();
        // console.log("Revalidate result:", result);

        // if (!result.revalidated) {
        //   console.error("Revalidation failed:", result.message);
        // }
      } catch (error) {
        console.error("Error adding to wishlist:", error);
      }
    }, 1000); // Delay 1 second to prevent spam clicks
  };

  // const handleWishlistClick = async (productId) => {
  //   if (!userToken) {
  //     console.warn("User not authenticated");
  //     return;
  //   }

  //   // Prevent multiple rapid clicks
  //   if (wishlistTimeout.current) {
  //     clearTimeout(wishlistTimeout.current);
  //   }

  //   wishlistTimeout.current = setTimeout(async () => {
  //     try {
  //       const wishlistResponse = await fetchListingData(
  //         "POST",
  //         "add-wishlist",
  //         userToken,
  //         { product_id: productId }
  //       );

  //       if (wishlistResponse?.success) {
  //         showToast(wishlistResponse.message);
  //         addWatchlistCount();
  //       }
  //       const respon = await fetch(
  //         `/api/revalidate?path=/flower-category/${category_slug}`,
  //         {
  //           method: "GET",
  //         }
  //       );
  //       console.log("respon", respon);
  //       // fetchData();
  //     } catch (error) {
  //       console.error("Error adding to wishlist:", error);
  //     }
  //   }, 1000); // Delay 1 second to prevent spam clicks
  // };

  const toggleDropdown = () => {
    setIsDropdownOpen((prev) => !prev);
  };

  const copyToClipboard = () => {
    navigator.clipboard.writeText(fullUrl).then(() => {
      showToast("URL copied to clipboard!", "success");
    });
  };

  return (
    <div className={styles.store_hover_card}>
      <div className={styles.store_hover_image}>
        <Link href={`/flowers/${slug}`}>
          <Image
            src={imageSrc === null ? "/assets/images/no-image.png" : imageSrc}
            alt={title}
            width={0}
            height={0}
            sizes="100vw"
            priority
          />
        </Link>
        <div className={styles.fav_icon}>
          {userToken &&
            (wishlist_id !== null || undefined ? (
              <FaHeart
                style={{ color: "red" }}
                onClick={() => handleDelete(wishlist_id)}
              />
            ) : (
              <FaRegHeart onClick={() => handleWishlistClick(productId)} />
            ))}

          <div className={styles.shareIcon}>
            <PiShareFat onClick={toggleDropdown} />
            {isDropdownOpen && (
              <div className={styles.shareDropdown}>
                <a
                  href={`https://api.whatsapp.com/send?text=Check%20this%20out!%20${encodeURIComponent(
                    fullUrl
                  )}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  alt="whatsapp"
                >
                  <IoLogoWhatsapp />
                </a>
                <a
                  href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
                    fullUrl
                  )}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  alt="facebook"
                >
                  <FaFacebook />
                </a>
                <a
                  href={`https://twitter.com/intent/tweet?text=Check%20this%20out!%20${encodeURIComponent(
                    fullUrl
                  )}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  alt="twitter"
                >
                  <RiTwitterXFill />
                </a>
                <button onClick={copyToClipboard}>Copy URL</button>
              </div>
            )}
          </div>
        </div>
      </div>
      <div className={styles.store_content}>
        <h6>{title}</h6>
        <div className={styles.store_price}>
          {originalPrice && (
            <p className={styles.originalPrice}>Rs {originalPrice} </p>
          )}
          <p>Rs {salePrice} </p>
        </div>
        <div className={styles.besides_btn}>
          <Link href={`/flowers/${slug}`}>
            <button className="primary-but" onClick={handleClick}>
              View Details
            </button>
          </Link>
        </div>
      </div>
      <Toast />
    </div>
  );
};

export default HoverCard;

