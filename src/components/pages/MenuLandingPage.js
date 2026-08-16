import React from "react";
import Image from "next/image";
import Link from "next/link";
import styles from "@/scss/pages/menuLanding.module.scss";

function SmartLink({ href, className, children }) {
  const isExternal = typeof href === "string" && href.startsWith("http");

  return (
    <Link
      href={href || "/contact-us"}
      className={className}
      target={isExternal ? "_blank" : undefined}
      rel={isExternal ? "noopener noreferrer" : undefined}
    >
      {children}
    </Link>
  );
}

export default function MenuLandingPage({ config }) {
  return (
    <section className={styles.menuLandingPage}>
      <div className="container">
        <nav className={styles.breadcrumbs} aria-label="Breadcrumb">
          <Link href="/">Home</Link>
          <span>›</span>
          <strong>{config.label}</strong>
        </nav>

        <section className={styles.heroCard}>
          <div className={styles.heroContent}>
            <span className={styles.eyebrow}>{config.eyebrow}</span>
            <h1>{config.title}</h1>
            <p>{config.description}</p>

            <div className={styles.heroActions}>
              <SmartLink href={config.primaryCta?.href} className={styles.primaryButton}>
                {config.primaryCta?.label || "Explore"}
              </SmartLink>
              <SmartLink href={config.secondaryCta?.href} className={styles.secondaryButton}>
                {config.secondaryCta?.label || "Contact Us"}
              </SmartLink>
            </div>
          </div>

          <div className={styles.heroImageWrap}>
            <Image
              src={config.heroImage}
              alt={config.title}
              width={620}
              height={420}
              className={styles.heroImage}
              priority
            />
          </div>
        </section>

        <div className={styles.highlightStrip}>
          {config.highlights?.map((highlight) => (
            <span key={highlight}>{highlight}</span>
          ))}
        </div>

        <section className={styles.cardGrid} aria-label={`${config.title} options`}>
          {config.cards?.map((card) => (
            <article className={styles.optionCard} key={card.title}>
              <span>{card.meta}</span>
              <h2>{card.title}</h2>
              <p>{card.description}</p>
              <SmartLink href={card.href} className={styles.cardLink}>
                View Details →
              </SmartLink>
            </article>
          ))}
        </section>

        <section className={styles.processCard}>
          <div>
            <span className={styles.eyebrow}>Simple process</span>
            <h2>{config.processTitle}</h2>
          </div>
          <ol>
            {config.processSteps?.map((step) => (
              <li key={step}>{step}</li>
            ))}
          </ol>
        </section>
      </div>
    </section>
  );
}
