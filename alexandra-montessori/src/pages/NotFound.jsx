import { Link } from "react-router-dom";
import { Home, ArrowRight } from "lucide-react";

export default function NotFound() {
  return (
    <section className="container-wide flex min-h-[70vh] flex-col items-center justify-center py-32 text-center">
      <p className="font-heading text-7xl font-medium text-sage-300">404</p>
      <h1 className="mt-4 text-3xl font-medium text-ink">Page not found</h1>
      <p className="mt-3 max-w-md text-pretty text-ink/90">
        The page you're looking for has wandered off to play. Let's get you back
        to familiar ground.
      </p>
      <div className="mt-8 flex flex-col gap-3 sm:flex-row">
        <Link to="/" className="btn-primary">
          <Home className="h-4 w-4" />
          Back home
        </Link>
        <Link to="/contact" className="btn-outline">
          Contact us
          <ArrowRight className="h-4 w-4" />
        </Link>
      </div>
    </section>
  );
}
