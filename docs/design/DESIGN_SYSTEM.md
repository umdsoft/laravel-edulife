# EDULIFE Design System (implementation reference)

This is the working reference for everyone restyling pages. The direction is in
[DESIGN_BRIEF.md](./DESIGN_BRIEF.md) ("calm precision"). This file says **what exists and how to use it**.

- Tokens: `resources/css/app.css` (CSS variables) → `tailwind.config.js` (Tailwind names)
- Components: `resources/js/Components/UI/*` (barrel: `@/Components/UI`)
- App shell: `resources/js/Components/Layout/*`, used by `resources/js/Layouts/*`
- Helpers: `@/Utils/nav` (routes / active state), `@/Composables/useToast`, `useTheme`, `useMediaQuery`

> Golden rule: **use token classes, not raw palette classes.** `bg-surface text-fg border-border`, not
> `bg-white text-gray-900 border-gray-200`. Tokens switch automatically in dark mode, so you never need `dark:`
> for token colours.

---

## 1. Tokens

All colours are `R G B` triplets, so opacity modifiers work: `bg-brand-600/10`, `ring-brand-500/15`.

### Neutrals

| Tailwind class part | CSS var | Light | Use |
|---|---|---|---|
| `bg` | `--bg` | `#F7F8FA` | app canvas (`bg-bg`) — set by the layouts |
| `surface` | `--surface` | `#FFFFFF` | cards, sidebar, top bar, inputs |
| `surface-2` | `--surface-2` | `#F1F3F6` | subtle fills, hover rows, table header, skeletons |
| `surface-3` | `--surface-3` | `#E4E7EC` | pressed state, switch track |
| `border` | `--border` | `#E4E7EC` | default borders/dividers (plain `border` already uses it) |
| `border-strong` | `--border-strong` | `#D0D5DD` | input borders, secondary buttons |
| `fg` | `--fg` | `#0F172A` | primary text |
| `fg-muted` | `--fg-muted` | `#475467` | secondary text, descriptions |
| `fg-subtle` | `--fg-subtle` | `#667085` | captions, placeholders, idle icons |
| `overlay` | `--overlay` | slate-900 | backdrops: `bg-overlay/40` |

### Brand (indigo-blue)

| Class | Use |
|---|---|
| `bg-brand-600` / `hover:bg-brand-700` | solid primary (buttons, active page in pagination, progress) |
| `bg-brand-50`, `bg-brand-100`, `border-brand-200` | soft fills (active nav item, selected card, info chips) |
| `ring-brand-500` / `focus:ring-brand-500/15` | focus rings |
| **`text-brand`** (= `--brand-fg`) | brand-coloured **text, links, icons**. Use this, not `text-brand-600` |

In dark mode 50–200 turn into dark tints and `text-brand` turns light, so `bg-brand-50 text-brand` stays readable.
Do not use `text-brand-600/700` for text (low contrast in dark).

### Semantic (`success`, `warning`, `danger`, `info`) and subject accents (`math`, `physics`)

Each has: `-50` soft bg, `-100` soft border/ring, `-600` solid/icon, `-700` solid hover, and **`text-{name}`**
(DEFAULT = theme-aware text colour).

```html
<span class="bg-success-50 text-success ring-1 ring-success-100">Faol</span>
<button class="bg-danger-600 hover:bg-danger-700 text-white">O'chirish</button>
```

`math` = indigo, `physics` = teal (#0D9488). **Only** for subject tags, chart series, subject icons.

Legacy aliases still compile (`primary` = brand-600, `admin`, `teacher`, `student`) — don't use them in new code.

### Type

- Font: **Inter Variable** (self-hosted, `font-sans` is the default). `font-mono` = JetBrains Mono → system mono.
- Body is **14px** (`text-sm`, set on `body`). Scale: `text-xs` 12 · `text-13` 13 · `text-sm` 14 · `text-base` 16 ·
  `text-lg` 18 · `text-xl` 20 · `text-2xl` 24 · `text-3xl` 30 · `text-4xl` 36.
- Headings: `font-semibold tracking-tight` (tracking-tight is applied to h1–h6 globally).
  Page title `text-2xl`, card title `text-base`, section title `text-lg`.
- Numbers in stats, scores, tables, timers: **`tabular-nums`**.

### Radius, shadow, spacing, layering

| Token | Value | Use |
|---|---|---|
| `rounded-sm` | 6px | small chips, kbd, tooltips |
| `rounded-md` | 10px | inputs, buttons, menus, list items |
| `rounded-lg` | 14px | cards, tables |
| `rounded-xl` | 20px | modals, hero panels |
| `rounded-full` | — | avatars, pills, badges |
| `shadow-xs` | `0 1px 2px …/.05` | cards, buttons, inputs |
| `shadow-sm` | — | raised cards |
| `shadow-md` | — | dropdowns, hovered interactive cards |
| `shadow-lg` | — | modals, drawer, toasts |

`rounded-2xl`/`rounded-3xl` are Tailwind defaults (16/24px) — **don't use in new code**.

- 4px grid. Page padding is handled by the layout (`.page-container`: 16px mobile / 24px desktop, max width 1200px).
- Card padding: 20–24px (`<Card>` does it). Vertical rhythm between page sections: **`space-y-6`** (24px).
- z-index scale: `z-topbar` 30 · `z-sidebar` 40 · dropdown 50 · `z-drawer` 60 · `z-modal` 70 · `z-toast` 80 · `z-tooltip` 90.

### Focus & motion

- Every interactive element needs a visible focus: add **`focus-ring`** (component class: 2px brand-500 ring,
  2px offset, keyboard only). A global `:focus-visible` outline is the fallback.
- Motion 150–200ms ease-out (`duration-150`, `ease-out`). `prefers-reduced-motion` disables animation globally;
  add `motion-reduce:animate-none` on custom infinite animations.
- `animate-fade-in`, `animate-scale-in` (modals/popovers) are available.

### Dark mode

`html.dark` toggled by `useTheme()` (key `edulife_dark_mode` in localStorage; applied before paint by an inline
script in `app.blade.php`). The toggle lives in every user menu. Token classes adapt automatically. Only reach for
`dark:` when you use a non-token colour (e.g. an illustration).

---

## 2. Components (`@/Components/UI`)

```js
import { Button, Card, PageHeader, Table, Badge } from '@/Components/UI';
// or single file: import Button from '@/Components/UI/Button.vue';
```

Icons: `@heroicons/vue/24/outline` for nav/actions, `@heroicons/vue/20/solid` for inline/small. Pass the component
(not a string) to `icon` props. **No emoji as UI icons.**

### Button
Props: `variant` primary | secondary | ghost | danger | soft | link (default primary) · `size` sm(32) | md(40) | lg(48) ·
`type` (button) · `loading` · `disabled` · `block` · `href` (renders Inertia `<Link>`) · `external` (plain `<a target=_blank>`) ·
`method`/`data`/`preserveScroll` (Link passthrough; non-GET renders `<button>`) · `iconLeft` / `iconRight` (components).
Slots: default, `#leading`, `#trailing`. Emits `click`.
```vue
<Button :icon-left="PlusIcon" :href="route('teacher.courses.create')">Yangi kurs</Button>
<Button variant="secondary" @click="cancel">Bekor qilish</Button>
<Button variant="danger" :loading="form.processing" type="submit">O'chirish</Button>
```
One primary button per view area. Destructive actions: `danger` inside a confirm Modal.

### IconButton
Props: `icon`, **`label` (required, aria-label + tooltip)**, `variant` ghost | secondary | primary | danger | soft, `size`, `href`,
`badge` (number → count pill, `true` → dot), `disabled`. Emits `click`.

### Card
Props: `title`, `description`, `padding` none | sm | md | lg, `href` (whole card is a Link), `interactive` (hover elevation),
`as`, `divided` (header/footer dividers, default true). Slots: default, `#header` (replaces title block), `#actions`, `#footer`.
```vue
<Card title="So'nggi natijalar" description="Oxirgi 7 kun">
  <template #actions><Button size="sm" variant="ghost" :href="…">Barchasi</Button></template>
  …
</Card>
<Card padding="none" title="O'quvchilar"><Table … :bordered="false" /></Card>
```

### Form controls — Input, Textarea, Select, Checkbox, Radio, Switch, FormField
All accept `label`, `hint`, `error` (string or array; first item shown), `required`, `optional`, `disabled`, `id`
(auto-generated otherwise) and wire `aria-invalid` / `aria-describedby`. Native attrs (`name`, `autocomplete`,
`maxlength`, `min`, `step`, `inputmode`, `autofocus` …) pass through to the control; `class` stays on the wrapper.

- **Input**: `v-model` (works with `.number`/`.trim`), `type`, `placeholder`, `size` sm|md|lg, `leadingIcon`, `trailingIcon`,
  `revealable` (eye toggle for passwords), slots `#leading`, `#trailing`. `ref.focus()` exposed.
- **Textarea**: `rows`, `maxlength`, `showCount`, `autoresize`.
- **Select** (native): `options` = `[{ value, label, disabled }]` or primitives, `placeholder` (→ `null` value),
  `valueKey`/`labelKey`, or `<option>`s in the default slot. Keeps numeric values numeric. Emits `update:modelValue`, `change`.
- **Checkbox**: boolean `v-model`, or array `v-model` + `value`; `description`, `indeterminate`.
- **Radio**: `v-model`, `value`, `name`, `description`. Group them inside `<FormField as="fieldset" label="…">`.
- **Switch**: boolean `v-model`, `label`, `description`, `size` sm|md, `labelPosition` left|right. For instant settings.
- **FormField**: wrapper for custom controls. Default slot gets `{ id, describedBy, invalid }`.

```vue
<form class="max-w-xl space-y-5" @submit.prevent="submit">
  <Input v-model="form.title" label="Kurs nomi" required :error="form.errors.title" />
  <Textarea v-model="form.description" label="Tavsif" :rows="4" :error="form.errors.description" optional />
  <Select v-model="form.direction_id" label="Yo'nalish" placeholder="Tanlang" :options="directions" value-key="id" label-key="name" />
  <Switch v-model="form.is_published" label="Nashr qilish" description="O'quvchilarga ko'rinadi" />
  <div class="flex justify-end gap-2 pt-2">
    <Button variant="secondary" :href="route('teacher.courses.index')">Bekor qilish</Button>
    <Button type="submit" :loading="form.processing">Saqlash</Button>
  </div>
</form>
```

### Badge
`variant` neutral | brand | success | warning | danger | info | math | physics (aliases: gray, primary, error, green, red,
yellow, blue, purple), `size` sm|md, `dot`, `icon`, `solid`.

### Avatar
`src` (full URL, absolute path, or storage path → `/storage/…`), `name` (initials + deterministic tint), `size`
xs 24 | sm 32 | md 40 | lg 48 | xl 64 | 2xl 96, `shape` circle|square, `status` online|offline|busy. Falls back to initials on error —
no ui-avatars.com needed.

### Modal
Kept API: `show`, `closeable` (default true), emits `close`, default slot. If you pass **no `title`**, the slot is rendered
raw (old modals keep their own padding). New: `title`, `description` (standard header + close button, padded body),
`maxWidth`/`size` sm | md | lg (default) | xl | 2xl | 3xl | 4xl | 5xl | full, `#footer` slot, `hideClose`.
Behaviour: Teleport, Escape/backdrop close, focus trap + restore, `[autofocus]` honoured, scroll lock, bottom sheet on mobile.

### Dropdown + DropdownItem (Headless UI Menu)
`<Dropdown>` props: `label` (default trigger text), `align` left|right, `width` (Tailwind class, default `w-56`), `up`.
Slots: `#trigger="{ open }"` (exactly one element), `#header`, default `{ close }`.
`<DropdownItem>` props: `href`, `method` (non-GET → button Link), `data`, `external`, `icon`, `danger`, `disabled`, `meta`,
`divider`. Emits `click`. Keyboard: arrows, Home/End, type-ahead, Escape, focus return — built in.
```vue
<Dropdown align="right">
  <template #trigger><IconButton :icon="EllipsisVerticalIcon" label="Amallar" /></template>
  <DropdownItem :href="route('admin.users.edit', user.id)" :icon="PencilSquareIcon">Tahrirlash</DropdownItem>
  <DropdownItem divider />
  <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(user)">O'chirish</DropdownItem>
</Dropdown>
```

### Tabs
`tabs: [{ key, label, count?, icon?, disabled?, href?, active? }]`, `v-model` (active key), `variant` underline | pills,
`fitted`, `label` (aria). With `href` on tabs it renders a `<nav>` of Links (active by `active` or by URL); otherwise an ARIA
tablist (←/→/Home/End). Panels: named slots per key (`<template #lessons>`), or use the default slot `{ active }`.
Don't use `default` as a tab key.

### Table
Props: `columns: [{ key, label, align, numeric, width, sortable, class, headerClass, hideBelow: 'sm'|'md'|'lg' }]`, `rows`,
`rowKey` ('id' or fn), `loading` (+`loadingRows`), `emptyTitle`, `emptyDescription`, `dense`, `hoverable`, `clickable`,
`sortKey`, `sortDir`, `stickyHeader`, `maxHeight` (header sticks inside the scroll box), `caption` (sr-only), `bordered`.
Slots: `#cell-<key>="{ row, value, index }"`, `#head-<key>`, `#empty`, `#footer`, default (custom `<tr>`s).
Emits `sort({ key, dir })`, `row-click(row, index)` (clicks on links/buttons inside rows are ignored).
Dot paths work: `{ key: 'user.full_name' }`. `numeric: true` = right aligned + tabular-nums.
```vue
<Table :columns="columns" :rows="payments.data" :loading="loading" empty-title="To'lovlar yo'q">
  <template #cell-amount="{ value }">{{ formatMoney(value) }} so'm</template>
  <template #cell-status="{ value }"><Badge :variant="statusVariant(value)" dot>{{ statusLabel(value) }}</Badge></template>
  <template #footer><Pagination :data="payments" /></template>
</Table>
```

### Pagination
Kept API: `:data` (Laravel paginator: `links`, `from`, `to`, `total`), emits `page-change(page)`, navigates with
`preserveState/preserveScroll`. Renders only when there is more than one page. Mobile shows prev/current/next.
Has its own top border — put it in Table `#footer` or Card `#footer` / at the bottom of a `padding="none"` Card.

### EmptyState
`icon`, `title` (required), `description`, `size` sm|md|lg, `bordered` (dashed box). Default slot = actions.

### PageHeader
`title`, `description`, `breadcrumbs` ([{ label, href? }]), `back` (href, shown when no breadcrumbs), `backLabel`.
Slots: `#actions`, `#meta` (badges under the title), `#tabs`, `#title`. Renders `<h1>` and `mb-6`.

### StatCard
`label`, `value` (numbers are formatted `12 345`), `unit`, `icon`, `tone` (icon chip colour), `hint`, `trend` (%; sign = colour),
`trendLabel`, `href`, `loading`. Default slot = extra footer content (e.g. a tiny ProgressBar).

### Alert
`variant` info | success | warning | danger (alias error), `title`, `dismissible` (emits `dismiss`), `icon` (component or `false`).
Slots: default, `#actions`. Inline, persistent. For transient feedback use toasts.

### Toast / ToastViewport / useToast
Layouts already render `<ToastViewport />` and turn `flash.success|error|warning|info` into toasts. From a page:
```js
import { useToast } from '@/Composables/useToast';
const toast = useToast();
toast.success("Saqlandi");            // .error .warning .info  — opts: { title, duration }
```
`<Toast>` kept API: `type`, `message`, `duration`, emits `close`; new `title`, `inline`. Don't mount Toasts in pages.

### Skeleton
`variant` rect (size via classes, e.g. `class="h-5 w-40"`) | text (`lines`) | circle | card.

### ProgressBar / ProgressRing
ProgressBar: `value`, `max` (100), `tone`, `size` xs|sm|md|lg, `label`, `showValue`, `indeterminate`.
ProgressRing: `value`, `max`, `size` (px, 48), `stroke` (5), `tone`, `showValue`, `label`; default slot = centre content.
(The older `Components/Student/ProgressBar.vue`/`ProgressRing.vue` still exist; prefer the UI ones in new markup.)

### Tooltip
`text`, `placement` top|bottom|left|right, `delay`, `disabled`. Slot gets `{ id }` for `aria-describedby`. Hover + focus, Escape hides.

### Breadcrumbs
`items: [{ label, href? }]` (last = current, `aria-current="page"`), `home` (href → leading home icon).

### Also available
`Spinner` (`size` classes, `label`), `LazyImage` (unchanged API).

---

## 3. Layouts

All four layouts keep their old usage. Pages do not need to change to get the new shell.

| Layout | Usage | Props / slots |
|---|---|---|
| `StudentLayout` | `<StudentLayout>…</StudentLayout>` | `title` (mobile top-bar title; was previously leaking to an HTML `title` attr) |
| `TeacherLayout` | `<TeacherLayout><template #header>…</template>…</TeacherLayout>` | `#header` is rendered **above the page content** (not in the top bar); `title` |
| `AdminLayout` | `<AdminLayout>…</AdminLayout>` | `title` |
| `GuestLayout` | `<GuestLayout>…form…</GuestLayout>` | split auth layout; slot lands in a `max-w-sm` column |

Shell behaviour (`Components/Layout/AppShell.vue`):
- ≥1024px sidebar 240px, user can collapse to a 64px icon rail (remembered in `edulife_sidebar_collapsed`);
  768–1023px icon rail; <768px off-canvas drawer (menu button, Escape/backdrop closes, closes on navigation).
- Top bar (64px, sticky, translucent): search (student), quick actions, notifications bell with unread count
  (`auth.unread_notifications_count`), avatar menu (profile links, light/dark toggle, logout → `POST route('logout')`).
- Student: compact level / streak / coins chips (from `auth.studentProfile`, fallback `auth.user`), level progress in the
  avatar menu, stats strip in the drawer, **mobile bottom tab bar** (5 items). Main content gets bottom padding automatically.
- Content area: `.page-container` (max 1200px, 16/24px padding, 24/32px top). A skip link targets `#main-content`.
- Flash messages → toasts in every layout (Guest included).

Navigation items are declared in each layout with Ziggy route names and **only render when `route().has(name)`**.
`route` may be a list of candidate names — e.g. Imtihonlar uses `['student.exams.index', 'student.exam.index',
'student.saturday-exams.index']`, so the item appears automatically once the exam routes are added. Active state is
computed from `page.url` (same path or a child path; `exact` for dashboards; `match: ['/student/learn']` for extra prefixes).

**Do not** put another header/nav inside pages. Page = `PageHeader` + content.

---

## 4. Page recipes

### List page (admin / teacher)
```vue
<AdminLayout>
  <Head title="Foydalanuvchilar" />
  <PageHeader title="Foydalanuvchilar" description="Platformadagi barcha foydalanuvchilar">
    <template #actions><Button :icon-left="PlusIcon" :href="route('admin.users.create')">Qo'shish</Button></template>
  </PageHeader>

  <div class="space-y-4">
    <!-- Filters row -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <Input v-model="filters.search" placeholder="Ism yoki telefon" :leading-icon="MagnifyingGlassIcon" class="sm:w-72" />
      <Select v-model="filters.role" :options="roles" placeholder="Barcha rollar" class="sm:w-48" />
    </div>

    <Table :columns="columns" :rows="users.data" empty-title="Foydalanuvchi topilmadi">
      <template #cell-name="{ row }">
        <div class="flex items-center gap-3"><Avatar :src="row.avatar" :name="row.full_name" size="sm" />
          <span class="font-medium">{{ row.full_name }}</span></div>
      </template>
      <template #cell-actions="{ row }">…Dropdown…</template>
      <template #footer><Pagination :data="users" /></template>
    </Table>
  </div>
</AdminLayout>
```
Rules: numbers right-aligned (`numeric: true`), money `12 500 so'm`, dates `dd.mm.yyyy`, row actions in a Dropdown,
empty + loading states always present.

### Detail page
`PageHeader` with `breadcrumbs`, `#meta` badges and `#actions` → a two-column grid on desktop:
```html
<div class="grid gap-6 lg:grid-cols-3">
  <div class="space-y-6 lg:col-span-2"><Card title="…">…</Card></div>
  <aside class="space-y-6"><Card title="Ma'lumot">…definition list…</Card></aside>
</div>
```
Definition list: `<dl class="divide-y divide-border text-sm"><div class="flex justify-between py-2.5"><dt class="text-fg-muted">…</dt><dd class="font-medium tabular-nums">…</dd></div></dl>`.

### Form page
Single column, max 640px (`max-w-xl`), inside a Card. Group long forms into several Cards with titles. Buttons at the
bottom right (`Bekor qilish` secondary + `Saqlash` primary), `:loading="form.processing"`. Always bind `:error="form.errors.x"`.
Keep field names / `useForm` keys / endpoints exactly as they are.

### Dashboard
```html
<PageHeader :title="`Salom, ${user.first_name}`" description="Bugungi holat" />
<div class="space-y-6">
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-4"><StatCard … /> ×4</div>
  <div class="grid gap-6 lg:grid-cols-3">
    <Card class="lg:col-span-2" title="Faollik">chart</Card>
    <Card title="Keyingi imtihon">…</Card>
  </div>
</div>
```
Student browsing pages (courses, lab, olympiads): responsive card grids `grid gap-4 sm:grid-cols-2 xl:grid-cols-3`, using
`<Card :href="…" padding="none">` with image on top and content in `p-5`.

---

## 5. Do / Don't

**Do**
- Use token classes (`bg-surface`, `text-fg-muted`, `border-border`, `text-brand`, `bg-success-50 text-success`).
- Use UI components for every button, input, badge, table, modal, menu, empty state.
- Heroicons (outline 24 for nav/actions, solid 20 inline), sized `h-5 w-5` / `h-4 w-4`, `aria-hidden="true"`.
- `tabular-nums` for numbers; `so'm`; `dd.mm.yyyy`.
- Provide empty, loading (Skeleton / `loading` props) and error (Alert) states.
- Touch targets ≥ 40px (`md` sizes are 40px), test at 375px and 1440px.
- Uzbek (Latin) for all UI text; apostrophes as `'` (`O'quvchilar`).
- `focus-ring` on any custom clickable element; `aria-label` on icon-only buttons.

**Don't**
- No `bg-white`, `text-gray-*`, `border-gray-*`, `bg-purple-*`, gradients, or `dark:` patches for chrome.
- No emoji as icons (content may keep emoji).
- No `rounded-2xl/3xl`, heavy shadows, or coloured page backgrounds.
- No `href="#"` — link only to routes that exist (`route().has()` / `routePath()` from `@/Utils/nav`).
- Don't change props, routes, form field names or emitted events.
- Don't mount `<Toast>` or your own flash handling in pages — use `useToast()`.
- Don't render page titles in the top bar; use `PageHeader`.
- Don't use `text-brand-600` for text (use `text-brand`).
