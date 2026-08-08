# Blog article detail template — section index

Route template: /blogs/:slug  
Source: alexandra-montessori/src/pages/BlogArticle.jsx

1. 01-loading-state — early-return loading public section.
2. 02-error-and-retry-state — early-return request-error public section.
3. 03-not-found-state — unavailable-article public section with its SEO fallback.
4. 04-article-hero-and-back — normal back-link plus metadata/title/intro/image section and dynamic SEO.
5. 05-rich-article-content — normal sanitized body HTML section.
6. 06-related-articles — normal More parent articles section/card grid.
7. 07-closing-help-cta — normal CTA section.

The first three are mutually exclusive actual branches. The final four render only when a bound Article exists.
