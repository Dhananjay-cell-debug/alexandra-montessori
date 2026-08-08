import { socialLinks } from "../data/socialLinks";
import { asset } from "../lib/asset";
import {
  editableMediaProps,
  homeElement,
  homeHref,
  homeMedia,
} from "../lib/homeVisual";

// MRN-style fixed social bar on the right edge: a stack of individual square
// blocks, each in its own platform brand colour with a white glyph.
const brandColor = {
  Facebook: "#1877f2",
  Instagram: "#d62976",
  YouTube: "#ff0000",
  Twitter: "#1da1f2",
  X: "#1da1f2",
  LinkedIn: "#0a66c2",
};

export default function SocialSidebar() {
  return (
    <div
      className="fixed right-0 top-1/2 z-40 hidden -translate-y-1/2 flex-col md:flex"
      data-am-vb-region="social-sidebar"
    >
      {socialLinks.map(({ label, href, Icon }, index) => {
        const key = `social-${index + 1}-icon`;
        const icon = homeMedia(key, "", `${label} icon`);
        const item = homeElement(key);
        const editable = editableMediaProps(key, `${label} icon`, "move", "", `${label} icon`);
        return (
        <a
          key={label}
          {...editable}
          href={homeHref(key, href)}
          target={homeHref(key, href) === "#" ? undefined : "_blank"}
          rel="noopener noreferrer"
          aria-label={item.linkDescription || icon.alt || label}
          title={item.linkDescription || undefined}
          style={{ ...editable.style, backgroundColor: brandColor[label] || "#3a5446" }}
          className="am-vb-editable-media flex h-10 w-10 items-center justify-center text-white transition-opacity hover:opacity-85"
        >
          {icon.src ? <img src={asset(icon.src)} alt={icon.alt} className="h-full w-full object-contain" /> : null}
          <Icon className={`h-[1.1rem] w-[1.1rem] ${icon.src ? "hidden" : ""}`} />
        </a>
        );
      })}
    </div>
  );
}
