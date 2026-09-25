<script setup lang="ts">
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import LessonsList from '@/components/lessons/LessonsList.vue';
import LessonScheduler from '@/components/lessons/LessonScheduler.vue';
import UIPageHeader from '@/components/UIPageHeader.vue';
import CButton from '@/components/CButton.vue';

const showSchedulerModal = ref(false);
const lessonsListKey = ref(0);
const activeFilter = ref<'all' | 'scheduled' | 'completed' | 'cancelled'>('all');

function handleLessonCreated(): void {
    showSchedulerModal.value = false;
    lessonsListKey.value += 1;
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <UIPageHeader
            title="Aulas de Direção"
            description="Acompanhe o agendamento, realização e débito automático de horas das aulas."
        >
            <template #actions>
                <CButton preset="primary" icon="heroicons:calendar-days" @click="showSchedulerModal = true">
                    Agendar Aula
                </CButton>
            </template>
        </UIPageHeader>

        <!-- Status Filter Tabs -->
        <div class="mb-6 flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button
                type="button"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeFilter === 'all'
                        ? 'border-red-600 text-red-600 dark:text-red-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="activeFilter = 'all'"
            >
                Todas
            </button>
            <button
                type="button"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeFilter === 'scheduled'
                        ? 'border-red-600 text-red-600 dark:text-red-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="activeFilter = 'scheduled'"
            >
                Agendadas
            </button>
            <button
                type="button"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeFilter === 'completed'
                        ? 'border-red-600 text-red-600 dark:text-red-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="activeFilter = 'completed'"
            >
                Concluídas
            </button>
            <button
                type="button"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeFilter === 'cancelled'
                        ? 'border-red-600 text-red-600 dark:text-red-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="activeFilter = 'cancelled'"
            >
                Canceladas
            </button>
        </div>

        <!-- Lessons List -->
        <LessonsList :key="lessonsListKey" :filter="activeFilter" />

        <!-- Scheduler Modal -->
        <Teleport to="body">
            <div
                v-if="showSchedulerModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showSchedulerModal = false"
            >
                <div class="w-full max-w-xl rounded-xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
                    <div
                        class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4"
                    >
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Nova Aula</h3>
                        <button
                            type="button"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="showSchedulerModal = false"
                        >
                            <Icon icon="heroicons:x-mark" class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6">
                        <LessonScheduler @lesson-created="handleLessonCreated" />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
