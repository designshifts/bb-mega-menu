# BB Mega Menu

Mega menu CPT and frontend behavior for classic menus and the Navigation block.

## What Changed
- Created `BB Mega Menu` plugin with a `megamenu` CPT.
- Moved mega menu CSS/JS out of the theme into this plugin.
- Added Navigation block support by injecting mega menu markup into `core/navigation-link`.
- Kept classic menu support for the `primary` menu location.
 - Added settings-driven CSS variables and optional default styling.

## How It Works
- A navigation item becomes a mega menu trigger when its label matches a `Mega Menu` post title.
- The plugin converts the nav item link into a button and injects the mega menu markup.
- The frontend script controls open/close behavior and accessibility states.

## Settings
Go to Appearance -> BB Mega Menu.

Available settings (stored in `bb_mega_menu_settings`):
- Header / Nav Height Offset
- Panel Padding
- Z-index
- Enable Default Styling (toggle)
- Panel Background
- Panel Shadow (None/Subtle/Medium)
- Transition Speed (ms)

CSS Variables output:
- `--bb-mm-header-offset`
- `--bb-mm-z`
- `--bb-mm-max-width`
- `--bb-mm-panel-padding`
- `--bb-mm-panel-bg`
- `--bb-mm-panel-shadow`
- `--bb-mm-transition`

## Testing Steps
1. Activate the plugin: **BB Mega Menu**.
2. In WP Admin, create a **Mega Menu** post with a title that matches a Navigation item label.
3. In the Site Editor (or Classic menu), add a nav item with the exact same label.
4. View the frontend:
   - The nav item should render as a button.
   - Clicking it should open the mega menu panel.
5. Test mobile behavior:
   - Toggle the menu on mobile and open the mega menu item.
6. Accessibility checks:
   - `aria-expanded` should toggle on the trigger button.
   - ESC should close any open mega menu.

## Notes
- Navigation block support keys off the label text.
- Classic menu support uses `theme_location = primary`.
