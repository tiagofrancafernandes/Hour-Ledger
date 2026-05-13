# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Hours Ledger System -- Frontend**

Vue 3 SPA for client hour tracking with a ledger-based model.

### Tech Stack

- **Framework**: Vue 3.5+ with Composition API
- **Build Tool**: Vite 7
- **Language**: TypeScript 5.9+
- **Styling**: TailwindCSS v4
- **Routing**: Vue Router 4
- **Code Style**: Prettier

## Development Commands

```bash
# Install dependencies
npm install

# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type check
vue-tsc -b
```

### Docker Commands

```bash
# From project root
docker compose --env-file .env.docker exec frontend npm install
docker compose --env-file .env.docker exec frontend npm run build
docker compose --env-file .env.docker exec frontend npx prettier --write src/
```

## Architecture

### Directory Structure

```
src/
├── composables/       # Reusable composition functions
│   ├── useClients.ts
│   ├── useWallets.ts
│   ├── useLedger.ts
│   ├── useTags.ts
│   └── useReports.ts
├── router/            # Vue Router configuration
├── services/          # API communication
│   └── api.ts
├── types/             # TypeScript interfaces
│   └── index.ts
├── views/             # Page components
│   ├── ClientsView.vue
│   ├── ClientDetailView.vue
│   ├── WalletDetailView.vue
│   ├── ReportsView.vue
│   └── TagsView.vue
├── App.vue            # Root component with navigation
└── main.ts            # Application entry point
```

### Key Patterns

**Composition API**: All components use `<script setup lang="ts">`

**Composables**: Data fetching and state management via composition functions

```typescript
const { clients, loading, error, fetchClients } = useClients();
```

**API Service**: Centralized API communication in `services/api.ts`

**TypeScript**: Strict typing for all entities and API responses

### Path Aliases

Configured in `vite.config.ts`:

- `@` → `./src`
- `@composables` → `./src/composables`
- `@views` → `./src/views`
- `@services` → `./src/services`
- `@types` → `./src/types`

## Vue.js Guidelines

### Conditional Classes

**Always use object syntax** instead of ternary operators:

```vue
<!-- ✅ Correct -->
<div :class="{ 'bg-blue-600': isActive, 'bg-gray-200': !isActive }"></div>

<!-- ❌ Wrong -->
<div :class="isActive ? 'bg-blue-600' : 'bg-gray-200'"></div>
```

### Combining Static and Conditional Classes

```vue
<div
    :class="[
        'px-4 py-2 rounded-lg',
        {
            'bg-blue-600 text-white': isActive,
            'bg-gray-200 text-gray-800': !isActive,
        },
    ]"
></div>
```

## TailwindCSS v4

This project uses TailwindCSS v4 with Vite plugin:

- CSS import: `@import "tailwindcss"` (not `@tailwind` directives)
- No `tailwind.config.js` required
- Use `bg-linear-*` instead of `bg-gradient-*`

## Environment Variables

```env
VITE_API_URL=http://api.local.tiagoapps.com.br
```

Access in code: `import.meta.env.VITE_API_URL`

## UI Design Reference

**All UI components must follow the design system specification defined in:**

**`design/design.json`**

### Design Guidelines

This file contains the complete design system extracted from the official mockups:

- **Color palette**: Primary (#dc2626), secondary, backgrounds, text colors
- **Typography**: Font families, sizes, weights
- **Component styles**: Buttons, cards, inputs, tables, badges, etc.
- **Spacing & Layout**: Consistent spacing scale and border radius
- **Navigation**: Header, sidebar, and mobile bottom navigation specs
- **Responsive breakpoints**: Mobile, tablet, and desktop layouts

### Toast messages

```ts
import { useToast } from '@/composables/useToast';

const toast = useToast();

toast.success('success!');
toast.error('error!');
toast.info('info!');
toast.dark('dark!');
toast.warning('warning!');
```

## Using buttons

To use <button> preffer use global `CButton` component (`src/components/CButton.vue` not need import this) and use presets to style
Example:

```vue
<template>
    <CButton preset="outlined-black" class="inline-flex items-center">Unlock</CButton>
</template>
```

## Using Custom Components (src/components/C\*.vue file)

Example:

```vue
<template>
    <!-- src/components/CButton.vue -->
    <CButton label="My Button label" />

    <!-- src/components/CDropZone.vue -->
    <CDropZone label="My DropZone label" />

    <!-- src/components/CInput.vue -->
    <CInput label="My Input label" />

    <!-- src/components/CSelect.vue -->
    <CSelect label="My Select label">
        <option>Opção</option>
    </CSelect>
    <!-- src/components/CTextarea.vue -->
    <CTextarea label="My Textarea label" />
</template>
```

### Using buttons with icons

- Content inside

```vue
<CButton class="inline-flex items-center gap-2">
    <Icon icon="material-symbols:lock-open-right-outline-rounded" />
    Unlock
</CButton>
```

- Icon on left

```vue
<CButton icon="mdi-light:home">
    Home
</CButton>
```

- Icon on right

```vue
<CButton right-icon="mdi-light:home">
    Home
</CButton>
```

- Icon on both (right and left)

```vue
<CButton icon="mdi-light:home" right-icon="mdi-light:home">
    Home
</CButton>
```

- Icon and label via args

```vue
<CButton icon="mdi-light:home" label="Home" />
```

### Key Design Principles

1. **Color Usage**:
    - Primary red (#dc2626) for actions, brand elements, and active states
    - Neutral grays for text hierarchy and backgrounds
    - White cards with subtle shadows for content containers

2. **Component Consistency**:
    - Rounded corners (0.5rem for most elements)
    - Consistent padding (0.5rem - 1rem for interactive elements)
    - Clear hover states for all interactive components

3. **Branding**:
    - Logo format: Brand name with accent color (e.g., "MK" in black + "Pay" in red)
    - Red badges for app/section identifiers
    - Clean, modern aesthetic with generous whitespace

4. **Responsive Design**:
    - Desktop: Expanded sidebar, full header
    - Mobile: Bottom navigation, collapsed sidebar, compact header
    - Breakpoints defined in design.json

**Always refer to `design/design.json` when:**

- Creating new components
- Styling existing components
- Implementing buttons, cards, or forms
- Choosing colors or spacing values
- Designing navigation elements

## Code Style Guideline (Mandatory)

All code generated, modified, or refactored **must strictly follow** the rules defined in:

**UNIVERSAL-CODE-STYLE-RULES.md**

### Enforcement Rules

- The rules in `UNIVERSAL-CODE-STYLE-RULES.md` are **authoritative and non-negotiable**
- No framework convention, language idiom, or AI default may override these rules
- Brevity, shortcuts, and one-liners are explicitly forbidden when they reduce clarity
- Explicit control flow, block scoping, and early returns are mandatory
- Logical sections must be separated by blank lines
- If multiple valid implementations exist, choose the **most explicit and readable**

### Conflict Resolution

If any instruction, suggestion, or generated code conflicts with the rules in
`UNIVERSAL-CODE-STYLE-RULES.md`, **that file always takes precedence**.

Any output that violates these rules must be considered **invalid and corrected**.
