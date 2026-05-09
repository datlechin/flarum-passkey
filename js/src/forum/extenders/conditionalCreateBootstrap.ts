import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import Session from 'flarum/common/Session';
import SuggestPasskeyModal from '../components/SuggestPasskeyModal';
import { isSupported } from '../lib/webauthn';
import type Passkey from '../../common/models/Passkey';

const POST_LOGIN_FLAG = 'datlechin-passkey.post-login';
const DISMISSED_FLAG = 'datlechin-passkey.suggest-dismissed';
const DISMISS_DURATION_MS = 30 * 24 * 60 * 60 * 1000;

/** Must run at module top level so it patches Session.login before LogInModal calls it. */
export function patchSessionForConditionalCreate(): void {
  override(Session.prototype, 'login', function (original: Function, ...args: unknown[]) {
    return (original.apply(this, args) as Promise<unknown>).then((result) => {
      try {
        sessionStorage.setItem(POST_LOGIN_FLAG, '1');
      } catch {}
      return result;
    });
  });
}

export function checkConditionalCreate(): void {
  if (!isSupported()) return;

  const pending = takeFlag(POST_LOGIN_FLAG);
  if (!pending) return;
  if (isRecentlyDismissed()) return;

  setTimeout(surface, 0);
}

async function surface(): Promise<void> {
  // Folds the user check and the count check into a single request: a guest
  // gets 401 and we silently abort, an authenticated user gets only their
  // own passkeys (the API resource is scoped by actor).
  let existing: Passkey[];
  try {
    existing = await app.store.find<Passkey[]>('passkeys');
  } catch {
    return;
  }

  if (existing.length > 0) return;

  app.modal.show(SuggestPasskeyModal, { onDismiss: rememberDismissal });
}

function takeFlag(key: string): string | null {
  try {
    const value = sessionStorage.getItem(key);
    if (value !== null) sessionStorage.removeItem(key);
    return value;
  } catch {
    return null;
  }
}

function rememberDismissal(): void {
  try {
    localStorage.setItem(DISMISSED_FLAG, String(Date.now()));
  } catch {}
}

function isRecentlyDismissed(): boolean {
  try {
    const stamp = Number(localStorage.getItem(DISMISSED_FLAG) ?? '0');
    return Number.isFinite(stamp) && Date.now() - stamp < DISMISS_DURATION_MS;
  } catch {
    return true;
  }
}
