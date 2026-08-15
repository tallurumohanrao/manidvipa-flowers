export async function fetchBlogData(userToken) {
  const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

  try {
    const response = await fetch(`${url}/auth-check`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${userToken}`,
        "Content-Type": "application/json",
      },
      next: { revalidate: 120 },
    });
    if (response.ok) {
      const userDetails = await response.json();

      return userDetails;
    } else {
      return null;
    }
  } catch (error) {
    console.error("Error checking authentication:", error);
    return null;
  }
}
