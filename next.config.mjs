/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    dangerouslyAllowLocalIP: process.env.NODE_ENV !== "production",
    remotePatterns: [
      {
        protocol: "https",
        hostname: "admin.manidvipastore.com",
        pathname: "/storage/banners/**",
      },
      {
        protocol: "https",
        hostname: "admin.manidvipastore.com",
        pathname: "/storage/**",
        // pathname: "/storage/products/**",
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
