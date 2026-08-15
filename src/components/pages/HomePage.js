"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import styles from "@/scss/pages/home.module.scss";
import { fetchListingData } from "../../../hook/userCookie";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const categoryCards = [
  {
    title: "Daily Puja Flowers",
    description: "Chamanthi, Banthi, Kanakambaram & more",
    image: "/assets/images/home-v2/category-daily-puja.jpg",
    terms: ["chamanthi", "banthi", "kanakambaram"],
  },
  {
    title: "Premium Flowers",
    description: "Roses, lilies, orchids & more",
    image: "/assets/images/home-v2/category-premium.jpg",
    terms: ["rose", "premium"],
  },
  {
    title: "Rare Flowers",
    description: "Seasonal & exotic varieties",
    image: "/assets/images/home-v2/category-rare.jpg",
    terms: ["other", "rare"],
  },
  {
    title: "Patri & Leaves",
    description: "Tulasi, Bilva, Mango leaves & more",
    image: "/assets/images/home-v2/category-patri.jpg",
    terms: ["patri", "leaves", "leaf"],
  },
  {
    title: "Bouquets & Gifting",
    description: "Perfect for every occasion",
    image: "/assets/images/home-v2/category-gifting.jpg",
    terms: ["rose", "flower"],
  },
  {
    title: "Temple & Pooja",
    description: "Garlands, pooja kits & more",
    image: "/assets/images/home-v2/category-temple.jpg",
    terms: ["pooja", "puja", "garland"],
  },
];

const subscriptions = [
  ["Daily Puja Subscription", "Fresh puja flowers delivered daily."],
  ["Weekly Subscription", "Three convenient deliveries every week."],
  ["Temple Subscription", "Bulk flowers, garlands and leaves."],
  ["Home Subscription", "Fresh flowers for puja and home décor."],
  ["Office / Business", "Office, temple and reception flowers."],
];

const benefits = [
  ["/assets/images/free-shipping.png", "Fresh Every Morning", "Sourced and prepared daily"],
  ["/assets/images/free-shipping.png", "Same-Day Delivery", "Fast delivery across Hyderabad"],
  ["/assets/images/kumbham-img-1.png", "Puja Ready", "Flowers and leaves ready to use"],
  ["/assets/images/online-support.png", "Easy Subscriptions", "Daily, weekly or monthly plans"],
  ["/assets/icons/whatsapp.png", "WhatsApp Ordering", "Quick support and easy ordering"],
  ["/assets/images/security.png", "Secure Payments", "Safe and secure payments"],
];

const occasions = [
  ["Daily Puja", "/assets/images/home-v2/category-daily-puja.jpg"],
  ["Temple Offering", "/assets/images/home-v2/category-temple.jpg"],
  ["Wedding", "/assets/images/home-v2/category-gifting.jpg"],
  ["Housewarming", "/assets/images/home-v2/category-patri.jpg"],
  ["Birthday", "/assets/images/home-v2/category-premium.jpg"],
  ["Anniversary", "/assets/images/home-v2/category-rare.jpg"],
];

const decorationGallery = [
  ["Weddings", "/assets/images/home-v2/decoration-wedding.jpg"],
  ["Pooja Decorations", "/assets/images/home-v2/decoration-pooja.jpg"],
  ["Temple Decorations", "/assets/images/home-v2/decoration-temple.jpg"],
  ["Events", "/assets/images/home-v2/decoration-events.jpg"],
];

const instagramImages = Array.from(
  { length: 8 },
  (_, index) => `/assets/images/home-v2/insta-${index + 1}.jpg`
);

function normalizeWhatsApp(value) {
  if (!value) return "/contact-us";
  if (value.startsWith("http")) return value;
  const phone = value.replace(/\D/g, "");
  return phone ? `https://wa.me/${phone}` : "/contact-us";
}

function SectionTitle({ title, subtitle, actionText, actionHref = "/search/all" }) {
  return (
    <div className={styles.sectionTitleRow}>
      <div>
        <h2>{title}</h2>
        {subtitle ? <p>{subtitle}</p> : null}
      </div>
      {actionText ? (
        <Link href={actionHref} className={styles.textLink}>
          {actionText} →
        </Link>
      ) : null}
    </div>
  );
}

function getProductImage(product) {
  return product?.image_name
    ? `${IMG_URL}/${product.image_name}`
    : "/assets/images/no-image.png";
}

function getProductHref(product) {
  return product?.slug ? `/product-details/${product.slug}` : "/search/all";
}

function FreshArrivalCard({ product }) {
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);

  return (
    <article className={styles.freshArrivalCard}>
      <Link href={href} className={styles.freshArrivalImage}>
        <span className={styles.freshBadge}>Fresh Today</span>
        <Image
          src={imageSrc}
          alt={product?.title || "Fresh flower"}
          width={300}
          height={230}
          sizes="(max-width: 700px) 62vw, 180px"
        />
      </Link>
      <div className={styles.freshArrivalBody}>
        <h3>{product?.title || "Fresh Flowers"}</h3>
        <div className={styles.freshStars}>★★★★★</div>
        <div className={styles.freshPrice}>
          {product?.list_price ? <span>Rs {product.list_price}</span> : null}
          <strong>Rs {product?.sell_price || product?.list_price || "--"}</strong>
        </div>
        <div className={styles.freshQuantity}>
          <span>−</span>
          <strong>1</strong>
          <span>kg</span>
          <span>+</span>
        </div>
        <Link href={href} className={styles.freshAddButton}>ADD TO CART</Link>
      </div>
    </article>
  );
}

function PremiumProductCard({ product }) {
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);

  return (
    <article className={styles.premiumProductCard}>
      <Link href={href} className={styles.premiumProductImage}>
        <Image
          src={imageSrc}
          alt={product?.title || "Premium flower"}
          width={320}
          height={220}
          sizes="(max-width: 700px) 44vw, 170px"
        />
      </Link>
      <div className={styles.premiumProductInfo}>
        <h3>{product?.title || "Premium Flowers"}</h3>
        <p>Starting <strong>Rs {product?.sell_price || product?.list_price || "--"}</strong></p>
      </div>
    </article>
  );
}

function RareProductCard({ product, index }) {
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);
  const labels = ["LIMITED", "SEASONAL", "RARE"];

  return (
    <article className={styles.rareProductCard}>
      <Link href={href} className={styles.rareProductImage}>
        <Image
          src={imageSrc}
          alt={product?.title || "Rare flower"}
          width={320}
          height={245}
          sizes="(max-width: 700px) 62vw, 175px"
        />
        <span className={`${styles.rareBadge} ${index % labels.length === 0 ? styles.rareBadgeLimited : index % labels.length === 1 ? styles.rareBadgeSeasonal : styles.rareBadgeRare}`}>{labels[index % labels.length]}</span>
      </Link>
      <div className={styles.rareProductInfo}>
        <h3>{product?.title || "Rare Flowers"}</h3>
        <div className={styles.rarePrice}>
          {product?.list_price ? <span>Rs {product.list_price}</span> : null}
          <strong>Rs {product?.sell_price || product?.list_price || "--"}</strong>
        </div>
      </div>
    </article>
  );
}

export default function HomePage({
  userToken,
  categories = [],
  initialHomeProducts = [],
  siteSettings,
}) {
  const [homeProducts, setHomeProducts] = useState(initialHomeProducts || []);

  const whatsappHref = normalizeWhatsApp(siteSettings?.data?.SITE_WHATSAPP);
  const instagramHref = siteSettings?.data?.INSTAGRAM_LINK || "#";

  const fetchData = useCallback(async () => {
    try {
      const response = await fetchListingData(
        "GET",
        "home-featured-products",
        userToken || undefined
      );
      if (response?.data?.length) setHomeProducts(response.data);
    } catch (error) {
      console.error("Unable to refresh homepage products:", error);
    }
  }, [userToken]);

  useEffect(() => {
    if (!initialHomeProducts?.length) fetchData();
  }, [fetchData, initialHomeProducts]);

  const categoryHref = useCallback(
    (terms, fallbackIndex = 0) => {
      const match = categories.find((category) => {
        const title = String(category?.title || "").toLowerCase();
        return terms.some((term) => title.includes(term));
      });
      const fallback = categories[fallbackIndex];
      const target = match || fallback;
      return target?.slug ? `/products/${target.slug}` : "/search/all";
    },
    [categories]
  );

  const productSections = useMemo(() => {
    const pool = homeProducts || [];
    const fresh = pool.slice(0, 4);
    const premium = pool.slice(4, 8).length ? pool.slice(4, 8) : pool.slice(0, 4);
    const rare = pool.slice(8, 12).length ? pool.slice(8, 12) : pool.slice(0, 4);
    return { fresh, premium, rare };
  }, [homeProducts]);

  return (
    <main className={styles.homePage}>
      <section className={styles.heroSection} id="home">
        <div className={styles.homeContainer}>
          <div className={styles.heroGrid}>
            <div className={styles.heroContent}>
              <h1>
                Fresh Flowers.
                <br />
                Delivered With Devotion.
              </h1>
              <p className={styles.heroDescription}>
                From Daily Puja Flowers to Premium & Rare Blooms — Freshly Sourced and
                Delivered to Your Doorstep.
              </p>

              <div className={styles.heroBenefits}>
                <div>
                  <strong>Fresh Every Morning</strong>
                  <span>Sourced Daily</span>
                </div>
                <div>
                  <strong>Same-Day Delivery</strong>
                  <span>Across Hyderabad</span>
                </div>
                <div>
                  <strong>Puja Ready</strong>
                  <span>Flowers & Leaves</span>
                </div>
              </div>

              <div className={styles.heroActions}>
                <Link href="/search/all" className="primary-but">
                  SHOP FRESH FLOWERS
                </Link>
                <Link href="#subscriptions" className="green-but">
                  START A SUBSCRIPTION
                </Link>
              </div>
              <Link href={whatsappHref} target="_blank" className={styles.whatsappTextLink}>
                Order on WhatsApp →
              </Link>
            </div>

            <div className={styles.heroImageWrap}>
              <Image
                src="/assets/images/home-v2/hero-flowers.jpg"
                alt="Fresh puja flowers arranged in a traditional tray"
                width={1000}
                height={600}
                priority
                sizes="(max-width: 900px) 100vw, 56vw"
              />
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="shop-by-category">
        <div className={styles.homeContainer}>
          <div className={styles.centerSectionTitle}>
            <Image src="/assets/icons/head-left.png" alt="" width={48} height={16} />
            <h2>Shop by Category</h2>
            <Image src="/assets/icons/head-right.png" alt="" width={48} height={16} />
          </div>
          <div className={styles.categoryGrid}>
            {categoryCards.map((card, index) => (
              <Link
                key={card.title}
                href={categoryHref(card.terms, index)}
                className={styles.categoryCardV2}
              >
                <Image src={card.image} alt={card.title} width={220} height={180} />
                <h3>{card.title}</h3>
                <p>{card.description}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className={styles.sectionSoft} id="subscriptions">
        <div className={styles.homeContainer}>
          <div className={styles.subscriptionLayout}>
            <div className={styles.subscriptionArea}>
              <SectionTitle
                title="Flower Subscriptions"
                subtitle="Never run out of fresh flowers for your rituals."
                actionText="View All Plans"
                actionHref={whatsappHref}
              />
              <div className={styles.subscriptionGrid}>
                {subscriptions.map(([title, description], index) => (
                  <div className={styles.subscriptionCard} key={title}>
                    <div className={styles.subscriptionIcon}>{index + 1}</div>
                    <h3>{title}</h3>
                    <p>{description}</p>
                    <Link href={whatsappHref} target="_blank" className={styles.smallPrimaryButton}>
                      View Plans
                    </Link>
                  </div>
                ))}
              </div>
              <div className={styles.subscriptionMeta}>
                <span>Daily</span>
                <span>Alternate Days</span>
                <span>Weekly</span>
                <span>Monthly</span>
                <strong>Easy • Flexible • Hassle Free</strong>
              </div>
            </div>

            <div className={styles.pujaBoxCard}>
              <div className={styles.pujaBoxContent}>
                <h2>Build Your Puja Box</h2>
                <p>Create your own puja flower box</p>
                <ul>
                  <li>✓ Choose Flowers</li>
                  <li>✓ Choose Leaves</li>
                  <li>✓ Select Quantity</li>
                  <li>✓ Select Delivery</li>
                </ul>
                <Link href={whatsappHref} target="_blank" className={styles.smallPrimaryButton}>
                  BUILD NOW →
                </Link>
              </div>
              <div className={styles.pujaBoxImage}>
                <Image
                  src="/assets/images/home-v2/puja-box.jpg"
                  alt="Custom puja flower box"
                  width={500}
                  height={430}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="fresh-arrivals">
        <div className={styles.homeContainer}>
          <div className={styles.threeProductColumns}>
            <div className={styles.freshProductSection}>
              <SectionTitle
                title="Today's Fresh Arrivals"
                subtitle="Prices updated today at 9:00 AM"
                actionText="View All"
              />
              <div className={styles.freshArrivalGrid}>
                {productSections.fresh.slice(0, 4).map((product, index) => (
                  <FreshArrivalCard
                    key={product?.product_id || product?.slug || `fresh-${index}`}
                    product={product}
                  />
                ))}
              </div>
            </div>

            <div className={styles.collectionPanel} id="premium">
              <SectionTitle
                title="Premium Collection"
                subtitle="Handpicked blooms for unforgettable moments."
                actionText="View All"
              />
              <div className={styles.premiumProductGrid}>
                {productSections.premium.slice(0, 4).map((product, index) => (
                  <PremiumProductCard
                    key={product?.product_id || product?.slug || `premium-${index}`}
                    product={product}
                  />
                ))}
              </div>
            </div>

            <div className={styles.rareCollectionPanel} id="rare-flowers">
              <SectionTitle
                title="Rare & Seasonal Flowers"
                subtitle="Limited stock. Don't miss out!"
                actionText="View All"
              />
              <div className={styles.rareProductGrid}>
                {productSections.rare.slice(0, 3).map((product, index) => (
                  <RareProductCard
                    key={product?.product_id || product?.slug || `rare-${index}`}
                    product={product}
                    index={index}
                  />
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionSoft} id="shop-by-occasion">
        <div className={styles.homeContainer}>
          <div className={styles.occasionDecorationGrid}>
            <div className={styles.occasionArea}>
              <SectionTitle title="Shop by Occasion" actionText="View All Occasions" />
              <div className={styles.occasionGrid}>
                {occasions.map(([title, image], index) => (
                  <Link
                    href={categoryHref([title.toLowerCase()], index)}
                    className={styles.occasionCard}
                    key={title}
                  >
                    <Image src={image} alt={title} width={120} height={120} />
                    <span>{title}</span>
                  </Link>
                ))}
              </div>
            </div>

            <div className={styles.decorationService} id="decorations">
              <div className={styles.decorationContent}>
                <h2>Flower Decoration Services</h2>
                <p>Make every moment beautiful with flowers.</p>
                <ul>
                  <li>✓ Wedding Decorations</li>
                  <li>✓ Pooja & Temple Decorations</li>
                  <li>✓ House Warming</li>
                  <li>✓ Birthday & Events</li>
                </ul>
                <div className={styles.inlineActions}>
                  <Link href="/contact-us" className={styles.smallPrimaryButton}>
                    VIEW GALLERY
                  </Link>
                  <Link href={whatsappHref} target="_blank" className={styles.smallGreenButton}>
                    GET A QUOTE
                  </Link>
                </div>
              </div>
              <div className={styles.decorationImage}>
                <Image
                  src="/assets/images/home-v2/decoration-service.jpg"
                  alt="Traditional flower decoration service"
                  width={560}
                  height={370}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.benefitStrip}>
        <div className={styles.homeContainer}>
          <div className={styles.benefitGrid}>
            {benefits.map(([image, title, description]) => (
              <div className={styles.benefitItem} key={title}>
                <Image src={image} alt="" width={52} height={52} />
                <div>
                  <strong>{title}</strong>
                  <span>{description}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="recent-decorations">
        <div className={styles.homeContainer}>
          <div className={styles.galleryTestimonialsGrid}>
            <div>
              <SectionTitle
                title="Our Recent Decorations"
                subtitle="See the beauty we create with flowers."
                actionText="View Gallery"
                actionHref="/contact-us"
              />
              <div className={styles.decorationGallery}>
                {decorationGallery.map(([title, image]) => (
                  <div key={title} className={styles.galleryCard}>
                    <Image src={image} alt={title} width={260} height={200} />
                    <span>{title}</span>
                  </div>
                ))}
              </div>
            </div>

            <div>
              <SectionTitle title="What Our Customers Say" />
              <div className={styles.testimonialGrid}>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>Fresh flowers, neatly packed and delivered on time for our daily puja.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>Good flower quality and convenient ordering support.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>The flower selection was fresh and suitable for our function.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionSoft} id="instagram">
        <div className={styles.homeContainer}>
          <SectionTitle
            title="Fresh From Manidvipa"
            subtitle="Follow us for fresh flowers, puja ideas and decorations."
            actionText="Follow Us on Instagram"
            actionHref={instagramHref}
          />
          <div className={styles.instagramGrid}>
            {instagramImages.map((image, index) => (
              <Link href={instagramHref} target="_blank" key={image}>
                <Image
                  src={image}
                  alt={`Manidvipa flowers gallery ${index + 1}`}
                  width={240}
                  height={170}
                />
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className={styles.finalCta}>
        <div className={styles.homeContainer}>
          <div className={styles.finalCtaGrid}>
            <div>
              <h2>Need Flowers Tomorrow Morning?</h2>
              <p>Order today and wake up to fresh flowers.</p>
              <div className={styles.inlineActions}>
                <Link href="/search/all" className={styles.whiteCtaButton}>
                  SHOP NOW
                </Link>
                <Link href={whatsappHref} target="_blank" className={styles.smallGreenButton}>
                  WHATSAPP US
                </Link>
              </div>
            </div>
            <Image
              src="/assets/images/home-v2/cta-flowers.jpg"
              alt="Fresh flowers for morning delivery"
              width={650}
              height={220}
            />
          </div>
        </div>
      </section>
    </main>
  );
}
