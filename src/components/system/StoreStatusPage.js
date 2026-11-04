import Image from "next/image";
import Link from "next/link";
import { FiAlertTriangle, FiSearch } from "react-icons/fi";
import styles from "@/scss/components/storeStatus.module.scss";

export default function StoreStatusPage({
  code = "404",
  eyebrow,
  title,
  description,
  variant = "notFound",
  children,
  suggestions,
}) {
  const StatusIcon = variant === "error" ? FiAlertTriangle : FiSearch;

  return (
    <main className={`${styles.page} ${styles[variant] || ""}`}>
      <span className={styles.glowOne} aria-hidden="true" />
      <span className={styles.glowTwo} aria-hidden="true" />

      <section className={styles.card} aria-labelledby="store-status-title">
        <div className={styles.visual} aria-hidden="true">
          <div className={styles.iconHalo}>
            <span className={styles.petalOne} />
            <span className={styles.petalTwo} />
            <span className={styles.petalThree} />
            <StatusIcon />
          </div>
          <strong className={styles.code}>{code}</strong>
          <span className={styles.visualCaption}>
            {variant === "error" ? "A temporary pause" : "This path has no flowers yet"}
          </span>
        </div>

        <div className={styles.content}>
          <Link href="/" className={styles.logoLink} aria-label="Manidvipa Flowers home">
            <Image
              src="/assets/images/manidvpa-flowers-2.png"
              alt="Manidvipa Flowers"
              width={210}
              height={70}
              priority
            />
          </Link>
          <span className={styles.eyebrow}>{eyebrow}</span>
          <h1 id="store-status-title">{title}</h1>
          <p className={styles.description}>{description}</p>
          <div className={styles.actions}>{children}</div>
          {suggestions ? <div className={styles.suggestions}>{suggestions}</div> : null}
        </div>
      </section>
    </main>
  );
}
