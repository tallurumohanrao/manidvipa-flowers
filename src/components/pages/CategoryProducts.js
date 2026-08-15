"use client";
import React, { useCallback, useEffect, useState } from "react";
import HoverCard from "@/components/hoverCard";
import styles from "@/scss/pages/listingPage.module.scss";
import Image from "next/image";
import Toast from "@/components/Toast";
import { fetchListingData } from "../../../hook/userCookie";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const debouncedFetchData = async (
  category_slug,
  userToken,
  setProductCategory
) => {
  const data = await fetchListingData(
    "GET",
    `products-by-category?category_slug=${category_slug}`,
    userToken ? userToken : undefined
  );
  setProductCategory(data?.data?.data);
};

export default function CategoryProducts({
  category_slug,
  userToken,
  produtsCategory,
}) {
  const [productCategory, setProductCategory] = useState(produtsCategory);
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [errorMessage, setErrorMessage] = useState("");
  const [sortOption, setSortOption] = useState("");

  const fetchData = useCallback(() => {
    debouncedFetchData(category_slug, userToken, setProductCategory);
  }, [category_slug, userToken, setProductCategory]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  useEffect(() => {
    const filterProductsByPrice = () => {
      if (minPrice !== "" && maxPrice !== "") {
        // Validate the price range
        if (Number(minPrice) > Number(maxPrice)) {
          setErrorMessage("Min price cannot be greater than max price.");
          setFilteredProducts([]); // Clear the filtered products
          return;
        }
        setErrorMessage(""); // Clear error if range is valid
        const filtered = productCategory.filter((product) => {
          const price = product.sell_price || 0;
          return price >= Number(minPrice) && price <= Number(maxPrice);
        });
        setFilteredProducts(filtered);
      } else {
        // If no valid price range is entered, show all products
        setErrorMessage(""); // Clear error
        setFilteredProducts(productCategory);
      }
    };
    filterProductsByPrice();
  }, [minPrice, maxPrice, productCategory]);

  const handleSortChange = (e) => {
    setSortOption(e.target.value);
  };

  // const sortedProducts = [...filteredProducts];
  const sortedProducts = Array.isArray(filteredProducts)
    ? [...filteredProducts]
    : [];

  if (sortOption === "A to Z") {
    sortedProducts?.sort((a, b) => {
      if (a.title && b.title) {
        return a.title.localeCompare(b.title);
      }
      return 0;
    });
  } else if (sortOption === "Z to A") {
    sortedProducts?.sort((a, b) => {
      if (a.title && b.title) {
        return b.title.localeCompare(a.title);
      }
      return 0;
    });
  } else if (sortOption === "Low price to high") {
    sortedProducts?.sort((a, b) => a.sell_price - b.sell_price);
  } else if (sortOption === "High price to low") {
    sortedProducts?.sort((a, b) => b.sell_price - a.sell_price);
  }

  return (
    <>
      <section className={`${styles.product_list_sec} py-5 py-xs-2`}>
        <div className="container">
          <div className="row justify-content-between">
            <div className="col-12">
              {/* <div> */}
              <div className={`${styles.selecting_butn} py-2 my-2 mt-4`}>
                <div className={`${styles.list_style_butn} calc`}>
                  <h3 className="mb-0">
                    {String(category_slug)
                      .split("-")
                      .map(
                        (word) => word.charAt(0).toUpperCase() + word.slice(1)
                      )
                      .join(" ")}{" "}
                  </h3>
                </div>
                <div className={`${styles.sorting_butn} px-2`}>
                  <h5>Sort by</h5>
                  <select
                    className={`${styles.form_select}`}
                    onChange={handleSortChange}
                  >
                    <option value="">Select sorting option</option>
                    <option value="A to Z">A to Z</option>
                    <option value="Z to A">Z to A</option>
                    <option value="Low price to high">
                      Price (Low to High)
                    </option>
                    <option value="High price to low">
                      Price (High to Low)
                    </option>
                  </select>
                </div>
              </div>
              <div className="selected-list">
                {/* Conditionally render the list based on activeButton */}
                <div className="list-block-inside list-block-inside_active py-4">
                  <section className="mb-5" id="tanjore-paintings">
                    {/* <div className="container"> */}
                    <div className="row">
                      {sortedProducts?.length > 0 ? (
                        sortedProducts?.map((painting, index) => (
                          <div
                            key={index}
                            className={`col-xs-6 col-sm-4 col-md-3 col-lg-3 my-2`}
                          >
                            <HoverCard
                              imageSrc={
                                painting.image_name === null
                                  ? "/assets/images/no-image.png"
                                  : `${IMG_URL}/${painting.image_name}`
                              }
                              hoverImageSrc={
                                painting.image_name === null
                                  ? "/assets/images/no-image.png"
                                  : `${IMG_URL}/${painting.image_name}`
                              }
                              title={painting.title}
                              rating={painting.rating}
                              originalPrice={painting.list_price}
                              salePrice={painting.sell_price}
                              slug={painting.slug}
                              productId={painting.id}
                              wishlist_id={painting.wishlist_id}
                              category_slug={category_slug}
                              userToken={userToken}
                              fetchData={() => fetchData()}
                            />
                          </div>
                        ))
                      ) : (
                        <p>No products found within this price range</p>
                      )}
                    </div>
                    {/* </div> */}
                  </section>
                </div>
              </div>
            </div>
            {/* </div> */}
          </div>
        </div>
        <Toast />
      </section>
    </>
  );
}
