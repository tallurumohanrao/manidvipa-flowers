import { requestApi } from "../src/lib/apiClient";

export async function fetchBlogData(userToken) {
  if (!userToken) return null;
  try {
    return await requestApi("auth-check", { token: userToken, allowFailure: true });
  } catch (error) {
    if (error.status === 401) return null;
    throw error;
  }
}
