<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useLessons } from '@/composables/useLessons';
import LessonConsumption from './LessonConsumption.vue';

interface Props {
    studentId?: number;
    instructorId?: number;
    filter?: 'all' | 'scheduled' | 'completed' | 'cancelled';
}

const props = withDefaults(defineProps<Props>(), {
    filter: 'all',
});

const { lessons, loading, error, fetchLessons, fetchStudentLessons, fetchInstructorLessons } = useLessons();
const selectedLesson = ref<number | null>(null);
const showConsumptionModal = ref(false);

onMounted(async () => {
    try {
        if (props.studentId) {
            await fetchStudentLessons(props.studentId);
        } else if (props.instructorId) {
            await fetchInstructorLessons(props.instructorId);
        } else {
            await fetchLessons();
        }
    } catch (err) {
        // Error is handled by composable
    }
});

const filteredLessons = () => {
    if (props.filter === 'all') {
        return lessons.value;
    }
    return lessons.value.filter((lesson) => lesson.status === props.filter);
};

const formatDateTime = (dateTime: string) => {
    const date = new Date(dateTime);
    return date.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        scheduled: 'Agendada',
        completed: 'Concluída',
        cancelled: 'Cancelada',
    };
    return labels[status] || status;
};

const handleOpenConsumption = (lessonId: number) => {
    selectedLesson.value = lessonId;
    showConsumptionModal.value = true;
};

const handleConsumptionCompleted = async () => {
    showConsumptionModal.value = false;
    selectedLesson.value = null;

    try {
        if (props.studentId) {
            await fetchStudentLessons(props.studentId);
        } else if (props.instructorId) {
            await fetchInstructorLessons(props.instructorId);
        } else {
            await fetchLessons();
        }
    } catch (err) {
        // Error is handled
    }
};
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Aulas Agendadas</h2>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center p-8">
            <div class="animate-spin">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
            </div>
        </div>

        <div v-else-if="filteredLessons().length === 0" class="text-center py-12">
            <p class="text-gray-500 dark:text-gray-400">Nenhuma aula encontrada</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="lesson in filteredLessons()" :key="lesson.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Aula com {{ lesson.student?.name || lesson.instructor?.name || 'N/A' }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                            {{ formatDateTime(lesson.scheduled_at) }}
                        </p>
                    </div>
                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(lesson.status)]">
                        {{ getStatusLabel(lesson.status) }}
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <p class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Duração:</span> {{ lesson.duration_minutes }} minutos
                    </p>
                    <p v-if="lesson.hours_consumed" class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Horas consumidas:</span> {{ lesson.hours_consumed }}h
                    </p>
                    <p v-if="lesson.notes" class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Observações:</span> {{ lesson.notes }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <button
                        v-if="lesson.status === 'scheduled'"
                        @click="handleOpenConsumption(lesson.id)"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                    >
                        Marcar como Concluída
                    </button>
                </div>
            </div>
        </div>

        <!-- Consumption Modal -->
        <div v-if="showConsumptionModal && selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                <LessonConsumption :lesson-id="selectedLesson" @completed="handleConsumptionCompleted" @cancel="showConsumptionModal = false" />
            </div>
        </div>
    </div>
</template>
