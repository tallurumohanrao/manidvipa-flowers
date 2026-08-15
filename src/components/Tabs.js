"use client";
import React, { useState } from "react";

export default function Tabs({ tabsContent, renderContent, customClasses }) {
  const [activeTab, setActiveTab] = useState(tabsContent[0].category);

  const handleTabClick = (category) => {
    setActiveTab(category);
  };

  return (
    <section className="container">
      <div className={`${customClasses?.gallerySection}`}>
        {/* Tabs (Filter Buttons) */}
        <ul className={`${customClasses?.controls}`}>
          {tabsContent.map((tab, index) => (
            <li
              key={index}
              className={`${customClasses?.buttons} ${
                activeTab === tab.category ? customClasses?.active : ""
              }`}
              onClick={() => handleTabClick(tab.category)}
            >
              {tab.category}
            </li>
          ))}
        </ul>

        <div className={customClasses?.contentContainer}>
          {tabsContent
            .filter((tab) => tab.category === activeTab)
            .map((item, index) => renderContent(item, index))}
        </div>
      </div>
    </section>
  );
}
