// Shop entry of the @vanssa/sylius-slider-plugin UX package.
//
// Intentionally empty: the plugin's Stimulus controllers register through the
// stimulus-bridge manifest (package.json "symfony.controllers" + the consumer's
// controllers.json) inside the APP's own startStimulusApp() — never here.
// Starting a second Stimulus application from this file makes every controller
// double-fire (see CLAUDE.md "Stimulus Controller Manifests").
// Styles arrive via the "slider" controller's autoimport (shop/styles/slider.scss).
//
// The file itself must remain: sylius/test-application hard-codes it as the
// "plugin-shop-entry" webpack entry.
