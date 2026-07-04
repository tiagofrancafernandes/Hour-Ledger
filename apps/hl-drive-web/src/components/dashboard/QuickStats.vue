<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useLessons } from '@/composables/useLessons';

interface Props {
    studentId?: number;
    instructorId?: number;
    walletBalance?: string;
}

const props = defineProps<Props>();

const { lessons, loading, fetchStudentLessons, fetchInstructorLessons } = useLessons();

const scheduledCount = ref(0);
const completedCount = ref(0);

onMounted(async () => {
    try {
        if (props.studentId) {
            await fetchStudentLessons(props.studentId);
        } else if (props.instructorId) {
            await fetchInstructorLessons(props.instructorId);
        }

        scheduledCount.value = lessons.value.filter((l) => l.status === 'scheduled').length;
        completedCount.value = lessons.value.filter((l) => l.status === 'completed').length;
    } catch (err) {
        // Error is handled
    }
});

const availableHours = () => {
    return props.walletBalance || '0';
};

const stats = [
    {
        label: 'Saldo de Horas',
        value: () => availableHours(),
        unit: 'h',
        color: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
    },
    {
        label: 'Aulas Agendadas',
        value: () => scheduledCount.value.toString(),
        unit: '',
        color: 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
    },
    {
        label: 'Aulas Concluídas',
        value: () => completedCount.value.toString(),
        unit: '',
        color: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
    },
];
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="(stat, index) in stats" :key="index" :class="['rounded-lg p-6 shadow-md border', stat.color]">
            <p class="text-sm font-medium mb-2 opacity-75">{{ stat.label }}</p>

            <div v-if="loading" class="animate-pulse">
                <div class="h-8 bg-gray-300 rounded w-20"></div>
            </div>

            <p v-else class="text-3xl font-bold flex items-baseline gap-1">
                {{ stat.value() }}<span class="text-lg">{{ stat.unit }}</span>
            </p>
        </div>
    </div>
</template>
