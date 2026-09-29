"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import {
  FaCalendarAlt,
  FaCheck,
  FaGift,
  FaLeaf,
  FaMagic,
  FaMapMarkerAlt,
  FaPhoneAlt,
  FaUpload,
} from "react-icons/fa";
import BouquetDesignStudio, { classifyBouquetProduct, createBouquetDesignItem } from "@/components/pages/BouquetDesignStudio";
import styles from "@/scss/pages/customBouquet.module.scss";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;
const API_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

const occasions = [
  "Birthday",
  "Anniversary",
  "Wedding",
  "Congratulations",
  "Romantic",
  "Corporate Gift",
  "Get Well Soon",
  "Sympathy",
  "Other",
];

const sizes = [
  { value: "Small", copy: "A thoughtful hand bouquet" },
  { value: "Medium", copy: "Balanced and gift-ready" },
  { value: "Large", copy: "More flowers and fuller styling" },
  { value: "Grand", copy: "Premium statement arrangement" },
];

const budgets = [
  "Below ₹1,000",
  "₹1,000 - ₹2,000",
  "₹2,000 - ₹3,500",
  "₹3,500 - ₹5,000",
  "Above ₹5,000",
];

const palettes = [
  { label: "Red & Pink", colors: ["#9b1237", "#ef8da1"] },
  { label: "White & Green", colors: ["#f7f3e8", "#6e9d59"] },
  { label: "Yellow & Orange", colors: ["#f6bf26", "#ee7623"] },
  { label: "Purple & White", colors: ["#7650a8", "#f7f3e8"] },
  { label: "Pastel Mix", colors: ["#efb6bc", "#edcf9d", "#c7b6dc"] },
  { label: "Bright Mix", colors: ["#e2354f", "#f4b223", "#8b4da4"] },
];

const presentationOptions = [
  "Premium paper wrap",
  "Kraft paper wrap",
  "Flower basket",
  "Glass vase",
  "Hand-tied ribbon",
];

const addonOptions = [
  "Message card",
  "Chocolates",
  "Teddy bear",
  "Extra ribbon",
  "No add-on",
];

const recommendations = {
  Birthday: {
    palettes: ["Bright Mix", "Yellow & Orange"],
    flowers: ["Roses", "Gerbera", "Seasonal Flowers"],
    presentation: "Premium paper wrap",
  },
  Anniversary: {
    palettes: ["Red & Pink", "Pastel Mix"],
    flowers: ["Roses", "Lilies"],
    presentation: "Premium paper wrap",
  },
  Wedding: {
    palettes: ["White & Green", "Pastel Mix"],
    flowers: ["Roses", "Lilies", "Orchids"],
    presentation: "Hand-tied ribbon",
  },
  Congratulations: {
    palettes: ["Yellow & Orange", "Bright Mix"],
    flowers: ["Gerbera", "Lilies", "Roses"],
    presentation: "Flower basket",
  },
  Romantic: {
    palettes: ["Red & Pink"],
    flowers: ["Roses", "Lilies"],
    presentation: "Premium paper wrap",
  },
  "Corporate Gift": {
    palettes: ["White & Green", "Purple & White"],
    flowers: ["Orchids", "Lilies", "Roses"],
    presentation: "Glass vase",
  },
  "Get Well Soon": {
    palettes: ["Yellow & Orange", "Pastel Mix"],
    flowers: ["Gerbera", "Carnations", "Seasonal Flowers"],
    presentation: "Flower basket",
  },
  Sympathy: {
    palettes: ["White & Green", "Purple & White"],
    flowers: ["Lilies", "Chrysanthemums", "Roses"],
    presentation: "Hand-tied ribbon",
  },
  Other: {
    palettes: ["Pastel Mix"],
    flowers: ["Roses", "Seasonal Flowers"],
    presentation: "Premium paper wrap",
  },
};

const initialForm = {
  base_product_id: "",
  occasion: "Birthday",
  bouquet_size: "Medium",
  budget_range: "₹1,000 - ₹2,000",
  color_palette: ["Bright Mix"],
  flower_preferences: [],
  greenery_preferences: [],
  presentation: "Premium paper wrap",
  addons: ["Message card"],
  delivery_date: "",
  delivery_area: "",
  name: "",
  phone: "",
  email: "",
  customer_notes: "",
  website: "",
};

function formatPrice(value) {
  const amount = Number(value);
  return Number.isFinite(amount)
    ? new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 }).format(amount)
    : "";
}

function productImage(product) {
  if (product?.image_url) return product.image_url;
  if (product?.image_name && IMG_URL) return IMG_URL + "/" + product.image_name;
  if (product?.images?.[0]?.name && IMG_URL) return IMG_URL + "/" + product.images[0].name;
  return "/assets/images/no-image.png";
}

function ToggleOptions({ legend, hint, options, selected, onChange, recommendations: suggested = [] }) {
  const toggle = (value) => {
    const exists = selected.includes(value);
    onChange(exists ? selected.filter((item) => item !== value) : [...selected, value]);
  };

  return (
    <fieldset className={styles.optionGroup}>
      <legend>{legend}</legend>
      {hint ? <p>{hint}</p> : null}
      <div className={styles.choiceGrid}>
        {options.map((option) => {
          const value = typeof option === "string" ? option : option.label;
          const isSelected = selected.includes(value);
          return (
            <button
              type="button"
              key={value}
              className={[styles.choiceButton, isSelected ? styles.choiceSelected : ""].join(" ")}
              onClick={() => toggle(value)}
              aria-pressed={isSelected}
            >
              {typeof option === "object" ? (
                <span className={styles.swatches}>
                  {option.colors.map((color) => <i key={color} style={{ backgroundColor: color }} />)}
                </span>
              ) : null}
              <span>{value}</span>
              {suggested.includes(value) ? <small>Suggested</small> : null}
              {isSelected ? <FaCheck aria-hidden="true" /> : null}
            </button>
          );
        })}
      </div>
    </fieldset>
  );
}

function SummaryLine({ label, value }) {
  return (
    <div className={styles.summaryLine}>
      <span>{label}</span>
      <strong>{value || "Not selected"}</strong>
    </div>
  );
}

export default function CustomBouquetBuilder({ bouquetProducts = [], designProducts = [], minimumDeliveryDate }) {
  const [form, setForm] = useState(initialForm);
  const [designItems, setDesignItems] = useState([]);
  const [referenceImage, setReferenceImage] = useState(null);
  const [imagePreview, setImagePreview] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formMessage, setFormMessage] = useState("");
  const [submission, setSubmission] = useState(null);

  const starterBouquets = useMemo(
    () => [...bouquetProducts]
      .sort((first, second) => Number(first?.sell_price || 0) - Number(second?.sell_price || 0))
      .slice(0, 8),
    [bouquetProducts]
  );
  const selectedStarter = starterBouquets.find(
    (product) => String(product?.product_id || product?.id) === String(form.base_product_id)
  );
  const suggested = recommendations[form.occasion] || recommendations.Other;

  useEffect(() => {
    if (!referenceImage) {
      setImagePreview("");
      return undefined;
    }
    const preview = URL.createObjectURL(referenceImage);
    setImagePreview(preview);
    return () => URL.revokeObjectURL(preview);
  }, [referenceImage]);

  const setValue = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setFormMessage("");
  };

  const handleDesignItemsChange = (nextItems) => {
    setDesignItems(nextItems);
    setForm((current) => ({
      ...current,
      flower_preferences: nextItems
        .filter((item) => item.kind === "flower")
        .map((item) => item.title + " x " + item.quantity + " (" + item.unitLabel + ")"),
      greenery_preferences: nextItems
        .filter((item) => item.kind === "greenery")
        .map((item) => item.title + " x " + item.quantity + " (" + item.unitLabel + ")"),
    }));
    setFormMessage("");
  };

  const applySuggestions = () => {
    const flowerProducts = designProducts.filter(
      (product) => classifyBouquetProduct(product) === "flower"
    );
    const matches = [];
    suggested.flowers.forEach((suggestion) => {
      const stem = suggestion
        .toLowerCase()
        .replace(/flowers?/g, "")
        .replace(/ies$/g, "y")
        .replace(/s$/g, "")
        .trim();
      const product = flowerProducts.find((candidate) => {
        const text = [
          candidate?.title,
          candidate?.category_title,
          ...(candidate?.categories || []).flatMap((category) => [category?.title, category?.slug]),
        ].join(" ").toLowerCase();
        return stem && text.includes(stem);
      });
      if (product && !matches.some((item) => Number(item?.product_id || item?.id) === Number(product?.product_id || product?.id))) {
        matches.push(product);
      }
    });

    const recommendedProducts = (matches.length ? matches : flowerProducts.slice(0, 3)).slice(0, 4);
    const greeneryItems = designItems.filter((item) => item.kind === "greenery");
    const flowerItems = recommendedProducts.map((product, index) => (
      createBouquetDesignItem(product, greeneryItems.length + index)
    ));

    setForm((current) => ({
      ...current,
      color_palette: suggested.palettes,
      presentation: suggested.presentation,
    }));
    handleDesignItemsChange([...greeneryItems, ...flowerItems]);
  };

  const chooseStarter = (product) => {
    const productId = String(product?.product_id || product?.id);
    setValue("base_product_id", form.base_product_id === productId ? "" : productId);
  };

  const chooseSingleAddon = (values) => {
    const selectedNoAddonNow =
      values.includes("No add-on") && !form.addons.includes("No add-on");
    if (selectedNoAddonNow) {
      setValue("addons", ["No add-on"]);
      return;
    }
    setValue("addons", values.filter((value) => value !== "No add-on"));
  };

  const handleReferenceImage = (event) => {
    const file = event.target.files?.[0] || null;
    if (file && file.size > 5 * 1024 * 1024) {
      setFormMessage("Reference image must be 5 MB or smaller.");
      event.target.value = "";
      setReferenceImage(null);
      return;
    }
    setReferenceImage(file);
    setFormMessage("");
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!form.color_palette.length || !form.flower_preferences.length) {
      setFormMessage("Choose at least one colour palette and one flower.");
      return;
    }
    if (!API_URL) {
      setFormMessage("The quotation service is unavailable. Please contact us by phone.");
      return;
    }

    setIsSubmitting(true);
    setFormMessage("");

    const payload = new FormData();
    Object.entries(form).forEach(([field, value]) => {
      if (Array.isArray(value)) {
        payload.append(field, JSON.stringify(value));
      } else if (value !== "") {
        payload.append(field, value);
      }
    });
    payload.append("selected_items", JSON.stringify(designItems.map((item) => ({
      product_id: item.productId,
      kind: item.kind,
      quantity: item.quantity,
      x: item.x,
      y: item.y,
      scale: item.scale,
      rotation: item.rotation,
      z: item.z,
    }))));
    if (referenceImage) payload.append("reference_image", referenceImage);

    try {
      const response = await fetch(API_URL + "/custom-bouquet-requests", {
        method: "POST",
        body: payload,
      });
      const result = await response.json();
      if (!response.ok || !result?.success) {
        const firstError = result?.errors
          ? Object.values(result.errors).flat().find(Boolean)
          : null;
        setFormMessage(firstError || result?.message || "Unable to submit your request.");
        return;
      }
      setSubmission({
        referenceCode: result?.data?.reference_code,
        message: result?.message,
      });
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
      console.error("Custom bouquet request failed:", error);
      setFormMessage("Unable to submit your request. Please check your connection and try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  if (submission) {
    return (
      <section className={styles.builderPage}>
        <div className="container">
          <div className={styles.successCard}>
            <span className={styles.successIcon}><FaCheck /></span>
            <p>Request received</p>
            <h1>Your custom bouquet request is ready for our florist.</h1>
            <div className={styles.referenceCode}>{submission.referenceCode}</div>
            <span>{submission.message}</span>
            <div className={styles.successActions}>
              <Link href="/gifts">Browse Bouquets</Link>
              <button
                type="button"
                onClick={() => {
                  setForm(initialForm);
                  setReferenceImage(null);
                  setDesignItems([]);
                  setSubmission(null);
                }}
              >
                Create Another
              </button>
            </div>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className={styles.builderPage}>
      <div className="container">
        <nav className={styles.breadcrumbs} aria-label="Breadcrumb">
          <Link href="/">Home</Link><span>›</span>
          <Link href="/gifts">Bouquets & Gifts</Link><span>›</span>
          <strong>Create Your Bouquet</strong>
        </nav>

        <header className={styles.hero}>
          <div>
            <span className={styles.eyebrow}><FaMagic /> Made for your moment</span>
            <h1>Create Your Own Bouquet</h1>
            <p>
              Choose the style, flowers and details you love. Our florist will check fresh availability
              and contact you with the final quotation before making it.
            </p>
          </div>
          <div className={styles.heroSteps}>
            <span><b>1</b> Choose</span>
            <span><b>2</b> Customise</span>
            <span><b>3</b> Submit</span>
            <span><b>4</b> Approve quote</span>
          </div>
        </header>

        <form onSubmit={handleSubmit} className={styles.builderLayout}>
          <main className={styles.builderMain}>
            <section className={styles.builderSection}>
              <div className={styles.sectionHeading}>
                <span>1</span>
                <div>
                  <h2>Choose a starting design</h2>
                  <p>Select one for inspiration, or leave this blank and create from scratch.</p>
                </div>
              </div>
              {starterBouquets.length ? (
                <div className={styles.starterGrid}>
                  {starterBouquets.map((product) => {
                    const productId = String(product?.product_id || product?.id);
                    const isSelected = form.base_product_id === productId;
                    return (
                      <button
                        type="button"
                        className={[styles.starterCard, isSelected ? styles.starterSelected : ""].join(" ")}
                        key={productId}
                        onClick={() => chooseStarter(product)}
                        aria-pressed={isSelected}
                      >
                        <span className={styles.starterImage}>
                          <Image
                            src={productImage(product)}
                            alt={product.title}
                            fill
                            sizes="(max-width: 575px) 45vw, 180px"
                          />
                          {isSelected ? <i><FaCheck /></i> : null}
                        </span>
                        <strong>{product.title}</strong>
                        <small>From {formatPrice(product.sell_price)}</small>
                      </button>
                    );
                  })}
                </div>
              ) : (
                <div className={styles.inlineNotice}>You can continue and create your bouquet from scratch.</div>
              )}
            </section>

            <section className={styles.builderSection}>
              <div className={styles.sectionHeading}>
                <span>2</span>
                <div>
                  <h2>Tell us about the occasion</h2>
                  <p>We use this to suggest flowers and presentation that work well together.</p>
                </div>
              </div>

              <div className={styles.fieldGrid}>
                <label>
                  <span>Occasion *</span>
                  <select value={form.occasion} onChange={(event) => setValue("occasion", event.target.value)} required>
                    {occasions.map((occasion) => <option value={occasion} key={occasion}>{occasion}</option>)}
                  </select>
                </label>
                <label>
                  <span>Budget *</span>
                  <select value={form.budget_range} onChange={(event) => setValue("budget_range", event.target.value)} required>
                    {budgets.map((budget) => <option value={budget} key={budget}>{budget}</option>)}
                  </select>
                </label>
              </div>

              <div className={styles.recommendation}>
                <div>
                  <FaMagic />
                  <p>
                    <strong>Suggested for {form.occasion}</strong>
                    {suggested.flowers.join(", ")} · {suggested.palettes.join(" or ")} · {suggested.presentation}
                  </p>
                </div>
                <button type="button" onClick={applySuggestions}>Use this mix</button>
              </div>

              <fieldset className={styles.sizeGroup}>
                <legend>Bouquet size *</legend>
                <div className={styles.sizeGrid}>
                  {sizes.map((size) => (
                    <button
                      type="button"
                      key={size.value}
                      className={form.bouquet_size === size.value ? styles.sizeSelected : ""}
                      onClick={() => setValue("bouquet_size", size.value)}
                      aria-pressed={form.bouquet_size === size.value}
                    >
                      <strong>{size.value}</strong>
                      <small>{size.copy}</small>
                    </button>
                  ))}
                </div>
              </fieldset>
            </section>

            <section className={[styles.builderSection, styles.visualBuilderSection].join(" ")}>
              <div className={styles.sectionHeading}>
                <span>3</span>
                <div>
                  <h2>Design your bouquet visually</h2>
                  <p>Browse every available flower, select the ones you like and arrange the live preview.</p>
                </div>
              </div>

              <ToggleOptions
                legend="Colour palette *"
                hint="Choose up to four palettes. The preview changes with your selection."
                options={palettes}
                selected={form.color_palette}
                onChange={(values) => setValue("color_palette", values.slice(-4))}
                recommendations={suggested.palettes}
              />

              <BouquetDesignStudio
                products={designProducts}
                items={designItems}
                onItemsChange={handleDesignItemsChange}
                palette={form.color_palette}
                presentation={form.presentation}
                bouquetSize={form.bouquet_size}
              />
            </section>

            <section className={styles.builderSection}>
              <div className={styles.sectionHeading}>
                <span>4</span>
                <div>
                  <h2>Choose presentation and extras</h2>
                  <p>Complete the bouquet with wrapping, a vase, basket or gift add-on.</p>
                </div>
              </div>

              <fieldset className={styles.radioGroup}>
                <legend>Presentation *</legend>
                <div className={styles.choiceGrid}>
                  {presentationOptions.map((option) => (
                    <button
                      type="button"
                      key={option}
                      className={[styles.choiceButton, form.presentation === option ? styles.choiceSelected : ""].join(" ")}
                      onClick={() => setValue("presentation", option)}
                      aria-pressed={form.presentation === option}
                    >
                      <span>{option}</span>
                      {suggested.presentation === option ? <small>Suggested</small> : null}
                      {form.presentation === option ? <FaCheck /> : null}
                    </button>
                  ))}
                </div>
              </fieldset>

              <ToggleOptions
                legend="Add-ons"
                options={addonOptions}
                selected={form.addons}
                onChange={chooseSingleAddon}
              />

              <div className={styles.uploadBox}>
                <label htmlFor="bouquet-reference">
                  <FaUpload />
                  <span>
                    <strong>Upload a reference photo</strong>
                    JPG, PNG or WebP · maximum 5 MB
                  </span>
                </label>
                <input
                  id="bouquet-reference"
                  type="file"
                  accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                  onChange={handleReferenceImage}
                />
                {imagePreview ? (
                  <div className={styles.uploadPreview}>
                    <Image src={imagePreview} alt="Your bouquet reference" width={110} height={110} unoptimized />
                    <div>
                      <strong>{referenceImage?.name}</strong>
                      <button type="button" onClick={() => setReferenceImage(null)}>Remove photo</button>
                    </div>
                  </div>
                ) : null}
              </div>
            </section>

            <section className={styles.builderSection}>
              <div className={styles.sectionHeading}>
                <span>5</span>
                <div>
                  <h2>Delivery and contact details</h2>
                  <p>We will contact you to confirm availability, substitutions and the final price.</p>
                </div>
              </div>

              <div className={styles.fieldGrid}>
                <label>
                  <span><FaCalendarAlt /> Delivery date *</span>
                  <input
                    type="date"
                    min={minimumDeliveryDate}
                    value={form.delivery_date}
                    onChange={(event) => setValue("delivery_date", event.target.value)}
                    required
                  />
                </label>
                <label>
                  <span><FaMapMarkerAlt /> Delivery area *</span>
                  <input
                    type="text"
                    value={form.delivery_area}
                    onChange={(event) => setValue("delivery_area", event.target.value)}
                    placeholder="Area or locality in Hyderabad"
                    maxLength={180}
                    required
                  />
                </label>
                <label>
                  <span>Your name *</span>
                  <input
                    type="text"
                    value={form.name}
                    onChange={(event) => setValue("name", event.target.value)}
                    placeholder="Full name"
                    maxLength={120}
                    required
                  />
                </label>
                <label>
                  <span><FaPhoneAlt /> Phone / WhatsApp *</span>
                  <input
                    type="tel"
                    value={form.phone}
                    onChange={(event) => setValue("phone", event.target.value)}
                    placeholder="+91"
                    minLength={8}
                    maxLength={30}
                    required
                  />
                </label>
                <label className={styles.fullField}>
                  <span>Email (optional)</span>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(event) => setValue("email", event.target.value)}
                    placeholder="you@example.com"
                    maxLength={191}
                  />
                </label>
                <label className={styles.fullField}>
                  <span>Special instructions (optional)</span>
                  <textarea
                    value={form.customer_notes}
                    onChange={(event) => setValue("customer_notes", event.target.value)}
                    placeholder="Recipient details, preferred flowers, flowers to avoid, message wording or any other request."
                    maxLength={2000}
                    rows={5}
                  />
                </label>
                <label className={styles.honeypot} aria-hidden="true">
                  Website
                  <input
                    type="text"
                    tabIndex="-1"
                    autoComplete="off"
                    value={form.website}
                    onChange={(event) => setValue("website", event.target.value)}
                  />
                </label>
              </div>

              {formMessage ? <div className={styles.formError} role="alert">{formMessage}</div> : null}
              <button
                type="submit"
                className={styles.submitButton}
                disabled={isSubmitting || !designItems.some((item) => item.kind === "flower")}
              >
                {isSubmitting ? "Submitting request..." : "Submit for quotation"}
              </button>
              <p className={styles.submitNote}>
                Submitting does not place a paid order. Our team will confirm fresh-flower availability and send the final quote.
              </p>
            </section>
          </main>

          <aside className={styles.summaryCard}>
            <div className={styles.summaryTitle}><FaGift /><div><span>Your creation</span><strong>Request summary</strong></div></div>
            {selectedStarter ? (
              <div className={styles.summaryStarter}>
                <Image src={productImage(selectedStarter)} alt="" width={66} height={66} />
                <div><small>Inspired by</small><strong>{selectedStarter.title}</strong></div>
              </div>
            ) : null}
            <SummaryLine label="Occasion" value={form.occasion} />
            <SummaryLine label="Size" value={form.bouquet_size} />
            <SummaryLine label="Budget" value={form.budget_range} />
            <SummaryLine label="Colours" value={form.color_palette.join(", ")} />
            <SummaryLine label="Flowers" value={form.flower_preferences.join(", ")} />
            <SummaryLine label="Greenery" value={form.greenery_preferences.join(", ") || "Florist choice"} />
            <SummaryLine label="Presentation" value={form.presentation} />
            <SummaryLine label="Add-ons" value={form.addons.join(", ") || "None"} />
            <div className={styles.quoteNotice}>
              <FaLeaf />
              <p><strong>Final quote after review</strong>Fresh flowers and exact colours depend on daily market availability.</p>
            </div>
          </aside>
        </form>
      </div>
    </section>
  );
}