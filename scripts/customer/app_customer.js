// ============================================================
// CABIN PUTIH CUSTOMER APP - Main Entry Point
// Imports all modular components and initializes the application
// ============================================================

// Import all module scripts (they self-register to window)
// Note: In a build system these would be ES modules, but for
// direct browser loading we use script tags in HTML instead.

// Modules loaded via script tags in HTML:
// - modules/core.js (API, auth, view switching, bootstrap)
// - modules/home.js (recommendations)
// - modules/menu.js (catalog, cart, checkout)
// - modules/tracker.js (order tracking)
// - modules/history.js (order history)
// - modules/profile.js (user profile)

// This file serves as the entry point and module loader reference
// All functionality is already registered to window object by modules

// Bootstrap is handled by core.js after DOMContentLoaded
// appBootstrapInit() is called automatically when scripts load
