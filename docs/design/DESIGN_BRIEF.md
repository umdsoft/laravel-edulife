# EDULIFE — Design brief (UI redesign)

Product: a math & physics learning platform for one teacher (ustoz) and their students
(grades 9–11 / applicants). Core flows: placement exam → weekly Saturday exams,
courses & lessons, virtual physics lab, battles, coins/missions. Panels: Student,
Teacher (ustoz), Admin. All UI text: Uzbek (Latin).

## Direction: "calm precision"
Light, airy, professional — closer to Linear / Stripe / Notion than to a game.
Content (formulas, problems, results) is the hero; chrome stays quiet.
- Light-first. Dark mode keeps working through tokens (`html.dark`), not per-page hacks.
- One brand accent, used sparingly for primary actions, focus and active nav.
- Generous whitespace, clear hierarchy, few borders, soft elevation.
- No emoji as UI icons — use @heroicons/vue (outline 24 for nav/actions, mini 20 inline).
  Emoji may remain only inside user/teacher content.
- Motion: subtle (150–200 ms ease-out), respects `prefers-reduced-motion`.

## Tokens (implemented as CSS variables in resources/css/app.css, mapped in tailwind.config.js)
- Font: Inter Variable (self-hosted via @fontsource-variable/inter, no CDN), `font-feature-settings: "cv11","ss01"`;
  numbers in stats/scores/tables use `tabular-nums`. Mono: JetBrains Mono (only for code/IDs) — optional.
- Type scale: 12 / 13 / 14 (body) / 16 / 18 / 20 / 24 / 30 / 36; headings semibold, tracking-tight.
- Neutrals: slate-based. Canvas `--bg` #F7F8FA, surface `--surface` #FFFFFF, subtle `--surface-2` #F1F3F6,
  border `--border` #E4E7EC, text `--fg` #0F172A, muted `--fg-muted` #475467, subtle `--fg-subtle` #667085.
- Brand: indigo-blue `--brand-600` #4F46E5 (hover 700 #4338CA, soft 50 #EEF2FF, 100 #E0E7FF).
  Subject accents (tags/charts only): math = indigo, physics = teal #0D9488.
- Semantic: success #16A34A, warning #D97706, danger #DC2626, info #0284C7 (each with 50/100/600/700).
- Radius: sm 6, md 10 (inputs, buttons), lg 14 (cards), xl 20 (hero/modals), full (avatars/pills).
- Shadow: xs `0 1px 2px rgb(16 24 40 / .05)`, sm card, md dropdown, lg modal — all low-opacity, cool-gray.
- Spacing: 4px grid; page padding 24 (desktop) / 16 (mobile); card padding 20–24; max content width 1200.
- Focus: 2px ring brand-500 with 2px offset on all interactive elements (`focus-visible`).

## Components (resources/js/Components/UI — the only place base styles live)
Button (primary/secondary/ghost/danger/link; sm/md/lg; loading; icon slots), IconButton, Card (+header/footer slots),
Input, Textarea, Select, Checkbox, Radio, Switch, FormField (label, hint, error), Badge (neutral/brand/success/warning/danger/info),
Avatar (image/initials), Modal (existing API kept), Dropdown/Menu, Tabs, Table (sticky header, zebra-free, hover row, empty state),
Pagination (existing API kept), EmptyState, PageHeader (title, description, actions, breadcrumbs), StatCard, Alert, Toast (existing API kept),
Skeleton, ProgressBar/ProgressRing, Tooltip, Breadcrumbs. Existing component props/emits must stay backward compatible.

## Layouts
- App shell: left sidebar (240px, collapsible to icons ≤1024px, off-canvas drawer on mobile) + top bar (search, notifications, avatar menu).
- Student: sidebar sections — Bosh sahifa, Imtihonlar (placeholder link if route missing → hide), Kurslarim, Kurslar, Laboratoriya,
  Olimpiada, Battle, Reyting, Topshiriqlar/Yutuqlar, Coin do'kon; mobile bottom tab bar with 5 key items.
  Top bar shows XP/level, streak, coins compactly (from shared props), never noisy.
- Teacher (ustoz): Dashboard, Kurslar, O'quvchilar, Savollar banki (hide if route missing), Sharhlar, Statistika, Profil.
- Admin: grouped sidebar (Asosiy, Ta'lim, Foydalanuvchilar, Gamifikatsiya, Moliya, Tizim).
- Auth: split layout — form card on the left, calm illustration/brand panel on the right (hidden on mobile).
Only link to routes that exist (`route().has(name)` with Ziggy) — never `#`.

## Page rules
- Every page: PageHeader → content in Cards/sections; consistent 24px vertical rhythm.
- Tables for admin lists; cards/grids for student browsing; forms in single column max 640px with FormField.
- Empty, loading (Skeleton) and error states for every list.
- Numbers right-aligned in tables, tabular-nums; currency `so'm`, dates `dd.mm.yyyy`.
- Responsive down to 360px; touch targets ≥ 40px; WCAG AA contrast.
- Do not change backend contracts (props, routes, form field names, emitted events). Pure presentation + small UX fixes.
- Physics/math content (formulas) will be rendered by a future `<MathContent>` (KaTeX); leave text as is.

## Definition of done
`npm run build` passes, `npx vitest run` passes, `php artisan test` passes; no console errors on render;
each page reviewed at 1440px and 375px widths (describe; screenshots not required).
