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
import { usePathname, useRouter } from "next/navigation";
import { useUser } from "@/context/UserContext";
import { FaPhoneAlt, FaStar } from "react-icons/fa";
import { BsCart } from "react-icons/bs";
import { BsWhatsapp } from "react-icons/bs";
import { useCartCount } from "@/context/UserContext";
import { useWatchlistCount } from "@/context/UserContext";
import Link from "next/link";
import { MdPerson } from "react-icons/md";
import { IoChevronDown, IoLocationOutline, IoSearch } from "react-icons/io5";
import { getCartCount } from "../../hook/userCookie";
import { storefrontNavItems } from "@/data/storefrontNavigation";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

const debouncedHandleSearch = async (searchInput, router) => {
  if (!searchInput) return;

  try {
    const searchParams = new URLSearchParams({ q: searchInput });
    const res = await fetch(`${url}/search?${searchParams.toString()}`);
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

const getWhatsAppHref = (value) => {
  const phone = String(value || "").replace(/\D/g, "");
  return phone ? `https://wa.me/${phone}` : "#";
};

const fetchData = async (guestSession, userToken, setCartCount) => {
  if (!guestSession) return null;

  try {
    const res = await fetch(`${url}/get-cart?cart_session=${guestSession}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
      },
      cache: "no-store",
    });

    if (!res.ok) {
      const errorData = await res.text();
      console.error("Fetch Error:", errorData);
      throw new Error(`Failed to fetch cart data: ${errorData}`);
    }

    const result = await res.json();
    setCartCount(getCartCount(result));
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
  siteSettings,
  guestSession,
  userToken,
}) => {
  const contactUs = siteSettings?.data;
  const whatsappHref = getWhatsAppHref(
    contactUs?.SITE_WHATSAPP || contactUs?.SITE_PHONE
  );
  const helpPhone = contactUs?.SITE_PHONE || contactUs?.SITE_WHATSAPP || "+91 73375 25445";

  const primaryNavItems = storefrontNavItems;
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { isAuthenticated, guestSession: clientGuestSession } = useUser();
  const activeGuestSession = clientGuestSession || guestSession;
  const { cartCount, setCartCount } = useCartCount();
  const { watchlistCount, setWatchlistCount } = useWatchlistCount();
  const router = useRouter();
  const pathname = usePathname();
  const [searchInput, setSearchInput] = useState("");
  const [allsuggestions, setAllSuggestions] = useState([]);
  const [inputSuggestions, setInputSuggestions] = useState([]);
  const [isSuggestionVisible, setIsSuggestionVisible] = useState(false);
  const inputRef = useRef(null);

  const debouncedFetchData = useCallback(fetchData, []);
  const debouncedFetchWatchlist = useCallback(fetchWatchlist, []);

  useEffect(() => {
    debouncedFetchData(activeGuestSession, userToken, setCartCount);
  }, [activeGuestSession, userToken, setCartCount, debouncedFetchData]);

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
  };

  const isActiveNavItem = (href) => {
    if (href === "/") return pathname === "/";
    return pathname === href || pathname.startsWith(`${href}/`);
  };
  return (
    <>
      <section className={styles.sec_Top_nav}>
        <div className={styles.topStripInner}>
          <div className={styles.topStripText}>
            <FaStar aria-hidden="true" />
            <span>Fresh Flower Prices Updated Daily at 8AM</span>
            <span className={styles.topStripDivider}>|</span>
            <span>Same-Day Delivery in Hyderabad</span>
            <span className={styles.topStripDivider}>|</span>
            <Link href={whatsappHref} target="_blank" rel="noopener noreferrer">
              Order on WhatsApp
            </Link>
            <span className={styles.topStripDivider}>|</span>
          </div>
          <div className={styles.topStripHelp}>
            <span>Need Help?</span>
            <FaPhoneAlt aria-hidden="true" />
            <Link href={`tel:${String(helpPhone).replace(/\D/g, "")}`}>{helpPhone}</Link>
          </div>
        </div>
      </section>
      <header className={`${styles.headerShell} header`}>
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
              <Link href="/contact-us" className={styles.deliveryLocation}>
                <span className={styles.locationIcon}>
                  <IoLocationOutline aria-hidden="true" />
                </span>
                <span className={styles.locationCopy}>
                  <span>Delivering to</span>
                  <strong>Hyderabad, TS</strong>
                </span>
                <IoChevronDown className={styles.locationChevron} aria-hidden="true" />
              </Link>
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
                    placeholder="Search for flowers, garlands, leaves..."
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
                <button
                  className={styles.search_but}
                  onClick={handleSearch}
                  aria-label="Search"
                >
                  <IoSearch aria-hidden="true" />
                </button>
              </div>
              <ul className={`${styles.nav_links} nav-links`}>
                <li>
                  <Link
                    href={whatsappHref}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={styles.whatsappOrder}
                  >
                    <span className={styles.whatsappCircle}>
                      <BsWhatsapp aria-hidden="true" />
                    </span>
                    <span>
                      Order on
                      <strong>WhatsApp</strong>
                    </span>
                  </Link>
                </li>

                <li>
                  <Link
                    href={isAuthenticated ? "/my-account" : "/login"}
                    className={styles.accountLink}
                  >
                    <span className={styles.accountIcon}>
                      <MdPerson aria-hidden="true" />
                    </span>
                    <span>{isAuthenticated ? "My Account" : "Login / Sign up"}</span>
                  </Link>
                </li>

                <li>
                  <Link href="/cart" className={styles.cart_link}>
                    <span className={styles.cartIconWrap}>
                      <BsCart className={styles.cart_icon} aria-hidden="true" />
                      {cartCount > 0 ? (
                        <span className={styles.cart_count}>{cartCount}</span>
                      ) : null}
                    </span>
                    <span>Cart</span>
                  </Link>
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
                {primaryNavItems.map((item) => (
                  <li
                    key={item.label}
                    className={`${styles.nav_item} ${
                      isActiveNavItem(item.href) ? styles.active_nav_item : ""
                    } nav-item`}
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
                      href={whatsappHref}
                      target="_blank"
                      rel="noopener noreferrer"
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
              placeholder="Search for flowers, garlands, leaves..."
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
          <button
            className={styles.search_but}
            onClick={handleSearch}
            aria-label="Search"
          >
            <IoSearch aria-hidden="true" />
          </button>
        </div>
        <section className={`${styles.second_nav} sticky`}>
          <div className="container">
            <div className="nav-exapnd-lg">
              <div className="nav-links">
                {primaryNavItems.map((item) => (
                  <li
                    key={item.label}
                    className={`${styles.nav_item} ${
                      isActiveNavItem(item.href) ? styles.active_nav_item : ""
                    } nav-item`}
                  >
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
