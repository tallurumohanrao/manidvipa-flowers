"use client";
import React, {
  useEffect,
  useState,
  useMemo,
  useRef,
  useCallback,
} from "react";
import styles from "@/scss/components/navbar.module.scss";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { useUser } from "@/app/(pages)/context/page";
import { FaRegHeart } from "react-icons/fa";
import { BsCart } from "react-icons/bs";
import { BsWhatsapp } from "react-icons/bs";
import { useCartCount } from "@/app/(pages)/context/page";
import { useWatchlistCount } from "@/app/(pages)/context/page";
import Link from "next/link";
import { BsPersonFillGear } from "react-icons/bs";
import { MdPerson } from "react-icons/md";
import { LuPencilLine } from "react-icons/lu";
import { fetchListingData } from "../../hook/userCookie";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

const debouncedHandleSearch = async (searchInput, router) => {
  if (!searchInput) return;

  try {
    const res = await fetch(`${url}/search?q=${searchInput}`);
    const result = await res.json();

    if (result?.data?.data?.length === 1) {
      router.push(`/product-details/${result.data.data[0].slug}`);
    } else if (result?.data?.data?.length > 1) {
      router.push(`/search/${searchInput}`);
    } else {
      router.push(`/search/all`);
    }
  } catch (error) {
    console.error("Error fetching search results:", error);
  }
};

// const fetchData = async (guestSession, userToken, setCartCount) => {
//   if (!guestSession) return;
//   const result = await fetchListingData(
//     "GET",
//     `get-cart?cart_session=${guestSession}`,
//     userToken || undefined
//   );

//   if (result) {
//     setCartCount(result?.data?.length);
//   }
// };

// const fetchWatchlist = async (userToken, setWatchlistCount) => {
//   if (!userToken) return;
//   const res = await fetchListingData("GET", "wishlist", userToken);
//   if (res) {
//     setWatchlistCount(res.data?.length);
//   }
// };
const fetchData = async (guestSession, userToken, setCartCount) => {
  if (!guestSession) return null;

  try {
    const res = await fetch(`${url}/get-cart?cart_session=${guestSession}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
      },
      next: { revalidate: 10 },
      // cache: "no-store",
    });

    if (!res.ok) {
      const errorData = await res.text();
      console.error("Fetch Error:", errorData);
      throw new Error(`Failed to fetch cart data: ${errorData}`);
    }

    const result = await res.json();
    setCartCount(result?.data?.length || 0);
  } catch (error) {
    console.error("Error fetching cart data:", error);
  }
};

const fetchWatchlist = async (userToken, setWatchlistCount) => {
  if (!userToken) return;

  try {
    const res = await fetch(`${url}/wishlist`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${userToken}`,
      },
      next: { revalidate: 200 },
      // cache: "no-store",
    });

    if (!res.ok) {
      const errorData = await res.text();
      console.error("Fetch Error:", errorData);
      throw new Error(`Failed to fetch watchlist data: ${errorData}`);
    }

    const result = await res.json();
    setWatchlistCount(result?.data?.length || 0);
  } catch (error) {
    console.error("Error fetching watchlist data:", error);
  }
};

const Navbar = ({
  categories,
  siteSettings,
  watchListData,
  cartSessionData,
  guestSession,
  userToken,
}) => {
  const listingsTitles = categories;
  const contactUs = siteSettings?.data;

  // Homepage navigation labels requested for the refreshed storefront.
  // Styling, colors, fonts, logo/search area and header structure remain unchanged.
  const homepageNavItems = [
    { label: "Home", href: "/" },
    { label: "Flowers", href: "/search/all" },
    { label: "Puja Flowers", href: "/#shop-by-category" },
    { label: "Subscriptions", href: "/#subscriptions" },
    { label: "Premium", href: "/#premium" },
    { label: "Rare Flowers", href: "/#rare-flowers" },
    { label: "Garlands", href: "/search/garland" },
    { label: "Decorations", href: "/#decorations" },
    { label: "Gifts", href: "/#shop-by-occasion" },
    { label: "Offers", href: "/search/all" },
  ];
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [openDropdownIndex, setOpenDropdownIndex] = useState(null);
  const [openSubmenuIndex, setOpenSubmenuIndex] = useState(null);
  const { isAuthenticated } = useUser();
  const { cartCount, setCartCount } = useCartCount();
  const { watchlistCount, setWatchlistCount } = useWatchlistCount();
  const router = useRouter();
  const [searchInput, setSearchInput] = useState("");
  const [allsuggestions, setAllSuggestions] = useState([]);
  const [inputSuggestions, setInputSuggestions] = useState([]);
  const [isSuggestionVisible, setIsSuggestionVisible] = useState(false);
  const inputRef = useRef(null);

  const debouncedFetchData = useCallback(fetchData, []);
  const debouncedFetchWatchlist = useCallback(fetchWatchlist, []);

  useEffect(() => {
    debouncedFetchData(guestSession, userToken, setCartCount);
  }, [guestSession, userToken, cartCount, setCartCount, debouncedFetchData]);

  // const fetchData = useCallback(async () => {
  //   const result = await fetchListingData(
  //     "GET",
  //     `get-cart?cart_session=${guestSession}`,
  //     userToken ? userToken : undefined
  //   );

  //   if (result) {
  //     setCartCount(result?.data?.length);
  //   }
  // }, [guestSession, cartCount, setCartCount, userToken]);

  // useEffect(() => {
  //   // const fetchData = async () => {
  //   //   const result = await fetchListingData(
  //   //     "GET",
  //   //     `get-cart?cart_session=${guestSession}`,
  //   //     userToken ? userToken : undefined
  //   //   );

  //   //   if (result) {
  //   //     setCartCount(result?.data?.length);
  //   //   }
  //   // };
  //   fetchData();
  // }, [fetchData]);

  useEffect(() => {
    debouncedFetchWatchlist(userToken, setWatchlistCount);
  }, [userToken, setWatchlistCount, debouncedFetchWatchlist]);

  const handleFocus = () => {
    setIsSuggestionVisible(true);
  };
  const handleClickOutside = (e) => {
    if (inputRef.current && !inputRef.current.contains(e.target)) {
      setIsSuggestionVisible(false);
    }
  };
  const handleSearchInput = (e) => {
    const value = e.target.value;
    setSearchInput(value === "" ? "" : value);
    if (value.trim() === "") {
      setInputSuggestions([]);
      setIsSuggestionVisible(false);
    } else {
      const filtered = allsuggestions.filter((item) =>
        item.title.toLowerCase().includes(e.target.value.toLowerCase())
      );
      if (filtered.length > 0) {
        setIsSuggestionVisible(true);
      }
      setInputSuggestions(filtered);
    }
  };

  const handleSearch = useCallback(() => {
    debouncedHandleSearch(searchInput, router);
    setInputSuggestions([]);
  }, [searchInput, router]);

  // const handleSearch = () => {
  //   const fetchData = async () => {
  //     const res = await fetch(`${url}/search?q=${searchInput}`);
  //     const result = await res.json();
  //     if (result.data.data.length == 1) {
  //       router.push(`/product-details/${result.data.data[0].slug}`);
  //     } else if (result.data.data.length > 1) {
  //       router.push(`/search/${searchInput}`);
  //     } else {
  //       router.push(`/search/all`);
  //     }
  //   };
  //   searchInput && fetchData();
  //   setInputSuggestions([]);
  // };

  useEffect(() => {
    const fetchData = async () => {
      const res = await fetch(`${url}/search`);
      const result = await res.json();
      if (result?.data?.data?.length > 0) {
        setAllSuggestions(result?.data?.data);
      }
    };
    fetchData();
  }, []);

  useEffect(() => {
    document.addEventListener("click", handleClickOutside);
    return () => {
      document.removeEventListener("click", handleClickOutside);
    };
  }, []);

  const handleMenuToggle = () => {
    setIsMenuOpen((prev) => !prev);
  };

  const handleCloseMenu = () => {
    setIsMenuOpen(false);
    setOpenDropdownIndex(null);
    setOpenSubmenuIndex(null);
  };

  const handleDropdownIndex = (index) => {
    setOpenDropdownIndex((prevIndex) => (prevIndex === index ? null : index));
    setOpenSubmenuIndex(null);
  };

  const handleSubmenuToggle = (index) => {
    setOpenSubmenuIndex((prevIndex) => (prevIndex === index ? null : index));
  };
  return (
    <>
      <section className={styles.sec_Top_nav}>
        <div>
          <span>{contactUs?.SITE_OFFERSTRIP}</span>
        </div>
      </section>
      <header className="header">
        <nav>
          <div className="container py-2">
            <div className={`${styles.nav_exapnd_lg} nav-exapnd-lg`}>
              <div className="logo">
                <Link href="/" className={styles.top_logo}>
                  <Image
                    // src="/assets/images/mobile-logo1.svg"
                    src={
                      siteSettings?.data?.SITE_LOGO ||
                      "/assets/images/manidvpa-flowers-2.png"
                    }
                    // src="/assets/images/manidvpa-flowers-2.png"
                    className={styles.logo}
                    alt="logo"
                    width={0}
                    height={0}
                    sizes="100vw"
                    // style={{ width: "100%", height: "80px" }}
                    priority
                  />
                </Link>
              </div>
              <div className={`${styles.search_input} search-input`}>
                {/* <div className={`${styles.dropdown} dropdown`}>
                  <button className={styles.dropdown_toggle}>
                    All Products
                  </button>
                </div> */}
                <div className={`${styles.input} input`}>
                  <input
                    className={`${styles.form_input} form_input`}
                    ref={inputRef}
                    onFocus={handleFocus}
                    value={searchInput || ""}
                    onChange={handleSearchInput}
                    onKeyDown={(e) => {
                      if (e.key == "Enter") {
                        handleSearch();
                      }
                    }}
                    placeholder="Search For Products..."
                  />
                  {inputSuggestions && isSuggestionVisible && (
                    <div className={`${styles.suggestionBox}`}>
                      <ul>
                        {inputSuggestions.map((item, index) => (
                          <li
                            key={index}
                            onClick={() => {
                              setSearchInput(item.title);
                              setInputSuggestions([]);
                            }}
                          >
                            {item.title}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
                <button className={styles.search_but} onClick={handleSearch}>
                  Search
                </button>
              </div>
              <ul className={`${styles.nav_links} nav-links`}>
                {isAuthenticated ? (
                  <>
                    <li>
                      <Link href="/my-account">
                        <BsPersonFillGear className={styles.watch_icon} />
                        My Account
                      </Link>
                    </li>
                    <li>
                      <Link href="/watchlist" className={styles.watch_link}>
                        <FaRegHeart className={styles.watch_icon} />
                        Watchlist
                        {watchlistCount > 0 ? (
                          <span className={styles.watch_count}>
                            {watchlistCount}
                          </span>
                        ) : null}
                      </Link>
                    </li>
                  </>
                ) : (
                  <>
                    <li>
                      <Link href="/login">
                        <MdPerson className={styles.watch_icon} />
                        Login
                      </Link>
                    </li>
                    <li>
                      <Link href="/register">
                        <LuPencilLine className={styles.watch_icon} />
                        Register
                      </Link>
                    </li>
                  </>
                )}

                <li>
                  <Link href="/cart" className={styles.cart_link}>
                    <BsCart className={styles.cart_icon} />
                    <span>Cart</span>
                    {cartCount > 0 ? (
                      <span className={styles.cart_count}>{cartCount}</span>
                    ) : null}
                  </Link>
                </li>

                <li>
                  <a
                    href={`https://wa.me/${contactUs?.SITE_WHATSAPP}`}
                    target="_blank"
                    className={styles.whatsapp_icon}
                  >
                    <BsWhatsapp />
                    Chat
                  </a>
                </li>
              </ul>
              <button
                className={` menu-toggle`}
                aria-label="Toggle menu"
                onClick={handleMenuToggle}
              >
                <span className="bar"></span>
                <span className="bar"></span>
                <span className="bar"></span>
              </button>
            </div>
            <div
              className={`offcanvas-menu ${styles.offcanvas_menu}  ${
                isMenuOpen ? "open" : ""
              }`}
            >
              <div className={`${styles.offecanvas_header} offecanvas-header`}>
                <Link href="/" as={"logo"}>
                  <Image
                    src={
                      siteSettings?.data?.SITE_LOGO2 ||
                      "/assets/images/logo2.png"
                    }
                    // src="/assets/images/logo2.png"
                    className="logo w-100"
                    alt=""
                    width={220}
                    height={90}
                    priority
                  />
                </Link>
                <button
                  className="close-btn"
                  aria-label="Close menu"
                  onClick={handleCloseMenu}
                >
                  ✖
                </button>
              </div>

              <ul className={`${styles.list} list`}>
                {homepageNavItems.map((item) => (
                  <li
                    key={item.label}
                    className={`${styles.nav_item} nav-item`}
                    onClick={handleCloseMenu}
                  >
                    <Link href={item.href}>{item.label}</Link>
                  </li>
                ))}
                <ul className={`${styles.nav_links} nav-links`}>
                  {isAuthenticated ? (
                    <>
                      <li onClick={handleCloseMenu}>
                        <Link href="/my-account">
                          {/* <BsPersonFillGear className={styles.watch_icon} /> */}
                          MyAccount
                        </Link>
                      </li>
                      <li onClick={handleCloseMenu}>
                        <Link href="/watchlist" className={styles.watch_link}>
                          {/* <FaRegHeart className={styles.watch_icon} /> */}
                          Watchlist
                          {watchlistCount > 0 ? (
                            <span> ( {watchlistCount} )</span>
                          ) : null}
                        </Link>
                      </li>
                    </>
                  ) : (
                    <>
                      <li onClick={handleCloseMenu}>
                        <Link href="/login">
                          {/* <MdPerson className={styles.watch_icon} /> */}
                          Login
                        </Link>
                      </li>
                      <li onClick={handleCloseMenu}>
                        <Link href="/register">
                          {/* <LuPencilLine className={styles.watch_icon} /> */}
                          Register
                        </Link>
                      </li>
                    </>
                  )}

                  <li onClick={handleCloseMenu}>
                    <Link href="/cart" className={styles.cart_link}>
                      {/* <BsCart className={styles.cart_icon} /> */}
                      <span>Cart</span>
                      {cartCount > 0 ? <span> ( {cartCount} )</span> : null}
                    </Link>
                  </li>

                  <li onClick={handleCloseMenu}>
                    <a
                      href={`https://wa.me/${contactUs?.SITE_WHATSAPP}`}
                      target="_blank"
                      className={styles.whatsapp_icon}
                    >
                      {/* <BsWhatsapp /> */}
                      Chat
                    </a>
                  </li>
                </ul>
              </ul>
            </div>
          </div>
        </nav>
        <div className={`${styles.search_input1} search-input`}>
          {/* <div className={`${styles.dropdown} dropdown`}>
                  <button className={styles.dropdown_toggle}>
                    All Products
                  </button>
                </div> */}
          <div className={`${styles.input} input`}>
            <input
              className={`${styles.form_input} form_input`}
              ref={inputRef}
              onFocus={handleFocus}
              value={searchInput || ""}
              onChange={handleSearchInput}
              onKeyDown={(e) => {
                if (e.key == "Enter") {
                  handleSearch();
                }
              }}
              placeholder="Search For Products..."
            />
            {inputSuggestions && isSuggestionVisible && (
              <div className={`${styles.suggestionBox}`}>
                <ul>
                  {inputSuggestions.map((item, index) => (
                    <li
                      key={index}
                      onClick={() => {
                        setSearchInput(item.title);
                        setInputSuggestions([]);
                      }}
                    >
                      {item.title}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
          <button className={styles.search_but} onClick={handleSearch}>
            Search
          </button>
        </div>
        <section className={`${styles.second_nav} sticky`}>
          <div className="container">
            <div className="nav-exapnd-lg">
              <div className="nav-links">
                {homepageNavItems.map((item) => (
                  <li key={item.label} className={`${styles.nav_item} nav-item`}>
                    <Link href={item.href}>{item.label}</Link>
                  </li>
                ))}
              </div>
            </div>
          </div>
        </section>
      </header>
    </>
  );
};

export default Navbar;
