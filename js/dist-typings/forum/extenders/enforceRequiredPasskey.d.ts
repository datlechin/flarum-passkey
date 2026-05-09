/**
 * If the admin has flagged this user's group as "passkey required" and the
 * user has not yet registered one, surface a sticky banner pointing them at
 * the registration flow. The banner reappears on every page load until the
 * user complies, but does not hard-block navigation , that would lock anyone
 * out who happened to lose their last device.
 *
 * The flag is computed server-side from the request actor + settings, so it
 * is always fresh: as soon as the user registers a passkey the next render
 * stops emitting the flag.
 */
export default function enforceRequiredPasskey(): void;
