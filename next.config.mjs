/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
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
    ],
  },
};

export default nextConfig;
