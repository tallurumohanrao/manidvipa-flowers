"use client";
import { useToast } from "@/context/UserContext";
import styles from "@/scss/components/toast.module.scss";

const Toast = () => {
  const { toast, closeToast } = useToast();

  if (!toast.visible) return null;

  return (
    <div className={`${styles.toast} ${styles[toast.type]}`}>
      {toast.message}
      <button className={`${styles.custom_button}`} onClick={closeToast}>
        &times;
      </button>
    </div>
  );
};

export default Toast;
