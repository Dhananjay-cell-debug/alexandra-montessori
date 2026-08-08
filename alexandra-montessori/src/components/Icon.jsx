// Alexandra Montessori - custom duotone icon set.
//
// Hand-built so the feature icons read as *designed* artwork (a soft filled
// silhouette + crisp detail strokes), not thin single-line glyphs. Everything is
// monochrome via `currentColor`, so a single text colour (e.g. text-sage-600)
// themes both the fill and the line - giving the calm, two-tone laurel look.
//
// Usage is unchanged: <Icon name="Sprout" className="h-8 w-8" />. Names map to the
// strings used in src/data/site.js. `strokeWidth`/extra props are ignored safely.

// Solid shape: soft laurel fill + matching outline (the duotone base).
const solid = {
  fill: "currentColor",
  fillOpacity: 0.16,
  stroke: "currentColor",
  strokeWidth: 1.7,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};
// Detail line: crisp stroke only (sits on top of the solid base).
const line = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.7,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};
// Faint wash (large backdrops like a target ring or coin face).
const wash = {
  fill: "currentColor",
  fillOpacity: 0.12,
  stroke: "currentColor",
  strokeWidth: 1.7,
};

const icons = {
  // - Growth / nature - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  Sprout: (
    <>
      <path {...line} d="M12 21v-8.5" />
      <path
        {...solid}
        d="M12 12.5C8.6 12.5 5.9 10.1 5.9 6.8 9.3 6.8 12 9.2 12 12.5Z"
      />
      <path
        {...solid}
        d="M12 10.6c0-2.9 2.5-5.1 5.7-5.1 0 2.9-2.5 5.1-5.7 5.1Z"
      />
    </>
  ),
  Leaf: (
    <>
      <path
        {...solid}
        d="M4.5 19.5C4.5 11.2 10.2 5.5 18.5 5.5c.6 0 1 .4 1 1 0 8.3-5.7 14-14 14-.6 0-1-.4-1-1Z"
      />
      <path {...line} d="M9 15.5c2.6-2.7 5-4.4 8.5-5.5" />
    </>
  ),
  Trees: (
    <>
      <path
        {...solid}
        d="M12 3c3.6 0 6.5 2.9 6.5 6.5S15.6 16 12 16s-6.5-2.9-6.5-6.5S8.4 3 12 3Z"
      />
      <path {...line} d="M12 16v5" />
      <path {...line} d="M9 21h6" />
    </>
  ),

  // - People / care - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  Users: (
    <>
      <circle {...solid} cx="9" cy="8.5" r="3" />
      <circle {...line} cx="16.2" cy="8.2" r="2.4" />
      <path {...line} d="M3.6 19c0-3 2.4-4.9 5.4-4.9s5.4 1.9 5.4 4.9" />
      <path {...line} d="M15.6 14.2c2.6 0 4.8 1.7 4.8 4.5" />
    </>
  ),
  Heart: (
    <path
      {...solid}
      d="M12 20.5C6 16.5 3 13 3 9.2 3 6.6 5 4.5 7.6 4.5c1.7 0 3.3.9 4.4 2.4C13.1 5.4 14.7 4.5 16.4 4.5 19 4.5 21 6.6 21 9.2c0 3.8-3 7.3-9 11.3Z"
    />
  ),
  HandHeart: (
    <>
      <path
        {...solid}
        d="M12 8.5c0-1.2-1-2.2-2.2-2.2-1.1 0-2.1.9-2.1 2.1 0 1.6 1.7 2.7 4.3 4.1 2.6-1.4 4.3-2.5 4.3-4.1 0-1.2-1-2.1-2.1-2.1C13 6.3 12 7.3 12 8.5Z"
      />
      <path {...line} d="M4.5 14.6c2.5 3.3 4.8 4.7 7.5 4.7s5-1.4 7.5-4.7" />
      <path
        {...line}
        d="M6.2 13.9 4.9 12.6M17.8 13.9l1.3-1.3M9.2 16.2l-.6-1.7M14.8 16.2l.6-1.7"
      />
    </>
  ),
  HeartHandshake: (
    <>
      <path
        {...solid}
        d="M12 10.6c0-1.4-1.1-2.5-2.5-2.5S7 9.2 7 10.6c0 1.7 1.9 3 5 4.7 3.1-1.7 5-3 5-4.7 0-1.4-1.1-2.5-2.5-2.5S12 9.2 12 10.6Z"
      />
      <path {...line} d="M3.2 9.8 6.4 7c.9-.8 2.2-.8 3.1 0" />
      <path {...line} d="M20.8 9.8 17.6 7c-.9-.8-2.2-.8-3.1 0" />
      <path {...line} d="M3.2 9.8 2.2 11.1M20.8 9.8l1 1.3" />
    </>
  ),

  // - Safety - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ShieldCheck: (
    <>
      <path
        {...solid}
        d="M12 3 5 5.5v5C5 15.5 8 18.6 12 20c4-1.4 7-4.5 7-9.5v-5L12 3Z"
      />
      <path {...line} d="m9 11.6 2 2 4-4" />
    </>
  ),
  Lock: (
    <>
      <rect {...solid} x="5" y="10.5" width="14" height="10" rx="2.6" />
      <path {...line} d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
      <circle {...line} cx="12" cy="14.6" r="1.4" />
      <path {...line} d="M12 16v1.8" />
    </>
  ),
  Clock: (
    <>
      <circle {...solid} cx="12" cy="12" r="8.5" />
      <path {...line} d="M12 7.5V12l3 2" />
    </>
  ),
  CarFront: (
    <>
      <path
        {...solid}
        d="M7 18.5h10c1.4 0 2.5-1.1 2.5-2.5v-3.6c0-.7-.2-1.3-.5-1.8l-1.7-3.1A2.5 2.5 0 0 0 15.1 6H8.9a2.5 2.5 0 0 0-2.2 1.4L5 10.5c-.3.5-.5 1.1-.5 1.8V16c0 1.4 1.1 2.5 2.5 2.5Z"
      />
      <path {...line} d="M5 12.4h14" />
      <path {...line} d="M7.4 9.8h9.2" />
      <path {...line} d="M8.1 15h0M15.9 15h0" />
      <path {...line} d="M7.5 18.5v2M16.5 18.5v2" />
    </>
  ),

  // - Learning - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  GraduationCap: (
    <>
      <path {...solid} d="M2.5 9 12 5l9.5 4-9.5 4-9.5-4Z" />
      <path {...line} d="M6 11.2V15c0 1.5 2.7 3 6 3s6-1.5 6-3v-3.8" />
      <path {...line} d="M21.5 9v4.6" />
    </>
  ),
  BookOpen: (
    <>
      <path
        {...solid}
        d="M12 6.6C10.5 5.4 8.3 5 6 5H3.4v12.2H6c2.3 0 4.5.4 6 1.6 1.5-1.2 3.7-1.6 6-1.6h2.6V5H18c-2.3 0-4.5.4-6 1.6Z"
      />
      <path {...line} d="M12 6.6v12.2" />
    </>
  ),
  Calculator: (
    <>
      <rect {...solid} x="5" y="3" width="14" height="18" rx="2.6" />
      <rect {...line} x="8" y="6" width="8" height="3" rx="1" />
      <path
        {...line}
        d="M9 13h0M12 13h0M15 13h0M9 16.6h0M12 16.6h0M15 16.6h0"
      />
    </>
  ),
  Globe2: (
    <>
      <circle {...solid} cx="12" cy="12" r="8.5" />
      <path {...line} d="M3.5 12h17" />
      <path
        {...line}
        d="M12 3.5c2.5 2.4 4 5.4 4 8.5s-1.5 6.1-4 8.5c-2.5-2.4-4-5.4-4-8.5s1.5-6.1 4-8.5Z"
      />
    </>
  ),
  Palette: (
    <>
      <path
        {...solid}
        d="M12 3.5C6.8 3.5 3 7.2 3 12s4 8.5 9 8.5c1.4 0 2.2-1 2.2-2 0-.5-.2-.9-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1 .8-1.7 1.9-1.7H17c2.2 0 4-1.7 4-4.2C21 6.3 17 3.5 12 3.5Z"
      />
      <path {...line} d="M7.4 12.5h0M9.8 8.5h0M14.4 8h0" />
    </>
  ),
  Shapes: (
    <>
      <circle {...solid} cx="8" cy="7.8" r="3.5" />
      <rect {...line} x="13" y="13" width="7.2" height="7.2" rx="1.6" />
      <path {...line} d="M7.6 13.5 3.6 20.5h8L7.6 13.5Z" />
    </>
  ),
  Sparkles: (
    <>
      <path
        {...solid}
        d="M11.5 3.2 13.3 7.4 17.5 9l-4.2 1.6L11.5 15 9.7 10.6 5.5 9l4.2-1.6L11.5 3.2Z"
      />
      <path {...solid} d="M18 14.2l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2Z" />
    </>
  ),
  CalendarHeart: (
    <>
      <rect {...solid} x="3.5" y="5" width="17" height="15.2" rx="2.6" />
      <path {...line} d="M3.5 9.6h17" />
      <path {...line} d="M8 3.4v3.2M16 3.4v3.2" />
      <path
        {...line}
        d="M12 18c-2-1.3-3.2-2.4-3.2-3.8 0-1 .8-1.7 1.7-1.7.7 0 1.2.4 1.5.9.3-.5.8-.9 1.5-.9.9 0 1.7.7 1.7 1.7 0 1.4-1.2 2.5-3.2 3.8Z"
      />
    </>
  ),

  // - Food - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  Utensils: (
    <>
      <path {...line} d="M7 3v4.6M9.5 3v4.6M7 7.6h2.5M8.25 7.6V21" />
      <path
        {...solid}
        d="M16.5 21V3c2.2 1 3.2 4.2 3.2 7.3 0 1-.8 1.7-1.8 1.7H16.5"
      />
    </>
  ),
  ChefHat: (
    <>
      <path
        {...solid}
        d="M6.6 19h10.8v-6a4.5 4.5 0 0 0-.6-8.6A4 4 0 0 0 12 3a4 4 0 0 0-4.8 1.4A4.5 4.5 0 0 0 6.6 13v6Z"
      />
      <path {...line} d="M6.6 16h10.8" />
    </>
  ),
  ShoppingBag: (
    <>
      <path
        {...solid}
        d="M5.5 8h13l-1 11.4a1.6 1.6 0 0 1-1.6 1.5H8.1a1.6 1.6 0 0 1-1.6-1.5L5.5 8Z"
      />
      <path {...line} d="M8.5 8V6.5a3.5 3.5 0 0 1 7 0V8" />
    </>
  ),

  // - Connect / process - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  Smartphone: (
    <>
      <rect {...solid} x="6.5" y="2.5" width="11" height="19" rx="2.6" />
      <path {...line} d="M10.5 18.5h3" />
    </>
  ),
  MessageCircle: (
    <>
      <path
        {...solid}
        d="M4 12a8 8 0 1 1 3.4 6.5L4 19.6l1.1-3.1A7.9 7.9 0 0 1 4 12Z"
      />
      <path {...line} d="M9 11.6h0M12 11.6h0M15 11.6h0" />
    </>
  ),
  Target: (
    <>
      <circle {...wash} cx="12" cy="12" r="8.5" />
      <circle {...line} cx="12" cy="12" r="4.8" />
      <circle cx="12" cy="12" r="1.5" fill="currentColor" />
    </>
  ),
  Settings: (
    <>
      <circle {...solid} cx="12" cy="12" r="3.3" />
      <path
        {...line}
        d="M12 3v2.6M12 18.4V21M21 12h-2.6M5.6 12H3M18.4 5.6l-1.9 1.9M7.5 16.5l-1.9 1.9M18.4 18.4l-1.9-1.9M7.5 7.5 5.6 5.6"
      />
    </>
  ),
  BarChart3: (
    <>
      <path {...line} d="M4 20h16" />
      <rect {...solid} x="5.4" y="12" width="3.3" height="6" rx="1" />
      <rect {...solid} x="10.4" y="8" width="3.3" height="10" rx="1" />
      <rect {...solid} x="15.4" y="5" width="3.3" height="13" rx="1" />
    </>
  ),
  TrendingUp: (
    <>
      <path {...line} d="M3.5 16 9 10.5l3.5 3.5L20.5 6" />
      <path {...line} d="M15 6h5.5v5.5" />
    </>
  ),
  PoundSterling: (
    <>
      <circle {...wash} cx="12" cy="12" r="8.5" />
      <path
        {...line}
        d="M14.5 8.2A2.7 2.7 0 0 0 9.6 9.8c0 .9.3 1.8.3 3 0 1.5-.7 2.3-1.4 3h6.4M9 13.2h3.6"
      />
    </>
  ),
};

export default function Icon({ name, className = "", ...rest }) {
  const glyph = icons[name] || icons.Leaf;
  // Strip props that would fight our internal stroke styling.
  const safe = { ...rest };
  delete safe.strokeWidth;
  return (
    <svg
      viewBox="0 0 24 24"
      className={className}
      aria-hidden="true"
      focusable="false"
      {...safe}
    >
      {glyph}
    </svg>
  );
}
