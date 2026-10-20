"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { BsCart, BsWhatsapp } from "react-icons/bs";
import { FaPhoneAlt } from "react-icons/fa";
import {
  IoChevronDown,
  IoClose,
  IoLocationOutline,
  IoMenu,
  IoSearch,
} from "react-icons/io5";
import { MdPerson } from "react-icons/md";

import { useCartCount, useUser, useWatchlistCount } from "@/context/UserContext";
import { storefrontNavItems } from "@/data/storefrontNavigation";
import {
  isExternalNavigationHref,
  isNavigationItemActive,
  navigationKey,
  prepareNavigationItems,
} from "@/lib/navigation";
import styles from "@/scss/components/navbar.module.scss";
import { getCartCount } from "../../hook/userCookie";

const apiUrl = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

function buildSearchPath(value) {
  const slug = String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
  return slug ? `/search/${slug}` : "/search/all";
}

async function searchProducts(searchInput, router) {
  if (!searchInput.trim()) return;
  try {
    const params = new URLSearchParams({ q: searchInput.trim() });
    const response = await fetch(`${apiUrl}/search?${params.toString()}`);
    const result = await response.json();
    const products = result?.data?.data || [];
    if (products.length === 1) router.push(`/flowers/${products[0].slug}`);
    else if (products.length > 1) router.push(buildSearchPath(searchInput));
    else router.push("/search/all");
  } catch (error) {
    console.error("Error fetching search results:", error);
  }
}

function getWhatsAppHref(value) {
  const phone = String(value || "").replace(/\D/g, "");
  return phone ? `https://wa.me/${phone}` : "/contact-us";
}

async function fetchCart(guestSession, userToken, setCartCount) {
  if (!guestSession) return;
  try {
    const response = await fetch(
      `${apiUrl}/get-cart?cart_session=${guestSession}`,
      {
        headers: {
          "Content-Type": "application/json",
          ...(userToken && { Authorization: `Bearer ${userToken}` }),
        },
        cache: "no-store",
      }
    );
    if (response.ok) setCartCount(getCartCount(await response.json()));
  } catch (error) {
    console.error("Error fetching cart data:", error);
  }
}

async function fetchWatchlist(userToken, setWatchlistCount) {
  if (!userToken) return;
  try {
    const response = await fetch(`${apiUrl}/wishlist`, {
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${userToken}`,
      },
      cache: "no-store",
    });
    if (!response.ok) return;
    const result = await response.json();
    setWatchlistCount(result?.data?.length || 0);
  } catch (error) {
    console.error("Error fetching watchlist data:", error);
  }
}

async function fetchDailyPriceStatus(setDailyPriceStatus) {
  try {
    const response = await fetch(`${apiUrl}/daily-price-status`, {
      headers: { "Content-Type": "application/json" },
      cache: "no-store",
    });
    if (!response.ok) return;
    const result = await response.json();
    setDailyPriceStatus(result?.data || null);
  } catch (error) {
    console.error("Error fetching daily price status:", error);
  }
}

function NavigationLink({ item, active, className, onClick, children }) {
  const content = children || item.label;
  if (isExternalNavigationHref(item.href)) {
    return (
      <a
        href={item.href}
        className={className}
        target="_blank"
        rel="noopener noreferrer"
        onClick={onClick}
      >
        {content}
      </a>
    );
  }
  return (
    <Link
      href={item.href}
      className={className}
      aria-current={active ? "page" : undefined}
      onClick={onClick}
    >
      {content}
    </Link>
  );
}

export default function Navbar({
  navigationItems = [],
  siteSettings,
  guestSession,
  userToken,
}) {
  const contactUs = siteSettings?.data;
  const whatsappHref = getWhatsAppHref(
    contactUs?.SITE_WHATSAPP || contactUs?.SITE_PHONE
  );
  const helpPhone =
    contactUs?.SITE_PHONE || contactUs?.SITE_WHATSAPP || "+91 73375 25445";
  const phoneHref = `tel:${String(helpPhone).replace(/\D/g, "")}`;
  const primaryNavItems = useMemo(
    () => prepareNavigationItems(navigationItems, storefrontNavItems),
    [navigationItems]
  );

  const router = useRouter();
  const pathname = usePathname();
  const { isAuthenticated, guestSession: clientGuestSession } = useUser();
  const { cartCount, setCartCount } = useCartCount();
  const { watchlistCount, setWatchlistCount } = useWatchlistCount();
  const activeGuestSession = clientGuestSession || guestSession;

  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [openDesktopMenu, setOpenDesktopMenu] = useState(null);
  const [expandedMobileMenus, setExpandedMobileMenus] = useState({});
  const [searchInput, setSearchInput] = useState("");
  const [allSuggestions, setAllSuggestions] = useState([]);
  const [inputSuggestions, setInputSuggestions] = useState([]);
  const [isSuggestionVisible, setIsSuggestionVisible] = useState(false);
  const [dailyPriceStatus, setDailyPriceStatus] = useState(null);
  const desktopSearchRef = useRef(null);
  const mobileSearchRef = useRef(null);
  const desktopNavRef = useRef(null);
  const mobileCloseRef = useRef(null);

  useEffect(() => {
    fetchCart(activeGuestSession, userToken, setCartCount);
  }, [activeGuestSession, userToken, setCartCount]);

  useEffect(() => {
    fetchWatchlist(userToken, setWatchlistCount);
  }, [userToken, setWatchlistCount]);

  useEffect(() => {
    fetchDailyPriceStatus(setDailyPriceStatus);
    const intervalId = window.setInterval(
      () => fetchDailyPriceStatus(setDailyPriceStatus),
      300000
    );
    return () => window.clearInterval(intervalId);
  }, []);

  useEffect(() => {
    async function loadSuggestions() {
      try {
        const response = await fetch(`${apiUrl}/search`);
        if (!response.ok) return;
        const result = await response.json();
        setAllSuggestions(result?.data?.data || []);
      } catch (error) {
        console.error("Error fetching search suggestions:", error);
      }
    }
    loadSuggestions();
  }, []);

  useEffect(() => {
    function handleDocumentClick(event) {
      const outsideDesktop = !desktopSearchRef.current?.contains(event.target);
      const outsideMobile = !mobileSearchRef.current?.contains(event.target);
      if (outsideDesktop && outsideMobile) setIsSuggestionVisible(false);
      if (!desktopNavRef.current?.contains(event.target)) setOpenDesktopMenu(null);
    }
    document.addEventListener("pointerdown", handleDocumentClick);
    return () => document.removeEventListener("pointerdown", handleDocumentClick);
  }, []);

  useEffect(() => {
    if (!isMenuOpen) return undefined;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    mobileCloseRef.current?.focus();
    function handleKeyDown(event) {
      if (event.key === "Escape") setIsMenuOpen(false);
    }
    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [isMenuOpen]);

  const closeMenus = useCallback(() => {
    setIsMenuOpen(false);
    setOpenDesktopMenu(null);
  }, []);

  const handleSearchInput = (event) => {
    const value = event.target.value;
    setSearchInput(value);
    const query = value.trim().toLowerCase();
    if (!query) {
      setInputSuggestions([]);
      setIsSuggestionVisible(false);
      return;
    }
    const matches = allSuggestions
      .filter((item) => String(item?.title || "").toLowerCase().includes(query))
      .slice(0, 8);
    setInputSuggestions(matches);
    setIsSuggestionVisible(matches.length > 0);
  };

  const handleSearch = useCallback(() => {
    searchProducts(searchInput, router);
    setInputSuggestions([]);
    setIsSuggestionVisible(false);
  }, [searchInput, router]);

  const renderSearch = (containerRef, mobile = false) => (
    <div className={mobile ? styles.mobileSearch : styles.search} ref={containerRef}>
      <div className={styles.searchInputWrap}>
        <label
          className={styles.srOnly}
          htmlFor={mobile ? "mobile-search" : "desktop-search"}
        >
          Search flowers
        </label>
        <input
          id={mobile ? "mobile-search" : "desktop-search"}
          className={styles.searchInput}
          value={searchInput}
          onFocus={() => inputSuggestions.length && setIsSuggestionVisible(true)}
          onChange={handleSearchInput}
          onKeyDown={(event) => event.key === "Enter" && handleSearch()}
          placeholder="Search for flowers, garlands, leaves..."
          autoComplete="off"
        />
        {isSuggestionVisible && inputSuggestions.length > 0 ? (
          <ul className={styles.suggestionBox} aria-label="Search suggestions">
            {inputSuggestions.map((item) => (
              <li key={item.id || item.slug || item.title}>
                <button
                  type="button"
                  onClick={() => {
                    setSearchInput(item.title);
                    setInputSuggestions([]);
                    setIsSuggestionVisible(false);
                  }}
                >
                  {item.title}
                </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
      <button
        type="button"
        className={styles.searchButton}
        onClick={handleSearch}
        aria-label="Search"
      >
        <IoSearch aria-hidden="true" />
      </button>
    </div>
  );

  return (
    <>
      <section className={styles.topBar} aria-label="Store information">
        <div className={styles.topBarInner}>
          <div className={styles.topBarMessage}>
            <span
              className={`${styles.priceStatusBadge} ${
                dailyPriceStatus?.is_updated_today
                  ? styles.priceStatusUpdated
                  : styles.priceStatusPending
              }`}
            >
              <span aria-hidden="true">
                {dailyPriceStatus?.is_updated_today ? "✓" : "⚠"}
              </span>
              <span>
                {dailyPriceStatus?.is_updated_today
                  ? `${dailyPriceStatus.status_text} at ${dailyPriceStatus.updated_time}`
                  : [
                      dailyPriceStatus?.status_text || "Prices Not Updated Today",
                      dailyPriceStatus?.last_updated_relative,
                    ]
                      .filter(Boolean)
                      .join(" · ")}
              </span>
            </span>
            <span className={styles.desktopTopMessage}>
              Same-Day Delivery in Hyderabad
            </span>
            <a href={whatsappHref} target="_blank" rel="noopener noreferrer">
              Order on WhatsApp
            </a>
          </div>
          <div className={styles.topBarHelp}>
            <span>Need Help?</span>
            <FaPhoneAlt aria-hidden="true" />
            <a href={phoneHref}>{helpPhone}</a>
          </div>
        </div>
      </section>

      <header className={styles.headerShell}>
        <div className={styles.mainHeader}>
          <Link href="/" className={styles.logoLink} aria-label="Manidvipa Flowers home">
            <Image
              src={contactUs?.SITE_LOGO || "/assets/images/manidvpa-flowers-2.png"}
              className={styles.logo}
              alt="Manidvipa Flowers"
              width={255}
              height={82}
              priority
            />
          </Link>

          <Link href="/contact-us" className={styles.deliveryLocation}>
            <span className={styles.locationIcon}>
              <IoLocationOutline aria-hidden="true" />
            </span>
            <span>
              <small>Delivering to</small>
              <strong>Hyderabad, TS</strong>
            </span>
            <IoChevronDown aria-hidden="true" />
          </Link>

          {renderSearch(desktopSearchRef)}

          <ul className={styles.headerActions} aria-label="Customer shortcuts">
            <li>
              <a
                href={whatsappHref}
                target="_blank"
                rel="noopener noreferrer"
                className={styles.whatsappOrder}
              >
                <span className={styles.whatsappCircle}>
                  <BsWhatsapp aria-hidden="true" />
                </span>
                <span>Order on<strong>WhatsApp</strong></span>
              </a>
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
              <Link href="/cart" className={styles.cartLink}>
                <span className={styles.cartIconWrap}>
                  <BsCart aria-hidden="true" />
                  {cartCount > 0 ? (
                    <span className={styles.cartCount}>{cartCount}</span>
                  ) : null}
                </span>
                <span>Cart</span>
              </Link>
            </li>
          </ul>

          <button
            type="button"
            className={styles.menuToggle}
            aria-label="Open navigation menu"
            aria-expanded={isMenuOpen}
            aria-controls="mobile-navigation"
            onClick={() => setIsMenuOpen(true)}
          >
            <IoMenu aria-hidden="true" />
          </button>
        </div>

        {renderSearch(mobileSearchRef, true)}

        <nav
          className={styles.desktopNav}
          aria-label="Primary navigation"
          ref={desktopNavRef}
        >
          <ul className={styles.desktopNavList}>
            {primaryNavItems.map((item) => {
              const active = isNavigationItemActive(pathname, item);
              const hasChildren = item.children.length > 0;
              const menuId = `desktop-menu-${
                navigationKey(item.href).replace(/[^a-z0-9]/g, "-") || item.key
              }`;
              const isOpen = openDesktopMenu === item.key;
              return (
                <li
                  key={item.key}
                  className={`${styles.desktopNavItem} ${
                    active ? styles.activeNavItem : ""
                  }`}
                  onMouseEnter={() => hasChildren && setOpenDesktopMenu(item.key)}
                  onMouseLeave={() => hasChildren && setOpenDesktopMenu(null)}
                  onFocusCapture={() => hasChildren && setOpenDesktopMenu(item.key)}
                  onBlurCapture={(event) => {
                    if (!event.currentTarget.contains(event.relatedTarget)) {
                      setOpenDesktopMenu(null);
                    }
                  }}
                >
                  <div className={styles.desktopNavTrigger}>
                    <NavigationLink item={item} active={active} />
                    {hasChildren ? (
                      <button
                        type="button"
                        aria-label={`Show ${item.label} categories`}
                        aria-expanded={isOpen}
                        aria-controls={menuId}
                        onClick={() => setOpenDesktopMenu(isOpen ? null : item.key)}
                      >
                        <IoChevronDown aria-hidden="true" />
                      </button>
                    ) : null}
                  </div>
                  {hasChildren ? (
                    <ul
                      id={menuId}
                      className={`${styles.desktopSubmenu} ${
                        isOpen ? styles.desktopSubmenuOpen : ""
                      }`}
                    >
                      {item.children.map((child) => (
                        <li key={child.key}>
                          <NavigationLink
                            item={child}
                            active={isNavigationItemActive(pathname, child)}
                            onClick={closeMenus}
                          />
                        </li>
                      ))}
                    </ul>
                  ) : null}
                </li>
              );
            })}
          </ul>
        </nav>
      </header>

      <button
        type="button"
        className={`${styles.mobileBackdrop} ${
          isMenuOpen ? styles.mobileBackdropOpen : ""
        }`}
        aria-label="Close navigation menu"
        tabIndex={isMenuOpen ? 0 : -1}
        onClick={closeMenus}
      />
      <aside
        id="mobile-navigation"
        className={`${styles.mobileDrawer} ${
          isMenuOpen ? styles.mobileDrawerOpen : ""
        }`}
        role="dialog"
        aria-modal="true"
        aria-label="Navigation menu"
        aria-hidden={!isMenuOpen}
      >
        <div className={styles.mobileDrawerHeader}>
          <Link href="/" onClick={closeMenus} aria-label="Manidvipa Flowers home">
            <Image
              src={contactUs?.SITE_LOGO || "/assets/images/manidvpa-flowers-2.png"}
              alt="Manidvipa Flowers"
              width={190}
              height={62}
            />
          </Link>
          <button
            ref={mobileCloseRef}
            type="button"
            onClick={closeMenus}
            aria-label="Close navigation menu"
          >
            <IoClose aria-hidden="true" />
          </button>
        </div>

        <nav className={styles.mobileNav} aria-label="Mobile primary navigation">
          <ul className={styles.mobileNavList}>
            {primaryNavItems.map((item) => {
              const active = isNavigationItemActive(pathname, item);
              const hasChildren = item.children.length > 0;
              const expanded = Boolean(expandedMobileMenus[item.key]);
              const submenuId = `mobile-menu-${
                navigationKey(item.href).replace(/[^a-z0-9]/g, "-") || item.key
              }`;
              return (
                <li key={item.key} className={active ? styles.mobileActiveItem : ""}>
                  <div className={styles.mobileNavRow}>
                    <NavigationLink item={item} active={active} onClick={closeMenus} />
                    {hasChildren ? (
                      <button
                        type="button"
                        aria-label={`Show ${item.label} categories`}
                        aria-expanded={expanded}
                        aria-controls={submenuId}
                        onClick={() =>
                          setExpandedMobileMenus((current) => ({
                            ...current,
                            [item.key]: !current[item.key],
                          }))
                        }
                      >
                        <IoChevronDown
                          className={expanded ? styles.chevronOpen : ""}
                          aria-hidden="true"
                        />
                      </button>
                    ) : null}
                  </div>
                  {hasChildren ? (
                    <ul
                      id={submenuId}
                      className={styles.mobileSubmenu}
                      hidden={!expanded}
                    >
                      {item.children.map((child) => {
                        const childActive = isNavigationItemActive(pathname, child);
                        return (
                          <li
                            key={child.key}
                            className={childActive ? styles.mobileChildActive : ""}
                          >
                            <NavigationLink
                              item={child}
                              active={childActive}
                              onClick={closeMenus}
                            />
                          </li>
                        );
                      })}
                    </ul>
                  ) : null}
                </li>
              );
            })}
          </ul>
        </nav>

        <div className={styles.mobileUtilities}>
          <Link
            href={isAuthenticated ? "/my-account" : "/login"}
            onClick={closeMenus}
          >
            <MdPerson aria-hidden="true" />
            {isAuthenticated ? "My Account" : "Login / Sign up"}
          </Link>
          {isAuthenticated ? (
            <Link href="/watchlist" onClick={closeMenus}>
              Watchlist{watchlistCount > 0 ? ` (${watchlistCount})` : ""}
            </Link>
          ) : null}
          <Link href="/cart" onClick={closeMenus}>
            <BsCart aria-hidden="true" />
            Cart{cartCount > 0 ? ` (${cartCount})` : ""}
          </Link>
          <a
            href={whatsappHref}
            target="_blank"
            rel="noopener noreferrer"
            onClick={closeMenus}
          >
            <BsWhatsapp aria-hidden="true" />
            Chat on WhatsApp
          </a>
        </div>
      </aside>
    </>
  );
}
