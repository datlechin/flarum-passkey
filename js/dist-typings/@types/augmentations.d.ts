/*
 * TypeScript module augmentations for the symbols this extension teaches the
 * canonical Flarum frontend at runtime. Pure declaration file, no emitted
 * code, so the assignments in `admin/index.ts` are typed everywhere they
 * are read.
 */

import 'flarum/common/models/Group';

declare module 'flarum/common/models/Group' {
  export default interface Group {
    passkeyRequired(): boolean | undefined;
  }
}

/**
 * Chromium's User-Agent Client Hints, exposed as `navigator.userAgentData`.
 * Not yet in lib.dom.d.ts at the TypeScript version Flarum core ships, so we
 * augment the global Navigator interface ourselves rather than pepper the
 * codebase with casts.
 */
declare global {
  interface NavigatorUAData {
    brands: { brand: string; version: string }[];
    mobile: boolean;
    platform?: string;
  }

  interface Navigator {
    userAgentData?: NavigatorUAData;
  }
}
