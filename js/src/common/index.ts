import app from 'flarum/common/app';
import Passkey from './models/Passkey';

app.initializers.add('datlechin-passkey-common', () => {
  app.store.models['passkeys'] = Passkey;
});

export { Passkey };
