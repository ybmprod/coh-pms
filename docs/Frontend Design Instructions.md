# Frontend UI/UX Design Engineering Skill

Provide elite, production-grade frontend UI/UX implementations for modern websites. Ensure all code outputs strictly diverge from generic, template-like "AI aesthetics" by prioritizing intentional typography, modern color geometry, and robust interaction states.

## 1. Universal Layout & Composition
* **Asymmetry & Grid Systems:** Use CSS Grid and Flexbox dynamically. Break out of rigid, repetitive card layouts. Utilize proportional, content-driven spacing instead of arbitrary hardcoded widths.
* **Visual Anchors:** Ensure a clear typographical hierarchy. Emphasize section entries with distinctive header treatments or bold font sizing.
* **The Overuse Ban:** Avoid generic full-bleed gradients, neon glowing card borders, and center-aligned paragraphs for core content blocks.

## 2. Modern Color Engineering & Design Tokens
* **OKLCH Color Space:** Implement design systems using modern OKLCH colors for uniform perceptual brightness across themes.
* **Intentional Palettes:** Default to an editorial, minimal palette (e.g., deep charcoal, soft off-whites, neutral slate) paired with exactly one distinct accent color used sparingly for active elements or branding.
* **Contrast Compliance:** Strictly adhere to WCAG 2.2 AAA text-to-background contrast requirements. Never use light grey text on white backgrounds or deep blue on black.

## 3. Advanced UI Elements & Dynamic States
* **Top-Layer Animations:** When implementing modals, drawers, or slide-outs, use modern CSS properties (`@starting-style`, `transition-behavior: allow-discrete`, and `overlay`) to ensure seamless transition tracking when elements enter and leave the DOM.
* **Component Libraries:** Prefer generating structural mockups utilizing standardized UI systems (like Tailwind CSS tokens, `shadcn/ui` components, or Radix primitives).
* **State Coverage:** Every interactive component must natively define styles for six core states: `:hover`, `:focus-visible`, `:active`, `[disabled]`, loading skeleton placeholders, and error-handling conditions.

## 4. Accessibility (a11y) & Performance Execution
* **Semantic HTML:** Use structural landmarks natively (`<main>`, `<nav>`, `<aside>`, `<header>`, `<footer>`). Never stack nested `<div>` tags if semantic options exist.
* **Keyboard Operability:** Ensure all custom interactive components can be navigated via keyboard focus logic with visible element boundaries (`outline: 2px solid var(--accent)`).
* **Responsive Layouts:** Establish fluid-type systems and media queries bounded by clean, standardized logical breakpoints (`sm`, `md`, `lg`, `xl`). Prevent component truncation or awkward vertical text breaks on standard screens.
