"use client";
import React, { useState } from "react";
import styles from "@/scss/components/modal.module.scss";

const Modal = ({
  title,
  children,
  buttonClass,
  buttonName,
  onOpen,
  onClose,
  onConfirm,
  userToken,
  guestSession,
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const openModal = () => {
    if (onOpen) onOpen();
    setIsOpen(true);
  };

  // const closeModal = () => setIsOpen(false);

  const closeModal = () => {
    setIsOpen(false);
    if (onClose) onClose(false); // Notify parent that the modal is closed
  };

  const confirmAction = () => {
    if (onConfirm) onConfirm();
    closeModal();
  };

  return (
    <>
      <button type="button" className={buttonClass} onClick={openModal}>
        {buttonName ? buttonName : "Modal"}
      </button>
      {isOpen && (
        <div className={styles.modalBackdrop} onClick={closeModal}>
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <div className={styles.modalHeader}>
              <span>{title}</span>
              <button
                type="button"
                className={styles.closeButton}
                onClick={closeModal}
              >
                ×
              </button>
            </div>
            <div className={styles.modalBody}>
              {React.Children.map(children, (child) => {
                if (
                  React.isValidElement(child) &&
                  typeof child.type === "function"
                ) {
                  return React.cloneElement(child, { closeModal });
                }
                return child;
              })}
            </div>
            {onConfirm && (
              <div className={styles.modalFooter}>
                <button
                  type="button"
                  className="primary-but"
                  onClick={closeModal}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  className="green-but"
                  onClick={confirmAction}
                >
                  OK
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
};

export default Modal;
