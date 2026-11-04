import { NextResponse } from "next/server";
import { requestApi } from "@/lib/apiClient";

export async function POST(request) {
  let input;
  try { input = await request.json(); } catch {
    return NextResponse.json({ message: "Invalid request." }, { status: 400 });
  }
  if (!input || typeof input !== "object") return NextResponse.json({ message: "Invalid request." }, { status: 400 });
  const { name, email, mobile, password, c_password } = input;
  try {
    const data = await requestApi("register", { method: "POST", body: { name, email, mobile, password, c_password } });
    return NextResponse.json({ message: "Registration successful!", data });
  } catch (error) {
    return NextResponse.json({ message: error.payload?.data?.email || error.message }, {
      status: error.status >= 400 ? error.status : 503,
    });
  }
}
