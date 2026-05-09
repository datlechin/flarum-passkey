import app from 'flarum/forum/app';
import extendLogInModal from './extenders/extendLogInModal';
import extendUserSecurityPage from './extenders/extendUserSecurityPage';
import setupConditionalUi from './extenders/conditionalUiBootstrap';
import { patchSessionForConditionalCreate, checkConditionalCreate } from './extenders/conditionalCreateBootstrap';
import enforceRequiredPasskey from './extenders/enforceRequiredPasskey';

// Patch `Session.prototype.login` at module top level , before LogInModal
// calls it , so the post-login flag lands inside the resolved promise.
patchSessionForConditionalCreate();

app.initializers.add('datlechin-passkey', () => {
  extendLogInModal();
  extendUserSecurityPage();
  setupConditionalUi();
  checkConditionalCreate();
  enforceRequiredPasskey();
});

export { default as PasskeyLoginButton } from './components/PasskeyLoginButton';
export { default as PasskeyList } from './components/PasskeyList';
export { default as AddPasskeyModal } from './components/AddPasskeyModal';
export { default as RenamePasskeyModal } from './components/RenamePasskeyModal';
export { default as SuggestPasskeyModal } from './components/SuggestPasskeyModal';
export * as webauthn from './lib/webauthn';
