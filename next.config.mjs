/** @type {import('next').NextConfig} */
const nextConfig = {
  async redirects() {
    return [
      {
        source: "/:path*",
        has: [
          {
            type: "host",
            value: "manidvipaflowers.com",
          },
        ],
        destination: "https://www.manidvipaflowers.com/:path*",
        permanent: true,
      },
    ];
  },

  images: {
    // Disable Next.js server-side image optimization
    // because product images are served from the Laravel admin domain.
    unoptimized: true,

    dangerouslyAllowLocalIP: process.env.NODE_ENV !== "production",

    remotePatterns: [
      {
        protocol: "https",
        hostname: "admin.manidvipaflowers.com",
        pathname: "/storage/banners/**",
      },
      {
        protocol: "https",
        hostname: "admin.manidvipaflowers.com",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        port: "8000",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        port: "",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "localhost",
        port: "",
        pathname: "/storage/**",
      },
    ],
  },
};

export default nextConfig;
