# ✅ `DateDisplay.vue` (atualizado)

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { useDate } from '@/composables/useDate';

import type { DateInput, DatePattern } from '@/utils/date-helpers';

interface Props {
    value: DateInput;
    from?: string;
    to?: string;
    locale?: string;
    dateStyle?: 'full' | 'long' | 'medium' | 'short';
    timeStyle?: 'full' | 'long' | 'medium' | 'short';
    format?: Intl.DateTimeFormatOptions;
    pattern?: DatePattern;
}

const props = withDefaults(defineProps<Props>(), {
    locale: 'en-US',
    dateStyle: 'short',
    timeStyle: 'medium',
});

const { formatted } = useDate({
    date: props.value,
    from: props.from,
    to: props.to,
    locale: props.locale,
    format: props.format,
    pattern: props.pattern,
});

const displayValue = computed(() => {
    return formatted.value;
});
</script>

<template>
    <span v-if="displayValue">
        {{ displayValue }}
    </span>
</template>
```

---

# 📘 Updated Documentation (EN - simple & short)

## Overview

`DateDisplay` renders a date using:

- safe validation
- timezone conversion
- flexible formatting

---

## Props

| Prop        | Type                                                          | Description                                             |
| ----------- | ------------------------------------------------------------- | ------------------------------------------------------- |
| `value`     | `string \| number \| Date`                                    | Date input                                              |
| `from`      | `string`                                                      | Source timezone (default: UTC if not present in string) |
| `to`        | `string`                                                      | Target timezone (fallback: user → browser)              |
| `locale`    | `string`                                                      | Default: `"en-US"`                                      |
| `dateStyle` | `"full" \| "long" \| "medium" \| "short"`                     | Default: `"short"`                                      |
| `timeStyle` | `"full" \| "long" \| "medium" \| "short"`                     | Default: `"medium"`                                     |
| `format`    | `Intl.DateTimeFormatOptions`                                  | Custom Intl format                                      |
| `pattern`   | `"iso-date" \| "br-date" \| "datetime" \| "datetime-seconds"` | Fixed format                                            |

---

## Behavior

- Invalid input → renders nothing
- Timezone priority:
    1. `to`
    2. user timezone
    3. browser timezone

- If input has no timezone → assumed UTC
- Uses `try/catch` for safety

---

## Examples

### Basic

```vue
<DateDisplay :value="date" />
```

---

### Convert timezone

```vue
<DateDisplay :value="date" to="America/Sao_Paulo" />
```

---

### Define source timezone

```vue
<DateDisplay :value="'2026-04-22 18:00:00'" from="UTC" to="America/New_York" />
```

---

### Fixed format (ISO)

```vue
<DateDisplay :value="date" pattern="iso-date" />
```

---

### Date + time

```vue
<DateDisplay :value="date" pattern="datetime" />
```

---

### Custom Intl format

```vue
<DateDisplay
    :value="date"
    :format="{
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }"
/>
```

---

## Notes

- Pattern → fixed output (tables, logs)
- Intl → localized output (UI)
- Uses native `Intl` only
- No external libraries

---

## Mental model

- Input → normalize (UTC)
- Convert → target timezone
- Format → pattern or Intl
