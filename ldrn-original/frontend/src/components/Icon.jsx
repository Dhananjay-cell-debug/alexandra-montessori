import {
  Activity,
  ArrowRight,
  Baby,
  BadgeCheck,
  BookOpen,
  CalendarCheck,
  Clock,
  HandHeart,
  Mail,
  MapPin,
  Menu,
  Music,
  Palette,
  Phone,
  Quote,
  ShieldCheck,
  Sigma,
  Sprout,
  Users,
  X,
} from "lucide-react";

/**
 * Content records refer to icons by name so an editor can pick one in
 * WordPress without touching code. Only the names in this map are allowed;
 * an unknown name renders nothing rather than breaking the layout.
 */
const ICONS = {
  ArrowRight,
  Activity,
  Baby,
  BadgeCheck,
  BookOpen,
  CalendarCheck,
  Clock,
  HandHeart,
  Music,
  Mail,
  MapPin,
  Menu,
  Phone,
  Palette,
  Quote,
  ShieldCheck,
  Sprout,
  Sigma,
  Users,
  X,
};

export default function Icon({ name, className = "h-5 w-5", ...rest }) {
  const Glyph = ICONS[name];
  if (!Glyph) return null;
  return (
    <Glyph className={className} strokeWidth={1.6} aria-hidden {...rest} />
  );
}
