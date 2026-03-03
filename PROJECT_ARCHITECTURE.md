# Project Architecture: WaterMeter

This document serves as the primary technical reference for the WaterMeter management project. All contributors (including AI agents) must adhere to these architectural principles.

## 1. Core Architecture: Custom MVC
The project follows a clean **Model-View-Controller** separation:
-   **Models (`app/Models/`)**: Handle all database interactions (using PDO). NO business logic in Controllers if it can be in Models.
-   **Views (`app/Views/`)**: PHP templates. They should remain as "pure" as possible, only for rendering.
-   **Controllers (`app/Controllers/`)**: Orchestrate data between Models and Views.
-   **Core (`app/Core/`)**: Contains the Router, Database connection, and base Controller class.

## 2. Frontend Paradigm: Single Page Application (SPA)
This is **NOT** a traditional multi-page PHP app. It uses a custom AJAX-based SPA engine.

### Essential Rules:
1.  **Partial Loading**: Navigation and form submissions are intercepted by `public/assets/js/app.js`.
2.  **Server Response**: The server checks for the `X-Requested-With: XMLHttpRequest` header. 
    -   If present: Returns ONLY the view content (no layout).
    -   If absent: Returns the full HTML (via `app/Views/layout/main.php`).
3.  **UI Updates**:
    -   **NEVER** use `window.location.reload()` or hard redirects for successful AJAX operations.
    -   **ALWAYS** use `window.loadPage(currentUrl, true)` to refresh data gracefully. The `true` parameter enables "partial update" (refreshing only the `#history-results` container instead of `#main-content`).
4.  **JavaScript Scope**: 
    -   Global helpers should be in `app.js`.
    -   Page-specific interactive logic (like Modal handling) is usually placed at the bottom of the View file inside a script tag (handled by `executeScripts` in SPA logic).

## 3. Directory Structure
-   `/public`: Document root. Contains `index.php` (Entry point) and `assets/`.
-   `/app`: Protected source code.
-   `/brain`: AI agent context and knowledge storage.

## 4. Coding Standards
-   **PHP**: Use PDO with prepared statements (Security!).
-   **CSS**: Glassmorphism aesthetic using vanilla CSS and semi-transparent backgrounds.
-   **Icons/Images**: Use high-quality visual cues (emojis or subtle SVGs).
