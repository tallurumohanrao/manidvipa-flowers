import { NextResponse } from "next/server";
const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

export async function POST(request) {
  try {
    const { name, email, mobile, password, c_password } = await request.json();

    // Make a POST request to the external API
    const response = await fetch(`${url}/register`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        name,
        email,
        mobile,
        password,
        c_password,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      return NextResponse.json(
        { message: "Registration successful!", data },
        { status: 200 }
      );
    } else {
      return NextResponse.json(
        { message: data?.data?.email || "Registration failed." },
        { status: response.status }
      );
    }
  } catch (error) {
    console.error("Server error:", error);
    return NextResponse.json(
      { message: "Server error. Please try again later." },
      { status: 500 }
    );
  }
}
