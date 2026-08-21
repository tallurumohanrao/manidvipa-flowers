"use client";

import React, { useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import {
  FaBuilding,
  FaCheckCircle,
  FaClock,
  FaHospital,
  FaHotel,
  FaLeaf,
  FaPhoneAlt,
  FaPrayingHands,
  FaWhatsapp,
} from "react-icons/fa";
import Toast from "@/components/Toast";
import { useToast } from "@/context/UserContext";
import { fetchListingData } from "../../../hook/userCookie";
import styles from "@/scss/pages/subscriptions.module.scss";

const fallbackImages = [
  "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  "/assets/images/home-v2/premium-collection/premium-orchids.jpg",
  "/assets/images/home-v2/premium-collection/premium-tulips.jpg",
  "/assets/images/home-v2/premium-collection/premium-roses.jpg",
  "/assets/images/home-v2/category-temple.jpg",
  "/assets/images/home-v2/custom-puja-flower-box.jpg",
];

const planFallbackImages = {
  "corporate-office-flower-subscription": "/assets/images/home-v2/premium-collection/premium-roses.jpg",
  "corporate-premium-flower-arrangement-plan": "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  "elite-imported-flower-arrangement-plan": "/assets/images/home-v2/premium-collection/premium-exotic.jpg",
  "hospital-fresh-flowers-supply": "/assets/images/home-v2/premium-collection/premium-orchids.jpg",
  "hotel-lobby-flower-plan": "/assets/images/home-v2/premium-collection/premium-exotic.jpg",
  "temple-daily-flower-subscription": "/assets/images/home-v2/custom-puja-flower-box.jpg",
  "business-bulk-flower-plan": "/assets/images/home-v2/premium-collection/premium-tulips.jpg",
};

const fallbackPlans = [
  {
    id: "corporate-fallback",
    title: "Corporate Premium Arrangement Plan",
    business_type: "Corporate Offices",
    subscription_type: "Premium Arrangements",
    flower_grade: "Premium / Imported Mix",
    short_description: "Reception plus desk/lounge arrangements using lilies, orchids and premium roses.",
    description: "Reliable premium flower arrangements for offices that need a clean, professional look.",
    price_label: "\u20B914,999",
    price_suffix: "/ month",
    delivery_frequency: "3 refreshes per week",
    included_quantity_text: "Not sold by kg; includes finished arrangements",
    included_arrangement_count: "1 reception arrangement + 2 desk/lounge arrangements",
    arrangement_size: "Medium reception + small desk arrangements",
    refresh_frequency: "3 refreshes per week",
    flower_examples: ["Oriental lilies", "Orchids", "Premium roses", "Anthuriums", "Seasonal fillers"],
    extra_quantity_note: "Imported flowers and extra arrangements are quoted separately before confirmation.",
    minimum_commitment: "1 month recommended",
    included_items: ["Reception arrangement", "2 desk or lounge arrangements", "Premium flower mix"],
    features: ["Monthly billing support", "Custom delivery timing", "Dedicated WhatsApp support"],
    ideal_for: ["Corporate offices", "Co-working spaces", "Showrooms"],
    cta_label: "Request Premium Plan",
    image_url: fallbackImages[0],
  },
  {
    id: "hospital-fallback",
    title: "Hospital & Clinic Floral Service",
    business_type: "Hospitals",
    subscription_type: "Premium Arrangements",
    flower_grade: "Standard / Premium",
    short_description: "Reception and prayer-area floral refresh for hospitals and clinics.",
    description: "Clean, predictable flower arrangements for hospital reception areas and visitor spaces.",
    price_label: "\u20B99,999",
    price_suffix: "/ month",
    delivery_frequency: "2-3 refreshes per week",
    included_quantity_text: "Finished arrangements; optional puja flowers can be added",
    included_arrangement_count: "1 reception arrangement + optional puja flower add-on",
    arrangement_size: "Medium reception arrangement",
    refresh_frequency: "2-3 refreshes per week",
    flower_examples: ["Lilies", "Gerbera", "Roses", "Orchids on request", "Seasonal fillers"],
    extra_quantity_note: "Daily puja flowers, garlands or extra arrangements are billed separately.",
    minimum_commitment: "1 month",
    included_items: ["Reception arrangement", "Prayer-area flower option", "Scheduled refresh support"],
    features: ["Hygienic packing", "Fixed delivery window", "Monthly billing support"],
    ideal_for: ["Hospitals", "Clinics", "Diagnostic centers"],
    cta_label: "Request Hospital Plan",
    image_url: fallbackImages[1],
  },
  {
    id: "elite-fallback",
    title: "Elite Imported Flower Arrangement Plan",
    business_type: "Corporate Offices",
    subscription_type: "Premium Arrangements",
    flower_grade: "Imported / Exotic",
    short_description: "Large lobby, boardroom and cabin arrangements with imported premium flowers.",
    description: "High-end floral presentation for corporate headquarters, hotels and premium spaces.",
    price_label: "\u20B925,000",
    price_suffix: "/ month",
    delivery_frequency: "Custom refresh schedule",
    included_quantity_text: "Custom arrangement scope; finalized after site requirement",
    included_arrangement_count: "Lobby arrangement + boardroom/cabin arrangements as quoted",
    arrangement_size: "Large lobby / premium boardroom / custom",
    refresh_frequency: "2-5 refreshes per week",
    flower_examples: ["Oriental lilies", "Imported orchids", "Tulips", "Anthuriums", "Imported roses"],
    extra_quantity_note: "Imported flower pricing changes by season and availability.",
    minimum_commitment: "3 months recommended",
    included_items: ["Premium lobby arrangement", "Boardroom or cabin arrangements", "Imported flower planning"],
    features: ["Custom color theme", "Dedicated account support", "Priority premium sourcing"],
    ideal_for: ["Corporate headquarters", "Hotels", "Luxury showrooms"],
    cta_label: "Request Elite Quote",
    image_url: fallbackImages[2],
  },
  {
    id: "temple-fallback",
    title: "Temple Daily Loose Flower Plan",
    business_type: "Temples",
    subscription_type: "Loose Flowers",
    flower_grade: "Fresh Daily",
    short_description: "Loose flowers, garlands and patri for daily temple pooja.",
    description: "Quantity-based flower, garland and patri supply for temples and daily pooja needs.",
    price_label: "\u20B91,199",
    price_suffix: "/ month",
    delivery_frequency: "Daily morning delivery",
    included_quantity_text: "Starts with 500g loose flowers per delivery",
    included_arrangement_count: "Garlands and leaves can be added as required",
    arrangement_size: "Not applicable",
    refresh_frequency: "Daily delivery",
    flower_examples: ["Chamanthi", "Banthi", "Kanakambaram", "Jasmine", "Tulasi", "Bilva leaves"],
    extra_quantity_note: "Extra kg, garlands and patri are billed as per daily market price.",
    minimum_commitment: "1 month",
    included_items: ["Loose flowers", "Garlands", "Patri and leaves"],
    features: ["Fresh morning sourcing", "Bulk quantity support", "Festival planning"],
    ideal_for: ["Temples", "Puja mandirs", "Community prayer halls"],
    cta_label: "Request Temple Plan",
    image_url: fallbackImages[4],
  },
];

const businessSegments = [
  {
    icon: <FaBuilding />,
    title: "Corporate Offices",
    text: "Premium reception, boardroom and desk arrangements with lilies, orchids, tulips and roses.",
  },
  {
    icon: <FaHospital />,
    title: "Hospitals & Clinics",
    text: "Clean reception arrangements plus optional pooja flowers for prayer areas.",
  },
  {
    icon: <FaHotel />,
    title: "Hotels & Hospitality",
    text: "Lobby, front desk and table flower refresh plans with premium presentation.",
  },
  {
    icon: <FaPrayingHands />,
    title: "Temples & Trusts",
    text: "Quantity-based loose flowers, garlands, patri and bulk festival planning.",
  },
];

const processSteps = [
  "Tell us the location, spaces and required flower style",
  "We suggest arrangement count, flower grade and refresh schedule",
  "You approve exact pricing, delivery time and billing cycle",
  "Finished arrangements or loose flowers are delivered as per schedule",
];

const initialFormState = {
  plan_id: "",
  name: "",
  phone: "",
  email: "",
  organization_name: "",
  business_type: "",
  location: "",
  preferred_delivery_time: "",
  estimated_quantity: "",
  message: "",
};

function normalizeList(value) {
  if (Array.isArray(value)) {
    return value.map((item) => String(item || "").trim()).filter(Boolean);
  }

  if (typeof value === "string") {
    return value
      .split(/\r\n|\r|\n|,/)
      .map((item) => item.trim())
      .filter(Boolean);
  }

  return [];
}

function getPlanFallbackImage(plan, index) {
  const slug = String(plan?.slug || "").toLowerCase();
  const businessType = String(plan?.business_type || "").toLowerCase();
  const subscriptionType = String(plan?.subscription_type || "").toLowerCase();
  const flowerGrade = String(plan?.flower_grade || "").toLowerCase();

  if (planFallbackImages[slug]) {
    return planFallbackImages[slug];
  }

  if (subscriptionType.includes("loose") || businessType.includes("temple")) {
    return "/assets/images/home-v2/custom-puja-flower-box.jpg";
  }

  if (businessType.includes("hotel") || flowerGrade.includes("exotic") || flowerGrade.includes("imported")) {
    return "/assets/images/home-v2/premium-collection/premium-exotic.jpg";
  }

  if (businessType.includes("hospital")) {
    return "/assets/images/home-v2/premium-collection/premium-orchids.jpg";
  }

  if (subscriptionType.includes("premium")) {
    return "/assets/images/home-v2/premium-collection/premium-lilies.jpg";
  }

  return fallbackImages[index % fallbackImages.length];
}

function normalizePlan(plan, index) {
  return {
    id: plan?.id || `fallback-${index}`,
    title: plan?.title || "Business Flower Subscription",
    business_type: plan?.business_type || "Business",
    subscription_type: plan?.subscription_type || "Premium Arrangements",
    flower_grade: plan?.flower_grade || "As per requirement",
    short_description:
      plan?.short_description || plan?.description || "Fresh flowers delivered on a fixed schedule.",
    description: plan?.description || plan?.short_description || "",
    price_label: plan?.price_label || "Custom Quote",
    price_suffix: plan?.price_suffix || "",
    delivery_frequency: plan?.delivery_frequency || "Custom delivery schedule",
    included_quantity_text: plan?.included_quantity_text || "",
    included_arrangement_count: plan?.included_arrangement_count || "",
    arrangement_size: plan?.arrangement_size || "",
    refresh_frequency: plan?.refresh_frequency || plan?.delivery_frequency || "Custom refresh schedule",
    flower_examples: normalizeList(plan?.flower_examples),
    extra_quantity_note: plan?.extra_quantity_note || "",
    minimum_commitment: plan?.minimum_commitment || "",
    included_items: normalizeList(plan?.included_items),
    features: normalizeList(plan?.features),
    ideal_for: normalizeList(plan?.ideal_for),
    cta_label: plan?.cta_label || "Request Plan",
    image_url: plan?.image_url || getPlanFallbackImage(plan, index),
  };
}

function getSettingsData(siteSettings) {
  return siteSettings?.data || siteSettings || {};
}

function normalizeWhatsApp(phone) {
  const digits = String(phone || "").replace(/\D/g, "");

  if (!digits) return "#subscription-enquiry";
  const withCountryCode = digits.length === 10 ? `91${digits}` : digits;
  return `https://wa.me/${withCountryCode}`;
}

function buildWhatsAppMessage(href, selectedPlan) {
  if (!href || href.startsWith("#")) return href;

  const message = [
    "Hi Manidvipa Flowers, I need a flower subscription plan.",
    selectedPlan ? `Plan: ${selectedPlan.title}` : "Plan: Business subscription",
    selectedPlan?.subscription_type ? `Package Type: ${selectedPlan.subscription_type}` : null,
    selectedPlan?.included_arrangement_count || selectedPlan?.included_quantity_text
      ? `Scope: ${selectedPlan.included_arrangement_count || selectedPlan.included_quantity_text}`
      : null,
    "Please share pricing and delivery options.",
  ].filter(Boolean).join("\n");

  return `${href}?text=${encodeURIComponent(message)}`;
}

export default function SubscriptionsPage({ initialPlans = [], siteSettings }) {
  const { showToast } = useToast();
  const formRef = useRef(null);
  const [formData, setFormData] = useState(initialFormState);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");

  const plans = useMemo(() => {
    const source = Array.isArray(initialPlans) && initialPlans.length ? initialPlans : fallbackPlans;
    return source.map(normalizePlan);
  }, [initialPlans]);

  const settings = getSettingsData(siteSettings);
  const whatsappHref = buildWhatsAppMessage(
    normalizeWhatsApp(settings?.SITE_WHATSAPP || settings?.SITE_PHONE),
    selectedPlan
  );

  const handlePlanSelect = (plan) => {
    setSelectedPlan(plan);
    setFormData((currentData) => ({
      ...currentData,
      plan_id: Number.isInteger(Number(plan.id)) ? String(plan.id) : "",
      business_type: plan.business_type,
      estimated_quantity:
        currentData.estimated_quantity || plan.included_arrangement_count || plan.included_quantity_text || "",
      message:
        currentData.message ||
        `I am interested in ${plan.title}. Package type: ${plan.subscription_type}.`,
    }));
    formRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  const handleChange = (event) => {
    const { name, value } = event.target;
    setFormData((currentData) => ({ ...currentData, [name]: value }));
  };

  const handlePlanDropdownChange = (event) => {
    const value = event.target.value;
    const plan = plans.find((currentPlan) => String(currentPlan.id) === value);
    setSelectedPlan(plan || null);
    setFormData((currentData) => ({
      ...currentData,
      plan_id: value,
      business_type: plan?.business_type || currentData.business_type,
      estimated_quantity:
        plan && !currentData.estimated_quantity
          ? plan.included_arrangement_count || plan.included_quantity_text || ""
          : currentData.estimated_quantity,
      message:
        plan && !currentData.message
          ? `I am interested in ${plan.title}. Package type: ${plan.subscription_type}.`
          : currentData.message,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSuccessMessage("");

    if (!formData.name.trim() || !formData.phone.trim()) {
      showToast("Name and phone number are required.", "error");
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetchListingData("POST", "subscription-enquiry", "", formData);

      if (!response?.success) {
        showToast(response?.message || "Unable to submit enquiry.", "error");
        return;
      }

      const message =
        response.message || "Subscription enquiry submitted successfully. Our team will contact you shortly.";
      setSuccessMessage(message);
      showToast(message, "success");
      setFormData(initialFormState);
      setSelectedPlan(null);
    } catch (error) {
      console.error("Subscription enquiry failed:", error);
      showToast("Unable to submit enquiry right now.", "error");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      <main className={styles.subscriptionPage}>
        <section className={styles.hero}>
          <div className="container">
            <div className={styles.heroGrid}>
              <div className={styles.heroContent}>
                <span className={styles.eyebrow}>Business flower subscriptions</span>
                <h1>Premium flower plans for offices, hospitals, hotels and temples.</h1>
                <p>
                  Corporate plans are arrangement-based with lilies, orchids, tulips and roses.
                  Temple and puja plans stay quantity-based for loose flowers, garlands and leaves.
                </p>
                <div className={styles.heroActions}>
                  <button type="button" onClick={() => formRef.current?.scrollIntoView({ behavior: "smooth" })}>
                    Request Custom Quote
                  </button>
                  <a href={whatsappHref} target="_blank" rel="noopener noreferrer">
                    <FaWhatsapp />
                    WhatsApp Us
                  </a>
                </div>
                <div className={styles.heroStats}>
                  <span><FaClock /> Planned refresh schedule</span>
                  <span><FaLeaf /> Premium + daily flower options</span>
                  <span><FaPhoneAlt /> Dedicated support</span>
                </div>
              </div>
              <div className={styles.heroImage}>
                <Image
                  src="/assets/images/home-v2/premium-collection/premium-lilies.jpg"
                  alt="Premium lily flower arrangement for business subscription"
                  width={680}
                  height={520}
                  priority
                />
              </div>
            </div>
          </div>
        </section>

        <section className={styles.segmentSection}>
          <div className="container">
            <div className={styles.sectionHeading}>
              <span className={styles.eyebrow}>Built for regular supply</span>
              <h2>Choose by business requirement</h2>
              <p>First decide whether the customer needs finished premium arrangements or kg-based loose flowers.</p>
            </div>
            <div className={styles.segmentGrid}>
              {businessSegments.map((segment) => (
                <article className={styles.segmentCard} key={segment.title}>
                  <div>{segment.icon}</div>
                  <h3>{segment.title}</h3>
                  <p>{segment.text}</p>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className={styles.planSection}>
          <div className="container">
            <div className={styles.sectionHeading}>
              <span className={styles.eyebrow}>Subscription plans</span>
              <h2>Clear packages with quantity and arrangement scope</h2>
              <p>Each plan explains what the price includes: arrangement count, flower grade, refresh frequency and extra-cost notes.</p>
            </div>
            <div className={styles.planGrid}>
              {plans.map((plan) => (
                <article className={styles.planCard} key={plan.id}>
                  <div className={styles.planImage}>
                    <Image src={plan.image_url} alt={plan.title} width={520} height={360} />
                    <span>{plan.business_type}</span>
                  </div>
                  <div className={styles.planBody}>
                    <div className={styles.planTypeRow}>
                      <span>{plan.subscription_type}</span>
                      <span>{plan.flower_grade}</span>
                    </div>
                    <h3>{plan.title}</h3>
                    <p>{plan.short_description}</p>
                    <div className={styles.planPrice}>
                      <strong>{plan.price_label}</strong>
                      <span>{plan.price_suffix}</span>
                    </div>
                    <div className={styles.planMetaGrid}>
                      {plan.included_arrangement_count ? (
                        <div>
                          <span>Includes</span>
                          <strong>{plan.included_arrangement_count}</strong>
                        </div>
                      ) : null}
                      {plan.included_quantity_text ? (
                        <div>
                          <span>Quantity / Scope</span>
                          <strong>{plan.included_quantity_text}</strong>
                        </div>
                      ) : null}
                      {plan.arrangement_size ? (
                        <div>
                          <span>Size</span>
                          <strong>{plan.arrangement_size}</strong>
                        </div>
                      ) : null}
                      <div>
                        <span>Refresh</span>
                        <strong>{plan.refresh_frequency}</strong>
                      </div>
                    </div>
                    {plan.flower_examples.length ? (
                      <div className={styles.flowerExamples}>
                        <strong>Flower examples</strong>
                        <div>
                          {plan.flower_examples.slice(0, 6).map((flower) => (
                            <span key={flower}>{flower}</span>
                          ))}
                        </div>
                      </div>
                    ) : null}
                    {plan.extra_quantity_note ? (
                      <div className={styles.planNote}>{plan.extra_quantity_note}</div>
                    ) : null}
                    {plan.minimum_commitment ? (
                      <div className={styles.minimumCommitment}>
                        Minimum commitment: <strong>{plan.minimum_commitment}</strong>
                      </div>
                    ) : null}
                    <ul>
                      {[...plan.included_items, ...plan.features].slice(0, 5).map((item) => (
                        <li key={item}>
                          <FaCheckCircle />
                          {item}
                        </li>
                      ))}
                    </ul>
                    <button type="button" onClick={() => handlePlanSelect(plan)}>
                      {plan.cta_label}
                    </button>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className={styles.processSection}>
          <div className="container">
            <div className={styles.processCard}>
              <div>
                <span className={styles.eyebrow}>How it works</span>
                <h2>Simple setup for business customers</h2>
              </div>
              <ol>
                {processSteps.map((step) => (
                  <li key={step}>{step}</li>
                ))}
              </ol>
            </div>
          </div>
        </section>

        <section className={styles.enquirySection} ref={formRef}>
          <div className="container">
            <div className={styles.enquiryGrid}>
              <div className={styles.enquiryInfo}>
                <span className={styles.eyebrow}>Request pricing</span>
                <h2>Get a custom subscription quote</h2>
                <p>
                  Share your business type, spaces, flower preference and delivery timing.
                  The enquiry will be saved in admin for follow-up.
                </p>
                <div className={styles.selectedPlanBox}>
                  <strong>Selected plan</strong>
                  <span>{selectedPlan?.title || "Custom business subscription"}</span>
                  {selectedPlan ? (
                    <small>
                      {selectedPlan.subscription_type} · {selectedPlan.included_arrangement_count || selectedPlan.included_quantity_text || "Scope to confirm"}
                    </small>
                  ) : null}
                </div>
                <Link href="/contact-us">Need one-time order? Contact us →</Link>
              </div>

              <form className={styles.enquiryForm} onSubmit={handleSubmit}>
                <div className={styles.formGrid}>
                  <label>
                    <span>Your Name *</span>
                    <input name="name" value={formData.name} onChange={handleChange} placeholder="Your name" />
                  </label>
                  <label>
                    <span>Phone Number *</span>
                    <input name="phone" value={formData.phone} onChange={handleChange} placeholder="+91 98765 43210" />
                  </label>
                  <label>
                    <span>Email</span>
                    <input name="email" value={formData.email} onChange={handleChange} placeholder="email@example.com" />
                  </label>
                  <label>
                    <span>Company / Organization</span>
                    <input
                      name="organization_name"
                      value={formData.organization_name}
                      onChange={handleChange}
                      placeholder="Hospital, office, hotel..."
                    />
                  </label>
                  <label>
                    <span>Business Type</span>
                    <select name="business_type" value={formData.business_type} onChange={handleChange}>
                      <option value="">Select business type</option>
                      {businessSegments.map((segment) => (
                        <option value={segment.title} key={segment.title}>{segment.title}</option>
                      ))}
                      <option value="Temples">Temples</option>
                      <option value="Business Bulk">Business Bulk</option>
                      <option value="Custom">Custom</option>
                    </select>
                  </label>
                  <label>
                    <span>Plan</span>
                    <select name="plan_id" value={formData.plan_id} onChange={handlePlanDropdownChange}>
                      <option value="">Custom plan / Not sure</option>
                      {plans
                        .filter((plan) => Number.isInteger(Number(plan.id)))
                        .map((plan) => (
                          <option value={plan.id} key={plan.id}>{plan.title}</option>
                        ))}
                    </select>
                  </label>
                  <label>
                    <span>Delivery Location</span>
                    <input name="location" value={formData.location} onChange={handleChange} placeholder="Area, Hyderabad" />
                  </label>
                  <label>
                    <span>Preferred Delivery Time</span>
                    <input
                      name="preferred_delivery_time"
                      value={formData.preferred_delivery_time}
                      onChange={handleChange}
                      placeholder="Example: 7 AM - 9 AM"
                    />
                  </label>
                  <label>
                    <span>Required Scope / Quantity</span>
                    <input
                      name="estimated_quantity"
                      value={formData.estimated_quantity}
                      onChange={handleChange}
                      placeholder="Example: 1 reception + 2 desk arrangements / 500g daily"
                    />
                  </label>
                  <label className={styles.fullWidth}>
                    <span>Requirement Details</span>
                    <textarea
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      rows={5}
                      placeholder="Tell us spaces, flower types, arrangement size, quantity, delivery days and billing preference..."
                    />
                  </label>
                </div>
                {successMessage ? <p className={styles.successMessage}>{successMessage}</p> : null}
                <button type="submit" disabled={isSubmitting}>
                  {isSubmitting ? "Submitting..." : "Submit Subscription Enquiry"}
                </button>
              </form>
            </div>
          </div>
        </section>
      </main>
      <Toast />
    </>
  );
}
