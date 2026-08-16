"use client";
import Banner from "@/components/banner";
import React, { useEffect, useState } from "react";
import { CgProfile } from "react-icons/cg";
import styles from "@/scss/pages/myAccount.module.scss";
import MyAddressesss from "@/components/MyAddressesss";
import MyProfile from "@/components/MyProfile";
import { useUser } from "@/context/UserContext";
import { useRouter } from "next/navigation";
import Orders from "@/components/order";
import ChangePassword from "@/components/ChangePassword";
import Modal from "@/components/modal";
import OrderDetails from "@/app/(pages)/orderDetails/page";

export default function MyAccount({ userData, userToken, guestSession }) {
  const router = useRouter();
  const { isAuthenticated, logout } = useUser();
  const [activeTab, setActiveTab] = useState(0);
  const [OrderActive, setOrderActive] = useState(false);
  const [id, setId] = useState(null);

  const content = [
    { category: "My Profile", icon: <CgProfile /> },
    { category: "Change Password", icon: <CgProfile /> },
    { category: "My Addresses", icon: <CgProfile /> },
    { category: "Your Orders", icon: <CgProfile /> },
    { category: "Logout", icon: <CgProfile /> },
  ];

  const handleLogout = () => {
    logout();
    router.push("/login");
    console.log("refresh");
    window.location.reload();
  };
  const handleOrderActive = (id) => {
    setOrderActive(true);
    setId(id);
  };
  const handleBack = () => {
    setOrderActive(false);
    setId(null);
  };

  useEffect(() => {
    if (isAuthenticated === false) {
      router.push("/login");
    } else if (isAuthenticated === "") {
      return;
    }
  }, [isAuthenticated, router]);

  if (!isAuthenticated) {
    return null;
  }

  return (
    <>
      <Banner title="My Account" />
      <div className="container my-5">
        <div className="row">
          <div className="col-md-4">
            <div className={styles.left_side}>
              {content.map((item, index) =>
                index !== 4 ? (
                  <div
                    key={index}
                    className={`${styles.profile_tab} ${
                      activeTab === index ? styles.active : ""
                    }`}
                    onClick={() => {
                      setActiveTab(index);
                      handleBack();
                    }}
                  >
                    {item.icon}
                    <h6>{item.category}</h6>
                  </div>
                ) : (
                  <Modal
                    buttonClass={`${styles.custom_logout}`}
                    buttonName={
                      <div
                        className={`${styles.profile_tab} ${
                          activeTab === index ? styles.active : ""
                        }`}
                      >
                        {item.icon}
                        <h6>{item.category}</h6>
                      </div>
                    }
                    onConfirm={handleLogout}
                    key={index}
                  >
                    <span style={{ color: "black" }}>
                      Are you sure you want to Logout?
                    </span>
                  </Modal>
                )
              )}
            </div>
          </div>
          <div className="col-md-8">
            {activeTab === 0 && (
              <MyProfile userData={userData} userToken={userToken} />
            )}
            {activeTab === 1 && <ChangePassword userToken={userToken} />}
            {activeTab === 2 && (
              <MyAddressesss
                userToken={userToken}
                guestSession={guestSession}
              />
            )}
            {activeTab === 3 &&
              (OrderActive && id ? (
                <OrderDetails
                  id={id}
                  handleBack={handleBack}
                  userData={userData}
                  userToken={userToken}
                />
              ) : (
                <Orders
                  handleOrderActive={handleOrderActive}
                  userToken={userToken}
                />
              ))}
          </div>
        </div>
      </div>
    </>
  );
}
