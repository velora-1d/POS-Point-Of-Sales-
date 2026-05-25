---
name: Warm Utilitarian
colors:
  surface: '#f9f9f7'
  surface-dim: '#dadad8'
  surface-bright: '#f9f9f7'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f4f2'
  surface-container: '#eeeeec'
  surface-container-high: '#e8e8e6'
  surface-container-highest: '#e2e3e1'
  on-surface: '#1a1c1b'
  on-surface-variant: '#524436'
  inverse-surface: '#2f3130'
  inverse-on-surface: '#f1f1ef'
  outline: '#857464'
  outline-variant: '#d7c3b1'
  surface-tint: '#885200'
  primary: '#855000'
  on-primary: '#ffffff'
  primary-container: '#a76500'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb869'
  secondary: '#5f5f59'
  on-secondary: '#ffffff'
  secondary-container: '#e1e0d9'
  on-secondary-container: '#63635d'
  tertiary: '#825100'
  on-tertiary: '#ffffff'
  tertiary-container: '#a36800'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdcbb'
  primary-fixed-dim: '#ffb869'
  on-primary-fixed: '#2b1700'
  on-primary-fixed-variant: '#673d00'
  secondary-fixed: '#e4e2dc'
  secondary-fixed-dim: '#c8c6c0'
  on-secondary-fixed: '#1b1c18'
  on-secondary-fixed-variant: '#474742'
  tertiary-fixed: '#ffddb7'
  tertiary-fixed-dim: '#ffb95d'
  on-tertiary-fixed: '#2a1700'
  on-tertiary-fixed-variant: '#653e00'
  background: '#f9f9f7'
  on-background: '#1a1c1b'
  surface-variant: '#e2e3e1'
  amber-50: '#FAEEDA'
  amber-100: '#FAC775'
  amber-600: '#854F0B'
  amber-800: '#633806'
  gray-200: '#D3D1C7'
  gray-400: '#888780'
  gray-600: '#444441'
  gray-800: '#2C2C2A'
  success-bg: '#EAF3DE'
  success-text: '#3B6D11'
  danger-bg: '#FCEBEB'
  danger-text: '#A32D2D'
  info-bg: '#E6F1FB'
  info-text: '#185FA5'
typography:
  display-lg:
    fontFamily: Instrument Serif
    fontSize: 32px
    fontWeight: '400'
    lineHeight: '1.2'
  display-md:
    fontFamily: Instrument Serif
    fontSize: 24px
    fontWeight: '400'
    lineHeight: '1.2'
  headline-sm:
    fontFamily: Geist
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Geist
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-md:
    fontFamily: Geist
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-caps:
    fontFamily: Geist
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  price-lg:
    fontFamily: JetBrains Mono
    fontSize: 16px
    fontWeight: '600'
    lineHeight: '1'
  price-md:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: '1'
  caption:
    fontFamily: Geist
    fontSize: 11px
    fontWeight: '400'
    lineHeight: 14px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  layout-margin: 28px
  sidebar-width: 220px
  topbar-height: 48px
---

## Brand & Style

The design system is built on a "Refined Utilitarian" philosophy, specifically tailored for the fast-paced F&B environment. It balances the warmth of hospitality with the rigorous precision of an operational tool. The aesthetic is inspired by high-density developer tools, prioritizing clarity, speed, and information density without sacrificing visual elegance.

The brand personality is **Professional, Organic, and Orderly**. It avoids the clinical coldness of traditional enterprise software by using an earthy, warm color palette, while maintaining a sophisticated edge through sharp typography and hair-line borders.

### Design Principles
- **Operational Density:** High information density is a feature, not a bug. Use tight spacing and clear visual separators to allow users to see more data at once.
- **Warm Precision:** Use "Warm Gray" surfaces to reduce eye strain during long shifts, contrasted with "Amber" for high-priority actions.
- **Structural Clarity:** Rely on subtle borders (0.5px to 1px) and background shifts rather than heavy shadows to define hierarchy.

## Colors

The palette is designed to evoke an "Artisanal Ledger" feel. 

- **Primary (Amber):** Reserved strictly for primary calls to action (CTAs), brand moments, and active interactive states. It represents the "heat" of the kitchen and the energy of service.
- **Neutral (Warm Gray):** A sophisticated range of off-whites and stony grays. This serves as the foundation for all UI surfaces, providing a softer, more premium feel than pure grayscale.
- **Semantic States:** Status colors are slightly desaturated to harmonize with the warm neutral base. Use background-tinted containers with high-contrast text for status badges.
- **Backgrounds:** The application uses `#FAFAF8` as the base canvas, with `#F1EFE8` used for structural components like sidebars, headers, and inset surfaces.

## Typography

This design system employs a specialized triple-font strategy to create a "Premium Ledger" aesthetic:

1.  **Display (Instrument Serif):** Used for page titles and high-level headings. It provides a touch of editorial sophistication and classical hospitality.
2.  **UI & Body (Geist):** A high-performance sans-serif used for all functional interface elements, navigation, and descriptions. Its geometric clarity ensures legibility at small sizes.
3.  **Data (JetBrains Mono):** Reserved exclusively for prices, quantities, SKU numbers, and timestamps. The monospaced nature allows for perfect vertical alignment in tables and receipt-style summaries, emphasizing the "Utilitarian" aspect of the system.

**Formatting Rules:**
- Use `label-caps` for table headers and sidebar section titles.
- Use `price-lg` for total amounts in checkout or summary cards.
- Body text should default to 13px (`body-md`) to maximize information density.

## Layout & Spacing

The layout is structured around a **Fixed Shell** model, optimized for desktop and tablet POS stations.

### Layout Model
- **Sidebar:** Fixed width (220px). Background uses `gray-50`. Items use a 2px left border to indicate active state.
- **Top Bar:** Fixed height (48px). Contains breadcrumbs and global actions.
- **Main Canvas:** Uses a fluid container with a consistent `layout-margin` (28px) at the bottom of major sections.
- **Grid:** Use a standard 12-column system for form layouts, but rely on flexbox/gap for list-heavy views (e.g., menu grids).

### Spacing Rhythm
The system uses a strict 4px/8px rhythm.
- **Gaps:** 8px for related elements (buttons in a group, swatches).
- **Padding:** 12px-16px for card interiors.
- **Density:** Components should favor smaller vertical padding (e.g., `py-1.5` or `py-2`) to keep actions visible without scrolling.

## Elevation & Depth

Hierarchy is established through **Tonal Layering** and **Hair-line Borders** rather than dramatic shadows.

- **Level 0 (Canvas):** `#FAFAF8`. The base on which everything sits.
- **Level 1 (Structural Surfaces):** `#F1EFE8`. Used for sidebars, headers, and footer bars. 
- **Level 2 (Interactive Cards):** `#FFFFFF`. White cards are used to "pop" against the warm background. They feature a 0.5px or 1px border (`gray-200`) and a very subtle, diffused shadow for focus.
- **Active States:** Indicated by a 2px solid `amber-400` border-left (for navigation) or a subtle `amber-50` background tint.

**Border Usage:**
- Default Border: `1px solid #D3D1C7`.
- High-Density Border: `0.5px solid #D3D1C7` for internal dividers and table rows.

## Shapes

The shape language is "Soft" yet structured. 

- **Primary UI Components:** (Buttons, Input Fields, Nav Items) use a `6px` radius (`rounded-md`). This keeps the interface feeling modern but efficient.
- **Containers:** (Cards, Modals) use an `8px` radius (`rounded-lg`) to provide a clear frame for content.
- **Status Indicators:** (Badges, Chips) use a `999px` (Pill) shape to distinguish them from interactive buttons.
- **Product Tiles:** Use a standard `6px` radius for image containers.

## Components

### Buttons
- **Primary:** `amber-400` background, `amber-800` text (or white if accessibility requires), `6px` radius.
- **Secondary:** `amber-50` background, `amber-200` border, `amber-800` text.
- **Ghost:** No background, `gray-600` text, subtle `gray-50` background on hover.

### Input Fields
- White background, `1px solid gray-200`. 
- Focus state: `1px solid amber-400` with a soft amber outer glow.
- Labels use `label-caps` typography, positioned above the field.

### Status Badges
- High-contrast text on a light-tinted background (e.g., `success-bg` with `success-text`).
- Always pill-shaped.
- Font: `label-caps` but scaled down to 10px if necessary.

### Cards & Tables
- **Cards:** White background, 1px border, 8px radius.
- **Tables:** No outer border, 0.5px horizontal dividers between rows. Table headers use `label-caps` with `gray-400` text and a `gray-50` background.
- **Price Columns:** Always right-aligned and set in `jetbrainsMono`.

### Sidebar Items
- `py-2 px-3` padding.
- `6px` radius on the hover/active state background.
- Active state: `gray-50` background, `gray-800` bold text, 2px `amber-400` left border.