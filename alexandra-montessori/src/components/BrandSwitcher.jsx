import alexandraBadge from "../assets/logo-badge-optimized.webp";
import { asset } from "../lib/asset";
import { homeMedia } from "../lib/homeVisual";

export default function BrandSwitcher() {
  const alexandra = homeMedia("header-logo", alexandraBadge, "Alexandra Montessori");
  return (
    <div className="brand-switcher" role="group" aria-label="Choose a nursery brand">
      <a href="/" className="brand-switcher-badge brand-switcher-alexandra" aria-label="Alexandra Montessori website" aria-current="true">
        <img src={asset(alexandra.src)} alt="Alexandra Montessori" width="128" height="128" />
      </a>
      <a href="/ldrn/" className="brand-switcher-badge brand-switcher-rainbows" aria-label="Little Rainbows Day Nursery website">
        <img src={asset("/assets/client/ldrn-logo.png")} alt="Little Rainbows Day Nursery" width="128" height="128" />
      </a>
    </div>
  );
}
