import { useParams, useLocation, Navigate } from "react-router-dom";
import { locationBySlug } from "../data/site";

// Preserve existing contact links while keeping the form on its nursery page.
export default function ContactLocation() {
  const { slug } = useParams();
  const { search } = useLocation();
  const loc = locationBySlug(slug);
  return <Navigate to={loc ? `/nurseries/${loc.id}${search}#contact` : "/contact"} replace />;
}
