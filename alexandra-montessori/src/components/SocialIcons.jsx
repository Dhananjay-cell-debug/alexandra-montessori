// Inline brand-social glyphs (lucide dropped these for trademark reasons).
// Shared by the floating SocialSidebar and the Footer.

export const FacebookIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
    <path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v6h3v-6h2.5l.5-3H14V9.5c0-.3.2-.5.5-.5H14z" />
  </svg>
);

export const InstagramIcon = (props) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    aria-hidden="true"
    {...props}
  >
    <rect x="3" y="3" width="18" height="18" rx="5" />
    <circle cx="12" cy="12" r="3.5" />
    <circle cx="17" cy="7" r="1.2" fill="currentColor" stroke="none" />
  </svg>
);

export const YoutubeIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
    <path d="M22.5 7.2a3 3 0 0 0-2.1-2.1C18.6 4.5 12 4.5 12 4.5s-6.6 0-8.4.6A3 3 0 0 0 1.5 7.2 31 31 0 0 0 1 12a31 31 0 0 0 .5 4.8 3 3 0 0 0 2.1 2.1c1.8.6 8.4.6 8.4.6s6.6 0 8.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 23 12a31 31 0 0 0-.5-4.8zM10 15.2V8.8l5.2 3.2z" />
  </svg>
);

export const TwitterIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
    <path d="M17.5 4h2.9l-6.3 7.2L21.6 20h-5.8l-4.1-5.3L6.9 20H4l6.8-7.7L4 4h5.9l3.7 4.9zM16.5 18.3h1.6L8 5.6H6.3z" />
  </svg>
);

export const LinkedinIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
    <path d="M6.94 7.5A1.94 1.94 0 1 1 7 3.62a1.94 1.94 0 0 1-.06 3.88zM5.4 9h3.1v9.6H5.4zm5.1 0h2.97v1.31h.04c.41-.74 1.42-1.52 2.93-1.52 3.13 0 3.71 2 3.71 4.7v5.11h-3.1v-4.5c0-1.08-.02-2.46-1.5-2.46-1.5 0-1.73 1.17-1.73 2.38v4.58h-3.1z" />
  </svg>
);
