"use client";
import React, { useEffect, useState } from "react";
import { CategoryProductCard } from "@/components/pages/CategoryProducts";
import styles from "@/scss/pages/listingPage.module.scss";
import { useParams, useSearchParams } from "next/navigation";
import Toast from "@/components/Toast";
import { compareProductsByStock } from "@/lib/productStock";

import { fetchAllProducts } from "@/lib/apiClient";

export default function Page() {
  const { searchvalue } = useParams();
  const params = useSearchParams();
  const pathQuery = Array.isArray(searchvalue)
    ? searchvalue.join("/")
    : searchvalue;
  const searchQuery = params.get("q") ?? (pathQuery === "all" ? "" : pathQuery);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState("");
  const [retry, setRetry] = useState(0);
  const [productCategory, setProductCategory] = useState([]);
  const sortedProducts = [...productCategory];
  const [sortOption, setSortOption] = useState("");

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setLoadError("");
    setProductCategory([]);
    const query = new URLSearchParams(searchQuery ? { q: searchQuery } : {});
    fetchAllProducts(`search?${query}`, { signal: controller.signal })
      .then((products) => { if (!controller.signal.aborted) setProductCategory(products); })
      .catch(() => { if (!controller.signal.aborted) setLoadError("Search is temporarily unavailable. Please try again."); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [searchQuery, retry]);

  const handleSortChange = (e) => {
    setSortOption(e.target.value);
  };

  if (sortOption === "A to Z") {
    sortedProducts.sort((a, b) => {
      if (a.title && b.title) {
        return a.title.localeCompare(b.title);
      }
      return 0;
    });
  } else if (sortOption === "Z to A") {
    sortedProducts.sort((a, b) => {
      if (a.title && b.title) {
        return b.title.localeCompare(a.title);
      }
      return 0;
    });
  } else if (sortOption === "Low price to high") {
    sortedProducts.sort((a, b) => a.sell_price - b.sell_price);
  } else if (sortOption === "High price to low") {
    sortedProducts.sort((a, b) => b.sell_price - a.sell_price);
  }

  sortedProducts.sort(compareProductsByStock);

  return (
    <>
      <section className={`${styles.product_list_sec} py-5 py-xs-2`}>
        <div className="container">
          <div className="row justify-content-between">
            <div className="col-lg-12 col-md-12 col-sm-12">
              <div>
                <div className={`${styles.selecting_butn} py-2 my-2 mt-4`}>
                  <div className={`${styles.list_style_butn} calc`}>
                    <h3 className="mb-0">{searchQuery || "All flowers"}</h3>
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
                        Low price to high
                      </option>
                      <option value="High price to low">
                        High price to low
                      </option>
                    </select>
                  </div>
                </div>
                <div className="selected-list">
                  {/* Conditionally render the list based on activeButton */}
                  <div className="list-block-inside list-block-inside_active py-4">
                    <section className="mb-5" id="tanjore-paintings">
                      <div className="container">
                        <div className="row">
                          {loading ? <p role="status">Searching flowers?</p> : loadError ? (
                            <div role="alert"><p>{loadError}</p><button type="button" onClick={() => setRetry((value) => value + 1)}>Try again</button></div>
                          ) : sortedProducts.length > 0 ? (
                            sortedProducts.map((painting, index) => {
                              return (
                              <div
                                key={painting.product_id || painting.id}
                                className={`col-xs-6 col-sm-4 col-md-3 col-lg-3 my-2`}
                              >
                                <CategoryProductCard product={painting} />
                              </div>
                              );
                            })
                          ) : (
                            <p>No products match your search.</p>
                          )}
                        </div>
                      </div>
                    </section>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <Toast />
      </section>
    </>
  );
}
