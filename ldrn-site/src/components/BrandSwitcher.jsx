import { asset } from "../lib/asset";

export default function BrandSwitcher() {
  return (
    <div className="brand-switcher" role="group" aria-label="Choose a nursery brand">
      <a href="/" className="brand-switcher-badge brand-switcher-alexandra" aria-label="Alexandra Montessori website">
        <img src={asset("/assets/group/alexandra-logo.webp")} alt="Alexandra Montessori" width="128" height="128" />
      </a>
      <a href="/ldrn/" className="brand-switcher-badge brand-switcher-rainbows" aria-label="Little Rainbows Day Nursery website" aria-current="true">
        <img src={asset("/assets/logo/logo-primary-navy-wordmark.png")} alt="Little Rainbows Day Nursery" width="128" height="128" />
      </a>
    </div>
  );
}
