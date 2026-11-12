import { groupAffiliation } from "../data/site";

/**
 * States the approved group relationship between the two nursery companies.
 * Shown on the hero and in the footer.
 *
 * The linked sites are separate products with separate WordPress installs;
 * this is a published corporate fact, not a data connection.
 */
export default function GroupNotice({ className = "", linkClassName = "" }) {
  const { members, joiner, suffix } = groupAffiliation;

  return (
    <p className={className}>
      {members.map((member, index) => (
        <span key={member.href}>
          {index > 0 && joiner}
          {member.external ? (
            <a
              href={member.href}
              target="_blank"
              rel="noopener noreferrer"
              className={linkClassName}
            >
              {member.label}
            </a>
          ) : (
            <a href={member.href} className={linkClassName}>
              {member.label}
            </a>
          )}
        </span>
      ))}
      {` ${suffix}`}
    </p>
  );
}

/** The group badge shown alongside the LDRN logo in the footer. */
export function GroupBadge() {
  const { badge, badgeAlt, members } = groupAffiliation;
  const external = members.find((member) => member.external);

  return (
    <a
      href={external?.href}
      target="_blank"
      rel="noopener noreferrer"
      className="group flex shrink-0 items-center justify-center"
      aria-label={`${badgeAlt} — visit website`}
    >
      <img
        src={badge}
        alt={badgeAlt}
        loading="lazy"
        className="h-32 w-32 object-contain transition-transform duration-300 group-hover:-rotate-2 sm:h-36 sm:w-36"
      />
    </a>
  );
}

