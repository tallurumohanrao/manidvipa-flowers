import Image from "next/image";
import React from "react";

const BlogDetail = ({ post }) => {
  return (
    <div>
      {post?.image && <Image 
                    width={0}
                    height={0}
                    sizes="100vw"
                    style={{ width: "100%", height: "100%" }} src={post.image} alt={post.image} />}
      <h2>{post.title}</h2>
      <p>{post.content}</p>
      <p>
        <strong>Author:</strong> {post.author}
      </p>
      <p>
        <strong>Date:</strong> {post.date}
      </p>
    </div>
  );
};

export default BlogDetail;
