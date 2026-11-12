import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import Seo from "../components/Seo";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import { workspaceRequest } from "../lib/workspace-request";

export default function BlogDetail() {
  const { slug } = useParams();
  const [result, setResult] = useState(null);
  useEffect(() => {
    const controller = new AbortController();
    const endpoint = window.ldrnData?.restBase || "/wp-json/ldrn/v1/";
    workspaceRequest(`${endpoint.replace(/\/$/, "")}/blogs/${encodeURIComponent(slug)}`, { signal: controller.signal })
      .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.status === 404 ? "missing" : "network")))
      .then((payload) => setResult({ slug, payload }))
      .catch((error) => { if (error.name !== "AbortError") setResult({ slug, error: error.message }); });
    return () => controller.abort();
  }, [slug]);
  const current = result?.slug === slug ? result : null;
  if (!current) return <section className="container-wide py-20" aria-busy="true"><p>Opening article…</p></section>;
  if (current.error) return <><Seo path={`/blogs/${slug}`} title="Article unavailable" noindex /><PageHeader title="Article unavailable" /><section className="container-wide py-16"><p>{current.error === "missing" ? "This article is no longer published." : "We could not load this article. Please try again."}</p><Link to="/blogs" className="btn-primary mt-6">Back to articles</Link></section></>;
  const article = current.payload.post;
  return <>
    <Seo path={`/blogs/${slug}`} title={`${article.title} | Little Rainbows`} description={article.excerpt} image={article.image} />
    <PageHeader title={article.title} />
    <section data-ldrn-ws={`article.${slug}.content`} className="container-wide py-16 sm:py-20">
      <div className="mx-auto max-w-3xl">
        <Link to="/blogs" className="text-sm font-bold">← All articles</Link>
        <p className="my-6 text-sm">{article.date}</p>
        {article.image && <Img src={article.image} alt={article.title} className="mb-8 aspect-[16/10] w-full" />}
        {/* The WordPress endpoint applies wp_kses_post to the article body. */}
        <article className="ldrn-article-body" data-ldrn-ws={`article.${slug}.body`} dangerouslySetInnerHTML={{ __html: article.content }} />
      </div>
    </section>
  </>;
}
