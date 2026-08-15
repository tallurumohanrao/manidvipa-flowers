// components/QuantityInput.js
import React from "react";
import styles from "@/scss/components/quantityComponent.module.scss";

const QuantityComponent = ({ quantity, onQuantityChange }) => {
  // Increment function
  const incrementValue = () => {
    onQuantityChange(quantity + 1);
  };

  // Decrement function
  const decrementValue = () => {
    if (quantity > 1) {
      onQuantityChange(quantity - 1);
    }
  };

  return (
    <div className={styles.quantity_container}>
      <input
        type="number"
        value={quantity}
        min="1"
        onChange={(e) => onQuantityChange(Number(e.target.value))}
      />
      <div className={styles.buttons}>
        <button type="button" onClick={incrementValue}>
          &#9650;
        </button>
        <button type="button" onClick={decrementValue}>
          &#9660;
        </button>
      </div>
    </div>
  );
};

export default QuantityComponent;
