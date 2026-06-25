<script setup lang="ts">
import { onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useInstructor } from '@/composables/useInstructor';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { activeInstructor, myInstructors, loading, setActiveInstructor, fetchMyInstructors } = useInstructor();

onMounted(() => {
    fetchMyInstructors();
});

function handleSelectInstructor(instructorId: number): void {
    setActiveInstructor(instructorId);
}
</script>

<template>
    <div class="relative">
        <!-- Trigger Button -->
        <button
            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors font-medium"
            :disabled="loading"
        >
            <Icon
                v-if="loading"
                icon="mdi:loading"
                class="w-4 h-4 animate-spin"
            />
            <Icon
                v-else
                icon="fa7-solid:user-tie"
                class="w-4 h-4"
            />
            <span class="hidden sm:inline max-w-32 truncate">
                {{ activeInstructor?.name ?? t('instructor.select') }}
            </span>
        </button>

        <!-- Dropdown List -->
        <div
            v-if="myInstructors.length > 0"
            class="absolute top-full mt-2 left-0 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
        >
            <div class="py-1">
                <button
                    v-for="link in myInstructors"
                    :key="link.id"
                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center justify-between"
                    :class="{ 'bg-blue-50 text-blue-600': activeInstructor?.id === link.instructor?.id }"
                    @click="handleSelectInstructor(link.instructor?.id || 0)"
                >
                    <span>{{ link.instructor?.name }}</span>
                    <Icon
                        v-if="activeInstructor?.id === link.instructor?.id"
                        icon="fa7-solid:check"
                        class="w-4 h-4"
                    />
                </button>
            </div>
        </div>
    </div>
</template>
