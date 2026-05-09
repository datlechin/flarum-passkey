/** Must run at module top level so it patches Session.login before LogInModal calls it. */
export declare function patchSessionForConditionalCreate(): void;
export declare function checkConditionalCreate(): void;
