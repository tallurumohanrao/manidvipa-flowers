"use client";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { fetchListingData, fetchUser } from "../../../../../hook/userCookie";
import Address from "../page";

const OfferFormPage = () => {
  const { id } = useParams();
  const [loadError, setLoadError] = useState("");
  const [retry, setRetry] = useState(0);
  const [userAddress, setUserAddress] = useState([]);
  const [userToken, setUserToken] = useState(null);

  useEffect(() => {
    const userToken = fetchUser();
    if (userToken) {
      setUserToken(userToken);
    }
  }, []);

  useEffect(() => {
    const endpoint = userToken
      ? "get-address-by-id"
      : "checkout-get-address-by-id";

    const fetchData = async () => {
      const data = await fetchListingData(
        "GET",
        `${endpoint}?address_id=${id}`,
        userToken ? userToken : null
      );
      if (data) {
        setUserAddress(data);
      } else {
        console.log("No data received");
      }
    };

    setLoadError("");
    if (id) fetchData().catch(() => setLoadError("Unable to load this address. Please try again."));
  }, [userToken, id, retry]);

  return (
    <div>
      {loadError ? <p role="alert">{loadError} <button type="button" onClick={() => setRetry((value) => value + 1)}>Try again</button></p> : <Address userAddress={userAddress} />}
    </div>
  );
};

export default OfferFormPage;
