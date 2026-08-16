import { revalidatePath } from "next/cache";

export async function GET(request) {
  const path = request.nextUrl.searchParams.get("path");
  const secret = request.nextUrl.searchParams.get("secret");
  const expectedSecret = process.env.REVALIDATE_SECRET;

  if (!expectedSecret || secret !== expectedSecret) {
    return Response.json(
      {
        revalidated: false,
        now: Date.now(),
        message: "Unauthorized revalidation request",
      },
      { status: 401 }
    );
  }

  if (path) {
    revalidatePath(path);
    return Response.json({ revalidated: true, now: Date.now() });
  }

  return Response.json({
    revalidated: false,
    now: Date.now(),
    message: "Missing path to revalidate",
  });
}
